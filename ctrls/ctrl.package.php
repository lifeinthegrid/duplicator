<?php
defined('ABSPATH') || defined('DUPXABSPATH') || exit;
// Exit if accessed directly
if (!defined('DUPLICATOR_VERSION'))
    exit;

require_once(DUPLICATOR_PLUGIN_PATH.'/ctrls/ctrl.base.php');
require_once(DUPLICATOR_PLUGIN_PATH.'/classes/utilities/class.u.scancheck.php');
require_once(DUPLICATOR_PLUGIN_PATH.'/classes/utilities/class.u.json.php');
require_once(DUPLICATOR_PLUGIN_PATH.'/classes/package/class.pack.php');

require_once(DUPLICATOR_PLUGIN_PATH.'/classes/package/duparchive/class.pack.archive.duparchive.state.create.php');
require_once(DUPLICATOR_PLUGIN_PATH.'/classes/package/duparchive/class.pack.archive.duparchive.php');
/* @var $package DUP_Package */

/**
 * Display error if any fatal error occurs occurs while scan ajax call
 *
 * @return void
 */
function duplicator_package_scan_shutdown()
{
    $logMessage = DUP_Handler::getVarLog();
    if (!empty($logMessage)) {
        echo nl2br($logMessage);
    }
}

/**
 *  DUPLICATOR_PACKAGE_SCAN
 *  Returns a JSON scan report object which contains data about the system
 *
 *  @return json   JSON report object
 *  @example	   to test: /wp-admin/admin-ajax.php?action=duplicator_package_scan
 */
function duplicator_package_scan()
{
    DUP_Handler::init_error_handler();
    DUP_Handler::setMode(DUP_Handler::MODE_VAR);
    register_shutdown_function('duplicator_package_scan_shutdown');

    check_ajax_referer('duplicator_package_scan', 'nonce');
    DUP_Util::hasCapability('export');

    header('Content-Type: application/json;');
    @ob_flush();

    @set_time_limit(0);
    $errLevel = error_reporting();
    error_reporting(E_ERROR);
    DUP_Util::initSnapshotDirectory();

    $package = DUP_Package::getActive();
    $report  = $package->runScanner();

    $package->saveActiveItem('ScanFile', $package->ScanFile);
    $json_response = DUP_JSON::safeEncode($report);

    DUP_Package::tempFileCleanup();
    error_reporting($errLevel);
    die($json_response);
}

/**
 *  duplicator_package_build
 *  Returns the package result status
 *
 *  @return json   JSON object of package results
 */
function duplicator_package_build()
{
    DUP_Handler::init_error_handler();

    check_ajax_referer('duplicator_package_build', 'nonce');

    header('Content-Type: application/json');

    $Package = null;

    try {
        DUP_Util::hasCapability('export', DUP_Util::SECURE_ISSUE_THROW);

        @set_time_limit(0);
        $errLevel = error_reporting();
        error_reporting(E_ERROR);
        DUP_Util::initSnapshotDirectory();

        $Package = DUP_Package::getActive();
        $Package->save('zip');

        DUP_Settings::Set('active_package_id', $Package->ID);
        DUP_Settings::Save();

        if (!is_readable(DUP_Settings::getSsdirTmpPath()."/{$Package->ScanFile}")) {
            die("The scan result file was not found.  Please run the scan step before building the package.");
        }

        $Package->runZipBuild();

        //JSON:Debug Response
        //Pass = 1, Warn = 2, Fail = 3
        $json                     = array();
        $json['status']           = 1;
        $json['error']            = '';
        $json['package']          = $Package;
        $json['instDownloadName'] = $Package->getInstDownloadName();
        $json['runtime']          = $Package->Runtime;
        $json['exeSize']          = $Package->ExeSize;
        $json['archiveSize']      = $Package->ZipSize;

        //Simulate a Host Build Interrupt
        //die(0);
    }
    catch (Exception $e) {
        $Package->setStatus(DUP_PackageStatus::ERROR);

        //JSON:Debug Response
        //Pass = 1, Warn = 2, Fail = 3
        $json                     = array();
        $json['status']           = 3;
        $json['error']            = $e->getMessage();
        $json['package']          = $Package;
        $json['instDownloadName'] = null;
        $json['runtime']          = null;
        $json['exeSize']          = null;
        $json['archiveSize']      = null;
    }
    $json_response = DupLiteSnapJsonU::wp_json_encode($json);

    error_reporting($errLevel);
    die($json_response);
}

