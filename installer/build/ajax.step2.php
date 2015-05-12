<?php
// Exit if accessed directly
if (! defined('DUPLICATOR_INIT')) {
	$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	$_baseURL =  "http://" . $_baseURL;
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $_baseURL");
	exit; 
}

/* JSON RESPONSE: Most sites have warnings turned off by default, but if they're turned on the warnings
cause errors in the JSON data Here we hide the status so warning level is reset at it at the end*/
$ajax2_error_level = error_reporting();
error_reporting(E_ERROR);

//====================================================================================================
//DATABASE UPDATES
//====================================================================================================

$ajax2_start = DupUtil::get_microtime();

//MYSQL CONNECTION
$dbh = DupUtil::mysqli_connect($_POST['dbhost'], $_POST['dbuser'], html_entity_decode($_POST['dbpass']), $_POST['dbname'], $_POST['dbport']);
$charset_server = @mysqli_character_set_name($dbh);
@mysqli_query($dbh, "SET wait_timeout = {$GLOBALS['DB_MAX_TIME']}");
DupUtil::mysql_set_charset($dbh, $_POST['dbcharset'], $_POST['dbcollate']);

//POST PARAMS
$_POST['blogname'] = mysqli_real_escape_string($dbh, $_POST['blogname']);
$_POST['postguid'] = isset($_POST['postguid']) && $_POST['postguid'] == 1 ? 1 : 0;
$_POST['fullsearch'] = isset($_POST['fullsearch']) && $_POST['fullsearch'] == 1 ? 1 : 0;
$_POST['path_old'] = isset($_POST['path_old']) ? trim($_POST['path_old']) : null;
$_POST['path_new'] = isset($_POST['path_new']) ? trim($_POST['path_new']) : null;
$_POST['siteurl'] = isset($_POST['siteurl']) ? rtrim(trim($_POST['siteurl']), '/') : null;
$_POST['tables'] = isset($_POST['tables']) && is_array($_POST['tables']) ? array_map('stripcslashes', $_POST['tables']) : array();
$_POST['url_old'] = isset($_POST['url_old']) ? trim($_POST['url_old']) : null;
$_POST['url_new'] = isset($_POST['url_new']) ? rtrim(trim($_POST['url_new']), '/') : null;

//LOGGING
$POST_LOG = $_POST;
unset($POST_LOG['tables']);
unset($POST_LOG['plugins']);
unset($POST_LOG['dbpass']);
ksort($POST_LOG);

$date = @date('h:i:s');
$charset_client = @mysqli_character_set_name($dbh);

$log = <<<LOG
\n\n
********************************************************************************
DUPLICATOR INSTALL-LOG
STEP2 START @ {$date}
NOTICE: Do not post to public sites or forums
********************************************************************************
CHARSET SERVER:\t{$charset_server}
CHARSET CLIENT:\t {$charset_client} \n
LOG;
DUPX_Log::Info($log);

//Detailed logging
$log  = "--------------------------------------\n";
$log .= "POST DATA\n";
$log .= "--------------------------------------\n";
$log .= print_r($POST_LOG, true);		
$log .= "--------------------------------------\n";
$log .= "SCANNED TABLES\n";
$log .= "--------------------------------------\n";
$log .= (isset($_POST['tables']) && count($_POST['tables'] > 0)) 
		? print_r($_POST['tables'], true) 
		: 'No tables selected to update';
$log .= "--------------------------------------\n";
$log .= "KEEP PLUGINS ACTIVE\n";
$log .= "--------------------------------------\n";
$log .= (isset($_POST['plugins']) && count($_POST['plugins'] > 0)) 
		? print_r($_POST['plugins'], true) 
		: 'No plugins selected for activation';
DUPX_Log::Info($log, 2);

//UPDATE SETTINGS
$serial_plugin_list = (isset($_POST['plugins']) && count($_POST['plugins'] > 0)) ? @serialize($_POST['plugins']) : '';
mysqli_query($dbh, "UPDATE `{$GLOBALS['FW_TABLEPREFIX']}options` SET option_value = '{$_POST['blogname']}' WHERE option_name = 'blogname' ");
mysqli_query($dbh, "UPDATE `{$GLOBALS['FW_TABLEPREFIX']}options` SET option_value = '{$serial_plugin_list}'  WHERE option_name = 'active_plugins' ");

$log  = "--------------------------------------\n";
$log .= "SERIALIZER ENGINE\n";
$log .= "[*] scan every column\n";
$log .= "[~] scan only text columns\n";
$log .= "[^] no searchable columns\n";
$log .= "--------------------------------------";
DUPX_Log::Info($log);

$url_old_json = str_replace('"', "", json_encode($_POST['url_old']));
$url_new_json = str_replace('"', "", json_encode($_POST['url_new']));
$path_old_json = str_replace('"', "", json_encode($_POST['path_old']));
$path_new_json = str_replace('"', "", json_encode($_POST['path_new']));

