<?php

/**
 * Used to get various pieces of information about the server environment
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2
 *
 * @package Duplicator
 * @subpackage classes/utilities
 * @copyright (c) 2017, Snapcreek LLC
 * 
 */
require_once (DUPLICATOR_PLUGIN_PATH . 'classes/utilities/class.u.php');

// Exit if accessed directly
if (!defined('DUPLICATOR_VERSION')) {
    exit;
}

class DUP_Server {

    const LockFileName = 'lockfile.txt';

    // Possibly use in the future if we want to prevent double building
    public static function isEngineLocked() {
        if (self::setEngineLock(true)) {
            self::setEngineLock(false);
            $locked = false;
        } else {
            $locked = true;
        }
    }

    // Possibly use in the future if we want to prevent double building
    public static function setEngineLock($shouldLock) {
        $success = false;

        $locking_file = @fopen(self::LockFileName, 'c+');

        if ($locking_file != false) {
            if ($shouldLock) {
                $success = @flock($locking_file, LOCK_EX | LOCK_NB);
            } else {
                $success = @flock($locking_file, LOCK_UN);
            }

            @fclose($locking_file);
        }

        return $success;
    }

    /**
     * Gets the system requirements which must pass to build a package
     *
     * @return array   An array of requirements
     */
    public static function getRequirements() {
        global $wpdb;
        $dup_tests = array();

        //PHP SUPPORT
        $safe_ini = strtolower(ini_get('safe_mode'));
        $dup_tests['PHP']['SAFE_MODE'] = $safe_ini != 'on' || $safe_ini != 'yes' || $safe_ini != 'true' || ini_get("safe_mode") != 1 ? 'Pass' : 'Fail';
        $dup_tests['PHP']['VERSION'] = DUP_Util::$on_php_529_plus ? 'Pass' : 'Fail';

        if (DUP_Settings::Get('archive_build_mode') == DUP_Archive_Build_Mode::ZipArchive) {
            $dup_tests['PHP']['ZIP'] = class_exists('ZipArchive') ? 'Pass' : 'Fail';
        }

        $dup_tests['PHP']['FUNC_1'] = function_exists("file_get_contents") ? 'Pass' : 'Fail';
        $dup_tests['PHP']['FUNC_2'] = function_exists("file_put_contents") ? 'Pass' : 'Fail';
        $dup_tests['PHP']['FUNC_3'] = function_exists("mb_strlen") ? 'Pass' : 'Fail';
        $dup_tests['PHP']['ALL'] = !in_array('Fail', $dup_tests['PHP']) ? 'Pass' : 'Fail';

        //REQUIRED PATHS
        $handle_test = @opendir(DUPLICATOR_WPROOTPATH);
        $dup_tests['IO']['WPROOT'] = is_writeable(DUPLICATOR_WPROOTPATH) && $handle_test ? 'Pass' : 'Warn';
        @closedir($handle_test);

        $dup_tests['IO']['SSDIR'] = (file_exists(DUPLICATOR_SSDIR_PATH) && is_writeable(DUPLICATOR_SSDIR_PATH)) ? 'Pass' : 'Fail';
        $dup_tests['IO']['SSTMP'] = is_writeable(DUPLICATOR_SSDIR_PATH_TMP) ? 'Pass' : 'Fail';
        $dup_tests['IO']['ALL'] = !in_array('Fail', $dup_tests['IO']) ? 'Pass' : 'Fail';

        //SERVER SUPPORT
        $dup_tests['SRV']['MYSQLi'] = function_exists('mysqli_connect') ? 'Pass' : 'Fail';
        $dup_tests['SRV']['MYSQL_VER'] = version_compare(DUP_DB::getVersion(), '5.0', '>=') ? 'Pass' : 'Fail';
        $dup_tests['SRV']['ALL'] = !in_array('Fail', $dup_tests['SRV']) ? 'Pass' : 'Fail';

        //RESERVED FILES
        $dup_tests['RES']['INSTALL'] = !(self::hasInstallerFiles()) ? 'Pass' : 'Fail';
        $dup_tests['Success'] = $dup_tests['PHP']['ALL'] == 'Pass' && $dup_tests['IO']['ALL'] == 'Pass' && $dup_tests['SRV']['ALL'] == 'Pass' && $dup_tests['RES']['INSTALL'] == 'Pass';

        $dup_tests['Warning'] = $dup_tests['IO']['WPROOT'] == 'Warn';

        return $dup_tests;
    }

