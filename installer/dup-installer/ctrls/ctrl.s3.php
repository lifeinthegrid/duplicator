<?php
defined("ABSPATH") or die("");
/** IDE HELPERS */
/* @var $GLOBALS['DUPX_AC'] DUPX_ArchiveConfig */

//-- START OF ACTION STEP 3: Update the database
require_once($GLOBALS['DUPX_INIT'].'/classes/config/class.archive.config.php');

/** JSON RESPONSE: Most sites have warnings turned off by default, but if they're turned on the warnings
  cause errors in the JSON data Here we hide the status so warning level is reset at it at the end */
$ajax3_start		 = DUPX_U::getMicrotime();
$ajax3_error_level	 = error_reporting();
error_reporting(E_ERROR);

//POST PARAMS
$_POST['blogname']		= isset($_POST['blogname']) ? DUPX_U::sanitize_text_field($_POST['blogname']) : 'No Blog Title Set';
$_POST['postguid']		= isset($_POST['postguid']) && $_POST['postguid'] == 1 ? 1 : 0;
$_POST['fullsearch']	= isset($_POST['fullsearch']) && $_POST['fullsearch'] == 1 ? 1 : 0;
$_POST['path_old']		= isset($_POST['path_old']) ? trim($_POST['path_old']) : null;
$_POST['path_new']		= isset($_POST['path_new']) ? trim($_POST['path_new']) : null;
$_POST['siteurl']		= isset($_POST['siteurl']) ? rtrim(trim($_POST['siteurl']), '/') : null;
$_POST['tables']		= isset($_POST['tables']) && is_array($_POST['tables']) ? array_map('stripcslashes', $_POST['tables']) : array();

if (isset($_POST['url_old'])) {
	$post_url_old = DUPX_U::sanitize_text_field($_POST['url_old']);
	$_POST['url_old'] = trim($post_url_old);
} else {
	$_POST['url_old'] = null;
}

if (isset($_POST['url_new'])) {
	$post_url_new = DUPX_U::sanitize_text_field($_POST['url_new']);
	$_POST['url_new'] = isset($_POST['url_new']) ? rtrim(trim($post_url_new), '/') : null;
} else {
	$_POST['url_new'] = null;
}


$_POST['ssl_admin']		= isset($_POST['ssl_admin']) ? true : false;
$_POST['cache_wp']		= isset($_POST['cache_wp']) ? true : false;
$_POST['cache_path']	= isset($_POST['cache_path']) ? true : false;
$_POST['exe_safe_mode']	= isset($_POST['exe_safe_mode']) ? $_POST['exe_safe_mode'] : 0;
$_POST['config_mode']	= (isset($_POST['config_mode'])) ? $_POST['config_mode'] : 'NEW';

//MYSQL CONNECTION
$dbh		 = DUPX_DB::connect($_POST['dbhost'], $_POST['dbuser'], html_entity_decode($_POST['dbpass']), $_POST['dbname'], $_POST['dbport']);
$dbConnError = (mysqli_connect_error()) ? 'Error: '.mysqli_connect_error() : 'Unable to Connect';

if (!$dbh) {
	$msg = "Unable to connect with the following parameters: <br/> <b>HOST:</b> {$_POST['dbhost']}<br/> <b>DATABASE:</b> {$_POST['dbname']}<br/>";
	$msg .= "<b>Connection Error:</b> ".htmlentities($dbConnError);
	DUPX_Log::error($msg);
}

$charset_server	 = @mysqli_character_set_name($dbh);
@mysqli_query($dbh, "SET wait_timeout = ".mysqli_real_escape_string($dbh, $GLOBALS['DB_MAX_TIME']));
DUPX_DB::setCharset($dbh, $_POST['dbcharset'], $_POST['dbcollate']);
$charset_client	 = @mysqli_character_set_name($dbh);

//LOGGING
$date = @date('h:i:s');
$log  = <<<LOG
\n\n
********************************************************************************
DUPLICATOR INSTALL-LOG
STEP-3 START @ {$date}
NOTICE: Do NOT post to public sites or forums
********************************************************************************
CHARSET SERVER:\t{$charset_server}
CHARSET CLIENT:\t{$charset_client}\n
LOG;
DUPX_Log::info($log);

