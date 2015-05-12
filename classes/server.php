<?php
if ( ! defined( 'DUPLICATOR_VERSION' ) ) exit; // Exit if accessed directly

require_once (DUPLICATOR_PLUGIN_PATH . 'classes/utility.php');

/**
 * Class used to get server statis
 * @package Dupicator\classes
 */
class DUP_Server 
{
	
	/** 
	* Gets the system requirements which must pass to buld a package
	* @return array   An array of requirements
	*/
	public static function GetRequirements() 
	{

		global $wpdb;
		$dup_tests = array();
		
		//PHP SUPPORT
		$safe_ini = strtolower(ini_get('safe_mode'));
		$dup_tests['PHP']['SAFE_MODE'] = $safe_ini  != 'on' || $safe_ini != 'yes' || $safe_ini != 'true' || ini_get("safe_mode") != 1 ? 'Pass' : 'Fail';
		$dup_tests['PHP']['VERSION'] = version_compare(phpversion(), '5.2.17') >= 0 ? 'Pass' : 'Fail';
		$dup_tests['PHP']['ZIP']	 = class_exists('ZipArchive')				? 'Pass' : 'Fail';
		$dup_tests['PHP']['FUNC_1']  = function_exists("file_get_contents")		? 'Pass' : 'Fail';
		$dup_tests['PHP']['FUNC_2']  = function_exists("file_put_contents")		? 'Pass' : 'Fail';
		$dup_tests['PHP']['FUNC_3']  = function_exists("mb_strlen")				? 'Pass' : 'Fail';
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
	public static function GetChecks() 
	{
		$checks = array();
		
		//WEB SERVER 
		$web_test1 = false;
		foreach ($GLOBALS['DUPLICATOR_SERVER_LIST'] as $value) {
			if (stristr($_SERVER['SERVER_SOFTWARE'], $value)) {
				$web_test1 = true;
				break;
			}
		}
		$checks['SRV']['WEB']['model'] = $web_test1;
		$checks['SRV']['WEB']['ALL']   = ($web_test1) ? 'Good' : 'Warn';
		
		//PHP SETTINGS
		$php_test1 = ini_get("open_basedir");
		$php_test1 = empty($php_test1) ? true : false;
		$php_test2 = ini_get("max_execution_time");
		$php_test2 = ($php_test2 > DUPLICATOR_SCAN_TIMEOUT)  || (strcmp($php_test2 , 'Off') == 0 || $php_test2  == 0) ? true : false;                
		$php_test3	= function_exists('mysqli_connect');
                            
		$checks['SRV']['PHP']['openbase'] = $php_test1;
		$checks['SRV']['PHP']['maxtime']  = $php_test2;
		$checks['SRV']['PHP']['mysqli']   = $php_test3;
		$checks['SRV']['PHP']['ALL']      = ($php_test1 && $php_test2 && $php_test3) ? 'Good' : 'Warn';
		
		//WORDPRESS SETTINGS
		global $wp_version;
		$wp_test1 = version_compare($wp_version,  DUPLICATOR_SCAN_MIN_WP) >= 0 ? true : false;
		
		//Core Files
		$files = array();
		$files['wp-config.php'] = file_exists(DUP_Util::SafePath(DUPLICATOR_WPROOTPATH .  '/wp-config.php'));
		$wp_test2 = $files['wp-config.php'];
		
		//Cache
		$Package = DUP_Package::GetActive();
		$cache_path = DUP_Util::SafePath(WP_CONTENT_DIR) .  '/cache';
		$dirEmpty	= DUP_Util::IsDirectoryEmpty($cache_path);
		$dirSize	= DUP_Util::GetDirectorySize($cache_path); 
		$cach_filtered = in_array($cache_path, explode(';', $Package->Archive->FilterDirs));
		$wp_test3 = ($cach_filtered || $dirEmpty  || $dirSize < DUPLICATOR_SCAN_CACHESIZE ) ? true : false;
		
		$checks['SRV']['WP']['version'] = $wp_test1;
		$checks['SRV']['WP']['core']	= $wp_test2;
		$checks['SRV']['WP']['cache']	= $wp_test3;
		$checks['SRV']['WP']['ALL']		= $wp_test1 && $wp_test2 && $wp_test3 ? 'Good' : 'Warn';

		return $checks;
	}
	
	/** 
	* Check to see if duplicator installer files are present
	* @return bool   True if any reserved files are found
	*/
	public static function InstallerFilesFound() 
	{
		
		$phpFile = file_exists(DUPLICATOR_WPROOTPATH . DUPLICATOR_INSTALL_PHP);
		$sqlFile = file_exists(DUPLICATOR_WPROOTPATH . DUPLICATOR_INSTALL_SQL);
		$logFile = file_exists(DUPLICATOR_WPROOTPATH . DUPLICATOR_INSTALL_LOG);
		return  ($phpFile || $sqlFile || $logFile);
	}
	
	/** 
	* Get the IP of a client machine
	* @return string   IP of the client machine
	*/
	public static function GetClientIP() 
	{
		
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
	public static function GetPHPMemory($peak = false) 
	{
		
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