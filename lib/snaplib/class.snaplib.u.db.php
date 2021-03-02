<?php
/**
 * Snap Database utils
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2
 *
 * @package SnapLib
 * @copyright (c) 2017, Snapcreek LLC
 * @license	https://opensource.org/licenses/GPL-3.0 GNU Public License
 *
 */
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

class DupLiteSnapLibDB
{

    const CACHE_PREFIX_PRIMARY_KEY_COLUMN = 'pkcol_';
    const DB_ENGINE_MYSQL                 = 'MySQL';
    const DB_ENGINE_MARIA                 = 'MariaDB';
    const DB_ENGINE_PERCONA               = 'Percona';

    private static $cache = array();

    /**
     * 
     * @param mysqli $dbh // Database connection handle
     * @param string $tableName
     * @return string|string[] // return array if primary key is composite key
     * @throws Exception
     */
    public static function getUniqueIndexColumn(\mysqli $dbh, $tableName, $logCallback = null)
    {
        $cacheKey = self::CACHE_PREFIX_PRIMARY_KEY_COLUMN.$tableName;

        if (!isset(self::$cache[$cacheKey])) {
            $query  = 'SHOW COLUMNS FROM `'.mysqli_real_escape_string($dbh, $tableName).'` WHERE `Key` IN ("PRI","UNI")';
            if (($result = mysqli_query($dbh, $query)) === false) {
                if (is_callable($logCallback)) {
                    call_user_func($logCallback, $dbh, $result, $query);
                }
                throw new Exception('SHOW KEYS QUERY ERROR: '.mysqli_error($dbh));
            }

            if (is_callable($logCallback)) {
                call_user_func($logCallback, $dbh, $result, $query);
            }

            if ($result->num_rows == 0) {
                self::$cache[$cacheKey] = false;
            } else {
                $primary = false;
                $unique  = false;

                while ($row = $result->fetch_assoc()) {
                    switch ($row['Key']) {
                        case 'PRI':
                            if ($primary === false) {
                                $primary = $row['Field'];
                            } else {
                                if (is_scalar($primary)) {
                                    $primary = array($primary);
                                }
                                $primary[] = $row['Field'];
                            }
                            break;
                        case 'UNI':
                            $unique = $row['Field'];
                            break;
                        default:
                            break;
                    }
                }
                if ($primary !== false) {
                    self::$cache[$cacheKey] = $primary;
                } else if ($unique !== false) {
                    self::$cache[$cacheKey] = $unique;
                } else {
                    self::$cache[$cacheKey] = false;
                }
            }

            $result->free();
        }

        return self::$cache[$cacheKey];
    }

    /**
     * 
     * @param array $row
     * @param string|string[] $indexColumns
     * @return string|string[]
     */
    public static function getOffsetFromRowAssoc($row, $indexColumns, $lastOffset)
    {
        if (is_array($indexColumns)) {
            $result = array();
            foreach ($indexColumns as $col) {
                $result[$col] = isset($row[$col]) ? $row[$col] : 0;
            }
            return $result;
        } else if (strlen($indexColumns) > 0) {
            return isset($row[$indexColumns]) ? $row[$indexColumns] : 0;
        } else {
            return $lastOffset + 1;
        }
    }

