<?php
defined("ABSPATH") or die("");
//-- START OF ACTION STEP 2
/** IDE HELPERS */
/* @var $GLOBALS['DUPX_AC'] DUPX_ArchiveConfig */

$_POST['dbaction']	 = isset($_POST['dbaction']) ? $_POST['dbaction'] : 'create';
$_POST['dbhost']	 = isset($_POST['dbhost']) ? DUPX_U::sanitize(trim($_POST['dbhost'])) : null;
$_POST['dbname']	 = isset($_POST['dbname']) ? DUPX_U::sanitize(trim($_POST['dbname'])) : null;
$_POST['dbuser']	 = isset($_POST['dbuser']) ? trim($_POST['dbuser']) : null;
$_POST['dbpass']	 = isset($_POST['dbpass']) ? trim($_POST['dbpass']) : null;
$_POST['dbport']	 = isset($_POST['dbhost']) ? parse_url($_POST['dbhost'], PHP_URL_PORT) : 3306;
$_POST['dbport']	 = (!empty($_POST['dbport'])) ? $_POST['dbport'] : 3306;
$_POST['dbnbsp']	 = (isset($_POST['dbnbsp']) && $_POST['dbnbsp'] == '1') ? true : false;
$_POST['dbcharset']		= isset($_POST['dbcharset']) ? DUPX_U::sanitize(trim($_POST['dbcharset'])) : $GLOBALS['DBCHARSET_DEFAULT'];
$_POST['dbcollate']		= isset($_POST['dbcollate']) ? DUPX_U::sanitize(trim($_POST['dbcollate'])) : $GLOBALS['DBCOLLATE_DEFAULT'];
$_POST['dbcollatefb']	= (isset($_POST['dbcollatefb']) && $_POST['dbcollatefb'] == '1') ? true : false;
$_POST['dbobj_views']	= isset($_POST['dbobj_views']) ? true : false; 
$_POST['dbobj_procs']	= isset($_POST['dbobj_procs']) ? true : false;

$ajax2_start	 = DUPX_U::getMicrotime();
$root_path		 = $GLOBALS['DUPX_ROOT'];
$JSON			 = array();
$JSON['pass']	 = 0;

/**
JSON RESPONSE: Most sites have warnings turned off by default, but if they're turned on the warnings
cause errors in the JSON data Here we hide the status so warning level is reset at it at the end */
$ajax2_error_level = error_reporting();
error_reporting(E_ERROR);
($GLOBALS['LOG_FILE_HANDLE'] != false) or DUPX_Log::error(ERR_MAKELOG);


//===============================================
//DB TEST & ERRORS: From Postback
//===============================================
//INPUTS
$dbTestIn			 = new DUPX_DBTestIn();
$dbTestIn->mode		 = $_POST['view_mode'];
$dbTestIn->dbaction	 = $_POST['dbaction'];
$dbTestIn->dbhost	 = $_POST['dbhost'];
$dbTestIn->dbuser	 = $_POST['dbuser'];
$dbTestIn->dbpass	 = $_POST['dbpass'];
$dbTestIn->dbname	 = $_POST['dbname'];
$dbTestIn->dbport	 = $_POST['dbport'];
$dbTestIn->dbcollatefb = $_POST['dbcollatefb'];

$dbTest	= new DUPX_DBTest($dbTestIn);

//CLICKS 'Test Database'
if (isset($_GET['dbtest'])) {
	
	$dbTest->runMode = 'TEST';
	$dbTest->responseMode = 'JSON';
	if (!headers_sent()) {
		header('Content-Type: application/json');
	}
	die($dbTest->run());
} 

$not_yet_logged = (isset($_POST['first_chunk']) && $_POST['first_chunk']) || (!isset($_POST['continue_chunking']));

if($not_yet_logged){
    DUPX_Log::info("\n\n\n********************************************************************************");
    DUPX_Log::info('* DUPLICATOR INSTALL-LOG');
    DUPX_Log::info('* STEP-2 START @ '.@date('h:i:s'));
    DUPX_Log::info('* NOTICE: Do NOT post to public sites or forums!!');
    DUPX_Log::info("********************************************************************************");
    $POST_LOG = $_POST;
    unset($POST_LOG['dbpass']);
    ksort($POST_LOG);
    $log = "--------------------------------------\n";
    $log .= "POST DATA\n";
    $log .= "--------------------------------------\n";
    $log .= print_r($POST_LOG, true);
    DUPX_Log::info($log, 2);
}


//===============================================
//DATABASE ROUTINES
//===============================================
$dbinstall = new DUPX_DBInstall($_POST, $ajax2_start);
if ($_POST['dbaction'] != 'manual') {
    if(!isset($_POST['continue_chunking'])){
        $dbinstall->prepareSQL();
        $dbinstall->prepareDB();
    } else if($_POST['first_chunk'] == 1) {
        $dbinstall->prepareDB();
    }
}
if($not_yet_logged) {
    DUPX_Log::info("--------------------------------------");
    DUPX_Log::info("DATABASE RESULTS");
    DUPX_Log::info("--------------------------------------");
}

if ($_POST['dbaction'] == 'manual') {
	DUPX_Log::info("\n** SQL EXECUTION IS IN MANUAL MODE **");
	DUPX_Log::info("- No SQL script has been executed -");
	$JSON['pass'] = 1;
} elseif(isset($_POST['continue_chunking']) && $_POST['continue_chunking'] === 'true') {
    print_r(json_encode($dbinstall->writeInChunks()));
    die();
} elseif(isset($_POST['continue_chunking']) && ($_POST['continue_chunking'] === 'false' && $_POST['pass'] == 1)) {
    $JSON['pass'] = 1;
} elseif(!isset($_POST['continue_chunking'])) {
	$dbinstall->writeInDB();
    $JSON['pass'] = 1;
}

$dbinstall->profile_end = DUPX_U::getMicrotime();
$dbinstall->writeLog();
$JSON = $dbinstall->getJSON($JSON);

//FINAL RESULTS
$ajax1_sum	 = DUPX_U::elapsedTime(DUPX_U::getMicrotime(), $dbinstall->start_microtime);
DUPX_Log::info("\nINSERT DATA RUNTIME: " . DUPX_U::elapsedTime($dbinstall->profile_end, $dbinstall->profile_start));
DUPX_Log::info('STEP-2 COMPLETE @ '.@date('h:i:s')." - RUNTIME: {$ajax1_sum}");

error_reporting($ajax2_error_level);
die(json_encode($JSON));