    /**
     * Gets the system checks which are not required
     *
     * @return array   An array of system checks
     */
    public static function getChecks() {
        $checks = array();

        //PHP/SYSTEM SETTINGS
        //Web Server
        $php_test0 = false;
        foreach ($GLOBALS['DUPLICATOR_SERVER_LIST'] as $value) {
            if (stristr($_SERVER['SERVER_SOFTWARE'], $value)) {
                $php_test0 = true;
                break;
            }
        }

        $php_test1 = ini_get("open_basedir");
        $php_test1 = empty($php_test1) ? true : false;
        $php_test2 = ini_get("max_execution_time");
        $php_test2 = ($php_test2 > DUPLICATOR_SCAN_TIMEOUT) || (strcmp($php_test2, 'Off') == 0 || $php_test2 == 0) ? true : false;
        $php_test3 = function_exists('mysqli_connect');
        $php_test4 = DUP_Util::$on_php_53_plus ? true : false;

        $checks['SRV']['PHP']['websrv'] = $php_test0;
        $checks['SRV']['PHP']['openbase'] = $php_test1;
        $checks['SRV']['PHP']['maxtime'] = $php_test2;
        $checks['SRV']['PHP']['mysqli'] = $php_test3;
        $checks['SRV']['PHP']['version'] = $php_test4;
        $checks['SRV']['PHP']['ALL'] = ($php_test0 && $php_test1 && $php_test2 && $php_test3 && $php_test4) ? 'Good' : 'Warn';

        //WORDPRESS SETTINGS
        global $wp_version;
        $wp_test1 = version_compare($wp_version, DUPLICATOR_SCAN_MIN_WP) >= 0 ? true : false;

        //Core Files
        $files = array();
        $files['wp-config.php'] = file_exists(DUP_Util::safePath(DUPLICATOR_WPROOTPATH . '/wp-config.php'));

        /** searching wp-config in working word press is not worthy
         * if this script is executing that means wp-config.php exists :)
         * we need to know the core folders and files added by the user at this point
         * retaining old logic as else for the case if its used some where else
         */
        //Core dir and files logic

        if (isset($_POST['file_notice']) && isset($_POST['dir_notice'])) {
            //means if there are core directories excluded or core files excluded return false
            if ((bool) $_POST['file_notice'] || (bool) $_POST['dir_notice'])
                $wp_test2 = false;
            else
                $wp_test2 = true;
        }else {
            $wp_test2 = $files['wp-config.php'];
        }

        //Cache
        $Package = DUP_Package::getActive();
        $cache_path = DUP_Util::safePath(WP_CONTENT_DIR) . '/cache';
        $dirEmpty = DUP_Util::isDirectoryEmpty($cache_path);
        $dirSize = DUP_Util::getDirectorySize($cache_path);
        $cach_filtered = in_array($cache_path, explode(';', $Package->Archive->FilterDirs));
        $wp_test3 = ($cach_filtered || $dirEmpty || $dirSize < DUPLICATOR_SCAN_CACHESIZE ) ? true : false;
        $wp_test4 = is_multisite();

        $checks['SRV']['WP']['version'] = $wp_test1;
        $checks['SRV']['WP']['core'] = $wp_test2;
        $checks['SRV']['WP']['cache'] = $wp_test3;
        $checks['SRV']['WP']['ismu'] = $wp_test4;
        $checks['SRV']['WP']['ALL'] = $wp_test1 && $wp_test2 && $wp_test3 && !$wp_test4 ? 'Good' : 'Warn';

        return $checks;
    }

    /**
     * Check to see if duplicator installer files are present
     *
     * @return bool   True if any reserved files are found
     */
    public static function hasInstallerFiles() {
        $files = self::getInstallerFiles();
        foreach ($files as $file => $path) {
            if (false !== strpos($path, '*')) {
                $glob_files = glob($path);
                if (!empty($glob_files)) {
                    return true;
                }
            } elseif (file_exists($path))
                return true;
        }
        return false;
    }

    /**
     * Gets a list of all the installer files by name and full path
     *
     * @return array [file_name, file_path]
     */
    public static function getInstallerFiles() {
        //Files:   installer.php, installer-backup.php, installer-bootlog.txt, wp-config.orig (legacy support)
        //Dirs:	   dup-installer
        return array(
            DUPLICATOR_INSTALL_PHP => DUPLICATOR_WPROOTPATH . DUPLICATOR_INSTALL_PHP,
            DUPLICATOR_INSTALL_BAK => DUPLICATOR_WPROOTPATH . DUPLICATOR_INSTALL_BAK,
            DUPLICATOR_INSTALL_BOOT_LOG => DUPLICATOR_WPROOTPATH . DUPLICATOR_INSTALL_BOOT_LOG,
            basename(DUPLICATOR_INSTALLER_DIRECTORY) . ' ' . __('(directory)') => DUPLICATOR_INSTALLER_DIRECTORY,
            'dup-wp-config-arc__[HASH].txt' => DUPLICATOR_WPROOTPATH . 'dup-wp-config-arc__*.txt',
			'wp-config.orig' => DUPLICATOR_WPROOTPATH . 'wp-config.orig'
        );
    }

    /**
     * Get the IP of a client machine
     *
     * @return string   IP of the client machine
     */
    public static function getClientIP() {
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            return $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            return $_SERVER["REMOTE_ADDR"];
        } else if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
            return $_SERVER["HTTP_CLIENT_IP"];
        }
        return '';
    }

    /**
     * Get PHP memory usage
     *
     * @return string   Returns human readable memory usage.
     */
    public static function getPHPMemory($peak = false) {
        if ($peak) {
            $result = 'Unable to read PHP peak memory usage';
            if (function_exists('memory_get_peak_usage')) {
                $result = DUP_Util::byteSize(memory_get_peak_usage(true));
            }
        } else {
            $result = 'Unable to read PHP memory usage';
            if (function_exists('memory_get_usage')) {
                $result = DUP_Util::byteSize(memory_get_usage(true));
            }
        }
        return $result;
    }

}