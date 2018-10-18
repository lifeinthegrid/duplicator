<?php
if (!defined('DUPLICATOR_VERSION')) exit; // Exit if accessed directly

require_once (DUPLICATOR_PLUGIN_PATH.'classes/utilities/class.u.php');
require_once (DUPLICATOR_PLUGIN_PATH.'classes/package/class.pack.archive.php');
require_once (DUPLICATOR_PLUGIN_PATH.'classes/package/class.pack.installer.php');
require_once (DUPLICATOR_PLUGIN_PATH.'classes/package/class.pack.database.php');

class DUP_Build_Progress
{
    public $thread_start_time;
    public $initialized = false;
    public $installer_built = false;
    public $archive_started = false;
    public $archive_has_database = false;
    public $archive_built = false;
    public $database_script_built = false;
    public $failed = false;
    public $retries = 0;
    public $build_failures = array();
    public $validation_failures = array();

    private $package;

    public function __construct($package)
    {
        $this->package = $package;
    }

    public function has_completed()
    {
        return $this->failed || ($this->installer_built && $this->archive_built && $this->database_script_built);
    }

    public function timed_out($max_time)
    {
        if ($max_time > 0) {
            $time_diff = time() - $this->thread_start_time;
            return ($time_diff >= $max_time);
        } else {
            return false;
        }
    }

    public function start_timer()
    {
        $this->thread_start_time = time();
    }

    public function set_validation_failures($failures)
	{
		$this->validation_failures = array();

		foreach ($failures as $failure) {
			$this->validation_failures[] = $failure;
		}
	}

	public function set_build_failures($failures)
	{
		$this->build_failures = array();

		foreach ($failures as $failure) {
			$this->build_failures[] = $failure->description;
		}
	}

	public function set_failed($failure_message = null)
    {
        if($failure_message !== null) {

            $failure = new StdClass();

            $failure->type        = 0;
            $failure->subject     = '';
            $failure->description = $failure_message;
            $failure->isCritical    = true;

            $this->build_failures[] = $failure;
        }

        $this->failed = true;
        $this->package->Status = DUP_PackageStatus::ERROR;
    }
}

final class DUP_PackageStatus
{
    private function __construct()
    {
    }

	const ERROR = -1;
	const CREATED  = 0;
    const START    = 10;
    const DBSTART  = 20;
    const DBDONE   = 30;
    const ARCSTART = 40;
    const ARCVALIDATION = 60;
    const ARCDONE = 65;
    const COMPLETE = 100;
}

final class DUP_PackageType
{
    const MANUAL    = 0;
    const SCHEDULED = 1;
}

abstract class DUP_PackageFileType
{

    const Installer = 0;
    const Archive = 1;
    const SQL = 2;
    const Log = 3;
    //const Dump = 4;
}

/**
 * Class used to store and process all Package logic
 *
 * @package Duplicator\classes
 */
class DUP_Package
{
    const OPT_ACTIVE = 'duplicator_package_active';

    //Properties
    public $Created;
    public $Version;
    public $VersionWP;
    public $VersionDB;
    public $VersionPHP;
    public $VersionOS;
    public $ID;
    public $Name;
    public $Hash;
    public $NameHash;
	//MANUAL, SCHEDULED (scheduled not active)
    public $Type;
    public $Notes;
    public $StorePath;
    public $StoreURL;
    public $ScanFile;
    public $TimerStart = -1;
    public $Runtime;
    public $ExeSize;
    public $ZipSize;
    public $Status;
    public $WPUser;
    //Objects
    public $Archive;
    public $Installer;
    public $Database;

	public $BuildProgress;

    /**
     *  Manages the Package Process
     */
    function __construct()
    {

        $this->ID      = null;
        $this->Version = DUPLICATOR_VERSION;

        $this->Type      = DUP_PackageType::MANUAL;
        $this->Name      = self::getDefaultName();
        $this->Notes     = null;
        $this->StoreURL  = DUP_Util::snapshotURL();
        $this->StorePath = DUPLICATOR_SSDIR_PATH_TMP;
        $this->Database  = new DUP_Database($this);
        $this->Archive   = new DUP_Archive($this);
        $this->Installer = new DUP_Installer($this);
		$this->BuildProgress = new DUP_Build_Progress($this);
		$this->Status = DUP_PackageStatus::CREATED;
    }

