<?php
global $wp_version;
global $wpdb;

$action_updated = null;
$action_response = __("Settings Saved", 'wpduplicator');

//SAVE RESULTS
if (isset($_POST['action']) && $_POST['action'] == 'save') {
    //General Tab
    //Plugin
    DUP_Settings::Set('uninstall_settings', isset($_POST['uninstall_settings']) ? "1" : "0");
    DUP_Settings::Set('uninstall_files', isset($_POST['uninstall_files']) ? "1" : "0");
    DUP_Settings::Set('uninstall_tables', isset($_POST['uninstall_tables']) ? "1" : "0");
    DUP_Settings::Set('storage_htaccess_off', isset($_POST['storage_htaccess_off']) ? "1" : "0");

    //Package
	$enable_mysqldump = isset($_POST['package_dbmode']) && $_POST['package_dbmode'] == 'mysql' ? "1" : "0";
    DUP_Settings::Set('package_debug', isset($_POST['package_debug']) ? "1" : "0");
    DUP_Settings::Set('package_zip_flush', isset($_POST['package_zip_flush']) ? "1" : "0");
	DUP_Settings::Set('package_mysqldump', $enable_mysqldump ? "1" : "0");
	DUP_Settings::Set('package_phpdump_qrylimit', isset($_POST['package_phpdump_qrylimit']) ? $_POST['package_phpdump_qrylimit'] : "100");
    DUP_Settings::Set('package_mysqldump_path', trim($_POST['package_mysqldump_path']));

    //WPFront
    DUP_Settings::Set('wpfront_integrate', isset($_POST['wpfront_integrate']) ? "1" : "0");
    
    $action_updated = DUP_Settings::Save();
    DUP_Util::InitSnapshotDirectory();
}

$uninstall_settings = DUP_Settings::Get('uninstall_settings');
$uninstall_files = DUP_Settings::Get('uninstall_files');
$uninstall_tables = DUP_Settings::Get('uninstall_tables');
$storage_htaccess_off = DUP_Settings::Get('storage_htaccess_off');

$package_debug = DUP_Settings::Get('package_debug');
$package_zip_flush = DUP_Settings::Get('package_zip_flush');

$phpdump_chunkopts = array("20", "100", "500", "1000", "2000");

$package_phpdump_qrylimit = DUP_Settings::Get('package_phpdump_qrylimit');
$package_mysqldump = DUP_Settings::Get('package_mysqldump');
$package_mysqldump_path = trim(DUP_Settings::Get('package_mysqldump_path'));

$wpfront_integrate = DUP_Settings::Get('wpfront_integrate');
$wpfront_ready = apply_filters('wpfront_user_role_editor_duplicator_integration_ready', false);

$mysqlDumpPath = DUP_Database::GetMySqlDumpPath();
$mysqlDumpFound = ($mysqlDumpPath) ? true : false;


?>