/**
 *  Returns the package result status
 *
 *  @return json   JSON object of package results
 */
function duplicator_duparchive_package_build()
{
    DUP_Handler::init_error_handler();
    DUP_Log::Info('[CTRL DUP ARCIVE] CALL TO '.__FUNCTION__);

    check_ajax_referer('duplicator_duparchive_package_build', 'nonce');
    DUP_Util::hasCapability('export');
    header('Content-Type: application/json');

    @set_time_limit(0);
    $errLevel = error_reporting();
    error_reporting(E_ERROR);

    // The DupArchive build process always works on a saved package so the first time through save the active package to the package table.
    // After that, just retrieve it.
    $active_package_id = DUP_Settings::Get('active_package_id');
    DUP_Log::Info('[CTRL DUP ARCIVE] CURRENT PACKAGE ACTIVE '.$active_package_id);

    if ($active_package_id == -1) {
        $package = DUP_Package::getActive();
        $package->save('daf');
        DUP_Log::Info('[CTRL DUP ARCIVE] PACKAGE AS NEW ID '.$package->ID.' SAVED | STATUS:'.$package->Status);
        //DUP_Log::TraceObject("[CTRL DUP ARCIVE] PACKAGE SAVED:", $package);
        DUP_Settings::Set('active_package_id', $package->ID);
        DUP_Settings::Save();
    } else {
        if (($package = DUP_Package::getByID($active_package_id)) == null) {
            DUP_Log::Info('[CTRL DUP ARCIVE] ERROR: Get package by id '.$active_package_id.' FAILED');
            die('Get package by id '.$active_package_id.' FAILED');
        }
        DUP_Log::Info('[CTRL DUP ARCIVE] PACKAGE GET BY ID '.$active_package_id.' | STATUS:'.$package->Status);
        // DUP_Log::TraceObject("getting active package by id {$active_package_id}", $package);
    }

    if (!is_readable(DUP_Settings::getSsdirTmpPath()."/{$package->ScanFile}")) {
        DUP_Log::Info('[CTRL DUP ARCIVE] ERROR: The scan result file was not found.  Please run the scan step before building the package.');
        die("The scan result file was not found.  Please run the scan step before building the package.");
    }

    if ($package === null) {
        DUP_Log::Info('[CTRL DUP ARCIVE] There is no active package.');
        die("There is no active package.");
    }

    if ($package->Status == DUP_PackageStatus::ERROR) {
        $package->setStatus(DUP_PackageStatus::ERROR);
        $hasCompleted = true;
    } else {
        try {
            $hasCompleted = $package->runDupArchiveBuild();
        }
        catch (Exception $ex) {
            DUP_Log::Info('[CTRL DUP ARCIVE] ERROR: caught exception');
            Dup_Log::error('[CTRL DUP ARCIVE]  Caught exception', $ex->getMessage(), Dup_ErrorBehavior::LogOnly);
            DUP_Log::Info('[CTRL DUP ARCIVE] ERROR: after log');
            $package->setStatus(DUP_PackageStatus::ERROR);
            $hasCompleted = true;
        }
    }

    $json             = array();
    $json['failures'] = array_merge($package->BuildProgress->build_failures, $package->BuildProgress->validation_failures);
    if (!empty($json['failures'])) {
        DUP_Log::Info('[CTRL DUP ARCIVE] FAILURES '. print_r($json['failures'], true));
    }

    //JSON:Debug Response
    //Pass = 1, Warn = 2, 3 = Failure, 4 = Not Done
    if ($hasCompleted) {
        DUP_Log::Info('[CTRL DUP ARCIVE] COMPLETED PACKAGE STATUS: '.$package->Status);

        if ($package->Status == DUP_PackageStatus::ERROR) {
            DUP_Log::Info('[CTRL DUP ARCIVE] ERROR');
            $error_message = __('Error building DupArchive package').'<br/>';

            foreach ($json['failures'] as $failure) {
                $error_message .= implode(',', $failure->description);
            }

            Dup_Log::error("Build failed so sending back error", esc_html($error_message), Dup_ErrorBehavior::LogOnly);
            DUP_Log::Info('[CTRL DUP ARCIVE] ERROR AFTER LOG 2');

            $json['status'] = 3;
        } else {
            Dup_Log::Info("sending back success status");
            $json['status'] = 1;
        }

        Dup_Log::Trace('#### json package');
        $json['package']          = $package;
        $json['instDownloadName'] = $package->getInstDownloadName();
        $json['runtime']          = $package->Runtime;
        $json['exeSize']          = $package->ExeSize;
        $json['archiveSize']      = $package->ZipSize;
        DUP_Log::Trace('[CTRL DUP ARCIVE] JSON PACKAGE');
    } else {
        DUP_Log::Info('[CTRL DUP ARCIVE] sending back continue status PACKAGE STATUS: '.$package->Status);
        $json['status'] = 4;
    }

    $json_response = DupLiteSnapJsonU::wp_json_encode($json);

    Dup_Log::TraceObject('json response', $json_response);
    error_reporting($errLevel);
    die($json_response);
}

