<?php
defined('ABSPATH') || defined('DUPXABSPATH') || exit;
require_once (DUPLICATOR_PLUGIN_PATH.'classes/utilities/class.u.php');

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

class DUP_Server
{

	const LockFileName = 'lockfile.txt';

	// Possibly use in the future if we want to prevent double building
	public static function isEngineLocked()
	{
		if (self::setEngineLock(true)) {
			self::setEngineLock(false);
			$locked = false;
		} else {
			$locked = true;
		}
	}

	// Possibly use in the future if we want to prevent double building
	public static function setEngineLock($shouldLock)
	{
		$success		 = false;
		$locking_file	 = @fopen(self::LockFileName, 'c+');
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

    public static function mysqlEscapeIsOk()
    {
        $escape_test_string            = chr(0).chr(26)."\r\n'\"\\";
        $escape_expected_result        = "\"\\0\Z\\r\\n\\'\\\"\\\\\"";
        $escape_actual_result          = DUP_DB::escValueToQueryString($escape_test_string);
        $result                        = $escape_expected_result === $escape_actual_result;

        if (!$result) {
            $msg = "mysqli_real_escape_string test results\n".
                "Expected escape result: ".$escape_expected_result."\n".
                "Actual escape result: ".$escape_actual_result;
            DUP_Log::trace($msg);

        }

        return $result;
    }

	/**
	 * Gets the system requirements which must pass to build a package
	 *
	 * @return array   An array of requirements
	 */
	public static function getRequirements()
	{
		$dup_tests = array();

		//PHP SUPPORT
		$safe_ini						 = strtolower(ini_get('safe_mode'));
		$dup_tests['PHP']['SAFE_MODE']	 = $safe_ini != 'on' || $safe_ini != 'yes' || $safe_ini != 'true' || ini_get("safe_mode") != 1 ? 'Pass' : 'Fail';
		self::logRequirementFail($dup_tests['PHP']['SAFE_MODE'], 'SAFE_MODE is on.');

		$dup_tests['PHP']['VERSION'] = DUP_Util::$on_php_529_plus ? 'Pass' : 'Fail';
		$phpversion					 = phpversion();
		self::logRequirementFail($dup_tests['PHP']['VERSION'], 'PHP version('.$phpversion.') is lower than 5.2.9');

		if (DUP_Settings::Get('archive_build_mode') == DUP_Archive_Build_Mode::ZipArchive) {
			$dup_tests['PHP']['ZIP'] = class_exists('ZipArchive') ? 'Pass' : 'Fail';
			self::logRequirementFail($dup_tests['PHP']['ZIP'], 'ZipArchive class doesn\'t exist.');
		}

		$dup_tests['PHP']['FUNC_1'] = function_exists("file_get_contents") ? 'Pass' : 'Fail';
		self::logRequirementFail($dup_tests['PHP']['FUNC_1'], 'file_get_contents function doesn\'t exist.');

		$dup_tests['PHP']['FUNC_2'] = function_exists("file_put_contents") ? 'Pass' : 'Fail';
		self::logRequirementFail($dup_tests['PHP']['FUNC_2'], 'file_put_contents function doesn\'t exist.');

		$dup_tests['PHP']['FUNC_3'] = function_exists("mb_strlen") ? 'Pass' : 'Fail';
		self::logRequirementFail($dup_tests['PHP']['FUNC_3'], 'mb_strlen function doesn\'t exist.');

		$dup_tests['PHP']['ALL'] = !in_array('Fail', $dup_tests['PHP']) ? 'Pass' : 'Fail';

		//REQUIRED PATHS
		$abs_path					 = duplicator_get_abs_path();
		$handle_test				 = @opendir($abs_path);
		$dup_tests['IO']['WPROOT']	 = is_writeable($abs_path) && $handle_test ? 'Pass' : 'Warn';
		@closedir($handle_test);
		self::logRequirementFail($dup_tests['IO']['WPROOT'], $abs_path.' (abs path) can\'t be opened.');

		$dup_tests['IO']['SSDIR'] = is_writeable(DUP_Settings::getSsdirPath()) ? 'Pass' : 'Fail';
		self::logRequirementFail($dup_tests['IO']['SSDIR'], DUP_Settings::getSsdirPath().' (DUPLICATOR_SSDIR_PATH) can\'t be writeable.');

		$dup_tests['IO']['SSTMP'] = is_writeable(DUP_Settings::getSsdirTmpPath()) ? 'Pass' : 'Fail';
		self::logRequirementFail($dup_tests['IO']['SSTMP'], DUP_Settings::getSsdirTmpPath().' (DUPLICATOR_SSDIR_PATH_TMP) can\'t be writeable.');

		$dup_tests['IO']['ALL'] = !in_array('Fail', $dup_tests['IO']) ? 'Pass' : 'Fail';

		//SERVER SUPPORT
		$dup_tests['SRV']['MYSQLi'] = function_exists('mysqli_connect') ? 'Pass' : 'Fail';
		self::logRequirementFail($dup_tests['SRV']['MYSQLi'], 'mysqli_connect function doesn\'t exist.');

        //mysqli_real_escape_string test
        $dup_tests['SRV']['MYSQL_ESC'] = self::mysqlEscapeIsOk() ? 'Pass' : 'Fail';
        self::logRequirementFail($dup_tests['SRV']['MYSQL_ESC'], "The function mysqli_real_escape_string is not escaping strings as expected.");

		$db_version						 = DUP_DB::getVersion();
		$dup_tests['SRV']['MYSQL_VER']	 = version_compare($db_version, '5.0', '>=') ? 'Pass' : 'Fail';
		self::logRequirementFail($dup_tests['SRV']['MYSQL_VER'], 'MySQL version '.$db_version.' is lower than 5.0.');

		$dup_tests['SRV']['ALL'] = !in_array('Fail', $dup_tests['SRV']) ? 'Pass' : 'Fail';

		//RESERVED FILES
		$dup_tests['RES']['INSTALL'] = !(self::hasInstallerFiles()) ? 'Pass' : 'Fail';
		self::logRequirementFail($dup_tests['RES']['INSTALL'], 'Installer file(s) are exist on the server.');
		$dup_tests['Success']		 = $dup_tests['PHP']['ALL'] == 'Pass' && $dup_tests['IO']['ALL'] == 'Pass' && $dup_tests['SRV']['ALL'] == 'Pass' && $dup_tests['RES']['INSTALL'] == 'Pass';

		$dup_tests['Warning'] = $dup_tests['IO']['WPROOT'] == 'Warn';

		return $dup_tests;
	}

	/**
	 * Logs requirement fail status informative message
	 *
	 * @param string $testStatus Either it is Pass or Fail
	 * @param string $errorMessage Error message which should be logged
	 * @return void
	 */
	private static function logRequirementFail($testStatus, $errorMessage)
	{
		if (empty($testStatus)) {
			throw new Exception('Exception: Empty $testStatus [File: '.__FILE__.', Ln: '.__LINE__);
		}

		if (empty($errorMessage)) {
			throw new Exception('Exception: Empty $errorMessage [File: '.__FILE__.', Ln: '.__LINE__);
		}

		$validTestStatuses = array('Pass', 'Fail', 'Warn');

		if (!in_array($testStatus, $validTestStatuses)) {
			throw new Exception('Exception: Invalid $testStatus value: '.$testStatus.' [File: '.__FILE__.', Ln: '.__LINE__);
		}

		if ('Fail' == $testStatus) {
			DUP_LOG::trace($errorMessage);
		}
	}

	/**
	 * Gets the system checks which are not required
	 *
	 * @return array   An array of system checks
	 */
	public static function getChecks()
	{
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
		self::logCheckFalse($php_test0, 'Any out of server software ('.implode(', ', $GLOBALS['DUPLICATOR_SERVER_LIST']).') doesn\'t exist.');

		$php_test1	 = ini_get("open_basedir");
		$php_test1	 = empty($php_test1) ? true : false;
		self::logCheckFalse($php_test1, 'open_basedir is enabled.');

		$max_execution_time	 = ini_get("max_execution_time");
		$php_test2			 = ($max_execution_time > DUPLICATOR_SCAN_TIMEOUT) || (strcmp($max_execution_time, 'Off') == 0 || $max_execution_time == 0) ? true : false;
		if (strcmp($max_execution_time, 'Off') == 0) {
			$max_execution_time_error_message = '$max_execution_time should not be'.$max_execution_time;
		} else {
			$max_execution_time_error_message = '$max_execution_time ('.$max_execution_time.') should not  be lower than the DUPLICATOR_SCAN_TIMEOUT'.DUPLICATOR_SCAN_TIMEOUT;
		}
		self::logCheckFalse($php_test2, $max_execution_time_error_message);

		$php_test3 = function_exists('mysqli_connect');
		self::logCheckFalse($php_test3, 'mysqli_connect function doesn\'t exist.');

		$php_test4 = DUP_Util::$on_php_53_plus ? true : false;
		self::logCheckFalse($php_test4, 'PHP Version is lower than 5.3.');

		$checks['SRV']['PHP']['websrv']		 = $php_test0;
		$checks['SRV']['PHP']['openbase']	 = $php_test1;
		$checks['SRV']['PHP']['maxtime']	 = $php_test2;
		$checks['SRV']['PHP']['mysqli']		 = $php_test3;
		$checks['SRV']['PHP']['version']	 = $php_test4;
        //MANAGED HOST
        $checks['SRV']['SYS']['managedHost'] = !DUP_Custom_Host_Manager::getInstance()->isManaged();
		$checks['SRV']['SYS']['ALL']		 = ($php_test0 && $php_test1 && $php_test2 && $php_test3 && $php_test4 && $checks['SRV']['SYS']['managedHost']) ? 'Good' : 'Warn';

		//WORDPRESS SETTINGS
		global $wp_version;
		$wp_test1 = version_compare($wp_version, DUPLICATOR_SCAN_MIN_WP) >= 0 ? true : false;
		self::logCheckFalse($wp_test1, 'WP version ('.$wp_version.') is lower than the DUPLICATOR_SCAN_MIN_WP ('.DUPLICATOR_SCAN_MIN_WP.').');

		//Core Files
		$files						 = array();
		$proper_wp_config_file_path	 = duplicator_get_abs_path().'/wp-config.php';
		$files['wp-config.php']		 = file_exists($proper_wp_config_file_path);
		self::logCheckFalse($files['wp-config.php'], 'The wp-config.php file doesn\'t exist on the '.$proper_wp_config_file_path);

		/** searching wp-config in working word press is not worthy
		 * if this script is executing that means wp-config.php exists :)
		 * we need to know the core folders and files added by the user at this point
		 * retaining old logic as else for the case if its used some where else
		 */
		//Core dir and files logic
		if (isset($_POST['file_notice']) && isset($_POST['dir_notice'])) {
			//means if there are core directories excluded or core files excluded return false
			if ((bool) $_POST['file_notice'] || (bool) $_POST['dir_notice'])
				$wp_test2	 = false;
			else
				$wp_test2	 = true;
		} else {
			$wp_test2 = $files['wp-config.php'];
		}

		//Cache
		/*
		  $Package = DUP_Package::getActive();
		  $cache_path = DUP_Util::safePath(WP_CONTENT_DIR) . '/cache';
		  $dirEmpty = DUP_Util::isDirectoryEmpty($cache_path);
		  $dirSize = DUP_Util::getDirectorySize($cache_path);
		  $cach_filtered = in_array($cache_path, explode(';', $Package->Archive->FilterDirs));
		  $wp_test3 = ($cach_filtered || $dirEmpty || $dirSize < DUPLICATOR_SCAN_CACHESIZE ) ? true : false;
		 */
		$wp_test3 = is_multisite();
		self::logCheckFalse($wp_test3, 'WP is multi-site setup.');

		$checks['SRV']['WP']['version']	 = $wp_test1;
		$checks['SRV']['WP']['core']	 = $wp_test2;
		$checks['SRV']['WP']['ismu']	 = $wp_test3;
		$checks['SRV']['WP']['ALL']		 = $wp_test1 && $wp_test2 && !$wp_test3 ? 'Good' : 'Warn';

		return $checks;
	}

	/**
	 * Logs checks false informative message
	 *
	 * @param boolean $check Either it is true or false
	 * @param string $errorMessage Error message which should be logged when check is false
	 * @return void
	 */
	private static function logCheckFalse($check, $errorMessage)
	{
		if (!is_bool($check)) {
			throw new Exception('Exception: Not boolean $check [File: '.__FILE__.', Ln: '.__LINE__);
		}

		if (empty($errorMessage)) {
			throw new Exception('Exception: Empty $errorMessage [File: '.__FILE__.', Ln: '.__LINE__);
		}

		if (false === $check) {
			DUP_LOG::trace($errorMessage);
		}
	}

	/**
	 * Check to see if duplicator installer files are present
	 *
	 * @return bool   True if any reserved files are found
	 */
	public static function hasInstallerFiles()
	{
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
	 * @remarks
	 *  FILES:		installer.php, installer-backup.php, dup-installer-bootlog__[HASH].txt
	 * 	DIRS:		dup-installer
	 * 	DEV FILES:	wp-config.orig
	 * 	Last set is for lazy developer cleanup files that a developer may have
	 *  accidently left around lets be proactive for the user just in case.
	 *
	 * @return array [file_name, file_path]
	 */
	public static function getInstallerFiles()
	{
		// alphanumeric 7 time, then -(dash), then 8 digits
		$abs_path				 = duplicator_get_abs_path();
		$four_digit_glob_pattern = '[0-9][0-9][0-9][0-9]';
		$retArr					 = array(
			basename(DUPLICATOR_INSTALLER_DIRECTORY).' '.esc_html__('(directory)', 'duplicator') => DUPLICATOR_INSTALLER_DIRECTORY,
			DUPLICATOR_INSTALL_PHP																 => $abs_path.'/'.DUPLICATOR_INSTALL_PHP,
			'[HASH]'.'_'.DUPLICATOR_INSTALL_PHP													 => $abs_path.'/*_*'.$four_digit_glob_pattern.'_'.DUPLICATOR_INSTALL_PHP,
			DUPLICATOR_INSTALL_BAK																 => $abs_path.'/'.DUPLICATOR_INSTALL_BAK,
			'[HASH]'.'_'.DUPLICATOR_INSTALL_BAK													 => $abs_path.'/*_*'.$four_digit_glob_pattern.'_'.DUPLICATOR_INSTALL_BAK,
			'[HASH]_archive.zip|daf'															 => $abs_path.'/*_*'.$four_digit_glob_pattern.'_archive.[zd][ia][pf]',
			'dup-installer-bootlog__[HASH].txt'													 => $abs_path.'/dup-installer-bootlog__'.DUPLICATOR_INSTALLER_HASH_PATTERN.'.txt',
		);
		if (DUPLICATOR_INSTALL_SITE_OVERWRITE_ON) {
			$retArr['dup-wp-config-arc__[HASH].txt'] = $abs_path.'/dup-wp-config-arc__'.DUPLICATOR_INSTALLER_HASH_PATTERN.'.txt';
		}
		return $retArr;
	}

	/**
	 * Get the IP of a client machine
	 *
	 * @return string   IP of the client machine
	 */
	public static function getClientIP()
	{
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
	public static function getPHPMemory($peak = false)
	{
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