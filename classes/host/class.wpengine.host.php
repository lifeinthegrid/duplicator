<?php
defined("ABSPATH") or die("");

// New encryption class

class DUP_WPEngine_Host implements DUP_Host_interface
{
    public function init()
    {
        add_filter('duplicator_installer_file_path', array(__CLASS__, 'installerFilePath'), 10, 1);
        add_filter('duplicator_global_file_filters_on', '__return_true');
        add_filter('duplicator_global_file_filters', array(__CLASS__, 'globalFileFilters'), 10, 1);
        add_filter('duplicator_defaults_settings', array(__CLASS__, 'defaultsSettings'));
    }

    public static function getIdentifier()
    {
        return DUP_Custom_Host_Manager::HOST_WPENGINE;
    }


    public function isHosting()
    {
        return apply_filters('duplicator_wp_engine_host_check', file_exists(DupLiteSnapLibIOU::safePathUntrailingslashit(WPMU_PLUGIN_DIR).'/wpengine-security-auditor.php'));
    }

    public static function installerFilePath($path)
    {
        $path_info = pathinfo($path);
        $newPath   = $path;
        if ('php' == $path_info['extension']) {
            $newPath = substr_replace($path, '.txt', -4);
        }
        return $newPath;
    }

    public static function globalFileFilters($files)
    {
        $files[] = wp_normalize_path(WP_CONTENT_DIR).'/mysql.sql';
        return $files;
    }

    public static function defaultsSettings($defaults)
    {
        $defaults['package_zip_flush'] = '1';
        return $defaults;
    }

}