<style>
    form#dup-settings-form input[type=text] {width: 400px; }
    input#package_mysqldump_path_found {margin-top:5px}
    div.dup-feature-found {padding:3px; border:1px solid silver; background: #f7fcfe; border-radius: 3px; width:400px; font-size: 12px}
    div.dup-feature-notfound {padding:3px; border:1px solid silver; background: #fcf3ef; border-radius: 3px; width:400px; font-size: 12px}
</style>

<form id="dup-settings-form" action="<?php echo admin_url('admin.php?page=duplicator-settings&tab=general'); ?>" method="post">

    <?php wp_nonce_field('duplicator_settings_page'); ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="page"   value="duplicator-settings">

    <?php if ($action_updated) : ?>
        <div id="message" class="updated below-h2"><p><?php echo $action_response; ?></p></div>
    <?php endif; ?>	


    <!-- ===============================
    PLUG-IN SETTINGS -->
    <h3 class="title"><?php _e("Plugin", 'wpduplicator') ?> </h3>
    <hr size="1" />
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label><?php _e("Version", 'wpduplicator'); ?></label></th>
            <td><?php echo DUPLICATOR_VERSION ?></td>
        </tr>	
        <tr valign="top">
            <th scope="row"><label><?php _e("Uninstall", 'wpduplicator'); ?></label></th>
            <td>
                <input type="checkbox" name="uninstall_settings" id="uninstall_settings" <?php echo ($uninstall_settings) ? 'checked="checked"' : ''; ?> /> 
                <label for="uninstall_settings"><?php _e("Delete Plugin Settings", 'wpduplicator') ?> </label><br/>

                <input type="checkbox" name="uninstall_files" id="uninstall_files" <?php echo ($uninstall_files) ? 'checked="checked"' : ''; ?> /> 
                <label for="uninstall_files"><?php _e("Delete Entire Storage Directory", 'wpduplicator') ?></label><br/>

            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label><?php _e("Storage", 'wpduplicator'); ?></label></th>
            <td>
                <?php _e("Full Path", 'wpduplicator'); ?>: 
                <?php echo DUP_Util::SafePath(DUPLICATOR_SSDIR_PATH); ?><br/><br/>
                <input type="checkbox" name="storage_htaccess_off" id="storage_htaccess_off" <?php echo ($storage_htaccess_off) ? 'checked="checked"' : ''; ?> /> 
                <label for="storage_htaccess_off"><?php _e("Disable .htaccess File In Storage Directory", 'wpduplicator') ?> </label>
                <p class="description">
                    <?php _e("Disable if issues occur when downloading installer/archive files.", 'wpduplicator'); ?>
                </p>
            </td>
        </tr>	
    </table>


    <!-- ===============================
    PACKAGE SETTINGS -->
    <h3 class="title"><?php _e("Package", 'wpduplicator') ?> </h3>
    <hr size="1" />
    <table class="form-table">
        <tr>
            <th scope="row"><label><?php _e("Archive Flush", 'wpduplicator'); ?></label></th>
            <td>
                <input type="checkbox" name="package_zip_flush" id="package_zip_flush" <?php echo ($package_zip_flush) ? 'checked="checked"' : ''; ?> />
                <label for="package_zip_flush"><?php _e("Attempt Network Keep Alive", 'wpduplicator'); ?></label>
                <i style="font-size:12px">(<?php _e("recommended only for large archives", 'wpduplicator'); ?>)</i> 
                <p class="description">
                    <?php _e("This will attempt to keep a network connection established for large archives.", 'wpduplicator'); ?>
                </p>
            </td>
        </tr>		
        <tr>
            <th scope="row"><label><?php _e("Database Build", 'wpduplicator'); ?></label></th>
            <td>
				<input type="radio" name="package_dbmode" id="package_phpdump" value="php" <?php echo (! $package_mysqldump) ? 'checked="checked"' : ''; ?> />
                <label for="package_phpdump"><?php _e("Use PHP", 'wpduplicator'); ?></label> &nbsp;
				
				<div style="margin:5px 0px 0px 25px">
					<label for="package_phpdump_qrylimit"><?php _e("Query Limit Size", 'wpduplicator'); ?></label> &nbsp;
					<select name="package_phpdump_qrylimit" id="package_phpdump_qrylimit">
						<?php 
							foreach($phpdump_chunkopts as $value) {
								$selected = ( $package_phpdump_qrylimit == $value ? "selected='selected'" : '' );
								echo "<option {$selected} value='{$value}'>" . number_format($value)  . '</option>';
							}
						?>
					</select>
					 <i style="font-size:12px">(<?php _e("higher values speed up build times but uses more memory", 'wpduplicator'); ?>)</i> 
					
				</div><br/>

                <?php if (!DUP_Util::IsShellExecAvailable()) : ?>
                    <p class="description">
                        <?php
                        _e("This server does not have shell_exec configured to run.", 'wpduplicator');
                        echo '<br/>';
                        _e("Please contact the server administrator to enable this feature.", 'wpduplicator');
                        ?>
                    </p>
                <?php else : ?>
                    <input type="radio" name="package_dbmode" value="mysql" id="package_mysqldump" <?php echo ($package_mysqldump) ? 'checked="checked"' : ''; ?> />
                    <label for="package_mysqldump"><?php _e("Use mysqldump", 'wpduplicator'); ?></label> &nbsp;
                    <i style="font-size:12px">(<?php _e("recommended for large databases", 'wpduplicator'); ?>)</i> <br/><br/>

                    <div style="margin:5px 0px 0px 25px">
                        <?php if ($mysqlDumpFound) : ?>
                            <div class="dup-feature-found">
                                <?php _e("Working Path:", 'wpduplicator'); ?> &nbsp;
                                <i><?php echo $mysqlDumpPath ?></i>
                            </div><br/>
                        <?php else : ?>
                            <div class="dup-feature-notfound">
                                <?php
                                _e('Mysqldump was not found at its default location or the location provided.  Please enter a path to a valid location where mysqldump can run.  If the problem persist contact your server administrator.', 'wpduplicator');
                                ?>
                            </div><br/>
                        <?php endif; ?>

                        <label><?php _e("Add Custom Path:", 'wpduplicator'); ?></label><br/>
                        <input type="text" name="package_mysqldump_path" id="package_mysqldump_path" value="<?php echo $package_mysqldump_path; ?> " />
                        <p class="description">
                            <?php
                            _e("This is the path to your mysqldump program.", 'wpduplicator');
                            ?>
                        </p>
                    </div>

                <?php endif; ?>
            </td>
        </tr>	
        <tr>
            <th scope="row"><label><?php _e("Package Debug", 'wpduplicator'); ?></label></th>
            <td>
                <input type="checkbox" name="package_debug" id="package_debug" <?php echo ($package_debug) ? 'checked="checked"' : ''; ?> />
                <label for="package_debug"><?php _e("Show Package Debug Status in Packages Screen", 'wpduplicator'); ?></label>
            </td>
        </tr>	

    </table>

    <!-- ===============================
    WPFRONT SETTINGS -->
    <h3 class="title"><?php _e("Roles & Capabilities", 'wpduplicator') ?> </h3>
    <hr size="1" />

    <table class="form-table">
        <tr>
            <th scope="row"><label><?php _e("Custom Roles", 'wpduplicator'); ?></label></th>
            <td>
                <input type="checkbox" name="wpfront_integrate" id="wpfront_integrate" <?php echo ($wpfront_integrate) ? 'checked="checked"' : ''; ?> <?php echo $wpfront_ready ? '' : 'disabled'; ?> />
                <label for="wpfront_integrate"><?php _e("Enable User Role Editor Plugin Integration", 'wpduplicator'); ?></label>
				
				<div style="margin:15px 0px 0px 25px">
					<p class="description">
						<?php printf('%s <a href="https://wordpress.org/plugins/wpfront-user-role-editor/" target="_blank">%s</a> %s'
									 . ' <a href="https://wpfront.com/user-role-editor-pro/?ref=3" target="_blank">%s</a> %s ' 
									 . ' <a href="https://wpfront.com/integrations/duplicator-integration/" target="_blank">%s</a>',
								__('The User Role Editor Plugin', 'wpduplicator'),
								__('Free', 'wpduplicator'),
								__('or', 'wpduplicator'),
								__('Professional', 'wpduplicator'),
								__('must be installed to use', 'wpduplicator'),
								__('this feature.', 'wpduplicator')
								); 
						?> 
					</p>
				</div>
            </td>
        </tr>	

    </table><br/>

    <p class="submit" style="margin: 20px 0px 0xp 5px;">
		<br/>
		<input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e("Save Settings", 'wpduplicator') ?>" style="display: inline-block;" />
	</p>
	
</form>