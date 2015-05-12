<?php
// Exit if accessed directly
if (! defined('DUPLICATOR_INIT')) {
	$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	$_baseURL =  "http://" . $_baseURL;
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $_baseURL");
	exit; 
}

//POST PARAMS
$_POST['dbaction']		= isset($_POST['dbaction']) ? $_POST['dbaction'] : 'create';
$_POST['dbnbsp']		= (isset($_POST['dbnbsp']) && $_POST['dbnbsp'] == '1') ? true : false;
$_POST['ssl_admin']		= (isset($_POST['ssl_admin'])) ? true : false;
$_POST['ssl_login']		= (isset($_POST['ssl_login'])) ? true : false;
$_POST['cache_wp']		= (isset($_POST['cache_wp'])) ? true : false;
$_POST['cache_path']	= (isset($_POST['cache_path'])) ? true : false;
$_POST['package_name']	= isset($_POST['package_name']) ? $_POST['package_name'] : null;
$_POST['zip_manual']	= (isset($_POST['zip_manual']) && $_POST['zip_manual'] == '1') ? true : false;

//LOGGING
$POST_LOG = $_POST;
unset($POST_LOG['dbpass']);
ksort($POST_LOG);

//PAGE VARS
$root_path		= DupUtil::set_safe_path($GLOBALS['CURRENT_ROOT_PATH']);
$package_path	= "{$root_path}/{$_POST['package_name']}";
$package_size	= @filesize($package_path);
$ajax1_start	= DupUtil::get_microtime();
$zip_support	= class_exists('ZipArchive') ? 'Enabled' : 'Not Enabled';
$JSON = array();
$JSON['pass'] = 0;

/* JSON RESPONSE: Most sites have warnings turned off by default, but if they're turned on the warnings
cause errors in the JSON data Here we hide the status so warning level is reset at it at the end*/
$ajax1_error_level = error_reporting();
error_reporting(E_ERROR);

