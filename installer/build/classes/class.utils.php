<?php
// Exit if accessed directly
if (! defined('DUPLICATOR_INIT')) {
	$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	$_baseURL =  "http://" . $_baseURL;
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $_baseURL");
	exit; 
}

/** * *****************************************************
 *  CLASS::DupUtil
 *  Various Static Utility methods for working with the installer */
class DupUtil {



    /** METHOD: GET_MICROTIME
     * Get current microtime as a float. Can be used for simple profiling.
     */
    static public function get_microtime() {
        return microtime(true);
    }

    /** METHOD: ELAPSED_TIME
     * Return a string with the elapsed time.
     * Order of $end and $start can be switched. 
     */
    static public function elapsed_time($end, $start) {
        return sprintf("%.4f sec.", abs($end - $start));
    }

    /** METHOD: ESC_HTML_ATTR
     * @param string $string Thing that needs escaping
     * @param bool $echo   Do we echo or return?
     * @return string    Escaped string. 
     */
    static public function esc_html_attr($string = '', $echo = false) {
        $output = htmlentities($string, ENT_QUOTES, 'UTF-8');
        if ($echo)
            echo $output;
        else
            return $output;
    }

    /** METHOD: TABLE_COUNT
     *  Count the tables in a given database
     *  @param string $_POST['dbname']		Database to count tables in 
     */
    static public function dbtable_count($conn, $dbname) {
        $res = mysqli_query($conn, "SELECT COUNT(*) AS count FROM information_schema.tables WHERE table_schema = '{$dbname}' ");
        $row = mysqli_fetch_row($res);
        return is_null($row) ? 0 : $row[0];
    }

    /** METHOD: TABLE_ROW_COUNT
     *  Returns the table count
     *  @param string $conn	        A valid link resource
     *  @param string $table_name	A valid table name
     */
    static public function table_row_count($conn, $table_name) {
        $total = mysqli_query($conn, "SELECT COUNT(*) FROM `$table_name`");
        if ($total) {
            $total = @mysqli_fetch_array($total);
            return $total[0];
        } else {
            return 0;
        }
    }

    /** METHOD: ADD_ENDING_SLASH
     *  Adds a slash to the end of a path
     *  @param string $path		A path
     */
    static public function add_slash($path) {
        $last_char = substr($path, strlen($path) - 1, 1);
        if ($last_char != '/') {
            $path .= '/';
        }
        return $path;
    }

    /** METHOD: SET_SAFE_PATH
     *  Makes path safe for any OS
     *  Paths should ALWAYS READ be "/"
     * 		uni: /home/path/file.xt
     * 		win:  D:/home/path/file.txt 
     *  @param string $path		The path to make safe
     */
    static public function set_safe_path($path) {
        return str_replace("\\", "/", $path);
    }

    static public function unset_safe_path($path) {
        return str_replace("/", "\\", $path);
    }

    /** METHOD: FCGI_FLUSH
     *  PHP_SAPI for fcgi requires a data flush of at least 256
     *  bytes every 40 seconds or else it forces a script hault
     */
    static public function fcgi_flush() {
        echo(str_repeat(' ', 256));
        @flush();
    }

    /** METHOD: COPY_FILE
     *  A safe method used to copy larger files
     *  @param string $source		The path to the file being copied
     *  @param string $destination	The path to the file being made
     */
    static public function copy_file($source, $destination) {
        $sp = fopen($source, 'r');
        $op = fopen($destination, 'w');

        while (!feof($sp)) {
            $buffer = fread($sp, 512);  // use a buffer of 512 bytes
            fwrite($op, $buffer);
        }
        // close handles
        fclose($op);
        fclose($sp);
    }

    /** METHOD: string_has_value
     *  Finds a string in a file and returns true if found
     *  @param array  $list		A list of strings to search for
     *  @param string $haystack	The string to search in
     */
    static public function string_has_value($list, $file) {
        foreach ($list as $var) {
            if (strstr($file, $var) !== false) {
                return true;
            }
        }
        return false;
    }

    /** METHOD: get_active_plugins
     *  Returns the active plugins for a package
     *  @param  conn   $dbh	 A database connection handle
     *  @return array  $list A list of active plugins
     */
    static public function get_active_plugins($dbh) {
        $query = @mysqli_query($dbh, "SELECT option_value FROM `{$GLOBALS['FW_TABLEPREFIX']}options` WHERE option_name = 'active_plugins' ");
        if ($query) {
            $row = @mysqli_fetch_array($query);
            $all_plugins = unserialize($row[0]);
            if (is_array($all_plugins)) {
                return $all_plugins;
            }
        }
        return array();
    }