    /**
     * Generates a json scan report
     *
     * @return array of scan results
     *
     * @notes: Testing = /wp-admin/admin-ajax.php?action=duplicator_package_scan
     */
    public function runScanner()
    {
        $timerStart     = DUP_Util::getMicrotime();
        $report         = array();
        $this->ScanFile = "{$this->NameHash}_scan.json";


        $report['RPT']['ScanTime'] = "0";
        $report['RPT']['ScanFile'] = $this->ScanFile;

        //SERVER
        $srv           = DUP_Server::getChecks();
        $report['SRV'] = $srv['SRV'];

        //FILES
        $this->Archive->getScannerData();
        $dirCount  = count($this->Archive->Dirs);
        $fileCount = count($this->Archive->Files);
        $fullCount = $dirCount + $fileCount;

        $report['ARC']['Size']      = DUP_Util::byteSize($this->Archive->Size) or "unknown";
        $report['ARC']['DirCount']  = number_format($dirCount);
        $report['ARC']['FileCount'] = number_format($fileCount);
        $report['ARC']['FullCount'] = number_format($fullCount);
		$report['ARC']['FilterDirsAll'] = $this->Archive->FilterDirsAll;
		$report['ARC']['FilterFilesAll'] = $this->Archive->FilterFilesAll;
		$report['ARC']['FilterExtsAll'] = $this->Archive->FilterExtsAll;
        $report['ARC']['FilterInfo'] = $this->Archive->FilterInfo;
        $report['ARC']['RecursiveLinks'] = $this->Archive->RecursiveLinks;
        $report['ARC']['UnreadableItems'] = array_merge($this->Archive->FilterInfo->Files->Unreadable,$this->Archive->FilterInfo->Dirs->Unreadable);
        $report['ARC']['Status']['Size']  = ($this->Archive->Size > DUPLICATOR_SCAN_SIZE_DEFAULT) ? 'Warn' : 'Good';
        $report['ARC']['Status']['Names'] = (count($this->Archive->FilterInfo->Files->Warning) + count($this->Archive->FilterInfo->Dirs->Warning)) ? 'Warn' : 'Good';
        $report['ARC']['Status']['UnreadableItems'] = !empty($this->Archive->RecursiveLinks) || !empty($report['ARC']['UnreadableItems'])? 'Warn' : 'Good';

        //$report['ARC']['Status']['Big']   = count($this->Archive->FilterInfo->Files->Size) ? 'Warn' : 'Good';
        $report['ARC']['Dirs']  = $this->Archive->Dirs;
        $report['ARC']['Files'] = $this->Archive->Files;
		$report['ARC']['Status']['AddonSites'] = count($this->Archive->FilterInfo->Dirs->AddonSites) ? 'Warn' : 'Good';
            


        //DATABASE
        $db  = $this->Database->getScannerData();
        $report['DB'] = $db;

        //Lite Limits
        $rawTotalSize = $this->Archive->Size + $report['DB']['RawSize'];
        $report['LL']['TotalSize'] = DUP_Util::byteSize($rawTotalSize);
        $report['LL']['Status']['TotalSize'] = ($rawTotalSize > DUPLICATOR_MAX_DUPARCHIVE_SIZE) ? 'Fail' : 'Good';

        $warnings = array(
            $report['SRV']['PHP']['ALL'],
            $report['SRV']['WP']['ALL'],
            $report['ARC']['Status']['Size'],
            $report['ARC']['Status']['Names'],
            $db['Status']['DB_Size'],
            $db['Status']['DB_Rows']);

        //array_count_values will throw a warning message if it has null values,
        //so lets replace all nulls with empty string
        foreach ($warnings as $i => $value) {
            if (is_null($value)) {
                $warnings[$i] = '';
            }
        }
		
        $warn_counts               = is_array($warnings) ? array_count_values($warnings) : 0;
        $report['RPT']['Warnings'] = is_null($warn_counts['Warn']) ? 0 : $warn_counts['Warn'];
        $report['RPT']['Success']  = is_null($warn_counts['Good']) ? 0 : $warn_counts['Good'];
        $report['RPT']['ScanTime'] = DUP_Util::elapsedTime(DUP_Util::getMicrotime(), $timerStart);
        $fp                        = fopen(DUPLICATOR_SSDIR_PATH_TMP."/{$this->ScanFile}", 'w');


        fwrite($fp, json_encode($report));
        fclose($fp);

        return $report;
    }

    // Saves the active package to the package table
	public function save($extension)
	{
        global $wpdb;

		$this->Archive->Format = strtoupper($extension);
		$this->Archive->File = "{$this->NameHash}_archive.{$extension}";
		$this->Installer->File = "{$this->NameHash}_installer.php";
		$this->Database->File = "{$this->NameHash}_database.sql";
		$this->WPUser          = isset($current_user->user_login) ? $current_user->user_login : 'unknown';

		//START LOGGING
		DUP_Log::Open($this->NameHash);

		$this->writeLogHeader();

		//CREATE DB RECORD
		$packageObj = serialize($this);
		if (!$packageObj) {
			DUP_Log::Error("Unable to serialize package object while building record.");
		}

		$this->ID = $this->getHashKey($this->Hash);

		if ($this->ID != 0) {
			DUP_LOG::Trace("ID non zero so setting to start");
			$this->setStatus(DUP_PackageStatus::START);
		} else {
			DUP_LOG::Trace("ID IS zero so creating another package");
			$results = $wpdb->insert($wpdb->base_prefix . "duplicator_packages", array(
				'name' => $this->Name,
				'hash' => $this->Hash,
				'status' => DUP_PackageStatus::START,
				'created' => current_time('mysql', get_option('gmt_offset', 1)),
				'owner' => isset($current_user->user_login) ? $current_user->user_login : 'unknown',
				'package' => $packageObj)
			);
			if ($results === false) {
				$wpdb->print_error();
				DUP_LOG::Trace("Problem inserting package: {$wpdb->last_error}");

				DUP_Log::Error("Duplicator is unable to insert a package record into the database table.", "'{$wpdb->last_error}'");
			}
			$this->ID = $wpdb->insert_id;
		}
	}
    
