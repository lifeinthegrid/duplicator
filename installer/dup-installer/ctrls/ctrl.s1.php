<?php
defined("ABSPATH") or die("");

/** IDE HELPERS */
/* @var $GLOBALS['DUPX_AC'] DUPX_ArchiveConfig */

//OPTIONS
$base_file_perms_value		= (isset($_POST['file_perms_value'])) ? $_POST['file_perms_value'] : 'not set';
$base_dir_perms_value		= (isset($_POST['dir_perms_value']))  ? $_POST['dir_perms_value']  : 'not set';
$_POST['set_file_perms']	= (isset($_POST['set_file_perms']))   ? 1 : 0;
$_POST['set_dir_perms']		= (isset($_POST['set_dir_perms']))    ? 1 : 0;
$_POST['file_perms_value']	= (isset($_POST['file_perms_value'])) ? intval(('0' . $_POST['file_perms_value']), 8) : 0755;
$_POST['dir_perms_value']	= (isset($_POST['dir_perms_value']))  ? intval(('0' . $_POST['dir_perms_value']), 8)  : 0644;
$_POST['zip_filetime']		= (isset($_POST['zip_filetime']))     ? $_POST['zip_filetime'] : 'current';
$_POST['config_mode']		= (isset($_POST['config_mode']))      ? $_POST['config_mode'] : 'NEW';
$_POST['archive_engine']	= (isset($_POST['archive_engine']))   ? $_POST['archive_engine'] : 'manual';
$_POST['exe_safe_mode']		= (isset($_POST['exe_safe_mode']))    ? $_POST['exe_safe_mode'] : 0;

//LOGGING
$POST_LOG = $_POST;
unset($POST_LOG['dbpass']);
ksort($POST_LOG);

if($_POST['archive_engine'] == 'manual') {
	$GLOBALS['DUPX_STATE']->isManualExtraction = true;
	$GLOBALS['DUPX_STATE']->save();
}

//ACTION VARS
$ajax1_start		= DUPX_U::getMicrotime();
$root_path			= $GLOBALS['DUPX_ROOT'];
$wpconfig_ark_path	= "{$root_path}/wp-config-arc.txt";
$archive_path		= $GLOBALS['FW_PACKAGE_PATH'];
$JSON				= array();
$JSON['pass']		= 0;

/** JSON RESPONSE: Most sites have warnings turned off by default, but if they're turned on the warnings
  cause errors in the JSON data Here we hide the status so warning level is reset at it at the end */
$ajax1_error_level = error_reporting();
error_reporting(E_ERROR);

//===============================
//ARCHIVE ERROR MESSAGES
//===============================
($GLOBALS['LOG_FILE_HANDLE'] != false) or DUPX_Log::error(ERR_MAKELOG);

if (! $GLOBALS['DUPX_AC']->exportOnlyDB) {

	$wpConfigPath	= "{$GLOBALS['DUPX_ROOT']}/wp-config.php";

	if (($_POST['archive_engine'] == 'manual') || $_POST['archive_engine'] == 'duparchive'){
		if (!file_exists($wpconfig_ark_path) && !file_exists("database.sql")) {
			DUPX_Log::error(ERR_ZIPMANUAL);
		}
	} else {

        if (!is_readable("{$archive_path}")) {
			DUPX_Log::error("archive path:{$archive_path}<br/>" . ERR_ZIPNOTFOUND);
		}
	}
}

DUPX_Log::info("********************************************************************************");
DUPX_Log::info('* DUPLICATOR: Install-Log');
DUPX_Log::info('* STEP-1 START @ ' . @date('h:i:s'));
DUPX_Log::info("* VERSION: {$GLOBALS['DUPX_AC']->version_dup}");
DUPX_Log::info('* NOTICE: Do NOT post to public sites or forums!!');
DUPX_Log::info("********************************************************************************");
DUPX_Log::info("PHP:\t\t".phpversion().' | SAPI: '.php_sapi_name());
DUPX_Log::info("PHP MEMORY:\t".$GLOBALS['PHP_MEMORY_LIMIT'].' | SUHOSIN: '.$GLOBALS['PHP_SUHOSIN_ON']);
DUPX_Log::info("SERVER:\t\t{$_SERVER['SERVER_SOFTWARE']}");
DUPX_Log::info("DOC ROOT:\t{$root_path}");
DUPX_Log::info("DOC ROOT 755:\t".var_export($GLOBALS['CHOWN_ROOT_PATH'], true));
DUPX_Log::info("LOG FILE 644:\t".var_export($GLOBALS['CHOWN_LOG_PATH'], true));
DUPX_Log::info("REQUEST URL:\t{$GLOBALS['URL_PATH']}");
DUPX_Log::info("SAFE MODE :\t{$_POST['exe_safe_mode']}");
DUPX_Log::info("CONFIG MODE :\t{$_POST['config_mode']}");

