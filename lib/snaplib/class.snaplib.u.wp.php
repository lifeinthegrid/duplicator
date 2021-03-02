<?php
/**
 * Wordpress utility functions
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2
 *
 * @package snaplib
 * @subpackage classes/utilities
 * @copyright (c) 2017, Snapcreek LLC
 * @license	https://opensource.org/licenses/GPL-3.0 GNU Public License
 *
 */
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

if (!class_exists('DupLiteSnapLibUtilWp', false)) {

    /**
     * Wordpress utility functions
     */
    class DupLiteSnapLibUtilWp
    {

        const PATH_FULL     = 0;
        const PATH_RELATIVE = 1;
        const PATH_AUTO     = 2;

        private static $corePathList = null;
        private static $safeAbsPath  = null;

        /**
         *
         * @var string if not empty alters isWpCore's operation 
         */
        private static $wpCoreRelativePath = '';

        /**
         * return safe ABSPATH without last /
         * perform safe function only one time
         *
         * @return string
         */
        public static function getSafeAbsPath()
        {
            if (is_null(self::$safeAbsPath)) {
                if (defined('ABSPATH')) {
                    self::$safeAbsPath = DupLiteSnapLibIOU::safePathUntrailingslashit(ABSPATH);
                } else {
                    self::$safeAbsPath = '';
                }
            }

            return self::$safeAbsPath;
        }

        /**
         * 
         * @param string $folder
         * @return boolean  // return true if folder is wordpress home folder
         * 
         */
        public static function isWpHomeFolder($folder)
        {
            $indexPhp = DupLiteSnapLibIOU::trailingslashit($folder).'index.php';
            if (!file_exists($indexPhp)) {
                return false;
            }

            if (($indexContent = file_get_contents($indexPhp)) === false) {
                return false;
            }

            return (preg_match('/require\s*[\s\(].*[\'"].*wp-blog-header.php[\'"]\s*\)?/', $indexContent) === 1);
        }

        /**
         * This function is the equivalent of the get_home_path function but with various fixes
         * 
         * @staticvar string $home_path
         * @return string
         */
        public static function getHomePath()
        {
            static $home_path = null;

            if (is_null($home_path)) {
                // outside wordpress this function makes no sense
                if (!defined('ABSPATH')) {
                    $home_path = false;
                    return $home_path;
                }

                if (isset($_SERVER['SCRIPT_FILENAME']) && is_readable($_SERVER['SCRIPT_FILENAME'])) {
                    $scriptFilename = $_SERVER['SCRIPT_FILENAME'];
                } else {
                    $files          = get_included_files();
                    $scriptFilename = array_shift($files);
                }

                $realScriptDirname = DupLiteSnapLibIOU::safePathTrailingslashit(dirname($scriptFilename), true);
                $realAbsPath       = DupLiteSnapLibIOU::safePathTrailingslashit(ABSPATH, true);

                if (strpos($realScriptDirname, $realAbsPath) === 0) {
                    // normalize URLs without www
                    $home    = DupLiteSnapLibURLU::wwwRemove(set_url_scheme(get_option('home'), 'http'));
                    $siteurl = DupLiteSnapLibURLU::wwwRemove(set_url_scheme(get_option('siteurl'), 'http'));

                    if (!empty($home) && 0 !== strcasecmp($home, $siteurl)) {
                        if (stripos($siteurl, $home) === 0) {
                            $wp_path_rel_to_home = str_ireplace($home, '', $siteurl); /* $siteurl - $home */
                            $pos                 = strripos(str_replace('\\', '/', $scriptFilename), DupLiteSnapLibIOU::trailingslashit($wp_path_rel_to_home));
                            $home_path           = substr($scriptFilename, 0, $pos);
                            $home_path           = DupLiteSnapLibIOU::trailingslashit($home_path);
                        } else {
                            $home_path = ABSPATH;
                        }
                    } else {
                        $home_path = ABSPATH;
                    }
                } else {
                    // On frontend the home path is the folder of index.php
                    $home_path = DupLiteSnapLibIOU::trailingslashit(dirname($scriptFilename));
                }

                // make sure the folder exists or consider ABSPATH
                if (!file_exists($home_path)) {
                    $home_path = ABSPATH;
                }

                $home_path = str_replace('\\', '/', $home_path);
            }
            return $home_path;
        }

        public static function setWpCoreRelativeAbsPath($string = '')
        {
            self::$wpCoreRelativePath = (string) $string;
        }

        /**
         * check if path is in wordpress core list
         *
         * @param string $path
         * @param int $fullPath // if PATH_AUTO check if is a full path or relative path
         *                         if PATH_FULL remove ABSPATH len without check
         *                         if PATH_RELATIVE consider path a relative path
         * @param bool $isSafe // if false call rtrim(DupLiteSnapLibIOU::safePath( PATH ), '/')
         *                        if true consider path a safe path without check
         *
         *  PATH_FULL and PATH_RELATIVE is better optimized and perform less operations
         *
         * @return boolean
         */
        public static function isWpCore($path, $fullPath = self::PATH_AUTO, $isSafe = false)
        {
            if ($isSafe == false) {
                $path = rtrim(DupLiteSnapLibIOU::safePath($path), '/');
            }

            switch ($fullPath) {
                case self::PATH_FULL:
                    $absPath = self::getSafeAbsPath();
                    if (strlen($path) < strlen($absPath)) {
                        return false;
                    }
                    $relPath = ltrim(substr($path, strlen($absPath)), '/');
                    break;
                case self::PATH_RELATIVE:
                    if (($relPath = DupLiteSnapLibIOU::getRelativePath($path, self::$wpCoreRelativePath)) === false) {
                        return false;
                    }
                    break;
                case self::PATH_AUTO:
                default:
                    $absPath = self::getSafeAbsPath();
                    if (strpos($path, $absPath) === 0) {
                        $relPath = ltrim(substr($path, strlen($absPath)), '/');
                    } else {
                        $relPath = ltrim($path, '/');
                    }
            }

            // if rel path is empty is consider root path so is a core folder.
            if (empty($relPath)) {
                return true;
            }

            $pExploded = explode('/', $relPath);
            $corePaths = self::getCorePathsList();

            foreach ($pExploded as $current) {
                if (!isset($corePaths[$current])) {
                    return false;
                }

                $corePaths = $corePaths[$current];
            }
            return true;
        }

        /**
         * get core path list from relative abs path
         * [
         *      'folder' => [
         *          's-folder1' => [
         *              file1 => [],
         *              file2 => [],
         *          ],
         *          's-folder2' => [],
         *          file1 => []
         *      ]
         * ]
         *
         * @return array
         */
        public static function getCorePathsList()
        {
            if (is_null(self::$corePathList)) {
                require_once(dirname(__FILE__).'/wordpress.core.files.php');
            }
            return self::$corePathList;
        }

        /**
         * return object list of sites
         * 
         * @return boolean
         */
        public static function getSites($args = array())
        {
            if (!function_exists('is_multisite') || !is_multisite()) {
                return false;
            }

            if (function_exists('get_sites')) {
                return get_sites($args);
            } else {
                $result = array();
                $blogs  = wp_get_sites($args);
                foreach ($blogs as $blog) {
                    $result[] = (object) $blog;
                }
                return $result;
            }
        }
    }
}
