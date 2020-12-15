<?php
defined('ABSPATH') || defined('DUPXABSPATH') || exit;
require_once(DUPLICATOR_PLUGIN_PATH . '/classes/ui/class.ui.dialog.php');
require_once(DUPLICATOR_PLUGIN_PATH . '/assets/js/javascript.php');
require_once(DUPLICATOR_PLUGIN_PATH . '/views/inc.header.php');

global $wpdb;
global $wp_version;

$dup_local_tools_path = dirname(__FILE__);

DUP_Handler::init_error_handler();
DUP_Util::hasCapability('manage_options');
$current_tab = isset($_REQUEST['tab']) ? esc_html($_REQUEST['tab']) : 'diagnostics';
if ('d' == $current_tab) {
	$current_tab = 'diagnostics';
}
?>

<div class="wrap">	
    <?php duplicator_header(__("Tools", 'duplicator')) ?>

    <h2 class="nav-tab-wrapper">  
        <a href="?page=duplicator-tools&tab=diagnostics" class="nav-tab <?php echo ($current_tab == 'diagnostics') ? 'nav-tab-active' : '' ?>"> <?php esc_html_e('Diagnostics', 'duplicator'); ?></a>
		<a href="?page=duplicator-tools&tab=templates" class="nav-tab <?php echo ($current_tab == 'templates') ? 'nav-tab-active' : '' ?>"> <?php esc_html_e('Templates', 'duplicator'); ?></a>
        <a href="?page=duplicator-tools&tab=recovery" class="nav-tab <?php echo ($current_tab == 'recovery') ? 'nav-tab-active' : '' ?>"> <?php esc_html_e('Recovery', 'duplicator'); ?></a>
        <a href="?page=duplicator-tools&tab=import" class="nav-tab <?php echo ($current_tab == 'import') ? 'nav-tab-active' : '' ?>"> <?php esc_html_e('Import', 'duplicator'); ?></a>
    </h2>

    <?php
		switch ($current_tab) {
			case 'diagnostics': include('diagnostics/main.php');
				break;
            case 'templates': include("{$dup_local_tools_path}/templates.php");
				break;
			case 'recovery': include("{$dup_local_tools_path}/recovery.php");
				break;
			case 'import': include("{$dup_local_tools_path}/import.php");
				break;
		}
	?>
</div>
