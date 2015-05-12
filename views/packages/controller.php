<?php

DUP_Util::CheckPermissions('export');

global $wpdb;

//COMMON HEADER DISPLAY
require_once(DUPLICATOR_PLUGIN_PATH . '/views/javascript.php');
require_once(DUPLICATOR_PLUGIN_PATH . '/views/inc.header.php');
$current_tab = isset($_REQUEST['tab']) ? esc_html($_REQUEST['tab']) : 'list';
?>

<style>
    /*WIZARD TABS */
    div#dup-wiz {padding:0px; margin:7px 0px 10px 0px; height: 30px }
    div#dup-wiz-steps {margin:0px 0px 0px 10px; padding:0px;  clear:both; font-weight:bold;font-size:12px; min-width:250px }
    div#dup-wiz-title {padding:2px 0px 0px 0px; font-size:18px;}
    /* wiz-steps numbers */
    #dup-wiz span {display:block;float:left; text-align:center; width:15px; margin:3px 4px 0px 0px; line-height:15px; color:#ccc; border:1px solid #CCCCCC; border-radius:4px;}
    /* wiz-steps default*/
    #dup-wiz a { position:relative; display:block; width:auto; height:24px; margin-right:18px; padding:0px 10px 0px 3px; float:left;  line-height:24px; color:#000; background:#E4E4E4; }
    #dup-wiz a:before { width:0px; height:0px; border-top:12px solid #E4E4E4; border-bottom:12px solid #E4E4E4; border-left:12px solid transparent; position:absolute; content:""; top:0px; left:-12px; }
    #dup-wiz a:after { width:0; height:0; border-top:12px solid transparent; border-bottom:12px solid transparent; border-left:12px solid #E4E4E4; position:absolute; content:""; top:0px; right:-12px; }
    /* wiz-steps completed */
    #dup-wiz .completed-step a {color:#ccc; background:#999;}
    #dup-wiz .completed-step a:before {border-top:12px solid #999; border-bottom:12px solid #999;}
    #dup-wiz .completed-step a:after {border-left:12px solid #999;}
    #dup-wiz .completed-step span {color:#ccc;}
    /* wiz-steps active*/
    #dup-wiz .active-step a {color:#fff; background:#999;}
    #dup-wiz .active-step a:before {border-top:12px solid #999; border-bottom:12px solid #999;}
    #dup-wiz .active-step a:after {border-left:12px solid #999;}
    #dup-wiz .active-step span {color:#fff;}

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
    <!-- h2 required here for general system messages  -->
    <h2 style='display:none'></h2>

<?php duplicator_header(__("Packages", 'wpduplicator')) ?>

    <h2 class="nav-tab-wrapper">  
        <a href="?page=duplicator" class="nav-tab <?php echo ($current_tab == 'list') ? 'nav-tab-active' : '' ?>"><?php _e("Packages", 'wpduplicator') ?></a>  
        <a href="?page=duplicator&tab=new1" class="nav-tab <?php echo ($current_tab != 'list') ? 'nav-tab-active' : '' ?>"><?php _e("Create New", 'wpduplicator') ?></a>  
    </h2> 	

<?php
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