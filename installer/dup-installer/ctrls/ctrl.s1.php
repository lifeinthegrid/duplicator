<?php
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

require_once($GLOBALS['DUPX_INIT'].'/ctrls/classes/class.ctrl.extraction.php');

$data = DUP_LITE_Extraction::getInstance()->runExtraction();
die(DupLiteSnapJsonU::wp_json_encode($data));
