<?php
defined("ABSPATH") or die("");
if (!defined('DUPLICATOR_VERSION')) exit; // Exit if accessed directly

//?require_once (DUPLICATOR_PLUGIN_PATH.'classes/package/class.pack.archive.php');
require_once (DUPLICATOR_PLUGIN_PATH.'classes/package/duparchive/class.pack.archive.duparchive.state.expand.php');
require_once (DUPLICATOR_PLUGIN_PATH.'classes/package/duparchive/class.pack.archive.duparchive.state.create.php');
require_once (DUPLICATOR_PLUGIN_PATH.'lib/dup_archive/classes/class.duparchive.loggerbase.php');
require_once (DUPLICATOR_PLUGIN_PATH.'lib/dup_archive/classes/class.duparchive.engine.php');
require_once (DUPLICATOR_PLUGIN_PATH.'lib/dup_archive/classes/states/class.duparchive.state.create.php');
require_once (DUPLICATOR_PLUGIN_PATH.'lib/dup_archive/classes/states/class.duparchive.state.expand.php');

class DUP_DupArchive_Logger extends DupArchiveLoggerBase
{

    public function log($s, $flush = false, $callingFunctionOverride = null)
    {
        DUP_Log::Trace($s, true, $callingFunctionOverride);
    }
}

