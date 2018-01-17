<?php
defined("ABSPATH") or die("");
if (!defined('DUPLICATOR_VERSION')) exit; // Exit if accessed directly

require_once (DUPLICATOR_PLUGIN_PATH.'classes/package/class.pack.archive.php');
require_once (DUPLICATOR_PLUGIN_PATH.'classes/package/duparchive/class.pack.archive.duparchive.state.expand.php');
require_once (DUPLICATOR_PLUGIN_PATH.'classes/package/duparchive/class.pack.archive.duparchive.state.create.php');
require_once (DUPLICATOR_PLUGIN_PATH.'classes/entities/class.global.entity.php');
require_once (DUPLICATOR_PLUGIN_PATH.'classes/entities/class.system.global.entity.php');
require_once (DUPLICATOR_PLUGIN_PATH.'lib/dup_archive/classes/class.duparchive.loggerbase.php');
require_once (DUPLICATOR_PLUGIN_PATH.'lib/dup_archive/classes/class.duparchive.engine.php');
require_once (DUPLICATOR_PLUGIN_PATH.'lib/dup_archive/classes/states/class.duparchive.state.create.php');
require_once (DUPLICATOR_PLUGIN_PATH.'lib/dup_archive/classes/states/class.duparchive.state.expand.php');
require_once (DUPLICATOR_PLUGIN_PATH.'classes/entities/class.duparchive.expandstate.entity.php');

class DUP_DupArchive_Logger extends DupArchiveLoggerBase
{

    public function log($s, $flush = false, $callingFunctionOverride = null)
    {
        // rsr todo ignoring flush for now
        DUP_LOG::Trace($s, true, $callingFunctionOverride);
    }
}

class DUP_DupArchive extends DUP_Archive
{
    // Using a worker time override since evidence shorter time works much
    const WorkerTimeInSec = 10;

