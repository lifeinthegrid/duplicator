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
    public $next_archive_file_index = 0;
    public $next_archive_dir_index = 0;
    public $retries = 0;
    public $current_build_mode = -1;
	public $current_build_compression = true;
    public $custom_data = null;
    public $warnings = array();

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
}

final class DUP_PackageStatus
{
    private function __construct()
    {
    }

	const ERROR = -1;
    const START    = 10;
    const DBSTART  = 20;
    const DBDONE   = 30;
    const ARCSTART = 40;
    const ARCDONE  = 50;
	const ARCVALIDATION = 60;
    const COMPLETE = 100;

}

final class DUP_PackageType
{
    const MANUAL    = 0;
    const SCHEDULED = 1;

}

/**
 * Class used to store and process all Package logic
 *
 * @package Dupicator\classes
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
    public $Type;
    public $Notes;
    public $StorePath;
    public $StoreURL;
    public $ScanFile;
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
		$this->BuildProgress = new DUP_Build_Progress();
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
        $report['ARC']['Status']['Size']  = ($this->Archive->Size > DUPLICATOR_SCAN_SIZE_DEFAULT) ? 'Warn' : 'Good';
        $report['ARC']['Status']['Names'] = (count($this->Archive->FilterInfo->Files->Warning) + count($this->Archive->FilterInfo->Dirs->Warning)) ? 'Warn' : 'Good';
        //$report['ARC']['Status']['Big']   = count($this->Archive->FilterInfo->Files->Size) ? 'Warn' : 'Good';
        $report['ARC']['Dirs']  = $this->Archive->Dirs;
        $report['ARC']['Files'] = $this->Archive->Files;
		$report['ARC']['Status']['AddonSites'] = count($this->Archive->FilterInfo->Dirs->AddonSites) ? 'Warn' : 'Good';
            


        //DATABASE
        $db  = $this->Database->getScannerData();
        $report['DB'] = $db;

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

	private function insertNewPackageForBuilding($extension)
	{
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
			DUP_Log::Error("Unable to serialize pacakge object while building record.");
		}

		$this->ID = $this->getHashKey($this->Hash);

		if ($this->ID != 0) {
			DUP_LOG::Trace("ID non zero so setting to start");
			$this->set_status(DUP_PRO_PackageStatus::START);
		} else {
			DUP_LOG::Trace("ID IS zero so creating another package");
			$results = $wpdb->insert($wpdb->base_prefix . "duplicator_packages", array(
				'name' => $this->Name,
				'hash' => $this->Hash,
				'status' => DUP_PRO_PackageStatus::START,
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

	public function runDupArchiveBuildIntegrityCheck()
    {
        //INTEGRITY CHECKS
        //We should not rely on data set in the serlized object, we need to manually check each value
        //indepentantly to have a true integrity check.
        DUP_PRO_Log::info("\n********************************************************************************");
        DUP_PRO_Log::info("INTEGRITY CHECKS:");
        DUP_PRO_Log::info("********************************************************************************");

        //------------------------
        //SQL CHECK:  File should be at minimum 5K.  A base WP install with only Create tables is about 9K
        $sql_temp_path = DUP_PRO_U::safePath(DUPLICATOR_PRO_SSDIR_PATH_TMP . '/' . $this->Database->File);
        $sql_temp_size = @filesize($sql_temp_path);
        $sql_easy_size = DUP_PRO_U::byteSize($sql_temp_size);
        $sql_done_txt = DUP_PRO_U::tailFile($sql_temp_path, 3);
        if (!strstr($sql_done_txt, 'DUPLICATOR_PRO_MYSQLDUMP_EOF') || $sql_temp_size < 5120) {
            $this->build_progress->failed = true;
            $this->update();
            $this->set_status(DUP_PRO_PackageStatus::ERROR);

            $error_text = "ERROR: SQL file not complete.  The file looks too small ($sql_temp_size bytes) or the end of file marker was not found.";

            DUP_Log::Error("$error_text  **RECOMMENDATION: $fix_text", '', false);

            return;
        }

        DUP_Log::Info("SQL FILE: {$sql_easy_size}");

        //------------------------
        //INSTALLER CHECK:
        $exe_temp_path = DUP_Util::safePath(DUPLICATOR_PRO_SSDIR_PATH_TMP . '/' . $this->Installer->File);
        $exe_temp_size = @filesize($exe_temp_path);
        $exe_easy_size = DUP_Util::byteSize($exe_temp_size);
        $exe_done_txt = DUP_Util::tailFile($exe_temp_path, 10);

        if (!strstr($exe_done_txt, 'DUPLICATOR_INSTALLER_EOF') && !$this->build_progress->failed) {
            $this->build_progress->failed = true;
            $this->update();
            $this->set_status(DUP_PRO_PackageStatus::ERROR);
            DUP_Log::error("ERROR: Installer file not complete.  The end of file marker was not found.  Please try to re-create the package.", '', false);
            return;
        }
        DUP_PRO_Log::info("INSTALLER FILE: {$exe_easy_size}");

        //------------------------
        //ARCHIVE CHECK:
        DUP_PRO_LOG::trace("Archive file count is " . $this->Archive->file_count);

        if ($this->Archive->file_count != -1) {
            $zip_easy_size = DUP_Util::byteSize($this->Archive->Size);
            if (!($this->Archive->Size)) {
                $this->build_progress->failed = true;
                $this->update();
                $this->set_status(DUP_PRO_PackageStatus::ERROR);
                DUP_Log::error("ERROR: The archive file contains no size.", "Archive Size: {$zip_easy_size}", false);
                return;
            }

            $scan_filepath = DUPLICATOR_SSDIR_PATH_TMP . "/{$this->NameHash}_scan.json";

            $json = '';

            DUP_LOG::trace("***********Does $scan_filepath exist?");
            if (file_exists($scan_filepath)) {
                $json = file_get_contents($scan_filepath);
            } else {
                $error_message = sprintf(__("Can't find Scanfile %s. Please ensure there no non-English characters in the package or schedule name.", 'duplicator'), $scan_filepath);

                $this->build_progress->failed = true;
                $this->set_status(DUP_PRO_PackageStatus::ERROR);
                $this->update();

                DUP_Log::Error($error_message, '', false);
                return;
            }

            $scanReport = json_decode($json);
			//RSR TODO: rework/simplify the validateion of duparchive
            $expected_filecount = $scanReport->ARC->UDirCount + $scanReport->ARC->UFileCount;

            DUP_Log::info("ARCHIVE FILE: {$zip_easy_size} ");
            DUP_Log::info(sprintf(DUP_PRO_U::__('EXPECTED FILE/DIRECTORY COUNT: %1$s'), number_format($expected_filecount)));
            DUP_Log::info(sprintf(DUP_PRO_U::__('ACTUAL FILE/DIRECTORY COUNT: %1$s'), number_format($this->Archive->file_count)));

            $this->ExeSize = $exe_easy_size;
            $this->ZipSize = $zip_easy_size;

            /* ------- ZIP Filecount Check -------- */
            // Any zip of over 500 files should be within 2% - this is probably too loose but it will catch gross errors
            DUP_PRO_LOG::trace("Expected filecount = $expected_filecount and archive filecount=" . $this->Archive->file_count);

            if ($expected_filecount > 500) {
                $straight_ratio = (float) $expected_filecount / (float) $this->Archive->file_count;

				$warning_count = $scanReport->ARC->WarnFileCount + $scanReport->ARC->WarnDirCount + $scanReport->ARC->UnreadableFileCount + $scanReport->ARC->UnreadableDirCount;

                DUP_PRO_LOG::trace("Warn/unread counts) warnfile:{$scanReport->ARC->WarnFileCount} warndir:{$scanReport->ARC->WarnDirCount} unreadfile:{$scanReport->ARC->UnreadableFileCount} unreaddir:{$scanReport->ARC->UnreadableDirCount}");

                $warning_ratio = ((float) ($expected_filecount + $warning_count)) / (float) $this->Archive->file_count;

                DUP_PRO_LOG::trace("Straight ratio is $straight_ratio and warning ratio is $warning_ratio. # Expected=$expected_filecount # Warning=$warning_count and #Archive File {$this->Archive->file_count}");

                // Allow the real file count to exceed the expected by 10% but only allow 1% the other way
                if (($straight_ratio < 0.90) || ($straight_ratio > 1.01)) {
                    // Has to exceed both the straight as well as the warning ratios
                    if (($warning_ratio < 0.90) || ($warning_ratio > 1.01)) {
                        $this->build_progress->failed = true;
                        $this->update();
                        $this->set_status(DUP_PRO_PackageStatus::ERROR);

                        $archive_file_count = $this->Archive->file_count;

                        $error_message = sprintf('ERROR: File count in archive vs expected suggests a bad archive (%1$d vs %2$d).', $archive_file_count, $expected_filecount);

                        DUP_Log::error($error_message, '', false);
                        return;
                    }
                }
            }
        }
    }

	public static function SafeTmpCleanup($purge_temp_archives = false)
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
     * Starts the package DupArchive progressive build process
     *
     * @return obj Returns a DUP_Package object
     */
	public function runDupArchiveBuild()
    {
        global $wp_version;
        global $wpdb;
        global $current_user;
		
        /* @var $global DUP_PRO_Global_Entity */
        $global = DUP_PRO_Global_Entity::get_instance();

        $this->build_progress->start_timer();

        if ($this->build_progress->initialized == false) {

            DUP_LOG::TraceObject("**** START OF BUILD ****", $this);

            $timerStart = DUP_Util::getMicrotime();

			$this->insertNewPackageForBuilding('daf');

            $this->build_progress->initialized = true;
            $this->update();
        }

        // Note: Think that by putting has_completed() at top of check will prevent archive from continuing to build after a failure has hit.
        if ($this->build_progress->has_completed()) {

            if (!$this->build_progress->failed) {
                // Only makees sense to perform build integrity check on completed archives
                $this->runDupArchiveBuildIntegrityCheck();
            }

            $timerEnd = DUP_Util::getMicrotime();
            $timerSum = DUP_Util::elapsedTime($timerEnd, $this->timer_start);
            $this->Runtime = $timerSum;

            //FINAL REPORT
            $info = "\n********************************************************************************\n";
            $info .= "RECORD ID:[{$this->ID}]\n";
            $info .= "TOTAL PROCESS RUNTIME: {$timerSum}\n";
            $info .= "PEAK PHP MEMORY USED: " . DUP_PRO_Server::getPHPMemory(true) . "\n";
            $info .= "DONE PROCESSING => {$this->Name} " . @date("Y-m-d H:i:s") . "\n";

            DUP_PRO_Log::info($info);
            DUP_PRO_LOG::trace("Done package building");

            if ($this->build_progress->failed) {

                $this->setStatus(DUP_PackageStatus::ERROR);

                $message = "Package creation failed.";
                DUP_Log::error($message);
                DUP_Log::trace($message);
            } else {
  
                //File Cleanup
                $this->buildCleanup();
            }
        }

        //START BUILD
        else if (!$this->build_progress->database_script_built) {
            $this->Database->build($this);
            $this->build_progress->database_script_built = true;
            $this->update();
            DUP_LOG::trace("Set db built for package $this->ID");
        } else if (!$this->build_progress->archive_built) {
            $this->Archive->build($this);
            $this->update();
        } else if (!$this->build_progress->installer_built) {

            // Note: Duparchive builds installer within the main build flow not here
            $this->Installer->build($this);
            $this->update();

            if ($this->build_progress->failed) {
                $this->set_status(DUP_PRO_PackageStatus::ERROR);
                DUP_PRO_Log::error('ERROR: Problem adding installer to archive.');
            }
        }

        if ($global->trace_profiler_on) {
            $profileLogsEntity = DUP_PRO_Profile_Logs_Entity::get_instance();
            $profileLogsEntity->profileLogs = DUP_PRO_Log::$profileLogs;
            $profileLogsEntity->save();
        }


        DUP_PRO_Log::close();

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

        $this->insertNewPackageForBuilding('zip');

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


	private function writeLogHeader($php_max_memory, $php_max_time)
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
        $info .= "WORDPRESS:\t{$wp_version}\n";
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

            $name       = ( isset($post['package-name']) && !empty($post['package-name'])) ? $post['package-name'] : self::getDefaultName();
            $name       = substr(sanitize_file_name($name), 0, 40);
            $name       = str_replace(array('.', '-', ';', ':', "'", '"'), '', $name);

            $filter_dirs  = isset($post['filter-dirs'])  ? $this->Archive->parseDirectoryFilter($post['filter-dirs']) : '';
			$filter_files = isset($post['filter-files']) ? $this->Archive->parseFileFilter($post['filter-files']) : '';
            $filter_exts  = isset($post['filter-exts'])  ? $this->Archive->parseExtensionFilter($post['filter-exts']) : '';
            $tablelist    = isset($post['dbtables'])	 ? implode(',', $post['dbtables']) : '';
            $compatlist   = isset($post['dbcompat'])	 ? implode(',', $post['dbcompat']) : '';
            $dbversion    = DUP_DB::getVersion();
            $dbversion    = is_null($dbversion) ? '- unknown -'  : $dbversion;
            $dbcomments   = DUP_DB::getVariable('version_comment');
            $dbcomments   = is_null($dbcomments) ? '- unknown -' : $dbcomments;


            //PACKAGE
            $this->Created    = date("Y-m-d H:i:s");
            $this->Version    = DUPLICATOR_VERSION;
            $this->VersionOS  = defined('PHP_OS') ? PHP_OS : 'unknown';
            $this->VersionWP  = $wp_version;
            $this->VersionPHP = phpversion();
            $this->VersionDB  = esc_html($dbversion);
            $this->Name       = sanitize_text_field($name);
            $this->Hash       = $this->makeHash();
            $this->NameHash   = "{$this->Name}_{$this->Hash}";

            $this->Notes                    = DUP_Util::escSanitizeTextAreaField($post['package-notes']);
            //ARCHIVE
            $this->Archive->PackDir         = rtrim(DUPLICATOR_WPROOTPATH, '/');
            $this->Archive->Format          = 'ZIP';
            $this->Archive->FilterOn        = isset($post['filter-on']) ? 1 : 0;
			$this->Archive->ExportOnlyDB    = isset($post['export-onlydb']) ? 1 : 0;
            $this->Archive->FilterDirs      = DUP_Util::escSanitizeTextAreaField($filter_dirs);
			 $this->Archive->FilterFiles    = DUP_Util::escSanitizeTextAreaField($filter_files);
            $this->Archive->FilterExts      = str_replace(array('.', ' '), '', DUP_Util::escSanitizeTextAreaField($filter_exts));
            //INSTALLER
            $this->Installer->OptsDBHost    = DUP_Util::escSanitizeTextField($post['dbhost']);
            $this->Installer->OptsDBPort    = DUP_Util::escSanitizeTextField($post['dbport']);
            $this->Installer->OptsDBName    = DUP_Util::escSanitizeTextField($post['dbname']);
            $this->Installer->OptsDBUser    = DUP_Util::escSanitizeTextField($post['dbuser']);
            //DATABASE
            $this->Database->FilterOn       = isset($post['dbfilter-on']) ? 1 : 0;
            $this->Database->FilterTables   = esc_html($tablelist);
            $this->Database->Compatible     = $compatlist;
            $this->Database->Comments       = esc_html($dbcomments);

            update_option(self::OPT_ACTIVE, $this);
        }
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
        global $wpdb;

        $packageObj = serialize($this);

        if (!isset($status)) {
            DUP_Log::Error("Package SetStatus did not receive a proper code.");
        }

        if (!$packageObj) {
            DUP_Log::Error("Package SetStatus was unable to serialize package object while updating record.");
        }

        $wpdb->flush();
        $table = $wpdb->prefix."duplicator_packages";
        $sql   = "UPDATE `{$table}` SET  status = {$status}, package = '{$packageObj}'	WHERE ID = {$this->ID}";
        $wpdb->query($sql);
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
        if (strlen($qry->hash) == 0) {
            return 0;
        } else {
            return $qry->ID;
        }
    }

    /**
     *  Makes the hashkey for the package files
	 *  Rare cases will need to fall back to GUID
     *
     *  @return string  Returns a unique hashkey
     */
    public function makeHash()
    {
		try {
			if (function_exists('random_bytes') && DUP_Util::$on_php_53_plus) {
				return bin2hex(random_bytes(8)).mt_rand(1000, 9999).date("ymdHis");
			} else {
				return DUP_Util::GUIDv4();
			}
		} catch (Exception $exc) {
			return DUP_Util::GUIDv4();
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
    private function buildCleanup()
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
}
?>