    /**
     * This function performs a select by structuring the primary key as offset if the table has a primary key. 
     * For optimization issues, no checks are performed on the input query and it is assumed that the select has at least a where value.
     * If there are no conditions, you still have to perform an always true condition, for example
     * SELECT * FROM `copy1_postmeta` WHERE 1
     * 
     * @param mysqli $dbh // Database connection handle
     * @param string $query
     * @param string $table
     * @param int $offset
     * @param int $limit // 0 no limit
     * @param mixed $lastRowOffset // last offset to use on next function call
     * @return mysqli_result 
     * @throws Exception // exception on query fail
     */
    public static function selectUsingPrimaryKeyAsOffset(\mysqli $dbh, $query, $table, $offset, $limit, &$lastRowOffset = null, $logCallback = null)
    {
        $where     = '';
        $orderby   = '';
        $offsetStr = '';
        $limitStr  = $limit > 0 ? ' LIMIT '.$limit : '';

        if (($primaryColumn = self::getUniqueIndexColumn($dbh, $table, $logCallback)) == false) {
            $offsetStr = ' OFFSET '.(is_scalar($offset) ? $offset : 0);
        } else {
            if (is_array($primaryColumn)) {
                // COMPOSITE KEY
                $orderByCols = array();
                foreach ($primaryColumn as $colIndex => $col) {
                    $orderByCols[] = '`'.$col.'` ASC';
                }
                $orderby = ' ORDER BY '.implode(',', $orderByCols);
            } else {
                $orderby = ' ORDER BY `'.$primaryColumn.'` ASC';
            }
            $where = self::getOffsetKeyCondition($dbh, $primaryColumn, $offset);
        }
        $query .= $where.$orderby.$limitStr.$offsetStr;

        if (($result = mysqli_query($dbh, $query)) === false) {
            if (is_callable($logCallback)) {
                call_user_func($logCallback, $dbh, $result, $query);
            }
            throw new Exception('SELECT ERROR: '.mysqli_error($dbh));
        }

        if (is_callable($logCallback)) {
            call_user_func($logCallback, $dbh, $result, $query);
        }

        if ($primaryColumn == false) {
            $lastRowOffset = $offset + $result->num_rows;
        } else {
            if ($result->num_rows == 0) {
                $lastRowOffset = $offset;
            } else {
                $result->data_seek(($result->num_rows - 1));
                $row = $result->fetch_assoc();
                if (is_array($primaryColumn)) {
                    $lastRowOffset = array();
                    foreach ($primaryColumn as $col) {
                        $lastRowOffset[$col] = $row[$col];
                    }
                } else {
                    $lastRowOffset = $row[$primaryColumn];
                }
                $result->data_seek(0);
            }
        }

        return $result;
    }

    /**
     * Depending on the structure type of the primary key returns the condition to position at the right offset
     * 
     * @param string|string[] $primaryColumn
     * @param mixed $offset
     * @return string
     */
    protected static function getOffsetKeyCondition(\mysqli $dbh, $primaryColumn, $offset)
    {
        $condition = '';

        if ($offset === 0) {
            return '';
        }

        // COUPOUND KEY
        if (is_array($primaryColumn)) {
            foreach ($primaryColumn as $colIndex => $col) {
                if (is_array($offset) && isset($offset[$col]) && $offset[$col] > 0) {
                    $condition .= ($colIndex == 0 ? '' : ' OR ');
                    $condition .= ' (';
                    for ($prevColIndex = 0; $prevColIndex < $colIndex; $prevColIndex++) {
                        $condition .= ' `'.$primaryColumn[$prevColIndex].'` = "'.mysqli_real_escape_string($dbh, $offset[$primaryColumn[$prevColIndex]]).'" AND ';
                    }
                    $condition .= ' `'.$col.'` > "'.mysqli_real_escape_string($dbh, $offset[$col]).'")';
                }
            }
        } else {
            $condition = '`'.$primaryColumn.'` > "'.mysqli_real_escape_string($dbh, (is_scalar($offset) ? $offset : 0)).'"';
        }

        return (strlen($condition) ? ' AND ('.$condition.')' : '');
    }

    public static function getDBEngine(\mysqli $dbh)
    {
        $result = mysqli_query($dbh, "SHOW VARIABLES LIKE 'version%'");
        $rows    = @mysqli_fetch_all($result);
        @mysqli_free_result($result);

        $version        = isset($rows[0][1]) ? $rows[0][1] : false;
        $versionComment = isset($rows[1][1]) ? $rows[1][1] : false;

        //Default is mysql
        if ($version === false && $versionComment === false) {
            return self::DB_ENGINE_MYSQL;
        }

        if (stripos($version, 'maria') !== false || stripos($versionComment, 'maria') !== false) {
            return self::DB_ENGINE_MARIA;
        }

        if (stripos($version, 'percona') !== false || stripos($versionComment, 'percona') !== false) {
            return self::DB_ENGINE_PERCONA;
        }

        return self::DB_ENGINE_MYSQL;
    }
}