    /**
     *  CREATE
     *  Creates the zip file and adds the SQL file to the archive
     */
    public static function create($archive, $buildProgress)
    {
        /* @var $buildProgress DUP_Build_Progress */

        try {
            $package = &$archive->Package;

            if ($buildProgress->retries > DUPLICATOR_MAX_BUILD_RETRIES) {
                $error_msg              = __('Package build appears stuck so marking package as failed. Is the Max Worker Time set too high?.', 'duplicator');
                DUP_Log::error(__('Build Failure', 'duplicator'), $error_msg, false);
                $buildProgress->failed = true;
                return true;
            } else {
                // If all goes well retries will be reset to 0 at the end of this function.
                $buildProgress->retries++;
                $archive->Package->update();
            }

            /* @var $archive DUP_PRO_Archive */
            /* @var $buildProgress DUP_PRO_Build_Progress */
            $done   = false;


            DupArchiveEngine::init(new DUP_PRO_Dup_Archive_Logger());

			DUP_Package::SafeTmpCleanup(true);

            /* @var $global DUP_PRO_Global_Entity */
            $global = DUP_PRO_Global_Entity::get_instance();

            $compressDir = rtrim(DUP_Util::safPath($archive->PackDir), '/');
            $sqlPath     = DUP_Util::safePath("{$archive->Package->StorePath}/{$archive->Package->Database->File}");
            $archivePath = DUP_Util::safePath("{$archive->Package->StorePath}/{$archive->File}");

            $filterDirs  = empty($archive->FilterDirs) ? 'not set' : $archive->FilterDirs;
            $filterExts  = empty($archive->FilterExts) ? 'not set' : $archive->FilterExts;
            $filterFiles = empty($archive->FilterFiles) ? 'not set' : $archive->FilterFiles;
            $filterOn    = ($archive->FilterOn) ? 'ON' : 'OFF';

            $scanFilepath = DUPLICATOR_SSDIR_PATH_TMP."/{$archive->Package->NameHash}_scan.json";

            $skipArchiveFinalization = false;
            $json                    = '';

            if (file_exists($scanFilepath)) {

                $json = file_get_contents($scanFilepath);

                if (empty($json)) {
                    $errorText = DUP_PRO_U::__("Scan file $scanFilepath is empty!");
                    $fixText = DUP_PRO_U::__("Click on \"Resolve This\" button to fix the JSON settings.");

                    DUP_PRO_LOG::trace($errorText);
                    DUP_PRO_Log::error("$errorText **RECOMMENDATION:  $fixText.", '', false);

                    $systemGlobal = DUP_PRO_System_Global_Entity::get_instance();

                    $systemGlobal->add_recommended_quick_fix($errorText, $fixText, 'global:{json_mode:1}');
                    $systemGlobal->save();

                    $buildProgress->failed = true;
                    return true;
                }
            } else {
                DUP_PRO_LOG::trace("**** scan file $scanFilepath doesn't exist!!");
                $errorMessage = sprintf(DUP_PRO_U::__("ERROR: Can't find Scanfile %s. Please ensure there no non-English characters in the package or schedule name."), $scanFilepath);

                DUP_PRO_Log::error($errorMessage, '', false);

                $buildProgress->failed = true;
                return true;
            }

            $scanReport = json_decode($json);

            if ($buildProgress->archive_started == false) {

                DUP_PRO_Log::info("\n********************************************************************************");
                DUP_PRO_Log::info("ARCHIVE Type=DUP Mode=DupArchive");
                DUP_PRO_Log::info("********************************************************************************");
                DUP_PRO_Log::info("ARCHIVE DIR:  ".$compressDir);
                DUP_PRO_Log::info("ARCHIVE FILE: ".basename($archivePath));
                DUP_PRO_Log::info("FILTERS: *{$filterOn}*");
                DUP_PRO_Log::info("DIRS:  {$filterDirs}");
                DUP_PRO_Log::info("EXTS:  {$filterExts}");
                DUP_PRO_Log::info("FILES:  {$filterFiles}");

                DUP_PRO_Log::info("----------------------------------------");
                DUP_PRO_Log::info("COMPRESSING");
                DUP_PRO_Log::info("SIZE:\t".$scanReport->ARC->Size);
                DUP_PRO_Log::info("STATS:\tDirs ".$scanReport->ARC->DirCount." | Files ".$scanReport->ARC->FileCount." | Total ".$scanReport->ARC->FullCount);

                if (($scanReport->ARC->DirCount == '') || ($scanReport->ARC->FileCount == '') || ($scanReport->ARC->FullCount == '')) {
                    DUP_PRO_Log::error('Invalid Scan Report Detected', 'Invalid Scan Report Detected', false);
                    $buildProgress->failed = true;
                    return true;
                }

                try {
					DupArchiveEngine::createArchive($archivePath, $buildProgress->current_build_compression);
                    
                    DupArchiveEngine::addRelativeFileToArchiveST($archivePath, $sqlPath, 'database.sql');
                } catch (Exception $ex) {
                    DUP_PRO_Log::error('Error initializing archive', $ex->getMessage(), false);
                    $buildProgress->failed = true;
                    return true;
                }

                $buildProgress->archive_started = true;

                $buildProgress->retries = 0;

                $archive->Package->Update();
            }

            try {
                if ($buildProgress->custom_data == null) {
					$createState                    = DUP_PRO_Dup_Archive_Create_State::createNew($archive->Package, $archivePath, $compressDir, self::WorkerTimeInSec, $buildProgress->current_build_compression, true);
                    $createState->throttleDelayInUs = DUP_PRO_Server_Load_Reduction::microseconds_from_reduction($global->server_load_reduction);
                } else {
                    DUP_PRO_LOG::traceObject('Resumed build_progress', $archive->Package->build_progress);

                    $createState = DUP_PRO_Dup_Archive_Create_State::createFromPackage($archive->Package);
                }

                if($buildProgress->retries > 1) {
                    // Indicates it had problems before so move into robustness mode
                    $createState->isRobust = true;
                    //$createState->timeSliceInSecs = self::WorkerTimeInSec / 2;
                    $createState->save();
                }

                if ($createState->working) {
                    DupArchiveEngine::addItemsToArchive($createState, $scanReport->ARC);

                    if($createState->isCriticalFailurePresent()) {

                        throw new Exception($createState->getFailureSummary());
                    }

                    $totalFileCount = count($scanReport->ARC->Files);

                    $archive->Package->Status = SnapLibUtil::getWorkPercent(DUP_PRO_PackageStatus::ARCSTART, DUP_PRO_PackageStatus::ARCVALIDATION, $totalFileCount, $createState->currentFileIndex);

                    $buildProgress->retries = 0;

                    $createState->save();

                    DUP_PRO_LOG::traceObject("Stored Create State", $createState);
                    DUP_PRO_LOG::traceObject('Stored build_progress', $archive->Package->build_progress);

                    if ($createState->working == false) {
                        // Want it to do the final cleanup work in an entirely new thread so return immediately
                        $skipArchiveFinalization = true;
                        DUP_PRO_LOG::traceObject("Done build phase. Create State=", $createState);
                    }
                }
            } catch (Exception $ex) {
                $message = DUP_PRO_U::__('Problem adding items to archive.').' '.$ex->getMessage();

                DUP_PRO_Log::error(DUP_PRO_U::__('Problems adding items to archive.'), $message, false);
                DUP_PRO_LOG::traceObject($message." EXCEPTION:", $ex);
                $buildProgress->failed = true;
                return true;
            }


            //-- Final Wrapup of the Archive
            if ((!$skipArchiveFinalization) && ($createState->working == false)) {


                if(!$buildProgress->installer_built) {

                    $package->Installer->build($package, $buildProgress);
                    //$archive->Package->update();

                    DUP_PRO_LOG::traceObject("INSTALLER", $package->Installer);

                    $expandStateEntity = DUP_PRO_DupArchive_Expand_State_Entity::get_by_package_id($archive->Package->ID);

                    if ($expandStateEntity == null) {

                        DUP_PRO_DupArchive_Expand_State_Entity::delete_all();

                        $expandStateEntity = new DUP_PRO_DupArchive_Expand_State_Entity();

                        $expandStateEntity->package_id = $archive->Package->ID;

                        $expandStateEntity->archivePath            = $archivePath;
                        $expandStateEntity->working                = true;
                        $expandStateEntity->timeSliceInSecs        = self::WorkerTimeInSec;
                        $expandStateEntity->basePath               = DUPLICATOR_PRO_SSDIR_PATH_TMP.'/validate';
                        $expandStateEntity->throttleDelayInUs      = DUP_PRO_Server_Load_Reduction::microseconds_from_reduction($global->server_load_reduction);
                        $expandStateEntity->validateOnly           = true;
                        $expandStateEntity->validationType         = DupArchiveValidationTypes::Standard;
                        $expandStateEntity->working                = true;
                        $expandStateEntity->expectedDirectoryCount = count($scanReport->ARC->Dirs) - $createState->skippedDirectoryCount + $package->Installer->numDirsAdded; 
                        $expandStateEntity->expectedFileCount      = count($scanReport->ARC->Files) + 1 - $createState->skippedFileCount + $package->Installer->numFilesAdded;    // database.sql will be in there

                        DUP_PRO_LOG::traceObject("EXPAND STATE ENTITY", $expandStateEntity);
                        $expandStateEntity->save();
                    }
                }
                else {
                    // $build_progress->warnings = $createState->getWarnings(); Auto saves warnings within build progress along the way

                   

                    try {
                        $expandStateEntity = DUP_PRO_DupArchive_Expand_State_Entity::get_by_package_id($archive->Package->ID);
                        
                        $expandState = new DUP_PRO_DupArchive_Expand_State($expandStateEntity);

                        if($buildProgress->retries > 1) {

                            // Indicates it had problems before so move into robustness mode
                            $expandState->isRobust = true;
                            //$expandState->timeSliceInSecs = self::WorkerTimeInSec / 2;
                            $expandState->save();
                        }

                        DUP_PRO_LOG::traceObject('Resumed validation expand state', $expandState);

                        DupArchiveEngine::expandArchive($expandState);

                        $totalFileCount = count($scanReport->ARC->Files);
                        $archiveSize    = @filesize($expandState->archivePath);

                        $archive->Package->Status = SnapLibUtil::getWorkPercent(DUP_PRO_PackageStatus::ARCVALIDATION, DUP_PRO_PackageStatus::ARCDONE, $archiveSize,
                                $expandState->archiveOffset);
                    } catch (Exception $ex) {
                        DUP_PRO_LOG::traceError('Exception:'.$ex->getMessage().':'.$ex->getTraceAsString());
                        $buildProgress->failed = true;
                        return true;
                    }

                    if($expandState->isCriticalFailurePresent())
                    {
                        // Fail immediately if critical failure present - even if havent completed processing the entire archive.

                        DUP_PRO_Log::error(DUP_PRO_U::__('Build Failure'), $expandState->getFailureSummary(), false);

                        $buildProgress->failed = true;
                        return true;
                    } else if (!$expandState->working) {

                        $buildProgress->archive_built = true;
                        $buildProgress->retries       = 0;

                        $archive->Package->update();

                        $timerAllEnd = DUP_PRO_U::getMicrotime();
                        $timerAllSum = DUP_PRO_U::elapsedTime($timerAllEnd, $archive->Package->timer_start);

                        DUP_PRO_LOG::traceObject("create state", $createState);

                        $archiveFileSize = @filesize($archivePath);
                        DUP_PRO_Log::info("COMPRESSED SIZE: ".DUP_PRO_U::byteSize($archiveFileSize));
                        DUP_PRO_Log::info("ARCHIVE RUNTIME: {$timerAllSum}");
                        DUP_PRO_Log::info("MEMORY STACK: ".DUP_PRO_Server::getPHPMemory());
                        DUP_PRO_LOG::info("CREATE WARNINGS: ".$createState->getFailureSummary(false, true));
                        DUP_PRO_LOG::info("VALIDATION WARNINGS: ".$expandState->getFailureSummary(false, true));

                        $archive->file_count = $expandState->fileWriteCount + $expandState->directoryWriteCount;

                        $archive->Package->update();

                        $done = true;
                    } else {
                        $expandState->save();
                    }
                }
            }
        } catch (Exception $ex) {
            // Have to have a catchall since the main system that calls this function is not prepared to handle exceptions
            DUP_PRO_LOG::traceError('Top level create Exception:'.$ex->getMessage().':'.$ex->getTraceAsString());
            $buildProgress->failed = true;
            return true;
        }

        $buildProgress->retries = 0;

        return $done;
    }
}