    public function delete()
    {
        global $wpdb;
        
        $tblName  = $wpdb->prefix.'duplicator_packages';

        $getResult = $wpdb->get_results($wpdb->prepare("SELECT name, hash FROM `{$tblName}` WHERE id = %d", $this->ID), ARRAY_A);

        if ($getResult) {
            $row       = $getResult[0];
            $nameHash  = "{$row['name']}_{$row['hash']}";
            $delResult = $wpdb->query($wpdb->prepare("DELETE FROM `{$tblName}` WHERE id = %d", $this->ID));
            
            if ($delResult != 0) {
                //Perms
                @chmod(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP."/{$nameHash}_archive.zip"), 0644);
                @chmod(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP."/{$nameHash}_database.sql"), 0644);
                @chmod(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP."/{$nameHash}_installer.php"), 0644);
                @chmod(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH."/{$nameHash}_archive.zip"), 0644);
                @chmod(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH."/{$nameHash}_database.sql"), 0644);
                @chmod(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH."/{$nameHash}_installer.php"), 0644);
                @chmod(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH."/{$nameHash}_scan.json"), 0644);
                @chmod(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH."/{$nameHash}.log"), 0644);
                //Remove
                @unlink(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP."/{$nameHash}_archive.zip"));
                @unlink(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP."/{$nameHash}_database.sql"));
                @unlink(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP."/{$nameHash}_installer.php"));
                @unlink(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH."/{$nameHash}_archive.zip"));
                @unlink(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH."/{$nameHash}_database.sql"));
                @unlink(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH."/{$nameHash}_installer.php"));
                @unlink(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH."/{$nameHash}_scan.json"));
                @unlink(DUP_Util::safePath(DUPLICATOR_SSDIR_PATH."/{$nameHash}.log"));
            }
        }
            
    }

	public function runDupArchiveBuildIntegrityCheck()
    {
        //INTEGRITY CHECKS
        //We should not rely on data set in the serlized object, we need to manually check each value
        //indepentantly to have a true integrity check.
        DUP_Log::info("\n********************************************************************************");
        DUP_Log::info("INTEGRITY CHECKS:");
        DUP_Log::info("********************************************************************************");

        //------------------------
        //SQL CHECK:  File should be at minimum 5K.  A base WP install with only Create tables is about 9K
        $sql_temp_path = DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP . '/' . $this->Database->File);
        $sql_temp_size = @filesize($sql_temp_path);
        $sql_easy_size = DUP_Util::byteSize($sql_temp_size);
        $sql_done_txt = DUP_Util::tailFile($sql_temp_path, 3);
        DUP_Log::Trace('rundupa1');
        if (!strstr($sql_done_txt, 'DUPLICATOR_MYSQLDUMP_EOF') || $sql_temp_size < 5120) {
            DUP_Log::Trace('rundupa2');
            
            $error_text = "ERROR: SQL file not complete.  The file {$sql_temp_path} looks too small ($sql_temp_size bytes) or the end of file marker was not found.";

            $this->BuildProgress->set_failed($error_text);
            $this->update();
            //$this->setStatus(DUP_PackageStatus::ERROR);
           
            DUP_Log::Error("$error_text", '', Dup_ErrorBehavior::LogOnly);

            return;
        }

        DUP_Log::Trace('rundupa3');
        DUP_Log::Info("SQL FILE: {$sql_easy_size}");

        //------------------------
        //INSTALLER CHECK:
        $exe_temp_path = DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP . '/' . $this->Installer->File);

        $exe_temp_size = @filesize($exe_temp_path);
        $exe_easy_size = DUP_Util::byteSize($exe_temp_size);
        $exe_done_txt = DUP_Util::tailFile($exe_temp_path, 10);

        if (!strstr($exe_done_txt, 'DUPLICATOR_INSTALLER_EOF') && !$this->BuildProgress->failed) {
            //$this->BuildProgress->failed = true;
            $error_message = 'ERROR: Installer file not complete.  The end of file marker was not found.  Please try to re-create the package.';

            $this->BuildProgress->set_failed($error_message);
            $this->update();
            //$this->setStatus(DUP_PackageStatus::ERROR);
            DUP_Log::error($error_message, '', Dup_ErrorBehavior::LogOnly);
            return;
        }
        DUP_Log::info("INSTALLER FILE: {$exe_easy_size}");

        //------------------------
        //ARCHIVE CHECK:
        DUP_LOG::trace("Archive file count is " . $this->Archive->file_count);

        if ($this->Archive->file_count != -1) {
            $zip_easy_size = DUP_Util::byteSize($this->Archive->Size);
            if (!($this->Archive->Size)) {
                //$this->BuildProgress->failed = true;
                $error_message = "ERROR: The archive file contains no size.";

                $this->BuildProgress->set_failed($error_message);
                $this->update();
                //$this->setStatus(DUP_PackageStatus::ERROR);
                DUP_Log::error($error_message, "Archive Size: {$zip_easy_size}", Dup_ErrorBehavior::LogOnly);
                return;
            }

            $scan_filepath = DUPLICATOR_SSDIR_PATH_TMP . "/{$this->NameHash}_scan.json";

            $json = '';

            DUP_LOG::Trace("***********Does $scan_filepath exist?");
            if (file_exists($scan_filepath)) {
                $json = file_get_contents($scan_filepath);
            } else {
                $error_message = sprintf(__("Can't find Scanfile %s. Please ensure there no non-English characters in the package or schedule name.", 'duplicator'), $scan_filepath);

                //$this->BuildProgress->failed = true;
                $this->setStatus(DUP_PackageStatus::ERROR);
                $this->BuildProgress->set_failed($error_message);
                $this->update();

                DUP_Log::Error($error_message, '', Dup_ErrorBehavior::LogOnly);
                return;
            }

            $scanReport = json_decode($json);
			//RSR TODO: rework/simplify the validateion of duparchive
//            $expected_filecount = $scanReport->ARC->UDirCount + $scanReport->ARC->UFileCount;

            $dirCount = count($scanReport->ARC->Dirs);
            $numInstallerDirs = $this->Installer->numDirsAdded;
            $fileCount = count($scanReport->ARC->Files);
            $numInstallerFiles = $this->Installer->numFilesAdded;

            $expected_filecount = $dirCount + $numInstallerDirs + $fileCount + $numInstallerFiles + 1 -1;   // Adding database.sql but subtracting the root dir

        //    Dup_Log::trace("#### a:{$dirCount} b:{$numInstallerDirs} c:{$fileCount} d:{$numInstallerFiles} = {$expected_filecount}");
            

         //   Dup_Log::traceObject('####scan report', $scanReport);
      //      Dup_Log::traceObject('####package', $this);
            //DUP_Log::traceObject("#### scan report", $scanReport);
            DUP_Log::info("ARCHIVE FILE: {$zip_easy_size} ");
            DUP_Log::info(sprintf(__('EXPECTED FILE/DIRECTORY COUNT: %1$s', 'duplicator'), number_format($expected_filecount)));
            DUP_Log::info(sprintf(__('ACTUAL FILE/DIRECTORY COUNT: %1$s', 'duplicator'), number_format($this->Archive->file_count)));

            $this->ExeSize = $exe_easy_size;
            $this->ZipSize = $zip_easy_size;

            /* ------- ZIP Filecount Check -------- */
            // Any zip of over 500 files should be within 2% - this is probably too loose but it will catch gross errors
            DUP_LOG::trace("Expected filecount = $expected_filecount and archive filecount=" . $this->Archive->file_count);

            if ($expected_filecount > 500) {
                $straight_ratio = (float) $expected_filecount / (float) $this->Archive->file_count;

				$warning_count = $scanReport->ARC->WarnFileCount + $scanReport->ARC->WarnDirCount + $scanReport->ARC->UnreadableFileCount + $scanReport->ARC->UnreadableDirCount;

                DUP_LOG::trace("Warn/unread counts) warnfile:{$scanReport->ARC->WarnFileCount} warndir:{$scanReport->ARC->WarnDirCount} unreadfile:{$scanReport->ARC->UnreadableFileCount} unreaddir:{$scanReport->ARC->UnreadableDirCount}");

                $warning_ratio = ((float) ($expected_filecount + $warning_count)) / (float) $this->Archive->file_count;

                DUP_LOG::trace("Straight ratio is $straight_ratio and warning ratio is $warning_ratio. # Expected=$expected_filecount # Warning=$warning_count and #Archive File {$this->Archive->file_count}");

                // Allow the real file count to exceed the expected by 10% but only allow 1% the other way
                if (($straight_ratio < 0.90) || ($straight_ratio > 1.01)) {
                    // Has to exceed both the straight as well as the warning ratios
                    if (($warning_ratio < 0.90) || ($warning_ratio > 1.01)) {

                        $error_message = sprintf('ERROR: File count in archive vs expected suggests a bad archive (%1$d vs %2$d).', $archive_file_count, $expected_filecount);

                        //$this->BuildProgress->failed = true;
                        $this->BuildProgress->set_failed($error_message);
                        $this->update();
                        //$this->setStatus(DUP_PackageStatus::ERROR);

                        $archive_file_count = $this->Archive->file_count;
                       
                        DUP_Log::error($error_message, '', Dup_ErrorBehavior::LogOnly);
                        return;
                    }
                }
            }
        }
    }

     public function getLocalPackageFile($file_type)
    {        
        $file_path = null;

        if ($file_type == DUP_PackageFileType::Installer) {
            DUP_Log::Trace("Installer requested");
            $file_name = $this->get_installer_filename();
        } else if ($file_type == DUP_PackageFileType::Archive) {
            DUP_Log::Trace("Archive requested");
            $file_name = $this->get_archive_filename();
        } else if ($file_type == DUP_PackageFileType::SQL) {
            DUP_Log::Trace("SQL requested");
            $file_name = $this->get_database_filename();        
        } else {
            DUP_Log::Trace("Log requested");
            $file_name = $this->get_log_filename();
        }

        $file_path = Dup_Util::safePath(DUPLICATOR_SSDIR_PATH) . "/$file_name";

        DUP_Log::Trace("File path $file_path");

        if (file_exists($file_path)) {
            return $file_path;
        } else {
            return null;
        }
    }

    public function get_scan_filename()
    {
        return $this->NameHash . '_scan.json';
    }

    public function get_log_filename()
    {
        return $this->NameHash . '.log';
    }

    public function get_dump_filename()
    {
        return $this->NameHash . '_dump.txt';
    }

    public function get_archive_filename()
    {
        $extension = strtolower($this->Archive->Format);

        return "{$this->NameHash}_archive.{$extension}";
    }

    public function get_installer_filename()
    {
        return "{$this->NameHash}_installer.php";
    }

    public function get_database_filename()
    {
        return $this->NameHash . '_database.sql';
    }

    public static function safeTmpCleanup($purge_temp_archives = false)
    {
        if ($purge_temp_archives) {
            $dir = DUPLICATOR_SSDIR_PATH_TMP . "/*_archive.zip.*";
            foreach (glob($dir) as $file_path) {
                unlink($file_path);
            }
            $dir = DUPLICATOR_SSDIR_PATH_TMP . "/*_archive.daf.*";
            foreach (glob($dir) as $file_path) {
                unlink($file_path);
            }
        } else {
            //Remove all temp files that are 24 hours old
            $dir = DUPLICATOR_SSDIR_PATH_TMP . "/*";

            $files = glob($dir);

            if ($files !== false) {
                foreach ($files as $file_path) {
                    // Cut back to keeping things around for just an hour 15 min
                    if (filemtime($file_path) <= time() - DUPLICATOR_TEMP_CLEANUP_SECONDS) {
                        unlink($file_path);
                    }
                }
            }
        }
    }

	/**
     * Starts the package DupArchive progressive build process - always assumed to only run off active package, NOT one in the package table
     *
     * @return obj Returns a DUP_Package object
     */
	public function runDupArchiveBuild()
    {
        global $wp_version;
        global $wpdb;
        global $current_user;
		
        $this->BuildProgress->start_timer();
        
        DUP_Log::Trace('Called');

        if ($this->BuildProgress->failed) {

            DUP_LOG::Trace("build progress failed so setting package to failed");
            $this->setStatus(DUP_PackageStatus::ERROR);

            $message = "Package creation failed.";

            DUP_Log::Trace($message);

            return true;
        }
        
        if ($this->BuildProgress->initialized == false) {

            DUP_Log::Trace('Initializing');

            $this->BuildProgress->initialized = true;

            $this->TimerStart = Dup_Util::getMicrotime();
			
            $this->update();
        }       

        //START BUILD
        if (!$this->BuildProgress->database_script_built) {
             DUP_Log::Trace('Building database script');
       
            $this->Database->build($this, Dup_ErrorBehavior::ThrowException);
            $this->BuildProgress->database_script_built = true;
            $this->update();
            DUP_LOG::Trace("Built database script");
        } else if (!$this->BuildProgress->archive_built) {
             DUP_Log::Trace('e');
       
            $this->Archive->build($this);
            $this->update();
        } else if (!$this->BuildProgress->installer_built) {

             DUP_Log::Trace('f');       
             // Installer being built is stuffed into the archive build phase
        }

        if ($this->BuildProgress->has_completed()) {

            DUP_Log::Trace('c');

            if (!$this->BuildProgress->failed) {
				DUP_LOG::trace("top of loop build progress not failed");
                // Only makees sense to perform build integrity check on completed archives
                $this->runDupArchiveBuildIntegrityCheck();
            } else {
				DUP_LOG::trace("top of loop build progress failed");
			}

            $timerEnd = DUP_Util::getMicrotime();
            $timerSum = DUP_Util::elapsedTime($timerEnd, $this->TimerStart);
            $this->Runtime = $timerSum;

            //FINAL REPORT
            $info = "\n********************************************************************************\n";
            $info .= "RECORD ID:[{$this->ID}]\n";
            $info .= "TOTAL PROCESS RUNTIME: {$timerSum}\n";
            $info .= "PEAK PHP MEMORY USED: " . DUP_Server::getPHPMemory(true) . "\n";
            $info .= "DONE PROCESSING => {$this->Name} " . @date("Y-m-d H:i:s") . "\n";

            DUP_Log::info($info);
            DUP_LOG::trace("Done package building");

            if (!$this->BuildProgress->failed) {
                
                $this->setStatus(DUP_PackageStatus::COMPLETE);
				DUP_LOG::Trace("Cleaning up duparchive temp files");
                //File Cleanup
                $this->buildCleanup();
            }
        }

        DUP_Log::Close();

        return $this->BuildProgress->has_completed();
    }

    /**
     * Starts the package build process
     *
     * @return obj Returns a DUP_Package object
     */
    public function runZipBuild()
    {
        global $wp_version;
        global $wpdb;
        global $current_user;

        $timerStart = DUP_Util::getMicrotime();


        DUP_Log::Trace('#### start of zip build');
        //START BUILD
        //PHPs serialze method will return the object, but the ID above is not passed
        //for one reason or another so passing the object back in seems to do the trick
        $this->Database->build($this);
        $this->Archive->build($this);
        $this->Installer->build($this);

        //INTEGRITY CHECKS
        DUP_Log::Info("\n********************************************************************************");
        DUP_Log::Info("INTEGRITY CHECKS:");
        DUP_Log::Info("********************************************************************************");
        $dbSizeRead  = DUP_Util::byteSize($this->Database->Size);
        $zipSizeRead = DUP_Util::byteSize($this->Archive->Size);
        $exeSizeRead = DUP_Util::byteSize($this->Installer->Size);

        DUP_Log::Info("SQL File: {$dbSizeRead}");
        DUP_Log::Info("Installer File: {$exeSizeRead}");
        DUP_Log::Info("Archive File: {$zipSizeRead} ");

        if (!($this->Archive->Size && $this->Database->Size && $this->Installer->Size)) {
            DUP_Log::Error("A required file contains zero bytes.", "Archive Size: {$zipSizeRead} | SQL Size: {$dbSizeRead} | Installer Size: {$exeSizeRead}");
        }

        //Validate SQL files completed
        $sql_tmp_path     = DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP.'/'.$this->Database->File);
        $sql_complete_txt = DUP_Util::tailFile($sql_tmp_path, 3);
        if (!strstr($sql_complete_txt, 'DUPLICATOR_MYSQLDUMP_EOF')) {
            DUP_Log::Error("ERROR: SQL file not complete.  The end of file marker was not found.  Please try to re-create the package.");
        }

        $timerEnd = DUP_Util::getMicrotime();
        $timerSum = DUP_Util::elapsedTime($timerEnd, $timerStart);

        $this->Runtime = $timerSum;
        $this->ExeSize = $exeSizeRead;
        $this->ZipSize = $zipSizeRead;

        $this->buildCleanup();

        //FINAL REPORT
        $info = "\n********************************************************************************\n";
        $info .= "RECORD ID:[{$this->ID}]\n";
        $info .= "TOTAL PROCESS RUNTIME: {$timerSum}\n";
        $info .= "PEAK PHP MEMORY USED: ".DUP_Server::getPHPMemory(true)."\n";
        $info .= "DONE PROCESSING => {$this->Name} ".@date(get_option('date_format')." ".get_option('time_format'))."\n";

        DUP_Log::Info($info);
        DUP_Log::Close();

        $this->setStatus(DUP_PackageStatus::COMPLETE);
        return $this;
    }


	private function writeLogHeader()
	{
		$php_max_time   = @ini_get("max_execution_time");
        $php_max_memory = @ini_set('memory_limit', DUPLICATOR_PHP_MAX_MEMORY);
        $php_max_time   = ($php_max_time == 0) ? "(0) no time limit imposed" : "[{$php_max_time}] not allowed";
        $php_max_memory = ($php_max_memory === false) ? "Unabled to set php memory_limit" : DUPLICATOR_PHP_MAX_MEMORY." ({$php_max_memory} default)";

		$info = "********************************************************************************\n";
        $info .= "DUPLICATOR-LITE PACKAGE-LOG: ".@date(get_option('date_format')." ".get_option('time_format'))."\n";
        $info .= "NOTICE: Do NOT post to public sites or forums \n";
        $info .= "********************************************************************************\n";
        $info .= "VERSION:\t".DUPLICATOR_VERSION."\n";
        $info .= "WORDPRESS:\t{$GLOBALS['wp_version']}\n";
        $info .= "PHP INFO:\t".phpversion().' | '.'SAPI: '.php_sapi_name()."\n";
        $info .= "SERVER:\t\t{$_SERVER['SERVER_SOFTWARE']} \n";
        $info .= "PHP TIME LIMIT: {$php_max_time} \n";
        $info .= "PHP MAX MEMORY: {$php_max_memory} \n";
        $info .= "MEMORY STACK: ".DUP_Server::getPHPMemory();
        DUP_Log::Info($info);

        $info = null;
	}
	
    /**
     *  Saves the active options associted with the active(latest) package.
     *
     *  @see DUP_Package::getActive
     *
     *  @param $_POST $post The Post server object
     * 
     *  @return null
     */
    public function saveActive($post = null)
    {
        global $wp_version;

        if (isset($post)) {
            $post = stripslashes_deep($post);

            $name       = ( isset($post['package-name']) && !empty($post['package-name'])) ? sanitize_text_field($post['package-name']) : self::getDefaultName();
            $name       = substr(sanitize_file_name($name), 0, 40);
            $name       = str_replace(array('.', '-', ';', ':', "'", '"'), '', $name);

            if (isset($post['filter-dirs'])) {
                $post_filter_dirs = sanitize_text_field($post['filter-dirs']);
                $filter_dirs  = $this->Archive->parseDirectoryFilter($post_filter_dirs);
            } else {
                $filter_dirs  = '';
            }

            if (isset($post['filter-dirs'])) {
                $post_filter_dirs = sanitize_text_field($post['filter-dirs']);
                $filter_dirs  = $this->Archive->parseDirectoryFilter($post_filter_dirs);
            } else {
                $filter_dirs  = '';
            }
            
            if (isset($post['filter-files'])) {
                $post_filter_files = sanitize_text_field($post['filter-files']);
                $filter_files = $this->Archive->parseFileFilter($post_filter_files);
            } else {
                $filter_files = '';
            }
            
            if (isset($post['filter-exts'])) {
                $post_filter_exts = sanitize_text_field($post['filter-exts']);
                $filter_exts  = $this->Archive->parseExtensionFilter($post_filter_exts);
            } else {
                $filter_exts  = '';
            }
            
            if (isset($post['dbtables'])) {
                $post_dbtables = sanitize_text_field($post['dbtables']);
                $tablelist    = implode(',', $post_dbtables);
            } else {
                $tablelist    = '';
            }
            
            if (isset($post['dbcompat'])) {
                $post_dbcompat = sanitize_text_field($post['dbcompat']);
                $compatlist = isset($post['dbcompat'])	 ? implode(',', $post_dbcompat) : '';
            } else {
                $compatlist = '';
            }
            
            $dbversion    = DUP_DB::getVersion();
            $dbversion    = is_null($dbversion) ? '- unknown -'  : sanitize_text_field($dbversion);
            $dbcomments   = sanitize_text_field(DUP_DB::getVariable('version_comment'));
            $dbcomments   = is_null($dbcomments) ? '- unknown -' : sanitize_text_field($dbcomments);


            //PACKAGE
            $this->Created    = date("Y-m-d H:i:s");
            $this->Version    = DUPLICATOR_VERSION;
            $this->VersionOS  = defined('PHP_OS') ? PHP_OS : 'unknown';
            $this->VersionWP  = $wp_version;
            $this->VersionPHP = phpversion();
            $this->VersionDB  = sanitize_text_field($dbversion);
            $this->Name       = sanitize_text_field($name);
            $this->Hash       = $this->makeHash();
            $this->NameHash   = sanitize_text_field("{$this->Name}_{$this->Hash}");

            $this->Notes                    = sanitize_textarea_field($post['package-notes']);
            //ARCHIVE
            $this->Archive->PackDir         = rtrim(DUPLICATOR_WPROOTPATH, '/');
            $this->Archive->Format          = 'ZIP';
            $this->Archive->FilterOn        = isset($post['filter-on']) ? 1 : 0;
			$this->Archive->ExportOnlyDB    = isset($post['export-onlydb']) ? 1 : 0;
            $this->Archive->FilterDirs      = sanitize_textarea_field($filter_dirs);
			$this->Archive->FilterFiles    = sanitize_textarea_field($filter_files);
            $this->Archive->FilterExts      = str_replace(array('.', ' '), '', $filter_exts);
            //INSTALLER
            $this->Installer->OptsDBHost		= sanitize_text_field($post['dbhost']);
            $this->Installer->OptsDBPort		= sanitize_text_field($post['dbport']);
            $this->Installer->OptsDBName		= sanitize_text_field($post['dbname']);
            $this->Installer->OptsDBUser		= sanitize_text_field($post['dbuser']);
            $this->Installer->OptsSecureOn		= isset($post['secure-on']) ? 1 : 0;
            $post_secure_pass                   = sanitize_text_field($post['secure-pass']);
			$this->Installer->OptsSecurePass	= DUP_Util::installerScramble($post_secure_pass);
            //DATABASE
            $this->Database->FilterOn       = isset($post['dbfilter-on']) ? 1 : 0;
            $this->Database->FilterTables   = sanitize_text_field($tablelist);
            $this->Database->Compatible     = $compatlist;
            $this->Database->Comments       = sanitize_text_field($dbcomments);

            update_option(self::OPT_ACTIVE, $this);
        }
    }

    public function update()
    {
        global $wpdb;

		//DUP_Log::TraceObject('Backtrace', debug_backtrace());
		
        $packageObj = serialize($this);

        if (!$packageObj) {
            DUP_Log::Error("Package SetStatus was unable to serialize package object while updating record.");
        }

        $wpdb->flush();
        $table = $wpdb->prefix."duplicator_packages";
        $sql  = "UPDATE `{$table}` SET  status = {$this->Status},";
        $sql .= "package = '" . esc_sql($packageObj) . "'";
        $sql .= "WHERE ID = {$this->ID}";
     
        DUP_Log::Trace('-------------------------');
        DUP_Log::Trace("status = {$this->Status}");
        DUP_Log::Trace("ID = {$this->ID}");
        DUP_Log::Trace('-------------------------');

        //DUP_Log::Trace('####Executing SQL' . $sql . '-----------');
        $wpdb->query($sql);
    }

    /**
     * Save any property of this class through reflection
     *
     * @param $property     A valid public property in this class
     * @param $value        The value for the new dynamic property
     *
     * @return null
     */
    public function saveActiveItem($property, $value)
    {
        $package = self::getActive();

        $reflectionClass = new ReflectionClass($package);
        $reflectionClass->getProperty($property)->setValue($package, $value);
        update_option(self::OPT_ACTIVE, $package);
    }

    /**
     * Sets the status to log the state of the build
     *
     * @param $status The status level for where the package is
     *
     * @return void
     */
    public function setStatus($status)
    {
        if (!isset($status)) {
            DUP_Log::Error("Package SetStatus did not receive a proper code.");
        }
        
        $this->Status = $status;

        $this->update();
    }

    /**
     * Does a hash already exisit
     *
     * @param string $hash An existing hash value
     *
     * @return int Returns 0 if no hash is found, if found returns the table ID
     */
    public function getHashKey($hash)
    {
        global $wpdb;

        $table = $wpdb->prefix."duplicator_packages";
        $qry   = $wpdb->get_row("SELECT ID, hash FROM `{$table}` WHERE hash = '{$hash}'");
        if (is_null($qry) || strlen($qry->hash) == 0) {
            return 0;
        } else {
            return $qry->ID;
        }
    }

    /**
     * Makes the hashkey for the package files
     *
     * @return string A unique hashkey
     */
    public function makeHash()
    {
        try {
            if (function_exists('random_bytes') && DUP_Util::PHP53()) {
                return bin2hex(random_bytes(8)) . mt_rand(1000, 9999) . '_' . date("YmdHis");
            } else {
                return strtolower(md5(uniqid(rand(), true))) . '_' . date("YmdHis");
            }
        } catch (Exception $exc) {
            return strtolower(md5(uniqid(rand(), true))) . '_' . date("YmdHis");
        }
    }

    /**
     * Gets the active package which is defined as the package that was lasted saved.
     * Do to cache issues with the built in WP function get_option moved call to a direct DB call.
     *
     * @see DUP_Package::saveActive
     *
     * @return obj  A copy of the DUP_Package object
     */
    public static function getActive()
    {
        global $wpdb;

        $obj = new DUP_Package();
        $row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM `{$wpdb->options}` WHERE option_name = %s LIMIT 1", self::OPT_ACTIVE));
        if (is_object($row)) {
            $obj = @unserialize($row->option_value);
        }
        //Incase unserilaize fails
        $obj = (is_object($obj)) ? $obj : new DUP_Package();
	
        return $obj;
    }

    /**
     * Gets the Package by ID
     *  
     * @param int $id A valid package id form the duplicator_packages table
     *
     * @return obj  A copy of the DUP_Package object
     */
    public static function getByID($id)
    {

        global $wpdb;
        $obj = new DUP_Package();

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}duplicator_packages` WHERE ID = %s", $id));
        if (is_object($row)) {
            $obj         = @unserialize($row->package);
            $obj->Status = $row->status;
        }
        //Incase unserilaize fails
        $obj = (is_object($obj)) ? $obj : null;
        return $obj;
    }

    /**
     *  Gets a default name for the package
     *
     *  @return string   A default package name such as 20170218_blogname
     */
    public static function getDefaultName($preDate = true)
    {
        //Remove specail_chars from final result
        $special_chars = array(".", "-");
        $name          = ($preDate) 
							? date('Ymd') . '_' . sanitize_title(get_bloginfo('name', 'display'))
							: sanitize_title(get_bloginfo('name', 'display')) . '_' . date('Ymd');
        $name          = substr(sanitize_file_name($name), 0, 40);
        $name          = str_replace($special_chars, '', $name);
        return $name;
    }

    /**
     *  Cleanup all tmp files
     *
     *  @param all empty all contents
     *
     *  @return null
     */
    public static function tempFileCleanup($all = false)
    {
        //Delete all files now
        if ($all) {
            $dir = DUPLICATOR_SSDIR_PATH_TMP."/*";
            foreach (glob($dir) as $file) {
                @unlink($file);
            }
        }
        //Remove scan files that are 24 hours old
        else {
            $dir = DUPLICATOR_SSDIR_PATH_TMP."/*_scan.json";
            foreach (glob($dir) as $file) {
                if (filemtime($file) <= time() - 86400) {
                    @unlink($file);
                }
            }
        }
    }

    /**
     *  Provides various date formats
     * 
     *  @param $date    The date to format
     *  @param $format  Various date formats to apply
     * 
     *  @return a formated date based on the $format
     */
    public static function getCreatedDateFormat($date, $format = 1)
    {
        $date = new DateTime($date);
        switch ($format) {
            //YEAR
            case 1: return $date->format('Y-m-d H:i');
                break;
            case 2: return $date->format('Y-m-d H:i:s');
                break;
            case 3: return $date->format('y-m-d H:i');
                break;
            case 4: return $date->format('y-m-d H:i:s');
                break;
            //MONTH
            case 5: return $date->format('m-d-Y H:i');
                break;
            case 6: return $date->format('m-d-Y H:i:s');
                break;
            case 7: return $date->format('m-d-y H:i');
                break;
            case 8: return $date->format('m-d-y H:i:s');
                break;
            //DAY
            case 9: return $date->format('d-m-Y H:i');
                break;
            case 10: return $date->format('d-m-Y H:i:s');
                break;
            case 11: return $date->format('d-m-y H:i');
                break;
            case 12: return $date->format('d-m-y H:i:s');
                break;
            default :
                return $date->format('Y-m-d H:i');
        }
    }

    /**
     *  Cleans up all the tmp files as part of the package build process
     */
    public function buildCleanup()
    {

        $files   = DUP_Util::listFiles(DUPLICATOR_SSDIR_PATH_TMP);
        $newPath = DUPLICATOR_SSDIR_PATH;

        if (function_exists('rename')) {
            foreach ($files as $file) {
                $name = basename($file);
                if (strstr($name, $this->NameHash)) {
                    rename($file, "{$newPath}/{$name}");
                }
            }
        } else {
            foreach ($files as $file) {
                $name = basename($file);
                if (strstr($name, $this->NameHash)) {
                    copy($file, "{$newPath}/{$name}");
                    @unlink($file);
                }
            }
        }
    }

    /**
     * Get package hash
     * 
     * @return string package hash
     */
    public function get_package_hash() {
        $hashParts = explode('_', $this->Hash);
        $firstPart = substr($hashParts[0], 0, 7);
        $secondPart = substr($hashParts[1], -8);
        $package_hash = $firstPart.'-'.$secondPart;
        return $package_hash;
    }

    /**
     *  Provides the full sql file path in archive
     *
     *  @return the full sql file path in archive
     */
    public function get_sql_ark_file_path()
    {
        $package_hash = $this->get_package_hash();
        $sql_ark_file_Path = 'dup-installer/dup-database__'.$package_hash.'.sql';
        return $sql_ark_file_Path;
    }    
}
?>
