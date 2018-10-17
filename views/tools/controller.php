<?php
require_once(DUPLICATOR_PLUGIN_PATH . '/classes/ui/class.ui.dialog.php');

DUP_Util::hasCapability('manage_options');

global $wpdb;

//COMMON HEADER DISPLAY
require_once(DUPLICATOR_PLUGIN_PATH . '/assets/js/javascript.php');
require_once(DUPLICATOR_PLUGIN_PATH . '/views/inc.header.php');
$current_tab = isset($_REQUEST['tab']) ? sanitize_text_field($_REQUEST['tab']) : 'diagnostics';
?>

<style>
	div.lite-sub-tabs {padding: 10px 0 10px 0; font-size: 14px}
	div.dup-no-files-msg {padding:10px 0 10px 0}
</style>
<?php

$installer_files = DUP_Server::getInstallerFiles();
$package_name = (isset($_GET['package'])) ?  sanitize_text_field($_GET['package']) : '';

if (empty($package_name)) {
	$installer_file_path = DUPLICATOR_WPROOTPATH . 'installer.php';
	if (file_exists($installer_file_path)) {
		$installer_file_data = file_get_contents($installer_file_path);
		$start_pos = strpos($installer_file_data, '$GLOBALS[\'FW_PACKAGE_NAME\']		= \'');
        if (false !== $start_pos) {
            $end_pos = stripos($installer_file_data, "';", $start_pos);
			      $substr_start_pos = $start_pos + 32;
            $substr_len = ($end_pos - $start_pos - 32);
      			$temp_archive_file = substr($installer_file_data, $substr_start_pos, $substr_len);
            if (!empty($temp_archive_file)) {
                $temp_archive_file_path = DUPLICATOR_WPROOTPATH . $temp_archive_file;
                if (file_exists($temp_archive_file_path)) {
                    $package_name = $temp_archive_file;
                }
            }
        }
	}
}

$package_path = (isset($package_name)) ?  DUPLICATOR_WPROOTPATH . $package_name : '';

$txt_found		= __('File Found: Unable to remove', 'duplicator');
$txt_removed	= __('File Removed', 'duplicator');
$nonce			= wp_create_nonce('duplicator_cleanup_page');
$section		= (isset($_GET['section'])) ?$_GET['section']:'';

