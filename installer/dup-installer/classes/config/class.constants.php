<?php
defined("ABSPATH") or die("");

/**
 * Class used to group all global constants
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2 Full Documentation
 *
 * @package SC\DUPX\Constants
 *
 */
class DUPX_MultisiteMode
{
	const Standalone = 0;
	const Subdomain = 1;
	const Subdirectory = 2;
}

class DUPX_Constants
{
	/**
	 * Init method used to auto initialize the global params
	 *
	 * @return null
	 */
	public static function init()
	{
		$GLOBALS['BOOTLOADER_NAME'] = isset($_GET['bootloader'])  ? $_GET['bootloader'] : null ;
        $GLOBALS['FW_PACKAGE_PATH'] = isset($_GET['archive'])     ? $_GET['archive']    : null; // '%fwrite_package_name%';
        $GLOBALS['FW_ENCODED_PACKAGE_PATH'] = urlencode($GLOBALS['FW_PACKAGE_PATH']);
        $GLOBALS['FW_PACKAGE_NAME'] = basename($GLOBALS['FW_PACKAGE_PATH']);

		$GLOBALS['FAQ_URL'] = 'https://snapcreek.com/duplicator/docs/faqs-tech';

		//DATABASE SETUP: all time in seconds
		//max_allowed_packet: max value 1073741824 (1268MB)
		@ini_set('mysql.connect_timeout', '5000');
		$GLOBALS['DB_MAX_TIME'] = 5000;
		$GLOBALS['DB_MAX_PACKETS'] = 268435456;
		$GLOBALS['DBCHARSET_DEFAULT'] = 'utf8';
		$GLOBALS['DBCOLLATE_DEFAULT'] = 'utf8_general_ci';
		$GLOBALS['DB_RENAME_PREFIX'] = 'x-bak-' . @date("dHis") . '__';

		//UPDATE TABLE SETTINGS
		$GLOBALS['REPLACE_LIST'] = array();
		$GLOBALS['DEBUG_JS'] = false;

		//PHP SETUP: all time in seconds
		@ini_set('memory_limit', '5000M');
		@ini_set("max_execution_time", '5000');
		@ini_set("max_input_time", '5000');
		@ini_set('default_socket_timeout', '5000');
		@set_time_limit(0);

		//CONSTANTS
		define("DUPLICATOR_PRO_INIT", 1);
		define("DUPLICATOR_PRO_SSDIR_NAME", 'wp-snapshots-dup-pro');  //This should match DUPLICATOR_PRO_SSDIR_NAME in duplicator.php

		//SHARED POST PARMS
		$_GET['debug'] = isset($_GET['debug']) ? true : false;
		$_GET['basic'] = isset($_GET['basic']) ? true : false;
		$_POST['view'] = isset($_POST['view']) ? $_POST['view'] : "secure";

		//GLOBALS
		$GLOBALS["VIEW"]				= isset($_GET["view"]) ? $_GET["view"] : $_POST["view"];
        $GLOBALS['INIT']                = ($GLOBALS['VIEW'] === 'secure');
		$GLOBALS['SQL_FILE_NAME']		= "installer-data.sql";
		$GLOBALS["LOG_FILE_NAME"]		= "installer-log.txt";
		$GLOBALS['SEPERATOR1']			= str_repeat("********", 10);
		$GLOBALS['LOGGING']				= isset($_POST['logging']) ? $_POST['logging'] : 1;
		$GLOBALS['CURRENT_ROOT_PATH']	= str_replace('\\', '/', realpath(dirname(__FILE__) . "/../../../"));
		$GLOBALS['LOG_FILE_PATH']		= $GLOBALS['CURRENT_ROOT_PATH'] . '/' . $GLOBALS["LOG_FILE_NAME"];
		$GLOBALS['CHOWN_ROOT_PATH']		= @chmod("{$GLOBALS['CURRENT_ROOT_PATH']}", 0755);
		$GLOBALS['CHOWN_LOG_PATH']		= @chmod("{$GLOBALS['CURRENT_ROOT_PATH']}/{$GLOBALS['LOG_FILE_NAME']}", 0644);
		$GLOBALS['URL_SSL']				= (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') ? true : false;
		$GLOBALS['URL_PATH']			= ($GLOBALS['URL_SSL']) ? "https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}" : "http://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}";
		$GLOBALS['PHP_MEMORY_LIMIT']	= ini_get('memory_limit') === false ? 'n/a' : ini_get('memory_limit');
		$GLOBALS['PHP_SUHOSIN_ON']		= extension_loaded('suhosin') ? 'enabled' : 'disabled';

		//Restart log if user starts from step 1
		$GLOBALS['LOG_FILE_HANDLE'] = ($GLOBALS["VIEW"] == "step1")
			? @fopen($GLOBALS['LOG_FILE_PATH'], "w+")
			: @fopen($GLOBALS['LOG_FILE_PATH'], "a+");

		$GLOBALS['FW_USECDN'] = false;
		$GLOBALS['HOST_NAME'] = strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	}
}

DUPX_Constants::init();