    /** METHOD: get_database_tables
     *  Returns the tables for a database
     *  @param  conn   $dbh	 A database connection handle	 
     *  @return array  $list A list of all table names
     */
    static public function get_database_tables($dbh) {
        $query = @mysqli_query($dbh, 'SHOW TABLES');
        if ($query) {
            while ($table = @mysqli_fetch_array($query)) {
                $all_tables[] = $table[0];
            }
            if (is_array($all_tables)) {
                return $all_tables;
            }
        }
        return array();
    }
	
    /**
     * MySQL connection support for sock
     * @param same as mysqli_connect
     * @return database connection handle
     */	
	static public function mysqli_connect( $host, $username, $password, $dbname = '', $port = null ) {
		if ( ! $port ) {
			$port = ini_get("mysqli.default_port");
			$port = empty($port) ? 3306 : $port;
		}

		if ( 'sock' === substr( $host, -4 ) ) {
			$url_parts = parse_url( $host );
			$dbh = @mysqli_connect( 'localhost', $username, $password, $dbname, null, $url_parts['path'] );
		}
		else {
			$dbh = @mysqli_connect( $host, $username, $password, $dbname, $port );
		}
		return $dbh;
	}
	

    /**
     * MySQL database version number
     * @param conn $dbh Database connection handle
     * @return false|string false on failure, version number on success
     */
    static public function mysql_version($dbh) {
        if (function_exists('mysqli_get_server_info')) {
            return preg_replace('/[^0-9.].*/', '', mysqli_get_server_info($dbh));
        } else {
            return 0;
        }
    }

    /**
     * MySQL server variable
     * @param conn $dbh Database connection handle
     * @return string the server variable to query for
     */
    static public function mysql_variable_value($dbh, $variable) {
        $result = @mysqli_query($dbh, "SHOW VARIABLES LIKE '{$variable}'");
        $row = @mysqli_fetch_array($result);
        @mysqli_free_result($result);
        return isset($row[1]) ? $row[1] : null;
    }

    /**
     * Determine if a MySQL database supports a particular feature
     * @param conn $dbh Database connection handle	 
     * @param string $feature the feature to check for
     * @return bool
     */
    static public function mysql_has_ability($dbh, $feature) {
        $version = self::mysql_version($dbh);

        switch (strtolower($feature)) {
            case 'collation' :
            case 'group_concat' :
            case 'subqueries' :
                return version_compare($version, '4.1', '>=');
            case 'set_charset' :
                return version_compare($version, '5.0.7', '>=');
        };
        return false;
    }

    /**
     * Sets the MySQL connection's character set.
     * @param resource $dbh     The resource given by mysqli_connect
     * @param string   $charset The character set (optional)
     * @param string   $collate The collation (optional)
     */
    static public function mysql_set_charset($dbh, $charset = null, $collate = null) {

        $charset = (!isset($charset) ) ? $GLOBALS['DBCHARSET_DEFAULT'] : $charset;
        $collate = (!isset($collate) ) ? $GLOBALS['DBCOLLATE_DEFAULT'] : $collate;

        if (self::mysql_has_ability($dbh, 'collation') && !empty($charset)) {
            if (function_exists('mysqli_set_charset') && self::mysql_has_ability($dbh, 'set_charset')) {
                return mysqli_set_charset($dbh, $charset);
            } else {
                $sql = " SET NAMES {$charset}";
                if (!empty($collate))
                    $sql .= " COLLATE {$collate}";
                return mysqli_query($dbh, $sql);
            }
        }
    }

    /**
     *  READABLE_BYTESIZE
     *  Display human readable byte sizes
     *  @param string $size		The size in bytes
     */
    static public function readable_bytesize($size) {
        try {
            $units = array('B', 'KB', 'MB', 'GB', 'TB');
            for ($i = 0; $size >= 1024 && $i < 4; $i++)
                $size /= 1024;
            return round($size, 2) . $units[$i];
        } catch (Exception $e) {
            return "n/a";
        }
    }
	
	/**
     *  PREG_REPLACEMENT_QUOTE
     *  The characters that are special in the replacement value of preg_replace are not the 
	 *  same characters that are special in the pattern
     *  @param string $str		The string to replace on
     */
	static public function preg_replacement_quote($str) {
		return preg_replace('/(\$|\\\\)(?=\d)/', '\\\\\1', $str);
	}
	
	
	/**
     *  IS_WEB_CONNECTED
     *  Check to see if the internet is accessable
	 *  @param string $url		A url e.g without prefix "ajax.googleapis.com"
	 *  @param string $port		A valid port number
	 *  @return bool
     */
	static public function is_url_active($url, $port) {
		if (function_exists('fsockopen')) {
			$port = isset($port) && is_integer($port) ? $port : 80;
			$connected = @fsockopen($url, $port); //website and port
			if ($connected){
				$is_conn = true;
				@fclose($connected);
			} else {
				$is_conn = false; 
			}
			return $is_conn;
		} else {
			return false;
		}
	}
	
	
}
?>