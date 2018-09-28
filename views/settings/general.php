<?php
global $wp_version;
global $wpdb;

$action_updated = null;
$action_response = __("General Settings Saved", 'duplicator');

//SAVE RESULTS
if (isset($_POST['action']) && $_POST['action'] == 'save') {

	//Nonce Check
	if (!isset($_POST['dup_settings_save_nonce_field']) || !wp_verify_nonce($_POST['dup_settings_save_nonce_field'], 'dup_settings_save')) {
		die('Invalid token permissions to perform this request.');
	}

	DUP_Settings::Set('uninstall_settings', isset($_POST['uninstall_settings']) ? "1" : "0");
	DUP_Settings::Set('uninstall_files', isset($_POST['uninstall_files']) ? "1" : "0");
	DUP_Settings::Set('uninstall_tables', isset($_POST['uninstall_tables']) ? "1" : "0");
	DUP_Settings::Set('storage_htaccess_off', isset($_POST['storage_htaccess_off']) ? "1" : "0");

	DUP_Settings::Set('wpfront_integrate', isset($_POST['wpfront_integrate']) ? "1" : "0");
	DUP_Settings::Set('package_debug', isset($_POST['package_debug']) ? "1" : "0");

    if(isset($_REQUEST['trace_log_enabled'])) {

        dup_log::trace("#### trace log enabled");
        // Trace on

        if (DUP_Settings::Get('trace_log_enabled') == 0) {
            DUP_Log::DeleteTraceLog();
        }

        DUP_Settings::Set('trace_log_enabled', 1);

    } else {
        dup_log::trace("#### trace log disabled");

        // Trace off
        DUP_Settings::Set('trace_log_enabled', 0);
    }

    DUP_Settings::Save();
	$action_updated = true;
	DUP_Util::initSnapshotDirectory();
}

$trace_log_enabled = DUP_Settings::Get('trace_log_enabled');
$uninstall_settings = DUP_Settings::Get('uninstall_settings');
$uninstall_files = DUP_Settings::Get('uninstall_files');
$uninstall_tables = DUP_Settings::Get('uninstall_tables');
$storage_htaccess_off = DUP_Settings::Get('storage_htaccess_off');

$wpfront_integrate = DUP_Settings::Get('wpfront_integrate');
$wpfront_ready = apply_filters('wpfront_user_role_editor_duplicator_integration_ready', false);
$package_debug = DUP_Settings::Get('package_debug');

?>

