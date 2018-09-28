<?php
if ( ! defined('DUPLICATOR_VERSION') ) exit; // Exit if accessed directly

require_once(DUPLICATOR_PLUGIN_PATH . '/ctrls/ctrl.base.php'); 
require_once(DUPLICATOR_PLUGIN_PATH . '/classes/utilities/class.u.scancheck.php');

/**
 * Controller for Tools 
 * @package Dupicator\ctrls
 */
class DUP_CTRL_Tools extends DUP_CTRL_Base
{	 
	/**
     *  Init this instance of the object
     */
	function __construct() 
	{
		add_action('wp_ajax_DUP_CTRL_Tools_runScanValidator', array($this, 'runScanValidator'));
        add_action('wp_ajax_DUP_CTRL_Tools_getTraceLog', array($this, 'getTraceLog'));
	}
	
	/** 
     * Calls the ScanValidator and returns a JSON result
	 * 
	 * @param string $_POST['scan-path']		The path to start scanning from, defaults to DUPLICATOR_WPROOTPATH
	 * @param bool   $_POST['scan-recursive']	Recursivly search the path
	 * 
	 * @notes: Testing = /wp-admin/admin-ajax.php?action=DUP_CTRL_Tools_runScanValidator
     */
	public function runScanValidator($post)
	{
        @set_time_limit(0);
		$post = $this->postParamMerge($post);
		check_ajax_referer($post['action'], 'nonce');
		
		$result = new DUP_CTRL_Result($this);
		 
		try 
		{
			//CONTROLLER LOGIC
			$path = isset($post['scan-path']) ? $post['scan-path'] : DUPLICATOR_WPROOTPATH;
			if (!is_dir($path)) {
				throw new Exception("Invalid directory provided '{$path}'!");
			}
			$scanner = new DUP_ScanCheck();
			$scanner->recursion = (isset($post['scan-recursive']) && $post['scan-recursive'] != 'false') ? true : false;
			$payload = $scanner->run($path);

			//RETURN RESULT
			$test = ($payload->fileCount > 0)
					? DUP_CTRL_Status::SUCCESS
					: DUP_CTRL_Status::FAILED;
			$result->process($payload, $test);
		} 
		catch (Exception $exc) 
		{
			$result->processError($exc);
		}
    }

    public function getTraceLog()
    {
        DUP_Log::Trace("enter");
        
        $nonce = sanitize_text_field($_GET['nonce']);
        if (!wp_verify_nonce($nonce, 'DUP_CTRL_Tools_getTraceLog')) {
            die('Security issue');
        }

        Dup_Util::hasCapability('export');

        $request     = stripslashes_deep($_REQUEST);
        $file_path   = DUP_Log::GetTraceFilepath();
        $backup_path = DUP_Log::GetBackupTraceFilepath();
        $zip_path    = DUPLICATOR_SSDIR_PATH."/".DUPLICATOR_ZIPPED_LOG_FILENAME;
        $zipped      = DUP_Zip_U::zipFile($file_path, $zip_path, true, null, true);

        if ($zipped && file_exists($backup_path)) {
            $zipped = DUP_Zip_U::zipFile($backup_path, $zip_path, false, null, true);
        }

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-Transfer-Encoding: binary");

        $fp = fopen($zip_path, 'rb');

        if (($fp !== false) && $zipped) {
            $zip_filename = basename($zip_path);

            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"$zip_filename\";");

            // required or large files wont work
            if (ob_get_length()) {
                ob_end_clean();
            }

            DUP_Log::trace("streaming $zip_path");
            if (fpassthru($fp) === false) {
                DUP_Log::trace("Error with fpassthru for $zip_path");
            }

            fclose($fp);
            @unlink($zip_path);
        } else {
            header("Content-Type: text/plain");
            header("Content-Disposition: attachment; filename=\"error.txt\";");
            if ($zipped === false) {
                $message = "Couldn't create zip file.";
            } else {
                $message = "Couldn't open $file_path.";
            }
            DUP_Log::trace($message);
            echo $message;
        }

        exit;
    }
	
}
