<?php
/** Absolute path to the DAWS directory. - necessary for php protection */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

ini_set('display_errors', 1);
error_reporting(E_ALL);
error_reporting(E_ALL);
set_error_handler("terminate_missing_variables");

require_once(dirname(__FILE__) . '/class.daws.constants.php');

require_once(DAWSConstants::$LIB_DIR . '/snaplib/snaplib.all.php');
require_once(DAWSConstants::$DUPARCHIVE_CLASSES_DIR . '/class.duparchive.loggerbase.php');
require_once(DAWSConstants::$DUPARCHIVE_CLASSES_DIR . '/class.duparchive.engine.php');
require_once(DAWSConstants::$DUPARCHIVE_CLASSES_DIR . '/class.duparchive.mini.expander.php');
require_once(DAWSConstants::$DUPARCHIVE_STATES_DIR . '/class.duparchive.state.simplecreate.php');
require_once(DAWSConstants::$DAWS_ROOT . '/class.daws.state.expand.php');

DupArchiveUtil::$TRACE_ON = false;


class DAWS_Logger extends DupArchiveLoggerBase
{
    public function log($s, $flush = false, $callingFunctionOverride = null)
    {
        SnapLibLogger::log($s, $flush, $callingFunctionOverride);
    }
}

class DAWS
{

    private $lock_handle = null;

    function __construct()
    {
        date_default_timezone_set('UTC'); // Some machines don’t have this set so just do it here.

        SnapLibLogger::init(DAWSConstants::$LOG_FILEPATH);

        DupArchiveEngine::init(new DAWS_Logger());
    }