$POST_LOG = $_POST;
unset($POST_LOG['tables']);
unset($POST_LOG['plugins']);
unset($POST_LOG['dbpass']);
ksort($POST_LOG);

//Detailed logging
$log = "--------------------------------------\n";
$log .= "POST DATA\n";
$log .= "--------------------------------------\n";
$log .= print_r($POST_LOG, true);
$log .= "--------------------------------------\n";
$log .= "TABLES TO SCAN\n";
$log .= "--------------------------------------\n";
$log .= (isset($_POST['tables']) && count($_POST['tables'] > 0)) ? print_r($_POST['tables'], true) : 'No tables selected to update';
$log .= "--------------------------------------\n";
$log .= "KEEP PLUGINS ACTIVE\n";
$log .= "--------------------------------------\n";
$log .= (isset($_POST['plugins']) && count($_POST['plugins'] > 0)) ? print_r($_POST['plugins'], true) : 'No plugins selected for activation';
DUPX_Log::info($log, 2);


//===============================================
//UPDATE ENGINE
//===============================================
$log = "--------------------------------------\n";
$log .= "SERIALIZER ENGINE\n";
$log .= "[*] scan every column\n";
$log .= "[~] scan only text columns\n";
$log .= "[^] no searchable columns\n";
$log .= "--------------------------------------";
DUPX_Log::info($log);

//CUSTOM REPLACE -> REPLACE LIST
if (isset($_POST['search'])) {
	$search_count = count($_POST['search']);
	if ($search_count > 0) {
		for ($search_index = 0; $search_index < $search_count; $search_index++) {
			$search_for		 = $_POST['search'][$search_index];
			$replace_with	 = $_POST['replace'][$search_index];

			if (trim($search_for) != '') {
				$search_json	 = json_encode($search_for);
				$replace_json	 = json_encode($replace_with);

				array_push($GLOBALS['REPLACE_LIST'], array('search' => $search_for, 'replace' => $replace_with));
				array_push($GLOBALS['REPLACE_LIST'], array('search' => urlencode($search_for), 'replace' => urlencode($replace_with)));
				array_push($GLOBALS['REPLACE_LIST'], array('search' => $search_json, 'replace' => $replace_json));
			}
		}
	}
}

//GENERAL -> REPLACE LIST
$url_old_json	 = str_replace('"', "", json_encode($_POST['url_old']));
$url_new_json	 = str_replace('"', "", json_encode($_POST['url_new']));
$path_old_json	 = str_replace('"', "", json_encode($_POST['path_old']));
$path_new_json	 = str_replace('"', "", json_encode($_POST['path_new']));


//DIRS PATHS
array_push($GLOBALS['REPLACE_LIST'],
	array('search' => $_POST['path_old'],			 'replace' => $_POST['path_new']),
	array('search' => $path_old_json,				 'replace' => $path_new_json),
	array('search' => urlencode($_POST['path_old']), 'replace' => urlencode($_POST['path_new'])),
	array('search' => rtrim(DUPX_U::unsetSafePath($_POST['path_old']), '\\'), 'replace' => rtrim($_POST['path_new'], '/'))
);

//SEARCH WITH NO PROTOCAL: RAW "//"
$url_old_raw = str_ireplace(array('http://', 'https://'), '//', $_POST['url_old']);
$url_new_raw = str_ireplace(array('http://', 'https://'), '//', $_POST['url_new']);
$url_old_raw_json = str_replace('"',  "", json_encode($url_old_raw));
$url_new_raw_json = str_replace('"',  "", json_encode($url_new_raw));

array_push($GLOBALS['REPLACE_LIST'],
    //RAW
    array('search' => $url_old_raw,			 	'replace' => $url_new_raw),
    array('search' => $url_old_raw_json,		'replace' => $url_new_raw_json),
    array('search' => urlencode($url_old_raw), 	'replace' => urlencode($url_new_raw))
);

