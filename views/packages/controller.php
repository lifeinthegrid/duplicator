<?php

DUP_Util::CheckPermissions('export');

global $wpdb;

//COMMON HEADER DISPLAY
require_once(DUPLICATOR_PLUGIN_PATH . '/views/javascript.php');
require_once(DUPLICATOR_PLUGIN_PATH . '/views/inc.header.php');
$current_tab = isset($_REQUEST['tab']) ? esc_html($_REQUEST['tab']) : 'list';
?>

<style>
	//TOOLBAR TABLE
	table#dup-toolbar td {padding:0px; white-space:nowrap;}
	table#dup-toolbar td.dup-toolbar-btns {width:100%; text-align: right; vertical-align: bottom}
	table#dup-toolbar td.dup-toolbar-btns a {font-size:16px}
	table#dup-toolbar td.dup-toolbar-btns span {font-size:16px}
	table#dup-toolbar {width:100%; border:0px solid red; padding: 0; margin:0 0 10px 0; height: 35px}
	
    /*WIZARD TABS */
    div#dup-wiz {padding:0px; margin:0;  }
    div#dup-wiz-steps {margin:10px 0px 0px 10px; padding:0px;  clear:both; font-size:12px; min-width:350px;}
    div#dup-wiz-title {padding:2px 0px 0px 0px; font-size:18px;}
	div#dup-wiz-steps a span {font-size:10px}
    /* wiz-steps numbers */
    #dup-wiz span {display:block;float:left; text-align:center; width:14px; margin:4px 5px 0px 0px; line-height:13px; color:#ccc; border:1px solid #CCCCCC; border-radius:5px; }
    /* wiz-steps default*/
    #dup-wiz a { position:relative; display:block; width:auto; min-width:55px; height:25px; margin-right:8px; padding:0px 10px 0px 10px; float:left; line-height:24px; color:#000; background:#E4E4E4; border-radius:5px }
	/* wiz-steps active*/
    #dup-wiz .active-step a {color:#fff; background:#BBBBBB;}
    #dup-wiz .active-step span {color:#fff; border:1px solid #fff;}
	/* wiz-steps completed */
    #dup-wiz .completed-step a {color:#E1E1E1; background:#BBBBBB; }
    #dup-wiz .completed-step span {color:#E1E1E1;}
    /*Footer */
    div.dup-button-footer input {min-width: 105px}
    div.dup-button-footer {padding: 1px 10px 0px 0px; text-align: right}
</style>

<script>
    jQuery(document).ready(function($) {

        /*	----------------------------------------
         *	METHOD: Triggers the download of an installer/package file
         *	@param name		Window name to open
         *	@param button	Button to change color */
        Duplicator.Pack.DownloadFile = function(event, button) {
            if (event.data != undefined) {
                window.open(event.data.name, '_self');
            } else {
                $(button).addClass('dup-button-selected');
                window.open(event, '_self');
            }
            return false;
        }

        /*	----------------------------------------
         * METHOD: Toggle links with sub-details */
        Duplicator.Pack.ToggleSystemDetails = function(event) {
            if ($(this).parents('div').children(event.data.selector).is(":hidden")) {
                $(this).children('span').addClass('ui-icon-triangle-1-s').removeClass('ui-icon-triangle-1-e');
                ;
                $(this).parents('div').children(event.data.selector).show(250);
            } else {
                $(this).children('span').addClass('ui-icon-triangle-1-e').removeClass('ui-icon-triangle-1-s');
                $(this).parents('div').children(event.data.selector).hide(250);
            }
        }

    });
</script>

<div class="wrap">
<?php
	duplicator_header(__("Packages", 'wpduplicator'));

	switch ($current_tab) {
		case 'list': include('list.base.php');
			break;
		case 'new1': include('new1.base.php');
			break;
		case 'new2': include('new2.base.php');
			break;
		case 'new3': include('new3.base.php');
			break;
	}
?>
</div>