$log = "--------------------------------------\n";
$log .= "POST DATA\n";
$log .= "--------------------------------------\n";
$log .= print_r($POST_LOG, true);
DUPX_Log::info($log, 2);

$log = "--------------------------------------\n";
$log .= "PRE-EXTRACT-CHECKS\n";
$log .= "--------------------------------------";
DUPX_Log::info($log);
DUPX_ServerConfig::beforeExtractionSetup();


$log = "--------------------------------------\n";
$log .= "ARCHIVE SETUP\n";
$log .= "--------------------------------------\n";
$log .= "NAME:\t{$GLOBALS['FW_PACKAGE_NAME']}\n";
$log .= "SIZE:\t".DUPX_U::readableByteSize(@filesize($GLOBALS['FW_PACKAGE_PATH']));
DUPX_Log::info($log . "\n");


$target	 = $root_path;

switch ($_POST['archive_engine']) {
	
	//-----------------------
	//MANUAL EXTRACTION
	case 'manual':
		DUPX_Log::info("\n** PACKAGE EXTRACTION IS IN MANUAL MODE ** \n");
		break;

	//-----------------------
	//SHELL EXEC
	case 'shellexec_unzip':

		$shell_exec_path = DUPX_Server::get_unzip_filepath();
		DUPX_Log::info("ZIP:\tShell Exec Unzip");

		$command = "{$shell_exec_path} -o -qq \"{$archive_path}\" -d {$target} 2>&1";
		if ($_POST['zip_filetime'] == 'original') {
			DUPX_Log::info("\nShell Exec Current does not support orginal file timestamp please use ZipArchive");
		}

		DUPX_Log::info(">>> Starting Shell-Exec Unzip:\nCommand: {$command}");
		$stderr = shell_exec($command);
		if ($stderr != '') {
			$zip_err_msg = ERR_SHELLEXEC_ZIPOPEN.": $stderr";
			$zip_err_msg .= "<br/><br/><b>To resolve error see <a href='https://snapcreek.com/duplicator/docs/faqs-tech/#faq-installer-130-q' target='_blank'>https://snapcreek.com/duplicator/docs/faqs-tech/#faq-installer-130-q</a></b>";
			DUPX_Log::error($zip_err_msg);
		}
		DUPX_Log::info("<<< Shell-Exec Unzip Complete.");

		break;

	//-----------------------
	//ZIP-ARCHIVE
	case 'ziparchive':
		DUPX_Log::info(">>> Starting ZipArchive Unzip");

		if (!class_exists('ZipArchive')) {
			DUPX_Log::info("ERROR: Stopping install process.  Trying to extract without ZipArchive module installed.  Please use the 'Manual Archive Extraction' mode to extract zip file.");
			DUPX_Log::error(ERR_ZIPARCHIVE);
		}

		$zip = new ZipArchive();

		if ($zip->open($archive_path) === TRUE) {

			if (!$zip->extractTo($target)) {
				$zip_err_msg = ERR_ZIPEXTRACTION;
				$zip_err_msg .= "<br/><br/><b>To resolve error see <a href='https://snapcreek.com/duplicator/docs/faqs-tech/#faq-installer-130-q' target='_blank'>https://snapcreek.com/duplicator/docs/faqs-tech/#faq-installer-130-q</a></b>";
				DUPX_Log::error($zip_err_msg);
			}
			$log = print_r($zip, true);

			//FILE-TIMESTAMP
			if ($_POST['zip_filetime'] == 'original') {
				$log .= "File timestamp set to Original\n";
				for ($idx = 0; $s = $zip->statIndex($idx); $idx++) {
					touch($target.DIRECTORY_SEPARATOR.$s['name'], $s['mtime']);
				}
			} else {
				$now  = @date("Y-m-d H:i:s");
				$log .= "File timestamp set to Current: {$now}\n";
			}

			$close_response = $zip->close();
			$log .= "<<< ZipArchive Unzip Complete: " . var_export($close_response, true);
			DUPX_Log::info($log);
		} else {
			$zip_err_msg = ERR_ZIPOPEN;
			$zip_err_msg .= "<br/><br/><b>To resolve error see <a href='https://snapcreek.com/duplicator/docs/faqs-tech/#faq-installer-130-q' target='_blank'>https://snapcreek.com/duplicator/docs/faqs-tech/#faq-installer-130-q</a></b>";
			DUPX_Log::error($zip_err_msg);
		}

		break;

	//-----------------------
	//DUP-ARCHIVE
	case 'duparchive':
        DUPX_Log::info(">>> DupArchive Extraction Complete");

        if (isset($_POST['extra_data'])) {
			$extraData = $_POST['extra_data'];

			$log = "\n--------------------------------------\n";
			$log .= "DUPARCHIVE EXTRACTION STATUS\n";
			$log .= "--------------------------------------\n";

			$dawsStatus = json_decode($extraData);

			if ($dawsStatus === null) {
				$log .= "Can't decode the dawsStatus!\n";
				$log .= print_r(extraData, true);
			} else {
				$criticalPresent = false;

				if (count($dawsStatus->failures) > 0) {
					$log .= "Archive extracted with errors.\n";

					foreach ($dawsStatus->failures as $failure) {
						if ($failure->isCritical) {
							$log			 .= '(C) ';
							$criticalPresent = true;
						}
						$log .= "{$failure->description}\n";
					}
				} else {
					$log .= "Archive extracted with no errors.\n";
				}

				if ($criticalPresent) {
					$log .= "\n\nCritical Errors present so stopping install.\n";
					exit();
				}
			}

			DUPX_Log::info($log);
		} else {
			DUPX_LOG::info("DAWS STATUS: UNKNOWN since extra_data wasn't in post!");
		}

		break;	
}


