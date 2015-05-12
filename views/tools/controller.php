<?php

DUP_Util::CheckPermissions('manage_options');

global $wpdb;

//COMMON HEADER DISPLAY
require_once(DUPLICATOR_PLUGIN_PATH . '/views/javascript.php');
require_once(DUPLICATOR_PLUGIN_PATH . '/views/inc.header.php');
$current_tab = isset($_REQUEST['tab']) ? esc_html($_REQUEST['tab']) : 'logging';
?>
<div class="wrap">
    <?php duplicator_header(__("Tools", 'wpduplicator')) ?>

    <h2 class="nav-tab-wrapper">  
        <a href="?page=duplicator-tools&tab=logging" class="nav-tab <?php echo ($current_tab == 'logging') ? 'nav-tab-active' : '' ?>"> <?php _e('Logging', 'wpduplicator'); ?></a>  
        <a href="?page=duplicator-tools&tab=diagnostics" class="nav-tab <?php echo ($current_tab == 'diagnostics') ? 'nav-tab-active' : '' ?>"> <?php _e('Diagnostics', 'wpduplicator'); ?></a>  
		<a href="?page=duplicator-tools&tab=cleanup" class="nav-tab <?php echo ($current_tab == 'cleanup') ? 'nav-tab-active' : '' ?>"> <?php _e('Cleanup', 'wpduplicator'); ?></a>
    </h2> 	

    <?php
    switch ($current_tab) {
        case 'logging': include('logging.php');
            break;
		case 'diagnostics': include('diagnostics.php');
            break;
        case 'cleanup': include('cleanup.php');
            break;
    }
    ?>
</div>
