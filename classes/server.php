<?php
if ( ! defined( 'DUPLICATOR_VERSION' ) ) exit; // Exit if accessed directly

require_once (DUPLICATOR_PLUGIN_PATH . 'classes/utility.php');

/**
 * Class used to get server statis
 * @package Dupicator\classes
 */
class DUP_Server {
	
	/** 
	* Gets the system requirments which must pass to buld a package
	* @return array   An array of requirements
	*/
	public static function GetRequirments() {

		global $wpdb;
		$dup_tests = array();
		
		//PHP SUPPORT
		$safe_ini = strtolower(ini_get('safe_mode'));
		$dup_tests['PHP']['SAFE_MODE'] = $safe_ini  != 'on' || $safe_ini != 'yes' || $safe_ini != 'true' || ini_get("safe_mode") != 1 ? 'Pass' : 'Fail';
		$dup_tests['PHP']['VERSION'] = version_compare(phpversion(), '5.2.17') >= 0 ? 'Pass' : 'Fail';
		$dup_tests['PHP']['ZIP']	 = class_exists('ZipArchive')				? 'Pass' : 'Fail';
		$dup_tests['PHP']['FUNC_1']  = function_exists("file_get_contents")		? 'Pass' : 'Fail';
		$dup_tests['PHP']['FUNC_2']  = function_exists("file_put_contents")		? 'Pass' : 'Fail';
		$dup_tests['PHP']['ALL']	 = ! in_array('Fail', $dup_tests['PHP'])	? 'Pass' : 'Fail';		
		
		//PERMISSIONS
		$handle_test = @opendir(DUPLICATOR_WPROOTPATH);		
		$dup_tests['IO']['WPROOT']	= is_writeable(DUPLICATOR_WPROOTPATH) && $handle_test ? 'Pass' : 'Fail';
		$dup_tests['IO']['SSDIR']	= is_writeable(DUPLICATOR_SSDIR_PATH)		? 'Pass' : 'Fail';
		$dup_tests['IO']['SSTMP']	= is_writeable(DUPLICATOR_SSDIR_PATH_TMP)	? 'Pass' : 'Fail';
		$dup_tests['IO']['ALL']		= ! in_array('Fail', $dup_tests['IO'])		? 'Pass' : 'Fail'; 
		@closedir($handle_test);
		
		//SERVER SUPPORT
		$dup_tests['SRV']['MYSQLi']		= function_exists('mysqli_connect')					? 'Pass' : 'Fail'; 
		$dup_tests['SRV']['MYSQL_VER']	= version_compare($wpdb->db_version(), '5.0', '>=')	? 'Pass' : 'Fail'; 
		$dup_tests['SRV']['ALL']		= ! in_array('Fail', $dup_tests['SRV'])				? 'Pass' : 'Fail'; 
		
		//RESERVED FILES
		$dup_tests['RES']['INSTALL'] = !(self::InstallerFilesFound()) ? 'Pass' : 'Fail';
		$dup_tests['Success'] = $dup_tests['PHP']['ALL']  == 'Pass' && $dup_tests['IO']['ALL'] == 'Pass' &&
								$dup_tests['SRV']['ALL']  == 'Pass' && $dup_tests['RES']['INSTALL'] == 'Pass';
		
		return $dup_tests;
	}		
	
	/** 
	* Gets the system checks which are not required
	* @return array   An array of system checks
	*/
	public static function GetChecks() {
		$checks = array();

		//CHK-SRV-100: PHP SETTINGS
		$php_test1 = ini_get("open_basedir");
		$php_test1 = empty($php_test1) ? true : false;
		$php_test2 = ini_get("max_execution_time");
		$php_test2  = ($php_test2  > DUPLICATOR_SCAN_TIMEOUT || strcmp($php_test2 , 'Off') == 0 || $php_test2  == 0) ? 'Good' : 'Warn';
		$checks['CHK-SRV-100'] = ($php_test1 && $php_test2) ? 'Good' : 'Warn';

		//CHK-SRV-101: WORDPRESS SETTINGS
		//Version
		global $wp_version;
		$version_test = version_compare($wp_version,  DUPLICATOR_SCAN_MIN_WP) >= 0 ? true : false;
		
		//Cache
		$Package = DUP_Package::GetActive();
		$cache_path = DUP_Util::SafePath(WP_CONTENT_DIR) .  '/cache';
		$dirEmpty	= DUP_Util::IsDirectoryEmpty($cache_path);
		$dirSize	= DUP_Util::GetDirectorySize($cache_path); 
		$cach_filtered = in_array($cache_path, explode(';', $Package->Archive->FilterDirs));
		$cache_test = ($cach_filtered || $dirEmpty  || $dirSize < DUPLICATOR_SCAN_CACHESIZE ) ? true : false;
		
		//Core Files
		$files = array();
		$files['wp-config.php'] = file_exists(DUP_Util::SafePath(DUPLICATOR_WPROOTPATH .  '/wp-config.php'));
		$files_test = $files['wp-config.php'];
		
		$checks['CHK-SRV-101'] = $files_test && $cache_test && $version_test ? 'Good' : 'Warn';

		//CHK-SRV-102: WEB SERVER 
		$servers = $GLOBALS['DUPLICATOR_SERVER_LIST'];
		$test = false;
		foreach ($servers as $value) {
			if (stristr($_SERVER['SERVER_SOFTWARE'], $value)) {
				$test = true;
				break;
			}
		}
		$checks['CHK-SRV-102'] = ($test) ? 'Good' : 'Warn';
		
		//RESULTS
		$result = in_array('Warn', $checks);
		$checks['Success'] = !$result;

		return $checks;
	}
	
	/** 
	* Check to see if duplicator installer files are present
	* @return bool   True if any reserved files are found
	*/
	public static function InstallerFilesFound() {
		
		$phpFile = file_exists(DUPLICATOR_WPROOTPATH . DUPLICATOR_INSTALL_PHP);
		$sqlFile = file_exists(DUPLICATOR_WPROOTPATH . DUPLICATOR_INSTALL_SQL);
		$logFile = file_exists(DUPLICATOR_WPROOTPATH . DUPLICATOR_INSTALL_LOG);
		return  ($phpFile || $sqlFile || $logFile);
	}
	
	/** 
	* Get the IP of a client machine
	* @return string   IP of the client machine
	*/
	public static function GetClientIP() {
		
		if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)){
            return  $_SERVER["HTTP_X_FORWARDED_FOR"];  
        }else if (array_key_exists('REMOTE_ADDR', $_SERVER)) { 
            return $_SERVER["REMOTE_ADDR"]; 
        }else if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
            return $_SERVER["HTTP_CLIENT_IP"]; 
        } 

        return '';
	}
	
	/** 
	* Get PHP memory useage 
	* @return string   Returns human readable memory useage.
	*/
	public static function GetPHPMemory($peak = false) {
		
		if ($peak) {
			$result = 'Unable to read PHP peak memory usage';
			if (function_exists('memory_get_peak_usage')) {
				$result = DUP_Util::ByteSize(memory_get_peak_usage(true));
			} 
		} else {
			$result = 'Unable to read PHP memory usage';
			if (function_exists('memory_get_usage')) {
				$result = DUP_Util::ByteSize(memory_get_usage(true));
			} 
		}

        return $result;
	}
	
}
?>