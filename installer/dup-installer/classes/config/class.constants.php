<?php
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

/**
 * Class used to group all global constants
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2 Full Documentation
 *
 * @package SC\DUPX\Constants
 *
 */
class DUPX_Constants
{
    const DEFAULT_MAX_STRLEN_SERIALIZED_CHECK_IN_M = 4; // 0 no limit

    /**
     *
     * @var int
     */
    public static $maxStrlenSerializeCheck = self::DEFAULT_MAX_STRLEN_SERIALIZED_CHECK;

	/**
	 * Init method used to auto initialize the global params
	 *
	 * @return null
	 */
	public static function init()
	{
		$dup_installer_dir_absolute_path = dirname(dirname(dirname(__FILE__)));
		$config_files = glob($dup_installer_dir_absolute_path.'/dup-archive__*.txt');
		$config_file_absolute_path = array_pop($config_files);
		$config_file_name = basename($config_file_absolute_path, '.txt');
		$archive_prefix_length = strlen('dup-archive__');
		$GLOBALS['PACKAGE_HASH'] = substr($config_file_name, $archive_prefix_length); 
        
        $bootloader                 = DUPX_CSRF::getVal('bootloader');
        $GLOBALS['BOOTLOADER_NAME'] = $bootloader ? $bootloader : 'installer.php';
        $package                    = DUPX_CSRF::getVal('archive');
        $GLOBALS['FW_PACKAGE_PATH'] = $package ? $package : null; // '%fwrite_package_name%';

        $GLOBALS['FW_ENCODED_PACKAGE_PATH'] = urlencode($GLOBALS['FW_PACKAGE_PATH']);
        $GLOBALS['FW_PACKAGE_NAME'] = basename($GLOBALS['FW_PACKAGE_PATH']);

		$GLOBALS['FAQ_URL'] = 'https://snapcreek.com/duplicator/docs/faqs-tech';

		//DATABASE SETUP: all time in seconds
		//max_allowed_packet: max value 1073741824 (1268MB) see my.ini
		$GLOBALS['DB_MAX_TIME'] = 5000;
        $GLOBALS['DATABASE_PAGE_SIZE'] = 3500;
		$GLOBALS['DB_MAX_PACKETS'] = 268435456;
		$GLOBALS['DBCHARSET_DEFAULT'] = 'utf8';
		$GLOBALS['DBCOLLATE_DEFAULT'] = 'utf8_general_ci';
		$GLOBALS['DB_RENAME_PREFIX'] = 'x-bak-' . @date("dHis") . '__';

        if (!defined('MAX_SITES_TO_DEFAULT_ENABLE_CORSS_SEARCH')) {
            define('MAX_SITES_TO_DEFAULT_ENABLE_CORSS_SEARCH',  10);
        }

		//UPDATE TABLE SETTINGS
		$GLOBALS['REPLACE_LIST'] = array();
		$GLOBALS['DEBUG_JS'] = false;

		//PHP INI SETUP: all time in seconds
		if (!$GLOBALS['DUPX_ENFORCE_PHP_INI']) {
			if (DupLiteSnapLibUtil::wp_is_ini_value_changeable('mysql.connect_timeout'))@ini_set('mysql.connect_timeout', '5000');
			if (DupLiteSnapLibUtil::wp_is_ini_value_changeable('memory_limit'))  @ini_set('memory_limit', DUPLICATOR_PHP_MAX_MEMORY);
			if (DupLiteSnapLibUtil::wp_is_ini_value_changeable('max_execution_time'))  @ini_set("max_execution_time", '5000');
			if (DupLiteSnapLibUtil::wp_is_ini_value_changeable('max_input_time'))  @ini_set("max_input_time", '5000');
			if (DupLiteSnapLibUtil::wp_is_ini_value_changeable('default_socket_timeout'))  @ini_set('default_socket_timeout', '5000');
			@set_time_limit(0);
		}

		//CONSTANTS
		define("DUPLICATOR_INIT", 1);

		//SHARED POST PARAMS
		$_GET['debug'] = isset($_GET['debug']) ? true : false;
		$_GET['basic'] = isset($_GET['basic']) ? true : false;
		// For setting of help view
		if (isset($_GET['view'])) {
			$_POST['view'] = $_GET['view'];
		} elseif (!isset($_POST['view'])) {
			$_POST['view'] = "step1";
		}

		//GLOBALS
		$GLOBALS["VIEW"]				= isset($_GET["view"]) ? $_GET["view"] : $_POST["view"];
		$GLOBALS['INIT']                = ($GLOBALS['VIEW'] === 'secure');
 		$GLOBALS["LOG_FILE_NAME"]		= 'dup-installer-log__'.DUPX_CSRF::getVal('secondaryHash').'.txt';
		$GLOBALS['SEPERATOR1']			= str_repeat("********", 10);
		$GLOBALS['LOGGING']				= isset($_POST['logging']) ? $_POST['logging'] : 1;
		$GLOBALS['CURRENT_ROOT_PATH']	= str_replace('\\', '/', realpath(dirname(__FILE__) . "/../../../"));
		$GLOBALS['LOG_FILE_PATH']		= $GLOBALS['DUPX_INIT'] . '/' . $GLOBALS["LOG_FILE_NAME"];
        $GLOBALS["NOTICES_FILE_NAME"]	= "dup-installer-notices__{$GLOBALS['PACKAGE_HASH']}.json";
        $GLOBALS["NOTICES_FILE_PATH"]	= $GLOBALS['DUPX_INIT'] . '/' . $GLOBALS["NOTICES_FILE_NAME"];
		$GLOBALS['CHOWN_ROOT_PATH']		= DupLiteSnapLibIOU::chmod("{$GLOBALS['CURRENT_ROOT_PATH']}", 'u+rwx');
		$GLOBALS['CHOWN_LOG_PATH']		= DupLiteSnapLibIOU::chmod("{$GLOBALS['LOG_FILE_PATH']}", 'u+rw');
        $GLOBALS['CHOWN_NOTICES_PATH']	= DupLiteSnapLibIOU::chmod("{$GLOBALS['NOTICES_FILE_PATH']}", 'u+rw');
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $_SERVER ['HTTPS'] = 'on';
        }
        $GLOBALS['URL_SSL']				= (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') ? true : false;
		$GLOBALS['URL_PATH']			= ($GLOBALS['URL_SSL']) ? "https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}" : "http://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}";
		$GLOBALS['PHP_MEMORY_LIMIT']	= ini_get('memory_limit') === false ? 'n/a' : ini_get('memory_limit');
		$GLOBALS['PHP_SUHOSIN_ON']		= extension_loaded('suhosin') ? 'enabled' : 'disabled';

        /**
         * Inizialize notices manager and load file
         */
        $noticesManager = DUPX_NOTICE_MANAGER::getInstance();

        //Restart log if user starts from step 1
        if ($GLOBALS["VIEW"] == "step1") {
            $GLOBALS['LOG_FILE_HANDLE'] = @fopen($GLOBALS['LOG_FILE_PATH'], "w+");
            $noticesManager->resetNotices();
        } else {
            $GLOBALS['LOG_FILE_HANDLE'] = @fopen($GLOBALS['LOG_FILE_PATH'], "a+");
        }

		// for ngrok url and Local by Flywheel Live URL
		if (isset($_SERVER['HTTP_X_ORIGINAL_HOST'])) {
			$host = $_SERVER['HTTP_X_ORIGINAL_HOST'];
		} else {
			$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];//WAS SERVER_NAME and caused problems on some boxes
		}
        $GLOBALS['HOST_NAME'] = $host;

