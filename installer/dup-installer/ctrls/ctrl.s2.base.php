<?php
defined("ABSPATH") or die("");
//-- START OF ACTION STEP 2
/** IDE HELPERS */
/* @var $GLOBALS['DUPX_AC'] DUPX_ArchiveConfig */

require_once($GLOBALS['DUPX_INIT'] . '/api/class.cpnl.ctrl.php');

//BASIC
if ($_POST['view_mode'] == 'basic') {
	$_POST['dbaction']	 = isset($_POST['dbaction']) ? $_POST['dbaction'] : 'create';
	$_POST['dbhost']	 = isset($_POST['dbhost']) ? trim($_POST['dbhost']) : null;
	$_POST['dbname']	 = isset($_POST['dbname']) ? trim($_POST['dbname']) : null;
	$_POST['dbuser']	 = isset($_POST['dbuser']) ? trim($_POST['dbuser']) : null;
	$_POST['dbpass']	 = isset($_POST['dbpass']) ? trim($_POST['dbpass']) : null;
	$_POST['dbport']	 = isset($_POST['dbhost']) ? parse_url($_POST['dbhost'], PHP_URL_PORT) : 3306;
	$_POST['dbport']	 = (!empty($_POST['dbport'])) ? $_POST['dbport'] : 3306;
	$_POST['dbnbsp']	 = (isset($_POST['dbnbsp']) && $_POST['dbnbsp'] == '1') ? true : false;
	$_POST['dbcharset']		= isset($_POST['dbcharset']) ? trim($_POST['dbcharset']) : $GLOBALS['DBCHARSET_DEFAULT'];
	$_POST['dbcollate']		= isset($_POST['dbcollate']) ? trim($_POST['dbcollate']) : $GLOBALS['DBCOLLATE_DEFAULT'];
	$_POST['dbcollatefb']	= (isset($_POST['dbcollatefb']) && $_POST['dbcollatefb'] == '1') ? true : false;
	$_POST['dbobj_views']	= isset($_POST['dbobj_views']) ? true : false; 
	$_POST['dbobj_procs']	= isset($_POST['dbobj_procs']) ? true : false;
}
//CPANEL
else {
	$_POST['dbaction']	= isset($_POST['cpnl-dbaction']) ? $_POST['cpnl-dbaction'] : 'create';
	$_POST['dbhost']	= isset($_POST['cpnl-dbhost']) ? trim($_POST['cpnl-dbhost']) : null;
	$_POST['dbname']	= isset($_POST['cpnl-dbname-result']) ? trim($_POST['cpnl-dbname-result']) : null;
	$_POST['dbuser']	= isset($_POST['cpnl-dbuser-result']) ? trim($_POST['cpnl-dbuser-result']) : null;
	$_POST['dbpass']	= isset($_POST['cpnl-dbpass']) ? trim($_POST['cpnl-dbpass']) : null;
	$_POST['dbport']	= isset($_POST['cpnl-dbhost']) ? parse_url($_POST['cpnl-dbhost'], PHP_URL_PORT) : 3306;
	$_POST['dbport']	= (!empty($_POST['cpnl-dbport'])) ? $_POST['cpnl-dbport'] : 3306;
	$_POST['dbnbsp']	= (isset($_POST['cpnl-dbnbsp']) && $_POST['cpnl-dbnbsp'] == '1') ? true : false;
	$_POST['dbmysqlmode']		= $_POST['cpnl-dbmysqlmode'];
	$_POST['dbmysqlmode_opts']	= $_POST['cpnl-dbmysqlmode_opts'];
	$_POST['dbcharset']			= isset($_POST['cpnl-dbcharset']) ? trim($_POST['cpnl-dbcharset']) : $GLOBALS['DBCHARSET_DEFAULT'];
	$_POST['dbcollate']			= isset($_POST['cpnl-dbcollate']) ? trim($_POST['cpnl-dbcollate']) : $GLOBALS['DBCOLLATE_DEFAULT'];
	$_POST['dbcollatefb']		= (isset($_POST['cpnl-dbcollatefb']) && $_POST['cpnl-dbcollatefb'] == '1') ? true : false;
	$_POST['dbchunk']			= (isset($_POST['cpnl-dbchunk']) && $_POST['cpnl-dbchunk'] == '1') ? true : false;
	$_POST['dbobj_views']		= isset($_POST['cpnl-dbobj_views']) ? true : false;
	$_POST['dbobj_procs']		= isset($_POST['cpnl-dbobj_procs']) ? true : false;
}