class DUP_DupArchive
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

            DupArchiveEngine::init(new DUP_PRO_DupArchive_Logger());

			DUP_Package::SafeTmpCleanup(true);
       
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

                    DUP_Log::trace($errorText);
                    DUP_Log::error("$errorText **RECOMMENDATION:  $fixText.", '', false);

                    $buildProgress->failed = true;
                    return true;
                }
            } else {
                DUP_PRO_Log::trace("**** scan file $scanFilepath doesn't exist!!");
                $errorMessage = sprintf(DUP_PRO_U::__("ERROR: Can't find Scanfile %s. Please ensure there no non-English characters in the package or schedule name."), $scanFilepath);

                DUP_PRO_Log::error($errorMessage, '', false);

                $buildProgress->failed = true;
                return true;
            }

            $scanReport = json_decode($json);

            if ($buildProgress->archive_started == false) {

                DUP_Log::info("\n********************************************************************************");
                DUP_Log::info("ARCHIVE Type=DUP Mode=DupArchive");
                DUP_Log::info("********************************************************************************");
                DUP_Log::info("ARCHIVE DIR:  ".$compressDir);
                DUP_Log::info("ARCHIVE FILE: ".basename($archivePath));
                DUP_Log::info("FILTERS: *{$filterOn}*");
                DUP_Log::info("DIRS:  {$filterDirs}");
                DUP_Log::info("EXTS:  {$filterExts}");
                DUP_Log::info("FILES:  {$filterFiles}");

                DUP_Log::info("----------------------------------------");
                DUP_Log::info("COMPRESSING");
                DUP_Log::info("SIZE:\t".$scanReport->ARC->Size);
                DUP_Log::info("STATS:\tDirs ".$scanReport->ARC->DirCount." | Files ".$scanReport->ARC->FileCount." | Total ".$scanReport->ARC->FullCount);

                if (($scanReport->ARC->DirCount == '') || ($scanReport->ARC->FileCount == '') || ($scanReport->ARC->FullCount == '')) {
                    DUP_Log::error('Invalid Scan Report Detected', 'Invalid Scan Report Detected', false);
                    $buildProgress->failed = true;
                    return true;
                }

                try {
					DupArchiveEngine::createArchive($archivePath, $buildProgress->current_build_compression);
                    
                    DupArchiveEngine::addRelativeFileToArchiveST($archivePath, $sqlPath, 'database.sql');
                } catch (Exception $ex) {
                    DUP_Log::error('Error initializing archive', $ex->getMessage(), false);
                    $buildProgress->failed = true;
                    return true;
                }

                $buildProgress->archive_started = true;

                $buildProgress->retries = 0;

                $archive->Package->Update();
            }

            try {
                if ($buildProgress->custom_data == null) {
					$createState                    = DUP_DupArchive_Create_State::createNew($archive->Package, $archivePath, $compressDir, self::WorkerTimeInSec, $buildProgress->current_build_compression, true);
                    $createState->throttleDelayInUs = 0; // RSR TODO
                } else {
                    DUP_LOG::TraceObject('Resumed build_progress', $archive->Package->BuildProgress);

                    $createState = DUP_DupArchive_Create_State::createFromPackage($archive->Package);
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

                    $archive->Package->Status = SnapLibUtil::getWorkPercent(DUP_PackageStatus::ARCSTART, DUP_PackageStatus::ARCVALIDATION, $totalFileCount, $createState->currentFileIndex);

                    $buildProgress->retries = 0;

                    $createState->save();

                    DUP_LOG::TraceObject("Stored Create State", $createState);
                    DUP_LOG::TraceObject('Stored build_progress', $archive->Package->BuildProgress);

                    if ($createState->working == false) {
                        // Want it to do the final cleanup work in an entirely new thread so return immediately
                        $skipArchiveFinalization = true;
                        DUP_LOG::TraceObject("Done build phase. Create State=", $createState);
                    }
                }
            } catch (Exception $ex) {
                $message = DUP_PRO_U::__('Problem adding items to archive.').' '.$ex->getMessage();

                DUP_Log::Error(DUP_PRO_U::__('Problems adding items to archive.'), $message, false);
                DUP_Log::TraceObject($message." EXCEPTION:", $ex);
                $buildProgress->failed = true;
                return true;
            }


            //-- Final Wrapup of the Archive
            if ((!$skipArchiveFinalization) && ($createState->working == false)) {

                if(!$buildProgress->installer_built) {

                    $package->Installer->build($package, $buildProgress);

                    DUP_PRO_Log::traceObject("INSTALLER", $package->Installer);

					$expandState = DUP_DupArchive_Expand_State::getInstance(true);
                    
					$expandState->archivePath            = $archivePath;
					$expandState->working                = true;
					$expandState->timeSliceInSecs        = self::WorkerTimeInSec;
					$expandState->basePath               = DUPLICATOR_SSDIR_PATH_TMP.'/validate';
					$expandState->throttleDelayInUs      = 0; // RSR TODO
					$expandState->validateOnly           = true;
					$expandState->validationType         = DupArchiveValidationTypes::Standard;
					$expandState->working                = true;
					$expandState->expectedDirectoryCount = count($scanReport->ARC->Dirs) - $createState->skippedDirectoryCount + $package->Installer->numDirsAdded;
					$expandState->expectedFileCount      = count($scanReport->ARC->Files) + 1 - $createState->skippedFileCount + $package->Installer->numFilesAdded;    // database.sql will be in there

					DUP_LOG::traceObject("EXPAND STATE", $expandState);

					$expandState->save();
                }
                else {
                  
                    try {
                     
                       // $expandState = new DUP_DupArchive_Expand_State($expandStateEntity);
						$expandState = DUP_DupArchive_Expand_State::getInstance();

                        if($buildProgress->retries > 1) {

                            // Indicates it had problems before so move into robustness mode
                            $expandState->isRobust = true;
                            //$expandState->timeSliceInSecs = self::WorkerTimeInSec / 2;
                            $expandState->save();
                        }

                        DUP_Log::traceObject('Resumed validation expand state', $expandState);

                        DupArchiveEngine::expandArchive($expandState);

                        $totalFileCount = count($scanReport->ARC->Files);
                        $archiveSize    = @filesize($expandState->archivePath);

                        $archive->Package->Status = SnapLibUtil::getWorkPercent(DUP_PRO_PackageStatus::ARCVALIDATION, DUP_PRO_PackageStatus::ARCDONE, $archiveSize,
                                $expandState->archiveOffset);
                    } catch (Exception $ex) {
                        DUP_Log::TraceError('Exception:'.$ex->getMessage().':'.$ex->getTraceAsString());
                        $buildProgress->failed = true;
                        return true;
                    }

                    if($expandState->isCriticalFailurePresent())
                    {
                        // Fail immediately if critical failure present - even if havent completed processing the entire archive.

                        DUP_Log::Error(__('Build Failure', 'duplicator'), $expandState->getFailureSummary(), false);

                        $buildProgress->failed = true;
                        return true;
                    } else if (!$expandState->working) {

                        $buildProgress->archive_built = true;
                        $buildProgress->retries       = 0;

                        $archive->Package->update();

                        $timerAllEnd = DUP_Util::getMicrotime();
                        $timerAllSum = DUP_Util::elapsedTime($timerAllEnd, $archive->Package->timer_start);

                        DUP_LOG::traceObject("create state", $createState);

                        $archiveFileSize = @filesize($archivePath);
                        DUP_Log::info("COMPRESSED SIZE: ".DUP_PRO_U::byteSize($archiveFileSize));
                        DUP_Log::info("ARCHIVE RUNTIME: {$timerAllSum}");
                        DUP_Log::info("MEMORY STACK: ".DUP_Server::getPHPMemory());
                        DUP_Log::info("CREATE WARNINGS: ".$createState->getFailureSummary(false, true));
                        DUP_Log::info("VALIDATION WARNINGS: ".$expandState->getFailureSummary(false, true));

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
            DUP_PRO_Log::traceError('Top level create Exception:'.$ex->getMessage().':'.$ex->getTraceAsString());
            $buildProgress->failed = true;
            return true;
        }

        $buildProgress->retries = 0;

        return $done;
    }
}