//===============================
//DATABASE TEST CONNECTION
//===============================
if (isset($_GET['dbtest'])) {
	
	$html    = "";
	$dbConn  = DupUtil::mysqli_connect($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass'], null, $_POST['dbport']);
	$dbErr	 = mysqli_connect_error();
	$dbFound = mysqli_select_db($dbConn, $_POST['dbname']);

	$tstSrv   = ($dbConn)  ? "<div class='dup-pass'>Success</div>" : "<div class='dup-fail'>Fail</div>";
	$tstDB    = ($dbFound) ? "<div class='dup-pass'>Success</div>" : "<div class='dup-fail'>Fail</div>";
	$html	 .= "<div class='dup-db-test'>";
	$html	 .= "<small style='display:block; padding:5px'>Using Connection String:<br/>Server={$_POST['dbhost']}; Database={$_POST['dbname']}; Uid={$_POST['dbuser']}; Pwd={$_POST['dbpass']}; Port={$_POST['dbport']}</small>";
	$html	 .= "<label>Server Connected:</label> {$tstSrv} <br/>";
	$html	 .= "<label>Database Found:</label>   {$tstDB} <br/>";
	

	if ($_POST['dbaction'] == 'create'){
		$tblcount = DupUtil::dbtable_count($dbConn, $_POST['dbname']);
		$html .= ($tblcount > 0) 
		? "<div class='dup-fail'><b>WARNING</b></div><br/>" . sprintf(ERR_DBEMPTY, $_POST['dbname'], $tblcount)
		: "";
	}
	$html .= "</div>";
	die($html);
}

//===============================
//ERROR MESSAGES
//===============================
//ERR_MAKELOG
($GLOBALS['LOG_FILE_HANDLE'] != false) or DUPX_Log::Error(ERR_MAKELOG);

//ERR_MYSQLI_SUPPORT
function_exists('mysqli_connect') or DUPX_Log::Error(ERR_MYSQLI_SUPPORT);

//ERR_DBCONNECT
$dbh = DupUtil::mysqli_connect($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass']);
($dbh) or DUPX_Log::Error(ERR_DBCONNECT . mysqli_connect_error());
if ($_POST['dbaction'] == 'empty') {
	mysqli_select_db($dbh, $_POST['dbname']) or DUPX_Log::Error(sprintf(ERR_DBCREATE, $_POST['dbname']));
}
//ERR_DBEMPTY
if ($_POST['dbaction'] == 'create' ) {
	$tblcount = DupUtil::dbtable_count($dbh, $_POST['dbname']);
	if ($tblcount > 0) {
		DUPX_Log::Error(sprintf(ERR_DBEMPTY, $_POST['dbname'], $tblcount));
	}
}

//ERR_ZIPMANUAL
if ($_POST['zip_manual']) {
	if (!file_exists("wp-config.php") && !file_exists("database.sql")) {
		DUPX_Log::Error(ERR_ZIPMANUAL);
	}
} else {
	//ERR_CONFIG_FOUND
	(!file_exists('wp-config.php')) 
		or DUPX_Log::Error(ERR_CONFIG_FOUND);
	//ERR_ZIPNOTFOUND
	(is_readable("{$package_path}")) 
		or DUPX_Log::Error(ERR_ZIPNOTFOUND);
}

DUPX_Log::Info("********************************************************************************");
DUPX_Log::Info('DUPLICATOR INSTALL-LOG');
DUPX_Log::Info('STEP1 START @ ' . @date('h:i:s'));
DUPX_Log::Info('NOTICE: Do NOT post to public sites or forums');
DUPX_Log::Info("********************************************************************************");
DUPX_Log::Info("VERSION:\t{$GLOBALS['FW_DUPLICATOR_VERSION']}");
DUPX_Log::Info("PHP:\t\t" . phpversion() . ' | SAPI: ' . php_sapi_name());
DUPX_Log::Info("SERVER:\t\t{$_SERVER['SERVER_SOFTWARE']}");
DUPX_Log::Info("DOC ROOT:\t{$root_path}");
DUPX_Log::Info("DOC ROOT 755:\t" . var_export($GLOBALS['CHOWN_ROOT_PATH'], true));
DUPX_Log::Info("LOG FILE 644:\t" . var_export($GLOBALS['CHOWN_LOG_PATH'], true));
DUPX_Log::Info("BUILD NAME:\t{$GLOBALS['FW_SECURE_NAME']}");
DUPX_Log::Info("REQUEST URL:\t{$GLOBALS['URL_PATH']}");

$log  = "--------------------------------------\n";
$log .= "POST DATA\n";
$log .= "--------------------------------------\n";
$log .= print_r($POST_LOG, true);
DUPX_Log::Info($log, 2);


//====================================================================================================
//UNZIP & FILE SETUP - Extract the zip file and prep files
//====================================================================================================
$log  = "\n********************************************************************************\n";
$log .= "ARCHIVE SETUP\n";
$log .= "********************************************************************************\n";
$log .= "NAME:\t{$_POST['package_name']}\n";
$log .= "SIZE:\t" . DupUtil::readable_bytesize(@filesize($_POST['package_name'])) . "\n";
$log .= "ZIP:\t{$zip_support} (ZipArchive Support)";
DUPX_Log::Info($log);

$zip_start = DupUtil::get_microtime();

if ($_POST['zip_manual']) {
	DUPX_Log::Info("\n** PACKAGE EXTRACTION IS IN MANUAL MODE ** \n");
} else {
	if ($GLOBALS['FW_PACKAGE_NAME'] != $_POST['package_name']) {
		$log  = "\n--------------------------------------\n";
		$log .= "WARNING: This package set may be incompatible!  \nBelow is a summary of the package this installer was built with and the package used. \n";
		$log .= "To guarantee accuracy the installer and archive should match. For details see the online FAQs.";
		$log .= "\nCREATED WITH:\t{$GLOBALS['FW_PACKAGE_NAME']} \nPROCESSED WITH:\t{$_POST['package_name']}  \n";
		$log .= "--------------------------------------\n";
		DUPX_Log::Info($log);
	}
	
	if (! class_exists('ZipArchive')) {
		DUPX_Log::Info("ERROR: Stopping install process.  Trying to extract without ZipArchive module installed.  Please use the 'Manual Package extraction' mode to extract zip file.");
		DUPX_Log::Error(ERR_ZIPARCHIVE);
	}

	$target = $root_path;
	$zip = new ZipArchive();
	if ($zip->open($_POST['package_name']) === TRUE) {
		DUPX_Log::Info("EXTRACTING");
		if (! $zip->extractTo($target)) {
			DUPX_Log::Error(ERR_ZIPEXTRACTION);
		}
		$log  = print_r($zip, true);
		$close_response = $zip->close();
		$log .= "COMPLETE: " . var_export($close_response, true);
		DUPX_Log::Info($log);
	} else {
		DUPX_Log::Error(ERR_ZIPOPEN);
	}
	$zip = null;
}

//===============================
//WP-CONFIG: wp-config
//===============================
$wpconfig = @file_get_contents('wp-config.php', true);

$patterns = array(
	"/'DB_NAME',\s*'.*?'/",
	"/'DB_USER',\s*'.*?'/",
	"/'DB_PASSWORD',\s*'.*?'/",
	"/'DB_HOST',\s*'.*?'/");

$replace = array(
	"'DB_NAME', "	  . '\'' . $_POST['dbname']				. '\'',
	"'DB_USER', "	  . '\'' . $_POST['dbuser']				. '\'',
	"'DB_PASSWORD', " . '\'' . DupUtil::preg_replacement_quote($_POST['dbpass']) . '\'',
	"'DB_HOST', "	  . '\'' . $_POST['dbhost']				. '\'');

//SSL CHECKS
if ($_POST['ssl_admin']) {
	if (! strstr($wpconfig, 'FORCE_SSL_ADMIN')) {
		$wpconfig = $wpconfig . PHP_EOL . "define('FORCE_SSL_ADMIN', true);";
	}
} else {
	array_push($patterns, "/'FORCE_SSL_ADMIN',\s*true/");
	array_push($replace,  "'FORCE_SSL_ADMIN', false");
}

if ($_POST['ssl_login']) {
	if (! strstr($wpconfig, 'FORCE_SSL_LOGIN')) {
		$wpconfig = $wpconfig . PHP_EOL . "define('FORCE_SSL_LOGIN', true);";
	}
} else {
	array_push($patterns, "/'FORCE_SSL_LOGIN',\s*true/");
	array_push($replace, "'FORCE_SSL_LOGIN', false");
}

//CACHE CHECKS
if ($_POST['cache_wp']) {
	if (! strstr($wpconfig, 'WP_CACHE')) {
		$wpconfig = $wpconfig . PHP_EOL . "define('WP_CACHE', true);";
	}
} else {
	array_push($patterns, "/'WP_CACHE',\s*true/");
	array_push($replace,  "'WP_CACHE', false");
}
if (! $_POST['cache_path']) {
	array_push($patterns, "/'WPCACHEHOME',\s*'.*?'/");
	array_push($replace,  "'WPCACHEHOME', ''");
}

$wpconfig = preg_replace($patterns, $replace, $wpconfig);
file_put_contents('wp-config.php', $wpconfig);
$wpconfig = null;

//CONFIG FILE RESETS
DUPX_Config::Reset();


//===============================
//DATABASE SCRIPT
//===============================
@chmod("{$root_path}/database.sql", 0777);
$sql_file = @file_get_contents('database.sql', true);
if ($sql_file == false || strlen($sql_file) < 10) {
	$sql_file = file_get_contents('installer-data.sql', true);
	if ($sql_file == false || strlen($sql_file) < 10) {
		DUPX_Log::Info("ERROR: Unable to read from the extracted database.sql file .\nValidate the permissions and/or group-owner rights on directory '{$root_path}'\n");
	}
}

//Complex Subject See: http://webcollab.sourceforge.net/unicode.html
//Removes invalid space characters
if ($_POST['dbnbsp']) {
	DUPX_Log::Info("ran fix non-breaking space characters\n");
	$sql_file = preg_replace('/\xC2\xA0/', ' ', $sql_file);
}

//Write new contents to install-data.sql
@chmod($sql_result_file_path, 0777);
file_put_contents($GLOBALS['SQL_FILE_NAME'], $sql_file);

$sql_result_file_data = explode(";\n", $sql_file);
$sql_result_file_length = count($sql_result_file_data);
$sql_result_file_path = "{$root_path}/{$GLOBALS['SQL_FILE_NAME']}";
$sql_file = null;

if (!is_readable($sql_result_file_path) || filesize($sql_result_file_path) == 0) {
	DUPX_Log::Info("ERROR: Unable to create new sql file {$GLOBALS['SQL_FILE_NAME']}.\nValidate the permissions and/or group-owner rights on directory '{$root_path}' and file '{$GLOBALS['SQL_FILE_NAME']}'\n");
}

DUPX_Log::Info("\nUPDATED FILES:");
DUPX_Log::Info("- SQL FILE:  '{$sql_result_file_path}'");
DUPX_Log::Info("- WP-CONFIG: '{$root_path}/wp-config.php'");
$zip_end = DupUtil::get_microtime();
DUPX_Log::Info("\nARCHIVE RUNTIME: " . DupUtil::elapsed_time($zip_end, $zip_start));
DUPX_Log::Info("\n");
DupUtil::fcgi_flush();


//====================================================================================================
//DATABASE ROUTINES
//====================================================================================================

@mysqli_query($dbh, "SET wait_timeout = {$GLOBALS['DB_MAX_TIME']}");
@mysqli_query($dbh, "SET max_allowed_packet = {$GLOBALS['DB_MAX_PACKETS']}");
DupUtil::mysql_set_charset($dbh, $_POST['dbcharset'], $_POST['dbcollate']);

//Set defaults incase the variable could not be read
$dbvar_maxtime = DupUtil::mysql_variable_value($dbh, 'wait_timeout');
$dbvar_maxpacks = DupUtil::mysql_variable_value($dbh, 'max_allowed_packet');
$dbvar_maxtime = is_null($dbvar_maxtime) ? 300 : $dbvar_maxtime;
$dbvar_maxpacks = is_null($dbvar_maxpacks) ? 1048576 : $dbvar_maxpacks;


DUPX_Log::Info("{$GLOBALS['SEPERATOR1']}");
DUPX_Log::Info('DATABASE-ROUTINES');
DUPX_Log::Info("{$GLOBALS['SEPERATOR1']}");
DUPX_Log::Info("--------------------------------------");
DUPX_Log::Info("SERVER ENVIROMENT");
DUPX_Log::Info("--------------------------------------");
DUPX_Log::Info("MYSQL VERSION:\t" . mysqli_get_server_info($dbh));
DUPX_Log::Info("TIMEOUT:\t{$dbvar_maxtime}");
DUPX_Log::Info("MAXPACK:\t{$dbvar_maxpacks}");

//CREATE DB
switch ($_POST['dbaction']) {
	case "create":	
		mysqli_query($dbh, "CREATE DATABASE IF NOT EXISTS `{$_POST['dbname']}`");
		mysqli_select_db($dbh, $_POST['dbname'])
		or DUPX_Log::Error(sprintf(ERR_DBCONNECT_CREATE, $_POST['dbname']));
		break;
	case "empty":	
		//DROP DB TABLES
		$drop_log = "Database already empty. Ready for install.";
		$sql = "SHOW TABLES FROM `{$_POST['dbname']}`";
		$found_tables = null;
		if ($result = mysqli_query($dbh, $sql)) {
			while ($row = mysqli_fetch_row($result)) {
				$found_tables[] = $row[0];
			}
			if (count($found_tables) > 0) {
				foreach ($found_tables as $table_name) {
					$sql = "DROP TABLE `{$_POST['dbname']}`.`{$table_name}`";
					if (!$result = mysqli_query($dbh, $sql)) {
						DUPX_Log::Error(sprintf(ERR_DBTRYCLEAN, $_POST['dbname']));
					}
				}
			}
			$drop_log = 'removed (' . count($found_tables) . ') tables';
		}
		break;
}


//WRITE DATA
DUPX_Log::Info("--------------------------------------");
DUPX_Log::Info("DATABASE RESULTS");
DUPX_Log::Info("--------------------------------------");
$profile_start = DupUtil::get_microtime();
$fcgi_buffer_pool = 5000;
$fcgi_buffer_count = 0;
$dbquery_rows = 0;
$dbtable_rows = 1;
$dbquery_errs = 0;
$counter = 0;
@mysqli_autocommit($dbh, false);
while ($counter < $sql_result_file_length) {

	$query_strlen = strlen(trim($sql_result_file_data[$counter]));
	if ($dbvar_maxpacks < $query_strlen) {
		DUPX_Log::Info("**ERROR** Query size limit [length={$query_strlen}] [sql=" . substr($sql_result_file_data[$counter], 75) . "...]");
		$dbquery_errs++;
	} elseif ($query_strlen > 0) {
		@mysqli_free_result(@mysqli_query($dbh, ($sql_result_file_data[$counter])));
		$err = mysqli_error($dbh);
		//Check to make sure the connection is alive
		if (!empty($err)) {

			if (!mysqli_ping($dbh)) {
				mysqli_close($dbh);
				$dbh = DupUtil::mysqli_connect($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass'], $_POST['dbname']);
			}
			DUPX_Log::Info("**ERROR** database error write '{$err}' - [sql=" . substr($sql_result_file_data[$counter], 0, 75) . "...]");
			$dbquery_errs++;

		//Buffer data to browser to keep connection open				
		} else {
			if ($fcgi_buffer_count++ > $fcgi_buffer_pool) {
				$fcgi_buffer_count = 0;
				DupUtil::fcgi_flush();
			}
			$dbquery_rows++;
		}
	}
	$counter++;
}
@mysqli_commit($dbh);
@mysqli_autocommit($dbh, true);

DUPX_Log::Info("ERRORS FOUND:\t{$dbquery_errs}");
DUPX_Log::Info("DROP TABLE:\t{$drop_log}");
DUPX_Log::Info("QUERIES RAN:\t{$dbquery_rows}\n");

$dbtable_count = 0;
if ($result = mysqli_query($dbh, "SHOW TABLES")) {
	while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
		$table_rows = DupUtil::table_row_count($dbh, $row[0]);
		$dbtable_rows += $table_rows;
		DUPX_Log::Info("{$row[0]}: ({$table_rows})");
		$dbtable_count++;
	}
	@mysqli_free_result($result);
}

if ($dbtable_count == 0) {
	DUPX_Log::Info("NOTICE: You may have to manually run the installer-data.sql to validate data input. Also check to make sure your installer file is correct and the
		table prefix '{$GLOBALS['FW_TABLEPREFIX']}' is correct for this particular version of WordPress. \n");
}


//DATA CLEANUP: Perform Transient Cache Cleanup
//Remove all duplicator entries and record this one since this is a new install.
$dbdelete_count = 0;
@mysqli_query($dbh, "DELETE FROM `{$GLOBALS['FW_TABLEPREFIX']}duplicator_packages`");
$dbdelete_count1 = @mysqli_affected_rows($dbh) or 0;
@mysqli_query($dbh, "DELETE FROM `{$GLOBALS['FW_TABLEPREFIX']}options` WHERE `option_name` LIKE ('_transient%') OR `option_name` LIKE ('_site_transient%')");
$dbdelete_count2 = @mysqli_affected_rows($dbh) or 0;
$dbdelete_count = (abs($dbdelete_count1) + abs($dbdelete_count2));
DUPX_Log::Info("Removed '{$dbdelete_count}' cache/transient rows");
//Reset Duplicator Options
foreach ($GLOBALS['FW_OPTS_DELETE'] as $value) {
	mysqli_query($dbh, "DELETE FROM `{$GLOBALS['FW_TABLEPREFIX']}options` WHERE `option_name` = '{$value}'");	
}

@mysqli_close($dbh);

$profile_end = DupUtil::get_microtime();
DUPX_Log::Info("\nSECTION RUNTIME: " . DupUtil::elapsed_time($profile_end, $profile_start));

//FINAL RESULTS
$ajax1_end = DupUtil::get_microtime();
$ajax1_sum = DupUtil::elapsed_time($ajax1_end, $ajax1_start);
DUPX_Log::Info("\n{$GLOBALS['SEPERATOR1']}");
DUPX_Log::Info('STEP1 COMPLETE @ ' . @date('h:i:s') . " - TOTAL RUNTIME: {$ajax1_sum}");
DUPX_Log::Info("{$GLOBALS['SEPERATOR1']}");

$JSON['pass'] = 1;
$JSON['table_count'] = $dbtable_count;
$JSON['table_rows']  = $dbtable_rows;
$JSON['query_errs']  = $dbquery_errs;
echo json_encode($JSON);
error_reporting($ajax1_error_level);
die('');
?>