//SEARCH HTTP(S) EXPLICIT REQUEST
//Because the raw replace above has already changed all urls just fix https/http issue
//if the user has explicitly asked other-wise word boundary issues will occur as:
//Old site: http://mydomain.com/somename/
//New site: http://mydomain.com/somename-dup/
//Result: http://mydomain.com/somename-dup-dup/
//At first glance $url_old_http looks incorrect but it should reference $_POST['url_new']
//Because this code is changing a value that has already been changed in an earlier pass
if (stristr($_POST['url_old'], 'http:') && stristr($_POST['url_new'], 'https:') ) {
    $url_old_http = str_ireplace('https:', 'http:', $_POST['url_new']);
    $url_new_http = $_POST['url_new'];
    $url_old_http_json = str_replace('"',  "", json_encode($url_old_http));
    $url_new_http_json = str_replace('"',  "", json_encode($url_new_http));

} elseif(stristr($_POST['url_old'], 'https:') && stristr($_POST['url_new'], 'http:')) {
    $url_old_http = str_ireplace('http:', 'https:', $_POST['url_new']);
    $url_new_http = $_POST['url_new'];
    $url_old_http_json = str_replace('"',  "", json_encode($url_old_http));
    $url_new_http_json = str_replace('"',  "", json_encode($url_new_http));
}

if(isset($url_old_http)){
    array_push($GLOBALS['REPLACE_LIST'],
        array('search' => $url_old_http,			 	 'replace' => $url_new_http),
        array('search' => $url_old_http_json,			 'replace' => $url_new_http_json),
        array('search' => urlencode($url_old_http),  	 'replace' => urlencode($url_new_http))
    );
}

/*=============================================================
 * REMOVE TRAILING SLASH LOGIC:
 * In many cases the trailing slash of a url or path causes issues in some
 * enviroments; so by default all trailing slashes have been removed.
 * This has worked well for several years.  However, there are some edge
 * cases where removing the trailing slash will cause issues such that
 * the following will happen:
	http://www.mysite.com  >>>>  http://C:/xampp/apache/htdocs/.mysite.com
 * So the edge case array is a place older for these types of issues.
*/
$GLOBALS['REPLACE_LIST_EDGE_CASES'] = array('/www/');
$_dupx_tmp_replace_list = $GLOBALS['REPLACE_LIST'];
foreach ($_dupx_tmp_replace_list as $key => $val) {
	foreach ($GLOBALS['REPLACE_LIST_EDGE_CASES'] as $skip_val) {
		$search  = $GLOBALS['REPLACE_LIST'][$key]['search'];
		$replace = $GLOBALS['REPLACE_LIST'][$key]['replace'];
		if (strcmp($skip_val, $search) !== 0) {
			$GLOBALS['REPLACE_LIST'][$key]['search']  = rtrim($search, '\/');
			$GLOBALS['REPLACE_LIST'][$key]['replace'] = rtrim($replace, '\/');
		} else {
			DUPX_Log::info("NOTICE: Edge case for path trimming detected on {$skip_val}");
		}
	}
}

DUPX_Log::info("Final replace list: \n". print_r($GLOBALS['REPLACE_LIST'], true), 2);
$report = DUPX_UpdateEngine::load($dbh, $GLOBALS['REPLACE_LIST'], $_POST['tables'], $_POST['fullsearch']);

//BUILD JSON RESPONSE
$JSON						 = array();
$JSON['step1']				 = json_decode(urldecode($_POST['json']));
$JSON['step3']				 = $report;
$JSON['step3']['warn_all']	 = 0;
$JSON['step3']['warnlist']	 = array();

DUPX_UpdateEngine::logStats($report);
DUPX_UpdateEngine::logErrors($report);

