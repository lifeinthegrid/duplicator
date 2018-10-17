<?php
/**
 * Used to display notices in the WordPress Admin area
 * This class takes advatage of the 'admin_notice' action.
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2
 *
 * @package Duplicator
 * @subpackage classes/ui
 * @copyright (c) 2017, Snapcreek LLC
 * @since 1.1.0
 *
 */

// Exit if accessed directly
if (!defined('DUPLICATOR_VERSION')) {
    exit;
}

class DUP_UI_Notice
{
    /**
     * Shows a display message in the wp-admin if any reserved files are found
     * 
     * @return string   Html formated text notice warnings
     */
    public static function showReservedFilesNotice()
    {
        //Show only on Duplicator pages and Dashboard when plugin is active
        $dup_active = is_plugin_active('duplicator/duplicator.php');
        $dup_perm   = current_user_can('manage_options');
        if (!$dup_active || !$dup_perm)
			return;
		
		$screen = get_current_screen();
        if (!isset($screen))
			return;
  
		$is_installer_cleanup_req = ($screen->id == 'duplicator_page_duplicator-tools' && isset($_GET['action']) && $_GET['action'] == 'installer');
        if (DUP_Server::hasInstallerFiles() && !$is_installer_cleanup_req) {

			$on_active_tab = isset($_GET['section'])? $_GET['section']: '';
            echo '<div class="updated notice-success" id="dup-global-error-reserved-files"><p>';

			//Safe Mode Notice
			$safe_html = '';
			if(get_option("duplicator_exe_safe_mode", 0) > 0 ){
				$safe_msg1 = __('Safe Mode:', 'duplicator');
				$safe_msg2 = __('During the install safe mode was enabled deactivating all plugins.<br/> Please be sure to ', 'duplicator');
				$safe_msg3 = __('reactivate the plugins', 'duplicator');
				$safe_html = "<div class='notice-safemode'><b>{$safe_msg1}</b><br/>{$safe_msg2} <a href='plugins.php'>{$safe_msg3}</a>!</div><br/>";
			}

			//On Tools > Cleanup Page

            if ($screen->id == 'duplicator_page_duplicator-tools' && ($on_active_tab == "info" || $on_active_tab == '') ) {

				$title = __('This site has been successfully migrated!', 'duplicator');
				$msg1  = __('Final step(s):', 'duplicator');
				$msg2  = __('This message will be removed after all installer files are removed.  Installer files must be removed to maintain a secure site.<br/>'
									. 'Click the link above or button below to remove all installer files and complete the migration.', 'duplicator');

				echo "<b class='pass-msg'><i class='fa fa-check-circle'></i> ".esc_html($title)."</b> <br/> {$safe_html} <b>".esc_html($msg1)."</b> <br/>";
				printf("1. <a href='javascript:void(0)' onclick='jQuery(\"#dup-remove-installer-files-btn\").click()'>%s</a><br/>", esc_html__('Remove Installation Files Now!', 'duplicator'));
				printf("2. <a href='https://wordpress.org/support/plugin/duplicator/reviews/?filter=5' target='wporg'>%s</a> <br/> ", esc_html__('Optionally, Review Duplicator at WordPress.org...', 'duplicator'));
                echo "<div class='pass-msg' style='color:maroon'>" . $msg2 . "</div>";
			//All other Pages
            } else {

				$title = __('Migration Almost Complete!', 'duplicator');
				$msg   = __('Reserved Duplicator installation files have been detected in the root directory.  Please delete these installation files to '
						. 'avoid security issues. <br/> Go to:Duplicator > Tools > Information >Stored Data and click the "Remove Installation Files" button', 'duplicator');

				$nonce = wp_create_nonce('duplicator_cleanup_page');
				$url   = self_admin_url('admin.php?page=duplicator-tools&tab=diagnostics&section=info&_wpnonce='.$nonce);
				echo "<b>{$title}</b><br/> {$safe_html} {$msg}";
				@printf("<br/><a href='{$url}'>%s</a>", __('Take me there now!', 'duplicator'));

            }
            echo "</p></div>";
        } 
    }

    /**
     * Shows a message for redirecting a page
     *
     * @return string   The location to redirect to
     */
    public static function redirect($location)
    {
        echo '<div class="dup-redirect"><i class="fa fa-circle-o-notch fa-spin fa-fw"></i>';
			__('Redirecting Please Wait...', 'duplicator');
		echo '</div>';
		echo "<script>window.location = '{$location}';</script>";
		die(__('Invalid token permissions to perform this request.', 'duplicator'));
    }
}