$log  = "--------------------------------------\n";
$log .= "POST-EXTACT-CHECKS\n";
$log .= "--------------------------------------";
DUPX_Log::info($log);

//===============================
//FILE PERMISSIONS
if ($_POST['set_file_perms'] || $_POST['set_dir_perms']) {

	// Skips past paths it can't read
	class IgnorantRecursiveDirectoryIterator extends RecursiveDirectoryIterator
	{
		function getChildren()
		{
			try {
				return new IgnorantRecursiveDirectoryIterator($this->getPathname());
			} catch (UnexpectedValueException $e) {
				return new RecursiveArrayIterator(array());
			}
		}
	}

	DUPX_Log::info("PERMISSION UPDATES:");
	DUPX_Log::info("    -DIRS:  '{$base_dir_perms_value}'");
	DUPX_Log::info("    -FILES: '{$base_file_perms_value}'");
	$set_file_perms		 = $_POST['set_file_perms'];
	$set_dir_perms		 = $_POST['set_dir_perms'];
	$set_file_mtime		 = ($_POST['zip_filetime'] == 'current');
	$file_perms_value	 = $_POST['file_perms_value'] ? $_POST['file_perms_value'] : 0755;
	$dir_perms_value	 = $_POST['dir_perms_value']  ? $_POST['dir_perms_value']  : 0644;

	$objects = new RecursiveIteratorIterator(new IgnorantRecursiveDirectoryIterator($root_path), RecursiveIteratorIterator::SELF_FIRST);

	foreach ($objects as $name => $object) {
		if ($set_file_perms && is_file($name)) {
			$retVal = @chmod($name, $file_perms_value);

			if (!$retVal) {
				DUPX_Log::info("Permissions setting on {$name} failed");
			}
		} else if ($set_dir_perms && is_dir($name)) {
			$retVal = @chmod($name, $dir_perms_value);

			if (!$retVal) {
				DUPX_Log::info("Permissions setting on {$name} failed");
			}
		}
		if ($set_file_mtime) {
			@touch($name);
		}
	}
} else {
	DUPX_Log::info("\nPERMISSION UPDATES: None Applied");
}

DUPX_ServerConfig::afterExtractionSetup();


//FINAL RESULTS
$ajax1_sum	 = DUPX_U::elapsedTime(DUPX_U::getMicrotime(), $ajax1_start);
DUPX_Log::info("\nSTEP-1 COMPLETE @ " . @date('h:i:s') . " - RUNTIME: {$ajax1_sum}");

$JSON['pass'] = 1;
error_reporting($ajax1_error_level);
die(json_encode($JSON));