//===============================================
//CREATE NEW ADMIN USER
//===============================================
if (strlen($_POST['wp_username']) >= 4 && strlen($_POST['wp_password']) >= 6) {
	
	$post_wp_username = $_POST['wp_username'];
    $post_wp_password = $_POST['wp_password'];
    $post_wp_mail     = $_POST['wp_mail'];
    $post_wp_nickname = $_POST['wp_nickname'];
    if (empty($post_wp_nickname)) {
        $post_wp_nickname = $post_wp_username;
    }
    $post_wp_first_name = $_POST['wp_first_name'];
    $post_wp_last_name  = $_POST['wp_last_name'];

    $post_wp_username = mysqli_real_escape_string($dbh, $post_wp_username);
	$post_wp_password = mysqli_real_escape_string($dbh, $post_wp_password);

    $post_wp_mail = mysqli_real_escape_string($dbh, $post_wp_mail);
	$post_wp_nickname = mysqli_real_escape_string($dbh, $post_wp_nickname);
    $post_wp_first_name = mysqli_real_escape_string($dbh, $post_wp_first_name);
	$post_wp_last_name = mysqli_real_escape_string($dbh, $post_wp_last_name);

	$newuser_check	 = mysqli_query($dbh, "SELECT COUNT(*) AS count FROM `".mysqli_real_escape_string($dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."users` WHERE user_login = '{$post_wp_username}' ");
	$newuser_row	 = mysqli_fetch_row($newuser_check);
	$newuser_count	 = is_null($newuser_row) ? 0 : $newuser_row[0];

	if ($newuser_count == 0) {

		$newuser_datetime	 = @date("Y-m-d H:i:s");
		$newuser_security	 = mysqli_real_escape_string($dbh, 'a:1:{s:13:"administrator";s:1:"1";}');

		$newuser1 = @mysqli_query($dbh,
				"INSERT INTO `{$GLOBALS['DUPX_AC']->wp_tableprefix}users`
				(`user_login`, `user_pass`, `user_nicename`, `user_email`, `user_registered`, `user_activation_key`, `user_status`, `display_name`)
				VALUES ('{$post_wp_username}', MD5('{$post_wp_password}'), '{$post_wp_username}', '{$post_wp_mail}', '{$newuser_datetime}', '', '0', '{$post_wp_username}')");

		$newuser1_insert_id = mysqli_insert_id($dbh);

		$newuser2 = @mysqli_query($dbh,
				"INSERT INTO `{$GLOBALS['DUPX_AC']->wp_tableprefix}usermeta`
				(`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser1_insert_id}', '".mysqli_real_escape_string($dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."capabilities', '{$newuser_security}')");

		$newuser3 = @mysqli_query($dbh,
				"INSERT INTO `{$GLOBALS['DUPX_AC']->wp_tableprefix}usermeta`
				(`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser1_insert_id}', '{$GLOBALS['DUPX_AC']->wp_tableprefix}user_level', '10')");

		//Misc Meta-Data Settings:
		@mysqli_query($dbh, "INSERT INTO `".mysqli_real_escape_string($dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."usermeta` (`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser1_insert_id}', 'rich_editing', 'true')");
		@mysqli_query($dbh, "INSERT INTO `".mysqli_real_escape_string($dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."usermeta` (`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser1_insert_id}', 'admin_color',  'fresh')");
        @mysqli_query($dbh, "INSERT INTO `".mysqli_real_escape_string($dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."usermeta` (`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser1_insert_id}', 'nickname', '{$post_wp_nickname}')");
        @mysqli_query($dbh, "INSERT INTO `".mysqli_real_escape_string($dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."usermeta` (`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser1_insert_id}', 'first_name', '{$post_wp_first_name}')");
        @mysqli_query($dbh, "INSERT INTO `".mysqli_real_escape_string($dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."usermeta` (`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser1_insert_id}', 'last_name', '{$post_wp_last_name}')");
	
		DUPX_Log::info("\nNEW WP-ADMIN USER:");
		if ($newuser1 && $newuser_test2 && $newuser3) {
			DUPX_Log::info("- New username '{$post_wp_username}' was created successfully allong with MU usermeta.");
		} elseif ($newuser1) {
			DUPX_Log::info("- New username '{$post_wp_username}' was created successfully.");
		} else {
			$newuser_warnmsg = "- Failed to create the user '{$post_wp_username}' \n ";
			$JSON['step3']['warnlist'][] = $newuser_warnmsg;
			DUPX_Log::info($newuser_warnmsg);
		}
	} else {
		$newuser_warnmsg = "\nNEW WP-ADMIN USER:\n - Username '{$post_wp_username}' already exists in the database.  Unable to create new account.\n";
		$JSON['step3']['warnlist'][] = $newuser_warnmsg;
		DUPX_Log::info($newuser_warnmsg);
	}
}

//===============================================
//CONFIGURATION FILE UPDATES
//===============================================
DUPX_Log::info("\n====================================");
DUPX_Log::info('CONFIGURATION FILE UPDATES:');
DUPX_Log::info("====================================\n");


// UPDATE WP-CONFIG FILE
$patterns = array("/('|\")WP_HOME.*?\)\s*;/",
	"/('|\")WP_SITEURL.*?\)\s*;/");

$replace = array("'WP_HOME', '{$_POST['url_new']}');",
	"'WP_SITEURL', '{$_POST['url_new']}');");

//@todo: integrate all logic into DUPX_WPConfig::updateVars
DUPX_WPConfig::updateVars($patterns, $replace);

$root_path = $GLOBALS['DUPX_ROOT'];
$wpconfig_ark_path	= "{$root_path}/dup-wp-config-arc__{$GLOBALS['DUPX_AC']->package_hash}.txt";
$wpconfig_ark_contents	= @file_get_contents($wpconfig_ark_path, true);
$wpconfig_ark_contents	= preg_replace($patterns, $replace, $wpconfig_ark_contents);

if (!is_writable($wpconfig_ark_path)) {
	$err_log = "\nWARNING: Unable to update file permissions and write to {$wpconfig_ark_path}.  ";
	$err_log .= "Check that the wp-config.php is in the archive.zip and check with your host or administrator to enable PHP to write to the wp-config.php file.  ";
	$err_log .= "If performing a 'Manual Extraction' please be sure to select the 'Manual Archive Extraction' option on step 1 under options.";
	chmod($wpconfig_ark_path, 0644) ? DUPX_Log::info("File Permission Update: {$wpconfig_ark_path} set to 0644") : DUPX_Log::error("{$err_log}");
}
$wpconfig_ark_contents = preg_replace($patterns, $replace, $wpconfig_ark_contents);
file_put_contents($wpconfig_ark_path, $wpconfig_ark_contents);
DUPX_Log::info("UPDATED WP-CONFIG ARK FILE:\n - '{$wpconfig_ark_path}'");

switch ($_POST['config_mode']) {
	case 'NEW':
		DUPX_ServerConfig::createNewConfigs();
		break;
	case 'RESTORE':
		DUPX_ServerConfig::renameOrigConfigs();
		DUPX_Log::info("\nWARNING: Retaining the original .htaccess or web.config files may cause");
		DUPX_Log::info("issues with the initial setup of your site.  If you run into issues with the install");
		DUPX_Log::info("process choose 'Create New' for the 'Config Files' options");
		break;
	case 'IGNORE':
		DUPX_Log::info("\nWARNING: Choosing the option to ignore the .htaccess, web.config and .user.ini files");
		DUPX_Log::info("can lead to install issues.  The 'Ignore All' option is designed for advanced users.");
		break;
}


//===============================================
//GENERAL UPDATES & CLEANUP
//===============================================
//DUPX_Log::info("\n====================================");
//DUPX_Log::info('GENERAL UPDATES & CLEANUP:');
//DUPX_Log::info("====================================\n");

$blog_name   = mysqli_real_escape_string($dbh, $_POST['blogname']);
$plugin_list = (isset($_POST['plugins'])) ? $_POST['plugins'] : array();

if (!in_array('duplicator/duplicator.php', $plugin_list)) {
    $plugin_list[] = 'duplicator/duplicator.php';
}
$serial_plugin_list	 = @serialize($plugin_list);
$serial_plugin_list	 = mysqli_real_escape_string($dbh, $serial_plugin_list);

/** FINAL UPDATES: Must happen after the global replace to prevent double pathing
  http://xyz.com/abc01 will become http://xyz.com/abc0101  with trailing data */
mysqli_query($dbh, "UPDATE `".mysqli_real_escape_string($dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."options` SET option_value = '{$blog_name}' WHERE option_name = 'blogname' ");
mysqli_query($dbh, "UPDATE `".mysqli_real_escape_string($dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."options` SET option_value = '{$serial_plugin_list}'  WHERE option_name = 'active_plugins' ");
mysqli_query($dbh, "UPDATE `".mysqli_real_escape_string($dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."options` SET option_value = '".mysqli_real_escape_string($dbh, $_POST['url_new'])."'  WHERE option_name = 'home' ");
mysqli_query($dbh, "UPDATE `".mysqli_real_escape_string($dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."options` SET option_value = '".mysqli_real_escape_string($dbh, $_POST['siteurl'])."'  WHERE option_name = 'siteurl' ");
mysqli_query($dbh, "INSERT INTO `".mysqli_real_escape_string($dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."options` (option_value, option_name) VALUES('".mysqli_real_escape_string($dbh, $_POST['exe_safe_mode'])."','duplicator_exe_safe_mode')");
//Reset the postguid data
if ($_POST['postguid']) {
	mysqli_query($dbh, "UPDATE `".mysqli_real_escape_string($dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."posts` SET guid = REPLACE(guid, '".mysqli_real_escape_string($dbh, $_POST['url_new'])."', '".mysqli_real_escape_string($dbh, $_POST['url_old'])."')");
	$update_guid = @mysqli_affected_rows($dbh) or 0;
	DUPX_Log::info("Reverted '{$update_guid}' post guid columns back to '{$_POST['url_old']}'");
}

//===============================================
//NOTICES TESTS
//===============================================
DUPX_Log::info("\n====================================");
DUPX_Log::info("NOTICES");
DUPX_Log::info("====================================\n");
$config_vars	= array('WPCACHEHOME', 'COOKIE_DOMAIN', 'WP_SITEURL', 'WP_HOME', 'WP_TEMP_DIR');
$config_found	= DUPX_U::getListValues($config_vars, $wpconfig_ark_contents);

//Files
if (! empty($config_found)) {
	$msg   = "WP-CONFIG NOTICE: The wp-config.php has following values set [".implode(", ", $config_found)."].  \n";
	$msg  .= "Please validate these values are correct by opening the file and checking the values.\n";
	$msg  .= "See the codex link for more details: https://codex.wordpress.org/Editing_wp-config.php";
	$JSON['step3']['warnlist'][] = $msg;
	DUPX_Log::info($msg);
}

//Database
$result = @mysqli_query($dbh, "SELECT option_value FROM `{$GLOBALS['DUPX_AC']->wp_tableprefix}options` WHERE option_name IN ('upload_url_path','upload_path')");
if ($result) {
	while ($row = mysqli_fetch_row($result)) {
		if (strlen($row[0])) {
			$msg  = "MEDIA SETTINGS NOTICE: The table '{$GLOBALS['DUPX_AC']->wp_tableprefix}options' has at least one the following values ['upload_url_path','upload_path'] \n";
			$msg .=	"set please validate settings. These settings can be changed in the wp-admin by going to /wp-admin/options.php'";
			$JSON['step3']['warnlist'][] = $msg;
			DUPX_Log::info($msg);
			break;
		}
	}
}

if (empty($JSON['step3']['warnlist'])) {
	DUPX_Log::info("No General Notices Found\n");
}

$JSON['step3']['warn_all'] = empty($JSON['step3']['warnlist']) ? 0 : count($JSON['step3']['warnlist']);

mysqli_close($dbh);


//-- Finally, back up the old wp-config and rename the new one
$wpconfig_path	= "{$GLOBALS['DUPX_ROOT']}/wp-config.php";
if(copy($wpconfig_ark_path, $wpconfig_path) === false) {
	DUPX_Log::error("ERROR: Unable to copy '{$wpconfig_ark_path}' to '{$wpconfig_path}'.  "
	. "Check server permissions for more details see FAQ: https://snapcreek.com/duplicator/docs/faqs-tech/#faq-trouble-055-q");
}

//Cleanup any tmp files a developer may have forgotten about
//Lets be proactive for the developer just in case
$wpconfig_path_bak	= "{$GLOBALS['DUPX_ROOT']}/wp-config.bak";
$wpconfig_path_old	= "{$GLOBALS['DUPX_ROOT']}/wp-config.old";
$wpconfig_path_org	= "{$GLOBALS['DUPX_ROOT']}/wp-config.org";
$wpconfig_path_orig	= "{$GLOBALS['DUPX_ROOT']}/wp-config.orig";
$wpconfig_safe_check = array($wpconfig_path_bak, $wpconfig_path_old, $wpconfig_path_org, $wpconfig_path_orig);

foreach ($wpconfig_safe_check as $file) {
	if(file_exists($file)) {
		$tmp_newfile = $file . uniqid('_');
		if(rename($file, $tmp_newfile) === false) {
			DUPX_Log::info("WARNING: Unable to rename '{$file}' to '{$tmp_newfile}'");
		}
	}
}

$ajax3_sum = DUPX_U::elapsedTime(DUPX_U::getMicrotime(), $ajax3_start);
DUPX_Log::info("\nSTEP-3 COMPLETE @ ".@date('h:i:s')." - RUNTIME: {$ajax3_sum} \n\n");

$JSON['step3']['pass'] = 1;
error_reporting($ajax3_error_level);
die(json_encode($JSON));