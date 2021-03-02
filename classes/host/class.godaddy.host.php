<?php
defined("ABSPATH") or die("");

class DUP_GoDaddy_Host implements DUP_Host_interface
{

    public static function getIdentifier()
    {
        return DUP_Custom_Host_Manager::HOST_GODADDY;
    }

    public function isHosting()
    {
        return apply_filters('duplicator_godaddy_host_check', file_exists(DupLiteSnapLibIOU::safePathUntrailingslashit(WPMU_PLUGIN_DIR).'/gd-system-plugin.php'));
    }

    public function init()
    {
        add_filter('duplicator_defaults_settings', array(__CLASS__, 'defaultsSettings'));
    }

    public static function defaultsSettings($defaults)
    {
        $defaults['archive_build_mode'] = DUP_Archive_Build_Mode::DupArchive;
        return $defaults;
    }
}