/**
 *  DUPLICATOR_PACKAGE_DELETE
 *  Deletes the files and database record entries
 *
 *  @return json   A JSON message about the action.
 * 				   Use console.log to debug from client
 */
function duplicator_package_delete()
{
    DUP_Handler::init_error_handler();
    check_ajax_referer('duplicator_package_delete', 'nonce');

    $json        = array(
        'success' => false,
        'message' => ''
    );
    $package_ids = filter_input(INPUT_POST, 'package_ids', FILTER_VALIDATE_INT, array(
        'flags'   => FILTER_REQUIRE_ARRAY,
        'options' => array(
            'default' => false
        )
    ));
    $delCount    = 0;

    try {
        DUP_Util::hasCapability('export', DUP_Util::SECURE_ISSUE_THROW);

        if ($package_ids === false || in_array(false, $package_ids)) {
            throw new Exception('Invalid Request.', 'duplicator');
        }

        foreach ($package_ids as $id) {
            $package = DUP_Package::getByID($id);

            if ($package === null) {
                throw new Exception('Invalid Request.', 'duplicator');
            }

            $package->delete();
            $delCount++;
        }

        $json['success'] = true;
        $json['ids']     = $package_ids;
        $json['removed'] = $delCount;
    }
    catch (Exception $ex) {
        $json['message'] = $ex->getMessage();
    }

    die(DupLiteSnapJsonU::wp_json_encode($json));
}

/**
 *  Active package info
 *  Returns a JSON scan report active package info or
 *  active_package_present == false if no active package is present.
 *
 *  @return json
 */
function duplicator_active_package_info()
{
    ob_start();
    try {
        DUP_Handler::init_error_handler();
        DUP_Util::hasCapability('export', DUP_Util::SECURE_ISSUE_THROW);

        if (!check_ajax_referer('duplicator_active_package_info', 'nonce', false)) {
            throw new Exception(__('An unauthorized security request was made to this page. Please try again!', 'duplicator'));
        }

        global $wpdb;

        $error  = false;
        $result = array(
            'active_package' => array(
                'present' => false,
                'status'  => 0,
                'size'    => 0
            ),
            'html'           => '',
            'message'        => ''
        );

        $result['active_package']['present'] = DUP_Package::is_active_package_present();

        if ($result['active_package']['present']) {
            $id      = DUP_Settings::Get('active_package_id');
            $package = DUP_Package::getByID($id);
            if (is_null($package)) {
                throw new Exception(__('Active package object error', 'duplicator'));
            }
            $result['active_package']['status']      = $package->Status;
            $result['active_package']['size']        = $package->getArchiveSize();
            $result['active_package']['size_format'] = DUP_Util::byteSize($package->getArchiveSize());
        }
    }
    catch (Exception $e) {
        $error             = true;
        $result['message'] = $e->getMessage();
    }

    $result['html'] = ob_get_clean();
    if ($error) {
        wp_send_json_error($result);
    } else {
        wp_send_json_success($result);
    }
}

/**
 * Controller for Tools
 * @package Duplicator\ctrls
 */
class DUP_CTRL_Package extends DUP_CTRL_Base
{