<style>
    form#dup-settings-form input[type=text] {width: 400px; }
    div.dup-feature-found {padding:3px; border:1px solid silver; background: #f7fcfe; border-radius: 3px; width:400px; font-size: 12px}
    div.dup-feature-notfound {padding:5px; border:1px solid silver; background: #fcf3ef; border-radius: 3px; width:500px; font-size: 13px; line-height: 18px}
</style>

<form id="dup-settings-form" action="<?php echo admin_url('admin.php?page=duplicator-settings&tab=general'); ?>" method="post">

    <?php wp_nonce_field('dup_settings_save', 'dup_settings_save_nonce_field', false); ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="page"   value="duplicator-settings">

    <?php if ($action_updated) : ?>
        <div id="message" class="notice notice-success is-dismissible dup-wpnotice-box"><p><?php echo esc_html($action_response); ?></p></div>
    <?php endif; ?>	


    <h3 class="title"><?php esc_html_e("Plugin", 'duplicator') ?> </h3>
    <hr size="1" />
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Version", 'duplicator'); ?></label></th>
            <td><?php echo DUPLICATOR_VERSION ?></td>
        </tr>	
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Uninstall", 'duplicator'); ?></label></th>
            <td>
                <input type="checkbox" name="uninstall_settings" id="uninstall_settings" <?php echo ($uninstall_settings) ? 'checked="checked"' : ''; ?> /> 
                <label for="uninstall_settings"><?php esc_html_e("Delete Plugin Settings", 'duplicator') ?> </label><br/>

                <input type="checkbox" name="uninstall_files" id="uninstall_files" <?php echo ($uninstall_files) ? 'checked="checked"' : ''; ?> /> 
                <label for="uninstall_files"><?php esc_html_e("Delete Entire Storage Directory", 'duplicator') ?></label><br/>

            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Storage", 'duplicator'); ?></label></th>
            <td>
                <?php esc_html_e("Full Path", 'duplicator'); ?>: 
                <?php echo DUP_Util::safePath(DUPLICATOR_SSDIR_PATH); ?><br/><br/>
                <input type="checkbox" name="storage_htaccess_off" id="storage_htaccess_off" <?php echo ($storage_htaccess_off) ? 'checked="checked"' : ''; ?> /> 
                <label for="storage_htaccess_off"><?php esc_html_e("Disable .htaccess File In Storage Directory", 'duplicator') ?> </label>
                <p class="description">
                    <?php esc_html_e("Disable if issues occur when downloading installer/archive files.", 'duplicator'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label><?php esc_html_e("Custom Roles", 'duplicator'); ?></label></th>
            <td>
                <input type="checkbox" name="wpfront_integrate" id="wpfront_integrate" <?php echo ($wpfront_integrate) ? 'checked="checked"' : ''; ?> <?php echo $wpfront_ready ? '' : 'disabled'; ?> />
                <label for="wpfront_integrate"><?php esc_html_e("Enable User Role Editor Plugin Integration", 'duplicator'); ?></label>
					<p class="description">
						<?php printf('%s <a href="https://wordpress.org/plugins/wpfront-user-role-editor/" target="_blank">%s</a> %s'
									 . ' <a href="https://wpfront.com/user-role-editor-pro/?ref=3" target="_blank">%s</a> %s '
									 . ' <a href="https://wpfront.com/integrations/duplicator-integration/" target="_blank">%s</a>',
								esc_html__('The User Role Editor Plugin', 'duplicator'),
								esc_html__('Free', 'duplicator'),
								esc_html__('or', 'duplicator'),
								esc_html__('Professional', 'duplicator'),
								esc_html__('must be installed to use', 'duplicator'),
								esc_html__('this feature.', 'duplicator')
								);
						?>
					</p>
            </td>
        </tr>
    </table>


    <h3 class="title"><?php esc_html_e("Debug", 'duplicator') ?> </h3>
    <hr size="1" />
    <table class="form-table">
        <tr>
            <th scope="row"><label><?php esc_html_e("Debugging", 'duplicator'); ?></label></th>
            <td>
                <input type="checkbox" name="package_debug" id="package_debug" <?php echo ($package_debug) ? 'checked="checked"' : ''; ?> />
                <label for="package_debug"><?php esc_html_e("Enable debug options throughout user interface", 'duplicator'); ?></label>
				<p class="description"><?php esc_html_e("Refresh page after saving to show/hide Debug menu", 'duplicator'); ?></p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Trace Log", 'duplicator'); ?></label></th>
            <td>
                <input type="checkbox" name="trace_log_enabled" id="trace_log_enabled" <?php echo ($trace_log_enabled == 1) ? 'checked="checked"' : ''; ?> />
                <label for="trace_log_enabled"><?php esc_html_e("Enabled", 'duplicator') ?> </label><br/>
                <p class="description">
                    <?php
                        esc_html_e('Turns on detailed operation logging. Logging will occur in both PHP error and local trace logs.');
                        echo ('<br/>');
                        esc_html_e('WARNING: Only turn on this setting when asked to by support as tracing will impact performance.', 'duplicator');
                    ?>
                </p><br/>
                <button class="button" <?php if(!DUP_Log::TraceFileExists()) { echo 'disabled'; } ?> onclick="Duplicator.Pack.DownloadTraceLog(); return false">
                    <i class="fa fa-download"></i> <?php echo esc_html__('Download Trace Log', 'duplicator') . ' (' . DUP_LOG::GetTraceStatus() . ')'; ?>
                </button>
            </td>
        </tr>
    </table><br/>

    <p class="submit" style="margin: 20px 0px 0xp 5px;">
		<br/>
		<input type="submit" name="submit" id="submit" class="button-primary" value="<?php esc_attr_e("Save General Settings", 'duplicator') ?>" style="display: inline-block;" />
	</p>
	
</form>

<script>
jQuery(document).ready(function($) 
{
	// which: 0=installer, 1=archive, 2=sql file, 3=log
	Duplicator.Pack.DownloadTraceLog = function () {
		var actionLocation = ajaxurl + '?action=DUP_CTRL_Tools_getTraceLog';
		location.href = actionLocation;
	};
});
</script>