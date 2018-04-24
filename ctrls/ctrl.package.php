<?php
require_once(DUPLICATOR_PLUGIN_PATH.'/ctrls/ctrl.base.php');
require_once(DUPLICATOR_PLUGIN_PATH.'/classes/utilities/class.u.scancheck.php');
require_once(DUPLICATOR_PLUGIN_PATH.'/classes/utilities/class.u.json.php');
require_once(DUPLICATOR_PLUGIN_PATH.'/classes/package/class.pack.php');
require_once(DUPLICATOR_PLUGIN_PATH.'/classes/package/duparchive/class.pack.archive.duparchive.state.create.php');

/**
 *  DUPLICATOR_PACKAGE_SCAN
 *  Returns a JSON scan report object which contains data about the system
 *  
 *  @return json   JSON report object
 *  @example	   to test: /wp-admin/admin-ajax.php?action=duplicator_package_scan
 */
function duplicator_package_scan()
{

    header('Content-Type: application/json;');
    DUP_Util::hasCapability('export');

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

    DUP_Util::hasCapability('export');

    check_ajax_referer('duplicator_package_build', 'nonce');

    header('Content-Type: application/json');

    @set_time_limit(0);
    $errLevel = error_reporting();
    error_reporting(E_ERROR);
    DUP_Util::initSnapshotDirectory();

    $Package = DUP_Package::getActive();
    
    $Package->save('zip');

    DUP_Settings::Set('active_package_id', $Package->ID);

    DUP_Settings::Save();

    if (!is_readable(DUPLICATOR_SSDIR_PATH_TMP."/{$Package->ScanFile}")) {
        die("The scan result file was not found.  Please run the scan step before building the package.");
    }

    $Package->runZipBuild();

    //JSON:Debug Response
    //Pass = 1, Warn = 2, Fail = 3
    $json            = array();
    $json['status']  = 1;
    $json['package'] = $Package;
    $json['runtime'] = $Package->Runtime;
    $json['exeSize'] = $Package->ExeSize;
    $json['archiveSize'] = $Package->ZipSize;
    $json_response   = json_encode($json);

    //Simulate a Host Build Interrupt
    //die(0);

    error_reporting($errLevel);
    die($json_response);
}

/**
 *  duplicator_duparchive_package_build
 *  Returns the package result status
 *
 *  @return json   JSON object of package results
 */