    /**
     *  Init this instance of the object
     */
    function __construct()
    {
        add_action('wp_ajax_DUP_CTRL_Package_addQuickFilters', array($this, 'addQuickFilters'));
        add_action('wp_ajax_DUP_CTRL_Package_getPackageFile', array($this, 'getPackageFile'));
        add_action('wp_ajax_DUP_CTRL_Package_getActivePackageStatus', array($this, 'getActivePackageStatus'));
    }

    /**
     * Removed all reserved installer files names
     *
     * @param string $_POST['dir_paths']		A semi-colon separated list of directory paths
     *
     * @return string	Returns all of the active directory filters as a ";" separated string
     */
    public function addQuickFilters()
    {
        DUP_Handler::init_error_handler();
        check_ajax_referer('DUP_CTRL_Package_addQuickFilters', 'nonce');

        $result = new DUP_CTRL_Result($this);

        $inputData = filter_input_array(INPUT_POST, array(
                'dir_paths' => array(
                    'filter'  => FILTER_DEFAULT,
                    'flags'   => FILTER_REQUIRE_SCALAR,
                    'options' => array(
                        'default' => ''
                    )
                ),
                'file_paths' => array(
                    'filter'  => FILTER_DEFAULT,
                    'flags'   => FILTER_REQUIRE_SCALAR,
                    'options' => array(
                        'default' => ''
                    )
                ),
            )
        );

        try {
            DUP_Util::hasCapability('export', DUP_Util::SECURE_ISSUE_THROW);

            //CONTROLLER LOGIC
            $package = DUP_Package::getActive();

            //DIRS
            $dir_filters = ($package->Archive->FilterOn) ? $package->Archive->FilterDirs.';'.$inputData['dir_paths'] : $inputData['dir_paths'];
            $dir_filters = $package->Archive->parseDirectoryFilter($dir_filters);
            $changed     = $package->Archive->saveActiveItem($package, 'FilterDirs', $dir_filters);

            //FILES
            $file_filters = ($package->Archive->FilterOn) ? $package->Archive->FilterFiles.';'.$inputData['file_paths'] : $inputData['file_paths'];
            $file_filters = $package->Archive->parseFileFilter($file_filters);
            $changed      = $package->Archive->saveActiveItem($package, 'FilterFiles', $file_filters);

            if (!$package->Archive->FilterOn && !empty($package->Archive->FilterExts)) {
                $changed = $package->Archive->saveActiveItem($package, 'FilterExts', '');
            }

            $changed = $package->Archive->saveActiveItem($package, 'FilterOn', 1);

            //Result
            $package              = DUP_Package::getActive();
            $payload['dirs-in']   = esc_html(sanitize_text_field($inputData['dir_paths']));
            $payload['dir-out']   = esc_html($package->Archive->FilterDirs);
            $payload['files-in']  = esc_html(sanitize_text_field($inputData['file_paths']));
            $payload['files-out'] = esc_html($package->Archive->FilterFiles);

            //RETURN RESULT
            $test = ($changed) ? DUP_CTRL_Status::SUCCESS : DUP_CTRL_Status::FAILED;
            $result->process($payload, $test);
        }
        catch (Exception $exc) {
            $result->processError($exc);
        }
    }

    /**
     * Get active package status
     *
     * <code>
     * //JavaScript Ajax Request
     * Duplicator.Package.getActivePackageStatus()
     * </code>
     */
    public function getActivePackageStatus()
    {
        DUP_Handler::init_error_handler();
        check_ajax_referer('DUP_CTRL_Package_getActivePackageStatus', 'nonce');

        $result = new DUP_CTRL_Result($this);

        try {
            DUP_Util::hasCapability('export', DUP_Util::SECURE_ISSUE_THROW);
            //CONTROLLER LOGIC
            $active_package_id = DUP_Settings::Get('active_package_id');
            $package           = DUP_Package::getByID($active_package_id);
            $payload           = array();

            if ($package != null) {
                $test              = DUP_CTRL_Status::SUCCESS;
                $payload['status'] = $package->Status;
            } else {
                $test = DUP_CTRL_Status::FAILED;
            }

            //RETURN RESULT
            return $result->process($payload, $test);
        }
        catch (Exception $exc) {
            $result->processError($exc);
        }
    }
}