array_push($GLOBALS['REPLACE_LIST'], 
		array('search' => $_POST['url_old'],  'replace' => $_POST['url_new']), 
		array('search' => $url_old_json,	  'replace' => $url_new_json), 
		array('search' => $_POST['path_old'], 'replace' => $_POST['path_new']), 
		array('search' => $path_old_json,	  'replace' => $path_new_json), 		
		array('search' => rtrim(DupUtil::unset_safe_path($_POST['path_old']), '\\'), 'replace' => rtrim($_POST['path_new'], '/'))
);

@mysqli_autocommit($dbh, false);
$report = DUPX_Serializer::load($dbh, $GLOBALS['REPLACE_LIST'], $_POST['tables'], $GLOBALS['TABLES_SKIP_COLS'], $_POST['fullsearch']);
@mysqli_commit($dbh);
@mysqli_autocommit($dbh, true);


//BUILD JSON RESPONSE
$JSON = array();
$JSON['step1'] = json_decode(urldecode($_POST['json']));
$JSON['step2'] = $report;
$JSON['step2']['warn_all'] = 0;
$JSON['step2']['warnlist'] = array();

DUPX_Serializer::log_stats($report);
DUPX_Serializer::log_errors($report);

//Reset the postguid data
if ($_POST['postguid']) {
	mysqli_query($dbh, "UPDATE `{$GLOBALS['FW_TABLEPREFIX']}posts` SET guid = REPLACE(guid, '{$_POST['url_new']}', '{$_POST['url_old']}')");
	$update_guid = @mysqli_affected_rows($dbh) or 0;
	DUPX_Log::Info("Reverted '{$update_guid}' post guid columns back to '{$_POST['url_old']}'");
}

/* FINAL UPDATES: Must happen after the global replace to prevent double pathing
  http://xyz.com/abc01 will become http://xyz.com/abc0101  with trailing data */
mysqli_query($dbh, "UPDATE `{$GLOBALS['FW_TABLEPREFIX']}options` SET option_value = '{$_POST['url_new']}'  WHERE option_name = 'home' ");
mysqli_query($dbh, "UPDATE `{$GLOBALS['FW_TABLEPREFIX']}options` SET option_value = '{$_POST['siteurl']}'  WHERE option_name = 'siteurl' ");


//====================================================================================================
//FINAL CLEANUP
//====================================================================================================
DUPX_Log::Info("\n********************************************************************************");
DUPX_Log::Info('START FINAL CLEANUP: ' . @date('h:i:s'));
DUPX_Log::Info("********************************************************************************");

