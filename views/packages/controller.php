<?php
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

DUP_Handler::init_error_handler();
DUP_Util::hasCapability('export');

global $wpdb;

//COMMON HEADER DISPLAY
require_once(DUPLICATOR_PLUGIN_PATH . '/assets/js/javascript.php');
require_once(DUPLICATOR_PLUGIN_PATH . '/views/inc.header.php');

$current_view =  (isset($_REQUEST['action']) && $_REQUEST['action'] == 'detail') ? 'detail' : 'main';

$get_package_file_nonce = wp_create_nonce('DUP_CTRL_Package_getPackageFile');
?>

<script>

</script>

<script>
    jQuery(document).ready(function($) {

        Duplicator.Pack.DownloadPackageFile = function (id, hash, file)
		{
            var actionLocation = ajaxurl + '?action=duplicator_download&id=' + id + '&hash=' + hash + '&file=' + file;
            window.location.assign(actionLocation)
        };

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
		    switch ($current_view) {
				case 'main': include('main/controller.php'); break;
				case 'detail' : include('details/controller.php'); break;
            break;	
    }
    ?>
</div>