        if (!defined('MAX_STRLEN_SERIALIZED_CHECK')) { define('MAX_STRLEN_SERIALIZED_CHECK', 2000000); }
	}

	public static function initErrDefines()
    {
		define('ERR_ZIPNOTFOUND', 'The packaged zip file was not found or has become unreadable. Be sure the zip package is in the same directory as the installer file.  If you are trying to reinstall a package you can copy the package from the storage directory back up to your root which is the same location as your installer file.');

		define('ERR_SHELLEXEC_ZIPOPEN', 'Failed to extract the archive using shell_exec unzip');

		define('ERR_ZIPOPEN', 'Failed to open the zip archive file. Please be sure the archive is completely downloaded before running the installer. Try to extract the archive manually to make sure the file is not corrupted.');

		define('ERR_ZIPEXTRACTION', 'Errors extracting the zip file.  Portions or part of the zip archive did not extract correctly.    Try to extract the archive manually with a client side program like unzip/win-zip/winrar to make sure the file is not corrupted.  If the file extracts correctly then there is an invalid file or directory that PHP is unable to extract.  This can happen if your moving from one operating system to another where certain naming conventions work on one environment and not another. <br/><br/> Workarounds: <br/> 1. Create a new package and be sure to exclude any directories that have name checks or files in them.   This warning will be displayed on the scan results under "Name Checks". <br/> 2. Manually extract the zip file with a client side program.  Then under options in step 1 of the installer select the "Manual Archive Extraction" option and perform the install.');

		define('ERR_ZIPMANUAL', 'When choosing "Manual Archive Extraction", the contents of the package must already be extracted for the process to continue.  Please manually extract the package into the current directory before continuing in manual extraction mode.  Also validate that the wp-config.php files are present.');

		define('ERR_MAKELOG', 'PHP is having issues writing to the log file <b>'.$GLOBALS['DUPX_INIT'].'\dup-installer-log__[HASH].txt .</b> In order for the Duplicator to proceed validate your owner/group and permission settings for PHP on this path. Try temporarily setting you permissions to 777 to see if the issue gets resolved.  If you are on a shared hosting environment please contact your hosting company and tell them you are getting errors writing files to the path above when using PHP.');

		define('ERR_ZIPARCHIVE', 'In order to extract the archive.zip file, the PHP ZipArchive module must be installed.  Please read the FAQ for more details.  You can still install this package but you will need to select the "Manual Archive Extraction" options found under Options.  Please read the online user guide for details in performing a manual archive extraction.');

		define('ERR_MYSQLI_SUPPORT', 'In order to complete an install the mysqli extension for PHP is required. If you are on a hosted server please contact your host and request that mysqli be enabled.  For more information visit: http://php.net/manual/en/mysqli.installation.php');

		define('ERR_DBCONNECT', 'DATABASE CONNECTION FAILED!<br/>');

		define('ERR_DBCONNECT_CREATE', 'DATABASE CREATION FAILURE!<br/> Unable to create database "%s". Check to make sure the user has "Create" privileges.  Some hosts will restrict the creation of a database only through the cpanel.  Try creating the database manually to proceed with installation.  If the database already exists select the action "Connect and Remove All Data" which will remove all existing tables.');

        define('ERR_DROP_TABLE_TRYCLEAN', 'TABLE CLEAN FAILURE'
            .'Unable to remove TABLE "%s" from database "%s".<br/>'
            .'Please remove all tables from this database and try the installation again. '
            .'If no tables show in the database, then Drop the database and re-create it.<br/>'
            .'ERROR MESSAGE: %s');
        define('ERR_DROP_PROCEDURE_TRYCLEAN', 'PROCEDURE CLEAN FAILURE. '
            .'Please remove all procedures from this database and try the installation again. '
            .'If no procedures show in the database, then Drop the database and re-create it.<br/>'
            .'ERROR MESSAGE: %s <br/><br/>');
        define('ERR_DROP_FUNCTION_TRYCLEAN', 'FUNCTION CLEAN FAILURE. '
            .'Please remove all functions from this database and try the installation again. '
            .'If no functions show in the database, then Drop the database and re-create it.<br/>'
            .'ERROR MESSAGE: %s <br/><br/>');
        define('ERR_DROP_VIEW_TRYCLEAN', 'VIEW CLEAN FAILURE. '
            .'Please remove all views from this database and try the installation again. '
            .'If no views show in the database, then Drop the database and re-create it.<br/>'
            .'ERROR MESSAGE: %s <br/><br/>');

		define('ERR_DBTRYRENAME', 'DATABASE CREATION FAILURE!<br/> Unable to rename a table from database "%s".<br/> Be sure the database user has RENAME privileges for this specific database on all tables.');

		define('ERR_DBCREATE', 'The database "%s" does not exist.<br/>  Change the action to create in order to "Create New Database" to create the database.  Some hosting providers do not allow database creation except through their control panels. In this case, you will need to login to your hosting providers\' control panel and create the database manually.  Please contact your hosting provider for further details on how to create the database.');

		define('ERR_DBEMPTY', 'The database "%s" already exists and has "%s" tables.  When using the "Create New Database" action the database should not exist.  Select the action "Connect and Remove All Data" or "Connect and Backup Any Existing Data" to remove or backup the existing tables or choose a database name that does not already exist. Some hosting providers do not allow table removal or renaming from scripts.  In this case, you will need to login to your hosting providers\' control panel and remove or rename the tables manually.  Please contact your hosting provider for further details.  Always backup all your data before proceeding!');

		define('ERR_DBMANUAL', 'The database "%s" has "%s" tables. This does not look to be a valid WordPress database.  The base WordPress install has 12 tables.  Please validate that this database is indeed pre-populated with a valid WordPress database.  The "Manual SQL execution" mode requires that you have a valid WordPress database already installed.');

		define('ERR_TESTDB_VERSION_INFO', 'The current version detected was released prior to MySQL 5.5.3 which had a release date of April 8th, 2010.  WordPress 4.2 included support for utf8mb4 which is only supported in MySQL server 5.5.3+.  It is highly recommended to upgrade your version of MySQL server on this server to be more compatible with recent releases of WordPress and avoid issues with install errors.');

		define('ERR_TESTDB_VERSION_COMPAT', 'In order to avoid database incompatibility issues make sure the database versions between the build and installer servers are as close as possible. If the package was created on a newer database version than where it is being installed then you might run into issues.<br/><br/> It is best to make sure the server where the installer is running has the same or higher version number than where it was built.  If the major and minor version are the same or close for example [5.7 to 5.6], then the migration should work without issues.  A version pair of [5.7 to 5.1] is more likely to cause issues unless you have a very simple setup.  If the versions are too far apart work with your hosting provider to upgrade the MySQL engine on this server.<br/><br/>   <b>MariaDB:</b> If see a version of 10.N.N then the database distribution is a MariaDB flavor of MySQL.   While the distributions are very close there are some subtle differences.   Some operating systems will report the version such as "5.5.5-10.1.21-MariaDB" showing the correlation of both.  Please visit the online <a href="https://mariadb.com/kb/en/mariadb/mariadb-vs-mysql-compatibility/" target="_blank">MariaDB versus MySQL - Compatibility</a> page for more details.<br/><br/> Please note these messages are simply notices.  It is highly recommended that you continue with the install process and closely monitor the dup-installer-log.txt file along with the install report found on step 3 of the installer.  Be sure to look for any notices/warnings/errors in these locations to validate the install process did not detect any errors. If any issues are found please visit the FAQ pages and see the question <a href="https://snapcreek.com/duplicator/docs/faqs-tech/#faq-installer-260-q" target="_blank">What if I get database errors or general warnings on the install report?</a>.');
	}
}