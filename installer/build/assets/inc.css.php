<?php
	// Exit if accessed directly
	if (! defined('DUPLICATOR_INIT')) {
		$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] :$_SERVER['HTTP_HOST'];
		$_baseURL =  "http://" . $_baseURL;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location:$_baseURL");
		exit; 
	}
?>
<style>
	body {font-family:"Open Sans",sans-serif;}
	body,td,th {font-size:13px;color:#000;}
	fieldset {border:1px solid silver; border-radius:5px; padding:10px}
	h3 {margin:1px; padding:1px; font-size:14px;}
	a {color:#222}
	a:hover{color:gray}
	input[type=text] { width:500px; border-radius:3px; height:17px; font-size:12px; border:1px solid silver;}
	input.readonly {background-color:#efefef;}
	i.small {font-size:11px}

	/* ============================
	COMMON VIEW ELEMENTS*/
	div#content {border:1px solid #CDCDCD;  width:750px; min-height:550px; margin:auto; margin-top:18px; border-radius:5px;  box-shadow:0 8px 6px -6px #333;}
	div#content-inner {padding:10px 30px; min-height:550px}
	form.content-form {min-height:550px; position:relative; line-height:17px}	
	div.dup-logfile-link {float:right; font-weight:normal; font-size:12px}
	
	/* WIZARD STEPS */
	table.header-wizard {border-top-left-radius:5px; border-top-right-radius:5px; width:100%; box-shadow:0 6px 4px -4px #777;	background-color:#F1F1F1}
	div#dup-wiz {padding:0px; margin:7px 0 10px 20px; height:20px }
	div#dup-wiz-steps {margin:0px 0 0 10px; padding:0px;  clear:both; font-weight:bold;font-size:12px; min-width:250px }
	#dup-wiz span {display:block;float:left; text-align:center; width:15px; margin:3px 4px 0 0px; line-height:15px; color:#ccc; border:1px solid #CCCCCC; border-radius:4px;}
	/* WIZ-DEFAULT*/
	#dup-wiz a { position:relative; display:block; width:auto; height:24px; margin-right:18px; padding:0px 10px 0 3px; float:left;  line-height:24px; color:#000; background:#E4E4E4; }
	#dup-wiz a:before { width:0px; height:0px; border-top:12px solid #E4E4E4; border-bottom:12px solid #E4E4E4; border-left:12px solid transparent; position:absolute; content:""; top:0px; left:-12px; }
	#dup-wiz a:after { width:0; height:0; border-top:12px solid transparent; border-bottom:12px solid transparent; border-left:12px solid #E4E4E4; position:absolute; content:""; top:0px; right:-12px; }
	/* WIZ-COMPLETED */
	#dup-wiz .completed-step a {color:#ccc; background:#999;}
	#dup-wiz .completed-step a:before {border-top:12px solid #999; border-bottom:12px solid #999;}
	#dup-wiz .completed-step a:after {border-left:12px solid #999;}
	#dup-wiz .completed-step span {color:#ccc;}
	/* WIZ-ACTIVE */
	#dup-wiz .active-step a {color:#fff; background:#999;}
	#dup-wiz .active-step a:before {border-top:12px solid #999; border-bottom:12px solid #999;}
	#dup-wiz .active-step a:after {border-left:12px solid #999;}
	#dup-wiz .active-step span {color:#fff;}
	
	/*Help */
	select#dup-hlp-lnk {border-radius:3px; font-size:11px; margin:3px 5px 0 0px; background-color:#efefef; border:1px solid silver}
	div.dup-help-page {padding:5px 0 0 5px}
	div.dup-help-page fieldset {margin-bottom:25px}
	div#dup-main-help h3 {background-color:#dfdfdf; border:1px solid silver; border-radius:5px; padding:3px; margin-bottom:8px}

	div#progress-area {padding:5px; margin:150px 0 0 0px; text-align:center;}
	div#ajaxerr-data {padding:5px; height:350px; width:99%; border:1px solid silver; border-radius:5px; background-color:#efefef; font-size:12px; overflow-y:scroll}
	div.title-header {padding:2px; border-bottom:1px solid silver; font-weight:bold; margin-bottom:5px;}
	
	/*BOXES:Expandable sections */
	div.dup-box {padding:0px; display:block; background-color:#fff; border:1px solid #e5e5e5; box-shadow:0 1px 1px rgba(0,0,0,.04);}
	div.dup-box-title {font-size:14px; padding:5px 0 0 10px; font-weight:bold; cursor:pointer; height:23px; margin:0px; background-color:#F9F9F9}
	div.dup-box-title:hover {background-color:#efefef;}
	div.dup-box-arrow {text-decoration:none!important; float:right; width:27px; height:30px; font-size:16px; cursor:pointer; padding:1px 0 0 0px; white-space:nowrap}
	div.dup-box-panel {padding:10px 15px 10px 15px;  border-top:1px solid #EEEEEE; margin:-1px 0 0 0px; background-color:#F9F9F9;   box-shadow:0 8px 6px -6px #999;}

	/* ============================
	STEP 1 VIEW */
	i#dup-step1-sys-req-msg {font-weight:normal; display:block; padding:0px 0 0 20px;}
	div.circle-pass, div.circle-fail {display:block;width:13px;height:13px;border-radius:50px;font-size:20px;color:#fff;line-height:100px;text-align:center;text-decoration:none;box-shadow:1px 1px 2px #000;background:#207D1D;opacity:0.95; display:inline-block;}
	div.circle-fail {background:#9A0D1D !important;}
	select#logging {font-size:11px}
	table.dup-step1-inputs {width:100%; border:0px;}
	table.dup-step1-inputs td{white-space:nowrap; padding:2px;}
	table.dup-step1-inputs td:first-child{width:125px}
	div.dup-step1-modes {padding:0px 15px 0 0px;}
	div#dup-step1-dbconn {margin:auto; text-align:center; margin:15px 0 20px 0px}
	input#dup-step1-dbconn-btn {font-size:11px; height:20px; border:1px solid gray; border-radius:3px; cursor:pointer}
	div.dup-db-test label{display:inline-block; width:150px; font-weight:bold; white-space:nowrap;}
	div.dup-db-test small{display:block; margin:5px 0 5px 0px; font-style:italic; color:#444}
	div#dbconn-test-msg {font-size:12px}
	div#dup-step1-dbconn-status {border:1px solid silver; border-radius:3px; background-color:#f9f9f9; padding:10px; margin-top:10px; height:125px; overflow-y: scroll}
	
	/*Warning Area and Message */
	div#dup-step1-warning {margin-top:40px;padding:5px;font-size:11px; color:gray; line-height:12px;font-style:italic; overflow-y:scroll; height:75px; border:1px solid #dfdfdf; background-color:#fff; border-radius:3px}
	div#dup-step1-warning-check {padding:5px; font-size:12px; font-weight:normal; font-style:italic;}
	div#dup-step1-warning-emptydb {display:none; color:#AF2222; margin:0px 0 0 20px}
	div#dup-step1-tryagain {padding-top:50px; text-align:center; width:100%; font-size:16px; color:#444; font-weight:bold;}

	/*Dialog*/
	div#dup-step1-dialog-data {height:90%; font-size:11px; padding:5px; line-height:16px; }
	td.dup-step1-dialog-data-details {padding:0px 0 0 30px; border-radius:4px; line-height:14px; font-size:11px; display:none}
	td.dup-step1-dialog-data-details b {width:50px;display:inline-block}
	.dup-pass {display:inline-block; color:green;}
	.dup-ok {display:inline-block; color:#5860C7;}
	.dup-fail {display:inline-block; color:#AF0000;}
	hr.dup-dots { border:none; border-top:1px dotted silver; height:1px; width:100%;}
	div.dup-ui-error {padding-top:2px; font-size:14px}
	div.help {color:#555; font-style:italic; font-size:11px}

	/* ============================
	STEP 2 VIEW */
	table.table-inputs-step2{width:100%; border:0px;}
	table.table-inputs-step2 td{white-space:nowrap; padding:2px;}
	div#dup-step2-adv-opts {margin-top:5px; }
	div.dup-step2-allnonelinks {font-size:11px; float:right;}

	/* ============================
	STEP 3 VIEW */
	div.dup-step3-final-msg {height:110px; border:1px solid #CDCDCD; padding:8px;font-size:12px; border-radius:5px;box-shadow:0 4px 2px -2px #777;}
	div.dup-step3-final-title {color:#BE2323;}
	div.dup-step3-connect {font-size:12px; text-align:center; font-style:italic; position:absolute; bottom:10px; padding:10px; width:100%; margin-top:20px}
	table.dup-step3-report-results,
	table.dup-step3-report-errs {border-collapse:collapse; border:1px solid #dfdfdf; }
	table.dup-step3-report-errs  td {text-align:center; width:33%}
	table.dup-step3-report-results th, table.dup-step3-report-errs th {background-color:#efefef; padding:0px; font-size:12px; padding:0px}
	table.dup-step3-report-results td, table.dup-step3-report-errs td {padding:0px; white-space:nowrap; border:1px solid #dfdfdf; text-align:center; font-size:11px}
	table.dup-step3-report-results td:first-child {text-align:left; font-weight:bold; padding-left:3px}

	div.dup-step3-err-title a {}
	div.dup-step3-err-msg {padding:8px;  display:none; border:1px dashed #999; margin:10px 0 20px 0px; border-radius:5px;}
	div.dup-step3-err-msg div.content{padding:5px; font-size:11px; line-height:17px; max-height:125px; overflow-y:scroll; border:1px solid silver; margin:3px;  }
	div.dup-step3-err-msg div.info{padding:2px; background-color:#FCFEC5; border:1px solid silver; border-radius:5px; font-size:11px; line-height:16px }
	table.dup-step3-final-step {width:100%;}
	table.dup-step3-final-step td {padding:5px 15px 5px 5px}
	table.dup-step3-final-step td:first-child {white-space:nowrap; font-weight:bold}
	div.dup-step3-go-back {border-bottom:1px dotted #dfdfdf; border-top:1px dotted #dfdfdf; margin:auto; text-align:center}

	/* ============================
	BUTTONS */	
	div.dup-footer-buttons {position:absolute; bottom:10px; padding:10px; width:100%; text-align:right;}
	div.dup-footer-buttons  input, button {
		color:#000; font-size:12px; border-radius:5px;	padding:6px 8px 4px 8px; border:1px solid #999;
		background-color:#F1F1F1;
		background-image:-ms-linear-gradient(top, #F9F9F9, #ECECEC);
		background-image:-moz-linear-gradient(top, #F9F9F9, #ECECEC);
		background-image:linear-gradient(top, #F9F9F9, #ECECEC);
	}
	div.dup-footer-buttons input[disabled=disabled]{background-color:#F4F4F4; color:silver; border:1px solid silver;}
	div.dup-footer-buttons  input, button {cursor:pointer; border:1px solid #000; }

	/*!
	 * password indicator
	 */
	.top_testresult{font-weight:bold;	font-size:11px; color:#222;	padding:1px 1px 1px 4px; margin:4px 0 0 0px; width:495px; dislay:inline-block}
	.top_testresult span{margin:0;}
	.top_shortPass{background:#edabab; border:1px solid #bc0000;display:block;}
	.top_badPass{background:#edabab;border:1px solid #bc0000;display:block;}
	.top_goodPass{background:#ffffe0; border:1px solid #e6db55;	display:block;}
	.top_strongPass{background:#d3edab;	border:1px solid #73bc00; display:block;}

	/* ============================
	CUSTOME OVERIDE */
	/*Hide X button on close dialog*/
	div.ui-dialog-titlebar button.ui-dialog-titlebar-close {display:none !important}
	
	/*================================================
	PARSLEY:Overrides*/
	input.parsley-error, textarea.parsley-error {
	  color:#B94A48 !important;
	  background-color:#F2DEDE !important;
	  border:1px solid #EED3D7 !important;
	}
	ul.parsley-error-list {margin:1px 0 0 -40px; list-style-type:none; font-size:10px}
</style>