/*CREATE NEW USER LOGIC */
if (strlen($_POST['wp_username']) >= 4 && strlen($_POST['wp_password']) >= 6) {
	
	$newuser_check = mysqli_query($dbh, "SELECT COUNT(*) AS count FROM `{$GLOBALS['FW_TABLEPREFIX']}users` WHERE user_login = '{$_POST['wp_username']}' ");
	$newuser_row   = mysqli_fetch_row($newuser_check);
    $newuser_count = is_null($newuser_row) ? 0 : $newuser_row[0];
	
	if ($newuser_count == 0) {
	
		$newuser_datetime =	@date("Y-m-d H:i:s");
		$newuser_security = mysqli_real_escape_string($dbh, 'a:1:{s:13:"administrator";s:1:"1";}');

		$newuser_test1 = @mysqli_query($dbh, "INSERT INTO `{$GLOBALS['FW_TABLEPREFIX']}users` 
			(`user_login`, `user_pass`, `user_nicename`, `user_email`, `user_registered`, `user_activation_key`, `user_status`, `display_name`) 
			VALUES ('{$_POST['wp_username']}', MD5('{$_POST['wp_password']}'), '{$_POST['wp_username']}', '', '{$newuser_datetime}', '', '0', '{$_POST['wp_username']}')");

		$newuser_insert_id = mysqli_insert_id($dbh);

		$newuser_test2 = @mysqli_query($dbh, "INSERT INTO `{$GLOBALS['FW_TABLEPREFIX']}usermeta` 
				(`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser_insert_id}', '{$GLOBALS['FW_TABLEPREFIX']}capabilities', '{$newuser_security}')");

		$newuser_test3 = @mysqli_query($dbh, "INSERT INTO `{$GLOBALS['FW_TABLEPREFIX']}usermeta` 
				(`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser_insert_id}', '{$GLOBALS['FW_TABLEPREFIX']}user_level', '10')");
				
		//Misc Meta-Data Settings:
		@mysqli_query($dbh, "INSERT INTO `{$GLOBALS['FW_TABLEPREFIX']}usermeta` (`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser_insert_id}', 'rich_editing', 'true')");
		@mysqli_query($dbh, "INSERT INTO `{$GLOBALS['FW_TABLEPREFIX']}usermeta` (`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser_insert_id}', 'admin_color',  'fresh')");
		@mysqli_query($dbh, "INSERT INTO `{$GLOBALS['FW_TABLEPREFIX']}usermeta` (`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser_insert_id}', 'nickname', '{$_POST['wp_username']}')");

		if ($newuser_test1 && $newuser_test2 && $newuser_test3) {
			DUPX_Log::Info("NEW WP-ADMIN USER: New username '{$_POST['wp_username']}' was created successfully \n ");
		} else {
			$newuser_warnmsg = "NEW WP-ADMIN USER: Failed to create the user '{$_POST['wp_username']}' \n ";
			$JSON['step2']['warnlist'][] = $newuser_warnmsg;
			DUPX_Log::Info($newuser_warnmsg);
		}			
	} 
	else {
		$newuser_warnmsg = "NEW WP-ADMIN USER: Username '{$_POST['wp_username']}' already exists in the database.  Unable to create new account \n";
		$JSON['step2']['warnlist'][] = $newuser_warnmsg;
		DUPX_Log::Info($newuser_warnmsg);
	}
}

/*MU Updates*/
$mu_newDomain = parse_url($_POST['url_new']);
$mu_oldDomain = parse_url($_POST['url_old']);
$mu_newDomainHost = $mu_newDomain['host'];
$mu_oldDomainHost = $mu_oldDomain['host'];
$mu_updates = @mysqli_query($dbh, "UPDATE `{$GLOBALS['FW_TABLEPREFIX']}blogs` SET domain = '{$mu_newDomainHost}' WHERE domain = '{$mu_oldDomainHost}'");
if ($mu_updates) {
	DUPX_Log::Info("Update MU table blogs: domain {$mu_newDomainHost} ");
} else {
	DUPX_Log::Info("UPDATE `{$GLOBALS['FW_TABLEPREFIX']}blogs` SET domain = '{$mu_newDomainHost}' WHERE domain = '{$mu_oldDomainHost}'");
}


/*UPDATE WP-CONFIG FILE */
$patterns = array("/'WP_HOME',\s*'.*?'/", 
				  "/'WP_SITEURL',\s*'.*?'/",
				  "/'DOMAIN_CURRENT_SITE',\s*'.*?'/");

$replace = array("'WP_HOME', "    . '\''		  . $_POST['url_new'] . '\'',
				 "'WP_SITEURL', " . '\''		  . $_POST['url_new'] . '\'',
				 "'DOMAIN_CURRENT_SITE', " . '\'' . $mu_newDomainHost     . '\'');

$config_file = @file_get_contents('wp-config.php', true);
$config_file = preg_replace($patterns, $replace, $config_file);
file_put_contents('wp-config.php', $config_file);


//Create Snapshots directory
if (!file_exists(DUPLICATOR_SSDIR_NAME)) {
	mkdir(DUPLICATOR_SSDIR_NAME, 0755);
}
$fp = fopen(DUPLICATOR_SSDIR_NAME . '/index.php', 'w');
fclose($fp);


//===============================
//WARNING TESTS
//===============================
DUPX_Log::Info("\n--------------------------------------");
DUPX_Log::Info("WARNINGS");
DUPX_Log::Info("--------------------------------------");
$config_vars = array('WP_CONTENT_DIR', 'WP_CONTENT_URL', 'WPCACHEHOME', 'COOKIE_DOMAIN', 'WP_SITEURL', 'WP_HOME', 'WP_TEMP_DIR');
$config_found = DupUtil::string_has_value($config_vars, $config_file);

//Files
if ($config_found) {
	$msg = 'WP-CONFIG WARNING: The wp-config.php has one or more of these values "' . implode(", ", $config_vars) . '" which may cause issues please validate these values by opening the file.';
	$JSON['step2']['warnlist'][] = $msg;
	DUPX_Log::Info($msg);
}

//Database
$result = @mysqli_query($dbh, "SELECT option_value FROM `{$GLOBALS['FW_TABLEPREFIX']}options` WHERE option_name IN ('upload_url_path','upload_path')");
if ($result) {
	while ($row = mysqli_fetch_row($result)) {
		if (strlen($row[0])) {
			$msg = "MEDIA SETTINGS WARNING: The table '{$GLOBALS['FW_TABLEPREFIX']}options' has at least one the following values ['upload_url_path','upload_path'] set please validate settings. These settings can be changed in the wp-admin by going to Settings->Media area see 'Uploading Files'";
			$JSON['step2']['warnlist'][] = $msg;
			DUPX_Log::Info($msg);
			break;
		}
	}
}

if (empty($JSON['step2']['warnlist'])) {
	DUPX_Log::Info("No Warnings Found\n");
}

$JSON['step2']['warn_all'] = empty($JSON['step2']['warnlist']) ? 0 : count($JSON['step2']['warnlist']);

mysqli_close($dbh);
@unlink('database.sql');

//CONFIG Setup
DUPX_Config::Setup();

$ajax2_end = DupUtil::get_microtime();
$ajax2_sum = DupUtil::elapsed_time($ajax2_end, $ajax2_start);
DUPX_Log::Info("********************************************************************************");
DUPX_Log::Info('STEP 2 COMPLETE @ ' . @date('h:i:s') . " - TOTAL RUNTIME: {$ajax2_sum}");
DUPX_Log::Info("********************************************************************************");

$JSON['step2']['pass'] = 1;
error_reporting($ajax2_error_level);
die(json_encode($JSON));
?>