if($current_tab == "diagnostics"  && ($section == "info" || $section == '')){
	$ajax_nonce	= wp_create_nonce('DUP_CTRL_Tools_deleteInstallerFiles');
	$_GET['action'] = isset($_GET['action']) ? $_GET['action'] : 'display';

	if (isset($_GET['action'])) {
		if (($_GET['action'] == 'installer') || ($_GET['action'] == 'legacy') || ($_GET['action'] == 'tmp-cache')) {
			$verify_nonce = $_REQUEST['_wpnonce'];
			if (!wp_verify_nonce($verify_nonce, 'duplicator_cleanup_page')) {
				exit; // Get out of here bad nounce!
			}
		}
	}

	switch ($_GET['action']) {
		case 'installer' :
			$action_response = __('Installer file cleanup ran!', 'duplicator');
			$css_hide_msg = 'div.error {display:none}';
			break;
		case 'legacy':
			DUP_Settings::LegacyClean();
			$action_response = __('Legacy data removed.', 'duplicator');
			break;
		case 'tmp-cache':
			DUP_Package::tempFileCleanup(true);
			$action_response = __('Build cache removed.', 'duplicator');
			break;
	}

	 if ($_GET['action'] != 'display')  :	?>
		<div id="message" class="notice notice-success is-dismissible">
			<p><b><?php echo esc_html($action_response); ?></b></p>
			<?php if ( $_GET['action'] == 'installer') :  ?>
				<?php
					$html = "";

					//REMOVE CORE INSTALLER FILES
					$installer_files = DUP_Server::getInstallerFiles();
					$installer_file_found = false;
					foreach ($installer_files as $file => $path) {
						$file_path = '';
						if (false !== stripos($file, '[hash]')) {
							$glob_files = glob($path);
							if (!empty($glob_files)) {
								$installer_file_found = true;
								$file_path = $glob_files[0];
							}
						} elseif (file_exists($path)) {
							$installer_file_found = true;
							$file_path = $path;
						}

                        if (!empty($file_path)) {
                            @unlink($file_path);
                            if (file_exists($file_path)) {
								$installer_file_found = true;
								echo "<div class='failed'><i class='fa fa-exclamation-triangle'></i> {$txt_found} - {$file_path}  </div>";
							} else {
								echo "<div class='success'> <i class='fa fa-check'></i> {$txt_removed} - {$file_path}	</div>";
							}
                        }
					}

					//No way to know exact name of archive file except from installer.
					//The only place where the package can be removed is from installer
					//So just show a message if removing from plugin.
					if (file_exists($package_path)) {
						$path_parts	 = pathinfo($package_name);
						$path_parts	 = (isset($path_parts['extension'])) ? $path_parts['extension'] : '';
						if ($path_parts == "zip" && !is_dir($package_path)) {
							$html .= (@unlink($package_path))
										? "<div class='success'><i class='fa fa-check'></i> ".esc_html($txt_removed)." - ".esc_html($package_path)."</div>"
										: "<div class='failed'><i class='fa fa-exclamation-triangle'></i> ".esc_html($txt_found)." - ".esc_html($package_path)."</div>";
						}
					}

					echo $html;

					if (!$installer_file_found) {
						echo '<div class="dup-no-files-msg success">'
								. '<i class="fa fa-check"></i> <b>'.__('No Duplicator installer files found on this WordPress Site.', 'duplicator').'</b>'
							. '</div>';
					}
				?>
				<div style="font-style: italic; max-width:1000px; padding-top:15px">
					<b><?php  esc_html_e('Security Notes', 'duplicator')?>:</b>
					<?php  _e('If the installer files do not successfully get removed with this action, then they WILL need to be removed manually through your hosts control panel  '
						 . ' or FTP.  Please remove all installer files to avoid any security issues on this site.  For more details please visit '
						. 'the FAQ link <a href="https://snapcreek.com/duplicator/docs/faqs-tech/#faq-installer-295-q" target="_blank">Which files need to be removed after an install?</a>', 'duplicator')?>
					<br/><br/>
				</div>

			<?php endif; ?>
		</div>
	<?php endif;
	if(isset($_GET['action']) && $_GET['action']=="installer" && get_option("duplicator_exe_safe_mode")){
		$safe_title = __('This site has been successfully migrated!');
		$safe_msg = __('Please test the entire site to validate the migration process!');

		switch(get_option("duplicator_exe_safe_mode")){
			//safe_mode basic
			case 1:
				$safe_msg = __('NOTICE: Safe mode (Basic) was enabled during install, be sure to re-enable all your plugins.');
			break;
			//safe_mode advance
			case 2:
				$safe_msg = __('NOTICE: Safe mode (Advanced) was enabled during install, be sure to re-enable all your plugins.');

				$temp_theme = null;
				$active_theme = wp_get_theme();
				$available_themes = wp_get_themes();
				foreach($available_themes as $theme){
					if($temp_theme == null && $theme->stylesheet != $active_theme->stylesheet){
						$temp_theme = array('stylesheet' => $theme->stylesheet, 'template' => $theme->template);
						break;
					}
				}

				if($temp_theme != null){
					//switch to another theme then backto default
					switch_theme($temp_theme['template'], $temp_theme['stylesheet']);
					switch_theme($active_theme->template, $active_theme->stylesheet);
				}
			break;
		}

		if (! DUP_Server::hasInstallerFiles()) {
			echo  "<div class='notice notice-success cleanup-notice'><p><b class='title'><i class='fa fa-check-circle'></i> ".esc_html($safe_title)."</b> "
				. "<div class='notice-safemode'>".esc_html($safe_msg)."</p></div></div>";
		}

		delete_option("duplicator_exe_safe_mode");
	}
}
?>

<div class="wrap">

    <?php duplicator_header(__("Tools", 'duplicator')) ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=duplicator-tools&tab=diagnostics" class="nav-tab <?php echo ($current_tab == 'diagnostics') ? 'nav-tab-active' : '' ?>"> <?php  esc_html_e('Diagnostics', 'duplicator'); ?></a>
		<a href="?page=duplicator-tools&tab=templates" class="nav-tab <?php echo ($current_tab == 'templates') ? 'nav-tab-active' : '' ?>"> <?php  esc_html_e('Templates', 'duplicator'); ?></a>
    </h2>

    <?php
    switch ($current_tab) {
		case 'diagnostics': include('diagnostics/main.php');
			break;
		case 'templates': include('templates.php');
			break;
	}
	?>
</div>