<?php
//Prevent directly browsing to the file
if (function_exists('plugin_dir_url')) {
    define('DUPLICATOR_VERSION',        '0.5.19');
    define("DUPLICATOR_HOMEPAGE",       "http://lifeinthegrid.com/labs/duplicator");
    define("DUPLICATOR_GIVELINK",       "http://lifeinthegrid.com/partner");
    define("DUPLICATOR_HELPLINK",       "http://lifeinthegrid.com/duplicator-docs");
    define("DUPLICATOR_CERTIFIED",      "http://lifeinthegrid.com/duplicator-hosts");
    define('DUPLICATOR_PLUGIN_URL',     plugin_dir_url(__FILE__));
	define('DUPLICATOR_SITE_URL',		get_site_url());
	define('DUPLICATOR_LANG_SLUG',		'wpduplicator');
	
    

    /* Paths should ALWAYS read "/"
      uni: /home/path/file.txt
      win:  D:/home/path/file.txt
      SSDIR = SnapShot Directory */
    if (!defined('ABSPATH')) {
		define('ABSPATH', dirname(__FILE__));
    }
	
	//PATH CONSTANTS
	define("DUPLICATOR_SSDIR_NAME",     'wp-snapshots');
	define('DUPLICATOR_PLUGIN_PATH',    str_replace("\\", "/", plugin_dir_path(__FILE__)));
    define('DUPLICATOR_WPROOTPATH',     str_replace("\\", "/", ABSPATH));
    define("DUPLICATOR_SSDIR_PATH",     str_replace("\\", "/", DUPLICATOR_WPROOTPATH . DUPLICATOR_SSDIR_NAME));
	define("DUPLICATOR_SSDIR_PATH_TMP", DUPLICATOR_SSDIR_PATH . '/tmp');
	define("DUPLICATOR_SSDIR_URL",      DUPLICATOR_SITE_URL . "/" . DUPLICATOR_SSDIR_NAME);
    define("DUPLICATOR_INSTALL_PHP",    'installer.php');
	define("DUPLICATOR_INSTALL_BAK",    'installer-backup.php');
    define("DUPLICATOR_INSTALL_SQL",    'installer-data.sql');
    define("DUPLICATOR_INSTALL_LOG",    'installer-log.txt');
	
	//RESTRAINT CONSTANTS
    define("DUPLICATOR_PHP_MAX_MEMORY",  '5000M');
    define("DUPLICATOR_DB_MAX_TIME",     5000);
	define("DUPLICATOR_DB_EOF_MARKER",   'DUPLICATOR_MYSQLDUMP_EOF');
	define("DUPLICATOR_SCAN_SITE",    157286400);	//150MB
	define("DUPLICATOR_SCAN_WARNFILESIZE", 3145728);//3MB
	define("DUPLICATOR_SCAN_CACHESIZE", 524288);	//512K
	define("DUPLICATOR_SCAN_DBSIZE",  52428800);	//50MB
	define("DUPLICATOR_SCAN_DBROWS",  250000);
	define("DUPLICATOR_SCAN_TIMEOUT", 150);			//Seconds
	define("DUPLICATOR_SCAN_MIN_WP", "3.7.0");
	define("DUPLICATOR_SCAN_USELEGACY", true);
    $GLOBALS['DUPLICATOR_SERVER_LIST'] = array('Apache','LiteSpeed', 'Nginx', 'Lighttpd', 'IIS', 'WebServerX', 'uWSGI');
	$GLOBALS['DUPLICATOR_OPTS_DELETE'] = array('duplicator_ui_view_state', 'duplicator_package_active', 'duplicator_settings');
	
	/* Used to flush a response every N items. 
	 * Note: This value will cause the Zip file to double in size durning the creation process only*/
	define("DUPLICATOR_ZIP_FLUSH_TRIGGER", 1000);

} else {
    error_reporting(0);
    $port = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off") ? "https://" : "http://";
    $url = $port . $_SERVER["HTTP_HOST"];
    header("HTTP/1.1 404 Not Found", true, 404);
    header("Status: 404 Not Found");
    exit();
}
?>
