<?php
wp_enqueue_script('dup-handlebars');
require_once(DUPLICATOR_PLUGIN_PATH . '/classes/utilities/class.u.scancheck.php');
require_once(DUPLICATOR_PLUGIN_PATH . '/classes/class.io.php');

$installer_files	= DUP_Server::getInstallerFiles();
$package_name		= (isset($_GET['package'])) ?  esc_html($_GET['package']) : '';
$package_path		= (isset($_GET['package'])) ?  DUPLICATOR_WPROOTPATH . esc_html($_GET['package']) : '';

$txt_found		 = __('File Found', 'duplicator');
$txt_removed	 = __('File Removed', 'duplicator');
$txt_archive_msg = __("<b>Archive File:</b> The archive file has a unique hashed name when downloaded.  Leaving the archive file on your server does not impose a security"
					. " risk if the file was not renamed.  It is still recommended to remove the archive file after install,"
					. " especially if it was renamed.", 'duplicator');

$nonce	  = wp_create_nonce('duplicator_cleanup_page');
$section  = (isset($_GET['section'])) ?$_GET['section']:'';

if ($section == "info" || $section == '') {

	$ajax_nonce	= wp_create_nonce('DUP_CTRL_Tools_deleteInstallerFiles');
	$_GET['action'] = isset($_GET['action']) ? $_GET['action'] : 'display';

	if (isset($_REQUEST['_wpnonce'])) {
		if (($_GET['action'] == 'installer') || ($_GET['action'] == 'tmp-cache')) {
			if (! wp_verify_nonce($_REQUEST['_wpnonce'], 'duplicator_cleanup_page')) {
				exit; // Get out of here bad nounce!
			}
		}
	}

	switch ($_GET['action']) {
		case 'installer' :
			$action_response = __('Installer file cleanup ran!', 'duplicator');
			break;
		case 'tmp-cache':
			DUP_Package::tempFileCleanup(true);
			$action_response = __('Build cache removed.', 'duplicator');
			break;
	}

	 if ($_GET['action'] != 'display')  :	?>
		<div id="message" class="notice notice-success is-dismissible  dup-wpnotice-box">
			<p><b><?php echo $action_response; ?></b></p>
			<?php if ( $_GET['action'] == 'installer') :  ?>
				<?php
					$html = "";

					//REMOVE CORE INSTALLER FILES
					$installer_files = DUP_Server::getInstallerFiles();
					foreach ($installer_files as $file => $path) {
						if (! is_dir($path)) {
							DUP_IO::deleteFile($path);

						} elseif (is_dir($path)) {
							// Extra protection to ensure we only are deleting the installer directory
							if(SnapLibStringU::contains($path, 'dup-installer')) {
								if(file_exists("{$path}/archive.cfg")) {
									DUP_IO::deleteTree($path);
								}
							}
						}

						echo (file_exists($path))
							? "<div class='failed'><i class='fa fa-exclamation-triangle'></i> {$txt_found} - {$path}  </div>"
							: "<div class='success'> <i class='fa fa-check'></i> {$txt_removed} - {$path}	</div>";
					}

					//No way to know exact name of archive file except from installer.
					//The only place where the package can be removed is from installer
					//So just show a message if removing from plugin.
					if (file_exists($package_path)) {
						$path_parts	 = pathinfo($package_name);
						$path_parts	 = (isset($path_parts['extension'])) ? $path_parts['extension'] : '';
						$valid_ext   = ($path_parts == "zip" || $path_parts == "daf");
						if ($valid_ext  && !is_dir($package_path)) {
							$html .= (@unlink($package_path))
										? "<div class='success'><i class='fa fa-check'></i> {$txt_removed} - {$package_path}</div>"
										: "<div class='failed'><i class='fa fa-exclamation-triangle'></i> {$txt_found} - {$package_path}</div>";
						}
					}

					echo $html;
				 ?><br/>

				<div style="font-style: italic; max-width:900px">
					<b><?php _e('Security Notes', 'duplicator')?>:</b>
					<?php _e('If the installer files do not successfully get removed with this action, then they WILL need to be removed manually through your hosts control panel,  '
						 . ' file system or FTP.  Please remove all installer files listed above to avoid leaving open security issues on your server.', 'duplicator')?>
					<br/><br/>
					<?php echo $txt_archive_msg; ?>
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
			echo  "<div class='notice notice-success cleanup-notice'><p><b class='title'><i class='fa fa-check-circle'></i> {$safe_title}</b> "
				. "<div class='notice-safemode'>{$safe_msg}</p></div></div>";
		}

		delete_option("duplicator_exe_safe_mode");
	}
}
?>


<form id="dup-settings-form" action="<?php echo admin_url( 'admin.php?page=duplicator-tools&tab=diagnostics&section=info' ); ?>" method="post">
	<?php wp_nonce_field( 'duplicator_settings_page', '_wpnonce', false ); ?>
	<input type="hidden" id="dup-remove-options-value" name="remove-options" value="">
	
	<?php
		if (isset($_POST['remove-options'])) {
			$action_result = DUP_Settings::DeleteWPOption($_POST['remove-options']);
			switch ($_POST['remove-options'])
			{
				case 'duplicator_settings'		 : 	$remove_response = __('Plugin settings reset.', 'duplicator');		break;
				case 'duplicator_ui_view_state'  : 	$remove_response = __('View state settings reset.', 'duplicator');	 break;
				case 'duplicator_package_active' : 	$remove_response = __('Active package settings reset.', 'duplicator'); break;
			}
		}
		
		if (! empty($remove_response))  {
			echo "<div id='message' class='notice notice-success is-dismissible dup-wpnotice-box'><p>{$remove_response}</p></div>";
		}

		include_once 'inc.data.php';
		include_once 'inc.settings.php';
		include_once 'inc.validator.php';
		include_once 'inc.phpinfo.php';
	?>
</form>