function duplicator_duparchive_package_build()
{
    DUP_LOG::Trace("call to duplicator_duparchive_package_build");

    DUP_Util::hasCapability('export');
    check_ajax_referer('duplicator_duparchive_package_build', 'nonce');
    header('Content-Type: application/json');
        
    @set_time_limit(0);
    $errLevel = error_reporting();
    error_reporting(E_ERROR);

    // The DupArchive build process always works on a saved package so the first time through save the active package to the package table. 
    // After that, just retrieve it.
    $active_package_id = DUP_Settings::Get('active_package_id');

    if ($active_package_id == -1) {

        $package = DUP_Package::getActive();

        $package->save('daf');

        DUP_Log::TraceObject("saving active package as new id={$package->ID}", package);

        DUP_Settings::Set('active_package_id', $package->ID);

        DUP_Settings::Save();
    } else {

        DUP_Log::TraceObject("getting active package by id {$active_package_id}", package);
        $package = DUP_Package::getByID($active_package_id);
    }

    if (!is_readable(DUPLICATOR_SSDIR_PATH_TMP."/{$package->ScanFile}")) {
        die("The scan result file was not found.  Please run the scan step before building the package.");
    }

    if ($package === null) {
        die("There is no active package.");
    }

    if($package->Status == DUP_PackageStatus::ERROR) {
        $hasCompleted = true;
    } else {
        try {
            $hasCompleted = $package->runDupArchiveBuild();
        }
        catch(Exception $ex) {
            Dup_Log::Trace('#### caught exception');
            Dup_Log::Error('Caught exception', $ex->getMessage(), Dup_ErrorBehavior::LogOnly);

            Dup_Log::Trace('#### after log');

            /* @var $package DUP_Package */
            $package->setStatus(DUP_PackageStatus::ERROR);

            $hasCompleted = true;
        }
    }
    
    $json = array();

    $json['failures'] = array_merge($package->BuildProgress->build_failures, $package->BuildProgress->validation_failures);

    DUP_LOG::traceObject("#### failures", $json['failures']);
    
    //JSON:Debug Response
    //Pass = 1, Warn = 2, 3 = Failure, 4 = Not Done
    if ($hasCompleted) {
        
        Dup_Log::Trace('#### completed');

        if($package->Status == DUP_PackageStatus::ERROR) {

            Dup_Log::Trace('#### error');

            $error_message = __('Error building DupArchive package') . '<br/>';

            foreach($json['failures'] as $failure) {
                $error_message .= implode(',', $failure->description);
            }

            Dup_Log::Error("Build failed so sending back error", $error_message, Dup_ErrorBehavior::LogOnly);
            Dup_Log::Trace('#### after log 2');

            $json['status'] = 3;
        } else {
            Dup_Log::Info("sending back success status");
            $json['status']  = 1;
        }

        Dup_Log::Trace('#### json package');

        $json['package']     = $package;
        $json['runtime']     = $package->Runtime;
        $json['exeSize']     = $package->ExeSize;
        $json['archiveSize'] = $package->ZipSize;
    } else {
        Dup_Log::Info("sending back continue status");
        $json['status'] = 4;
    }

    $json_response = json_encode($json);

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

    DUP_Util::hasCapability('export');
    check_ajax_referer('package_list', 'nonce');

    try {
        global $wpdb;
        $json     = array();
        $post     = stripslashes_deep($_POST);
        $tblName  = $wpdb->prefix.'duplicator_packages';
        $postIDs  = isset($post['duplicator_delid']) ? $post['duplicator_delid'] : null;
        $list     = explode(",", $postIDs);
        $delCount = 0;

        if ($postIDs != null) {

            foreach ($list as $id) {

                $getResult = $wpdb->get_results($wpdb->prepare("SELECT name, hash FROM `{$tblName}` WHERE id = %d", $id), ARRAY_A);

                if ($getResult) {
                    $row       = $getResult[0];
                    $nameHash  = "{$row['name']}_{$row['hash']}";
                    $delResult = $wpdb->query($wpdb->prepare("DELETE FROM `{$tblName}` WHERE id = %d", $id));
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
                        //Unfinished Zip files
                        $tmpZip = DUPLICATOR_SSDIR_PATH_TMP."/{$nameHash}_archive.zip.*";
                        if ($tmpZip !== false) {
                            array_map('unlink', glob($tmpZip));
                        }
                        $delCount++;
                    }
                }
            }
        }
    } catch (Exception $e) {
        $json['error'] = "{$e}";
        die(json_encode($json));
    }

    $json['ids']     = "{$postIDs}";
    $json['removed'] = $delCount;
    die(json_encode($json));
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
    }

    /**
     * Removed all reserved installer files names
     *
     * @param string $_POST['dir_paths']		A semi-colon separated list of directory paths
     *
     * @return string	Returns all of the active directory filters as a ";" separated string
     */
    public function addQuickFilters($post)
    {
        $post   = $this->postParamMerge($post);
        check_ajax_referer($post['action'], 'nonce');
        $result = new DUP_CTRL_Result($this);

        try {
            //CONTROLLER LOGIC
            $package = DUP_Package::getActive();

            //DIRS
            $dir_filters = $package->Archive->FilterDirs.';'.$post['dir_paths'];
            $dir_filters = $package->Archive->parseDirectoryFilter($dir_filters);
            $changed     = $package->Archive->saveActiveItem($package, 'FilterDirs', $dir_filters);

            //FILES
            $file_filters = $package->Archive->FilterFiles.';'.$post['file_paths'];
            $file_filters = $package->Archive->parseFileFilter($file_filters);
            $changed      = $package->Archive->saveActiveItem($package, 'FilterFiles', $file_filters);


            $changed = $package->Archive->saveActiveItem($package, 'FilterOn', 1);

            //Result
            $package              = DUP_Package::getActive();
            $payload['dirs-in']   = $post['dir_paths'];
            $payload['dir-out']   = $package->Archive->FilterDirs;
            $payload['files-in']  = $post['file_paths'];
            $payload['files-out'] = $package->Archive->FilterFiles;

            //RETURN RESULT
            $test = ($changed) ? DUP_CTRL_Status::SUCCESS : DUP_CTRL_Status::FAILED;
            $result->process($payload, $test);
        } catch (Exception $exc) {
            $result->processError($exc);
        }
    }

    /**
     * Download the requested package file
     *
     * @param string $_POST['which']
     * @param string $_POST['package_id']
     *
     * @return downloadable file
     */
    function getPackageFile($post)
    {
        $params = $this->postParamMerge($post);
        $params = $this->getParamMerge($params);
//       check_ajax_referer($post['action'], 'nonce');

        $result = new DUP_CTRL_Result($this);

        try {
            //CONTROLLER LOGIC

            DUP_Util::hasCapability('export');

            $request   = stripslashes_deep($_REQUEST);
            $which     = (int) $request['which'];
            $packageId = (int) $request['package_id'];
            $package   = DUP_Package::getByID($packageId);
            $isBinary  = ($which != DUP_PackageFileType::Log);
            $filePath  = $package->getLocalPackageFile($which);

            //OUTPUT: Installer, Archive, SQL File
            if ($isBinary) {
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: private", false);
                header("Content-Transfer-Encoding: binary");

                if ($filePath != null) {
                    $fp = fopen($filePath, 'rb');
                    if ($fp !== false) {

                        if ($which == DUP_PackageFileType::Installer) {
                            $fileName = 'installer.php';
                        } else {
                            $fileName = basename($filePath);
                        }

                        header("Content-Type: application/octet-stream");
                        header("Content-Disposition: attachment; filename=\"{$fileName}\";");

                        @ob_end_clean(); // required or large files wont work
                        DUP_Log::Trace("streaming $filePath");

                        if (fpassthru($fp) === false) {
                            DUP_Log::Trace("Error with fpassthru for {$filePath}");
                        }
                        fclose($fp);
                        die(); //Supress additional ouput
                    } else {
                        header("Content-Type: text/plain");
                        header("Content-Disposition: attachment; filename=\"error.txt\";");
                        $message = "Couldn't open $filePath.";
                        DUP_Log::Trace($message);
                        echo $message;
                    }
                } else {
                    $message = __("Couldn't find a local copy of the file requested.", 'duplicator');

                    header("Content-Type: text/plain");
                    header("Content-Disposition: attachment; filename=\"error.txt\";");

                    // Report that we couldn't find the file
                    DUP_Log::Trace($message);
                    echo $message;
                }

                //OUTPUT: Log File
            } else {
                if ($filePath != null) {
                    header("Content-Type: text/plain");
                    $text = file_get_contents($filePath);

                    die($text);
                } else {
                    $message = __("Couldn't find a local copy of the file requested.", 'duplicator');
                    echo $message;
                }
            }
        } catch (Exception $exc) {
            $result->processError($exc);
        }
    }
}