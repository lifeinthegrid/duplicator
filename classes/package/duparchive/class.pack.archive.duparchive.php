<?php
defined("ABSPATH") or die("");
if (!defined('DUPLICATOR_VERSION')) exit; // Exit if accessed directly

//?require_once (DUPLICATOR_PLUGIN_PATH.'classes/package/class.pack.archive.php');
//require_once (DUPLICATOR_PLUGIN_PATH.'classes/utilities/class.u.php');
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
    public static function create($archive, $buildProgress, $package)
    {
        /* @var $buildProgress DUP_Build_Progress */

		DUP_LOG::trace("start");
        try {
            if(DUP_Log::$logFileHandle == null) {
                DUP_Log::Open($package->NameHash);
            }

			DUP_LOG::trace("c2");
            if ($buildProgress->retries > DUPLICATOR_MAX_BUILD_RETRIES) {
				DUP_LOG::trace("c3");
                $error_msg              = __('Package build appears stuck so marking package as failed. Is the Max Worker Time set too high?.', 'duplicator');
                DUP_Log::error(__('Build Failure', 'duplicator'), $error_msg, false);
                $buildProgress->failed = true;
                return true;
            } else {
				DUP_LOG::trace("c4");
                // If all goes well retries will be reset to 0 at the end of this function.
                $buildProgress->retries++;
                $package->update();
            }

            $done   = false;

			DUP_LOG::trace("c5");
            DupArchiveEngine::init(new DUP_DupArchive_Logger());

			DUP_LOG::trace("c6");
			DUP_Package::safeTmpCleanup(true);
       
            $compressDir = rtrim(DUP_Util::safePath($archive->PackDir), '/');
            $sqlPath     = DUP_Util::safePath("{$package->StorePath}/{$package->Database->File}");
            $archivePath = DUP_Util::safePath("{$package->StorePath}/{$archive->File}");

            $scanFilepath = DUPLICATOR_SSDIR_PATH_TMP."/{$package->NameHash}_scan.json";

			DUP_LOG::trace("c7");
            $skipArchiveFinalization = false;
            $json                    = '';

			DUP_LOG::trace("c8");
            if (file_exists($scanFilepath)) {

				DUP_LOG::trace("c9");
                $json = file_get_contents($scanFilepath);

                if (empty($json)) {
					DUP_LOG::trace("c10");
                    $errorText = __("Scan file $scanFilepath is empty!", 'duplicator');
                    $fixText = __("Click on \"Resolve This\" button to fix the JSON settings.", 'duplicator');

                    DUP_Log::Trace($errorText);
                    DUP_Log::error("$errorText **RECOMMENDATION:  $fixText.", '', false);

                    $buildProgress->failed = true;
                    return true;
                }
            } else {
				DUP_LOG::trace("c11");
                DUP_Log::trace("**** scan file $scanFilepath doesn't exist!!");
                $errorMessage = sprintf(__("ERROR: Can't find Scanfile %s. Please ensure there no non-English characters in the package or schedule name.", 'duplicator'), $scanFilepath);

                DUP_Log::error($errorMessage, '', false);

                $buildProgress->failed = true;
                return true;
            }

			DUP_LOG::trace("c12");
            Dup_Log::TraceObject("buildprogress object", $buildProgress);

            $scanReport = json_decode($json);

            if ($buildProgress->archive_started == false) {

                $filterDirs  = empty($archive->FilterDirs) ? 'not set' : $archive->FilterDirs;
                $filterExts  = empty($archive->FilterExts) ? 'not set' : $archive->FilterExts;
                $filterFiles = empty($archive->FilterFiles) ? 'not set' : $archive->FilterFiles;
                $filterOn    = ($archive->FilterOn) ? 'ON' : 'OFF';

				DUP_LOG::trace("c13");
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
					DupArchiveEngine::createArchive($archivePath, true);
                    
                    DupArchiveEngine::addRelativeFileToArchiveST($archivePath, $sqlPath, 'database.sql');
                } catch (Exception $ex) {
                    DUP_Log::error('Error initializing archive', $ex->getMessage(), false);
                    $buildProgress->failed = true;
                    return true;
                }

                $buildProgress->archive_started = true;

                $buildProgress->retries = 0;

				$createState = DUP_DupArchive_Create_State::createNew($archivePath, $compressDir, self::WorkerTimeInSec, true, true);
				$createState->throttleDelayInUs = 0;

				$createState->save();

                $package->Update();
            }

            try {

				DUP_LOG::trace("c14");
                $createState = DUP_DupArchive_Create_State::get_instance();
                
                if($buildProgress->retries > 1) {
                    // Indicates it had problems before so move into robustness mode
                    $createState->isRobust = true;
                    
                    $createState->save();
                }

                if ($createState->working) {
					DUP_LOG::Trace("Create state is working");
                    DupArchiveEngine::addItemsToArchive($createState, $scanReport->ARC);

                    $buildProgress->set_build_failures($createState->failures);

                    if($createState->isCriticalFailurePresent()) {

                        throw new Exception($createState->getFailureSummary());
                    }

                    $totalFileCount = count($scanReport->ARC->Files);

                    $package->Status = SnapLibUtil::getWorkPercent(DUP_PackageStatus::ARCSTART, DUP_PackageStatus::ARCVALIDATION, $totalFileCount, $createState->currentFileIndex);

                    $buildProgress->retries = 0;

                    $createState->save();

                    DUP_LOG::TraceObject("Stored Create State", $createState);
                    DUP_LOG::TraceObject('Stored build_progress', $package->BuildProgress);

                    if ($createState->working == false) {
                        // Want it to do the final cleanup work in an entirely new thread so return immediately
                        $skipArchiveFinalization = true;
                        DUP_LOG::TraceObject("Done build phase. Create State=", $createState);
                    }
                }
            } catch (Exception $ex) {
				DUP_LOG::trace("c15");
                $message = __('Problem adding items to archive.', 'duplicator').' '.$ex->getMessage();

                DUP_Log::Error(__('Problems adding items to archive.', 'duplicator'), $message, false);
                DUP_Log::TraceObject($message." EXCEPTION:", $ex);
                $buildProgress->failed = true;
                return true;
            }

			DUP_LOG::trace("c16");

            //-- Final Wrapup of the Archive
            if ((!$skipArchiveFinalization) && ($createState->working == false)) {

				DUP_LOG::Trace("Create state is not working and not skip archive finalization");

				DUP_LOG::trace("c17");

                if(!$buildProgress->installer_built) {

                    $package->Installer->build($package);

					$package->Runtime = -1;
					$package->ExeSize = DUP_Util::byteSize($package->Installer->Size);
					$package->ZipSize = DUP_Util::byteSize($package->Archive->Size);

					$package->update();

			//rsr todo need this somewhere		$package->buildCleanup();

                    DUP_Log::Trace("Installer has been built so running expand now");

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

					$expandState->save();

                    $sfc = count($scanReport->ARC->Files);
                    $nfa = $package->Installer->numFilesAdded;
                    Dup_Log::trace("####scan files {$sfc} skipped files {$createState->skippedFileCount} num files added {$nfa}");
					DUP_LOG::traceObject("EXPAND STATE AFTER SAVE", $expandState);                    
                }
                else {

					DUP_LOG::trace("c18");
                    try {						

						$expandState = DUP_DupArchive_Expand_State::getInstance();					
						
                        if($buildProgress->retries > 1) {

                            // Indicates it had problems before so move into robustness mode
                            $expandState->isRobust = true;
                    
                            $expandState->save();
                        }

                        DUP_Log::traceObject('Resumed validation expand state', $expandState);

                        DupArchiveEngine::expandArchive($expandState);

                        $buildProgress->set_validation_failures($expandState->failures);
                        
                        $totalFileCount = count($scanReport->ARC->Files);
                        $archiveSize    = @filesize($expandState->archivePath);

                        $package->Status = SnapLibUtil::getWorkPercent(DUP_PackageStatus::ARCVALIDATION, DUP_PackageStatus::ARCDONE, $archiveSize,
                                $expandState->archiveOffset);
                        DUP_LOG::TraceObject("package status after expand=", $package->Status);
                        DUP_LOG::Trace("archive size:{$archiveSize} archive offset:{$expandState->archiveOffset}");
                 
                    } catch (Exception $ex) {
                        DUP_Log::Trace('Exception:'.$ex->getMessage().':'.$ex->getTraceAsString());
                        $buildProgress->failed = true;
                        return true;
                    }

                    if($expandState->isCriticalFailurePresent())
                    {
						DUP_LOG::trace("c20");
                        // Fail immediately if critical failure present - even if havent completed processing the entire archive.

                        DUP_Log::Error(__('Critical failure present in validation', 'duplicator'), $expandState->getFailureSummary(), false);

                        $buildProgress->failed = true;
                        return true;
                    } else if (!$expandState->working) {
						DUP_LOG::trace("c21");

                        $buildProgress->archive_built = true;
                        $buildProgress->retries       = 0;

                        // rsr todo is this required?
                        $package->update();

                        $timerAllEnd = DUP_Util::getMicrotime();
                        $timerAllSum = DUP_Util::elapsedTime($timerAllEnd, $package->TimerStart);

                        DUP_LOG::traceObject("create state", $createState);

                        $archiveFileSize = @filesize($archivePath);
                        DUP_Log::info("COMPRESSED SIZE: ".DUP_Util::byteSize($archiveFileSize));
                        DUP_Log::info("ARCHIVE RUNTIME: {$timerAllSum}");
                        DUP_Log::info("MEMORY STACK: ".DUP_Server::getPHPMemory());
                        DUP_Log::info("CREATE WARNINGS: ".$createState->getFailureSummary(false, true));
                        DUP_Log::info("VALIDATION WARNINGS: ".$expandState->getFailureSummary(false, true));

                        $archive->file_count = $expandState->fileWriteCount + $expandState->directoryWriteCount;

                        $package->update();

						DUP_LOG::trace("c22");
                        $done = true;
                    } else {
						DUP_LOG::trace("c23");
                        $expandState->save();
						DUP_LOG::trace("c24");
                    }
                }
            }
        } catch (Exception $ex) {
            // Have to have a catchall since the main system that calls this function is not prepared to handle exceptions
            DUP_Log::trace('Top level create Exception:'.$ex->getMessage().':'.$ex->getTraceAsString());
            $buildProgress->failed = true;
            return true;
        }

        $buildProgress->retries = 0;

        return $done;
    }
}