    public function processRequest()
    {
        try {
			SnapLibLogger::log('process request');
            $retVal = new StdClass();

            $retVal->pass = false;

            if (isset($_REQUEST['action'])) {
                $params = $_REQUEST;
                SnapLibLogger::log('b');
            } else {
                $json = file_get_contents('php://input');
                $params = json_decode($json, true);
            }

            SnapLibLogger::logObject('params', $params);
            SnapLibLogger::logObject('keys', array_keys($params));

            $action = $params['action'];

            $initializeState = false;

            $isClientDriven = SnapLibUtil::getArrayValue($params, 'client_driven', false);

            if ($action == 'start_expand') {

                $initializeState = true;

                DAWSExpandState::purgeStatefile();
                SnapLibLogger::clearLog();

                SnapLibIOU::rm(DAWSConstants::$PROCESS_CANCEL_FILEPATH);
                $archiveFilepath = SnapLibUtil::getArrayValue($params, 'archive_filepath');
                $restoreDirectory = SnapLibUtil::getArrayValue($params, 'restore_directory');
                $workerTime = SnapLibUtil::getArrayValue($params, 'worker_time', false, DAWSConstants::$DEFAULT_WORKER_TIME);
                $filteredDirectories = SnapLibUtil::getArrayValue($params, 'filtered_directories', false, array());
                $filteredFiles = SnapLibUtil::getArrayValue($params, 'filtered_files', false, array()); 
                $fileRenames = SnapLibUtil::getArrayValue($params, 'file_renames', false, array());

                $action = 'expand';

				SnapLibLogger::log('startexpand->expand');
            } else if($action == 'start_create') {
             
                $archiveFilepath = SnapLibUtil::getArrayValue($params, 'archive_filepath');
                $workerTime = SnapLibUtil::getArrayValue($params, 'worker_time', false, DAWSConstants::$DEFAULT_WORKER_TIME);
                
                $createState->basePath        = $dataDirectory;
                $createState->isCompressed    = $isCompressed;
                
                $sourceDirectory = SnapLibUtil::getArrayValue($params, 'source_directory');
                $isCompressed = SnapLibUtil::getArrayValue($params, 'is_compressed') === 'true' ? true : false;
            }

			$throttleDelayInMs = SnapLibUtil::getArrayValue($params, 'throttle_delay', false, 0);

            if ($action == 'expand') {

                SnapLibLogger::log('expand action');

                /* @var $expandState DAWSExpandState */
                $expandState = DAWSExpandState::getInstance($initializeState);

				$this->lock_handle = SnapLibIOU::fopen(DAWSConstants::$PROCESS_LOCK_FILEPATH, 'c+');
				SnapLibIOU::flock($this->lock_handle, LOCK_EX);

				if($initializeState || $expandState->working) {

					if ($initializeState) {

                        SnapLibLogger::logObject('file renames', $fileRenames);

						$expandState->archivePath = $archiveFilepath;
						$expandState->working = true;
						$expandState->timeSliceInSecs = $workerTime;
						$expandState->basePath = $restoreDirectory;
						$expandState->working = true;
						$expandState->filteredDirectories = $filteredDirectories;
                        $expandState->filteredFiles = $filteredFiles;
                        $expandState->fileRenames = $fileRenames;
                        $expandState->fileModeOverride = 0644;
                        $expandState->directoryModeOverride = 0755;

						$expandState->save();
					}

					$expandState->throttleDelayInUs = 1000 * $throttleDelayInMs;

                    SnapLibLogger::logObject('Expand State In', $expandState);

					DupArchiveEngine::expandArchive($expandState);
				}

                if (!$expandState->working) {

                    $deltaTime = time() - $expandState->startTimestamp;
                    SnapLibLogger::log("###### Processing ended.  Seconds taken:$deltaTime");

                    if (count($expandState->failures) > 0) {
                        SnapLibLogger::log('Errors detected');

                        foreach ($expandState->failures as $failure) {
                            SnapLibLogger::log("{$failure->subject}:{$failure->description}");
                        }
                    } else {
                        SnapLibLogger::log('Expansion done, archive checks out!');
                    }
                }
				else {
					SnapLibLogger::log("Processing will continue");
				}


                SnapLibIOU::flock($this->lock_handle, LOCK_UN);

                $retVal->pass = true;
                $retVal->status = $this->getStatus($expandState);
            } else if ($action == 'create') {

                SnapLibLogger::log('create action');

                /* @var $expandState DAWSExpandState */
                $createState = DAWSCreateState::getInstance($initializeState);

				$this->lock_handle = SnapLibIOU::fopen(DAWSConstants::$PROCESS_LOCK_FILEPATH, 'c+');
				SnapLibIOU::flock($this->lock_handle, LOCK_EX);

				if($initializeState || $createState->working) {

                    DupArchiveEngine::createArchive($archiveFilepath, $isCompressed);

                    $createState->archivePath     = $archiveFilepath;
                    $createState->archiveOffset   = SnapLibIOU::filesize($archiveFilepath);
                    $createState->working         = true;
                    $createState->timeSliceInSecs = $workerTime;
                    $createState->basePath        = $dataDirectory;
                    $createState->isCompressed    = $isCompressed;
                    $createState->throttleDelayInUs = $throttleDelayInUs;

                    //   $daTesterCreateState->globSize        = self::GLOB_SIZE;

                    $createState->save();

                    $scan = DupArchiveScanUtil::createScan($this->paths->scanFilepath, $this->paths->dataDirectory);
				}
                
                $createState->throttleDelayInUs = 1000 * $throttleDelayInMs;

                if (!$createState->working) {

                    $deltaTime = time() - $createState->startTimestamp;
                    SnapLibLogger::log("###### Processing ended.  Seconds taken:$deltaTime");

                    if (count($createState->failures) > 0) {
                        SnapLibLogger::log('Errors detected');

                        foreach ($createState->failures as $failure) {
                            SnapLibLogger::log("{$failure->subject}:{$failure->description}");
                        }
                    } else {
                        SnapLibLogger::log('Creation done, archive checks out!');
                    }
                }
				else {
					SnapLibLogger::log("Processing will continue");
				}

                SnapLibIOU::flock($this->lock_handle, LOCK_UN);

                $retVal->pass = true;
                $retVal->status = $this->getStatus($createState);
            } else if ($action == 'get_status') {
                /* @var $expandState DAWSExpandState */
                $expandState = DAWSExpandState::getInstance($initializeState);

                $retVal->pass = true;
                $retVal->status = $this->getStatus($expandState);
            } else if ($action == 'cancel') {
                SnapLibIOU::touch(DAWSConstants::$PROCESS_CANCEL_FILEPATH);
                $retVal->pass = true;
            } else {
                throw new Exception('Unknown command.');
            }

            session_write_close();
            
        } catch (Exception $ex) {
            $error_message = "Error Encountered:" . $ex->getMessage() . '<br/>' . $ex->getTraceAsString();

            SnapLibLogger::log($error_message);

            $retVal->pass = false;
            $retVal->error = $error_message;
        }

		SnapLibLogger::logObject("before json encode retval", $retVal);

		$jsonRetVal = json_encode($retVal);
		SnapLibLogger::logObject("json encoded retval", $jsonRetVal);
        echo $jsonRetVal;
    }

    private function getStatus($state)
    {
        /* @var $state DupArchiveStateBase */

        $ret_val = new stdClass();

        $ret_val->archive_offset = $state->archiveOffset;
        $ret_val->archive_size = @filesize($state->archivePath);
        $ret_val->failures = $state->failures;
        $ret_val->file_index = $state->fileWriteCount;
        $ret_val->is_done = !$state->working;
        $ret_val->timestamp = time();

        return $ret_val;
    }
}

function generateCallTrace()
{
    $e = new Exception();
    $trace = explode("\n", $e->getTraceAsString());
    // reverse array to make steps line up chronologically
    $trace = array_reverse($trace);
    array_shift($trace); // remove {main}
    array_pop($trace); // remove call to this method
    $length = count($trace);
    $result = array();

    for ($i = 0; $i < $length; $i++) {
        $result[] = ($i + 1) . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
    }

    return "\t" . implode("\n\t", $result);
}

function terminate_missing_variables($errno, $errstr, $errfile, $errline)
{
    echo "<br/>ERROR: $errstr $errfile $errline<br/>";
    //  if (($errno == E_NOTICE) and ( strstr($errstr, "Undefined variable"))) die("$errstr in $errfile line $errline");


    SnapLibLogger::log("ERROR $errno, $errstr, {$errfile}:{$errline}");
    SnapLibLogger::log(generateCallTrace());
    //  DaTesterLogging::clearLog();

    exit(1);
    //return false; // Let the PHP error handler handle all the rest
}

$daws = new DAWS();

$daws->processRequest();