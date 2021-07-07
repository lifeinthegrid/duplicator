<?php
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

DUP_Handler::init_error_handler();
DUP_Util::hasCapability('export');

global $wpdb;

//COMMON HEADER DISPLAY
require_once(DUPLICATOR_PLUGIN_PATH . '/assets/js/javascript.php');
require_once(DUPLICATOR_PLUGIN_PATH . '/views/inc.header.php');

$current_view =  (isset($_REQUEST['action']) && $_REQUEST['action'] == 'detail') ? 'detail' : 'main';

$download_installer_nonce = wp_create_nonce('duplicator_download_installer');
?>
<script>
    jQuery(document).ready(function($) {

        Duplicator.Pack.DownloadInstaller = function (json)
        {
            var actionLocation = ajaxurl + '?action=duplicator_download_installer&id=' + json.id + '&hash='+ json.hash +'&nonce=' + '<?php echo $download_installer_nonce; ?>';
            location.href      = actionLocation;
            return false;
        };

        Duplicator.Pack.DownloadFile = function(json)
        {
            var link = document.createElement('a');        
            link.target = "_blank";
            link.download = json.filename;
            link.href= json.url;
            document.body.appendChild(link);
            
            // click event fire
            if (document.dispatchEvent) {
                // First create an event
                var click_ev = document.createEvent("MouseEvents");
                // initialize the event
                click_ev.initEvent("click", true /* bubble */, true /* cancelable */);
                // trigger the event
                link.dispatchEvent(click_ev);
            } else if (document.fireEvent) {
                link.fireEvent('onclick');
            } else if (link.click()) {
                link.click()
            }

            document.body.removeChild(link);
            return false;
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
				case 'main': include(DUPLICATOR_PLUGIN_PATH.'views/packages/main/controller.php'); break;
				case 'detail' : include(DUPLICATOR_PLUGIN_PATH.'views/packages/details/controller.php'); break;
    }
    ?>
</div>