$_POST['cpnl-dbuser-chk'] = (isset($_POST['cpnl-dbuser-chk']) && $_POST['cpnl-dbuser-chk'] == '1') ? true : false;
$_POST['cpnl-host']		  = isset($_POST['cpnl-host']) ? $_POST['cpnl-host'] : '';
$_POST['cpnl-user']		  = isset($_POST['cpnl-user']) ? $_POST['cpnl-user'] : '';
$_POST['cpnl-pass']		  = isset($_POST['cpnl-pass']) ? $_POST['cpnl-pass'] : '';

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
$dbTestIn->cpnlHost  = $_POST['cpnl-host'];
$dbTestIn->cpnlUser  = $_POST['cpnl-user'];
$dbTestIn->cpnlPass  = $_POST['cpnl-pass'];
$dbTestIn->cpnlNewUser = $_POST['cpnl-dbuser-chk'];

$dbTest	= new DUPX_DBTest($dbTestIn);

//CLICKS 'Test Database'
if (isset($_GET['dbtest'])) {
	
	$dbTest->runMode = 'TEST';
	$dbTest->responseMode = 'JSON';
	if (!headers_sent()) {
		header('Content-Type: application/json');
	}
	die($dbTest->run());
	
//CLICKS 'Next' 
} else {

	//@todo: 
	// - Convert DUPX_DBTest to DUPX_DBSetup
	// - implement property runMode = "Test/Live"
	// - This should replace the cpnl code block below
	/*
	$dbSetup->runMode = 'LIVE';
	$dbSetup->responseMode = 'PHP';
	$dbSetupResult = $dbSetup->run();

	if (! $dbSetupResult->payload->reqsPass) {
		$errorMessage = $dbTestResult->payload->lastError;
		DUPX_Log::error(empty($errorMessage) ? 'UNKNOWN ERROR RESPONSE:  Please try the process again!' : $errorMessage);
	}*/
}

//===============================================
//CPANEL LOGIC: From Postback
//===============================================
$cpnllog = "";
if ($_POST['view_mode'] == 'cpnl') {
	try {
		$cpnllog	  ="--------------------------------------\n";
		$cpnllog	 .="CPANEL API\n";
		$cpnllog	 .="--------------------------------------\n";

		$CPNL		 = new DUPX_cPanel_Controller();
		$cpnlToken	 = $CPNL->create_token($_POST['cpnl-host'], $_POST['cpnl-user'], $_POST['cpnl-pass']);
		$cpnlHost	 = $CPNL->connect($cpnlToken);
		
		//CREATE DB USER: Attempt to create user should happen first in the case that the
		//user passwords requirements are not met.
		if ($_POST['cpnl-dbuser-chk']) {
			$result = $CPNL->create_db_user($cpnlToken, $_POST['dbuser'], $_POST['dbpass']);
			if ($result['status'] !== true) {
				DUPX_Log::info('CPANEL API ERROR: create_db_user ' . print_r($result['cpnl_api'], true), 2);
				DUPX_Log::error(sprintf(ERR_CPNL_API, $result['status']));
			} else {
				$cpnllog .= "- A new database user was created\n";
			}
		}

		//CREATE NEW DB
		if ($_POST['dbaction'] == 'create') {
			$result = $CPNL->create_db($cpnlToken, $_POST['dbname']);
			if ($result['status'] !== true) {
				DUPX_Log::info('CPANEL API ERROR: create_db '.print_r($result['cpnl_api'], true), 2);
				DUPX_Log::error(sprintf(ERR_CPNL_API, $result['status']));
			} else {
				$cpnllog .= "- A new database was created\n";
			}
		} else {
			$cpnllog .= "- Used to connect to existing database named [{$_POST['dbname']}]\n";
		}

		//ASSIGN USER TO DB IF NOT ASSIGNED
		$result = $CPNL->is_user_in_db($cpnlToken, $_POST['dbname'], $_POST['dbuser']);
		if (!$result['status']) {
			$result		 = $CPNL->assign_db_user($cpnlToken, $_POST['dbname'], $_POST['dbuser']);
			if ($result['status'] !== true) {
				DUPX_Log::info('CPANEL API ERROR: assign_db_user '.print_r($result['cpnl_api'], true), 2);
				DUPX_Log::error(sprintf(ERR_CPNL_API, $result['status']));
			} else {
				$cpnllog .= "- Database user was assigned to database";
			}
		}
	} catch (Exception $ex) {
		DUPX_Log::error($ex);
	}
}

$not_yet_logged = (isset($_POST['first_chunk']) && $_POST['first_chunk']) || (!isset($_POST['continue_chunking']));

if($not_yet_logged){
    DUPX_Log::info("\n\n\n********************************************************************************");
    DUPX_Log::info('* DUPLICATOR PRO INSTALL-LOG');
    DUPX_Log::info('* STEP-2 START @ '.@date('h:i:s'));
    DUPX_Log::info('* NOTICE: Do NOT post to public sites or forums!!');
    DUPX_Log::info("********************************************************************************");
    if (! empty($cpnllog)) {
        DUPX_Log::info($cpnllog);
    }

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
	DUPX_Log::info("- No SQL script has been ran -");

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