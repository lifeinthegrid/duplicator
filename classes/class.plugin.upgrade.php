<?php
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

/**

 * Upgrade logic of plugin resides here

 */
class DUP_LITE_Plugin_Upgrade
{

    const DUP_VERSION_OPT_KEY = 'duplicator_version_plugin';

    public static function onActivationAction()
    {
        if (($oldDupVersion = get_option(self::DUP_VERSION_OPT_KEY, false)) === false) {
            self::newInstallation();
        } else {
            self::updateInstallation($oldDupVersion);
        }

        //Setup All Directories
        DUP_Util::initSnapshotDirectory();
    }

    protected static function newInstallation()
    {
        self::updateDatabase();

        //WordPress Options Hooks
        update_option(self::DUP_VERSION_OPT_KEY, DUPLICATOR_VERSION);
    }

    protected static function updateInstallation($oldVersion)
    {
        self::updateDatabase();

        //Do not update to new wp-content storage till after 1.3.35
        if (version_compare($oldVersion, '1.3.35', '<')) {
            DUP_Settings::Set('storage_position', DUP_Settings::STORAGE_POSITION_LECAGY);
            DUP_Settings::Save();
        }

        //WordPress Options Hooks
        update_option(self::DUP_VERSION_OPT_KEY, DUPLICATOR_VERSION);
    }

    protected static function updateDatabase()
    {
        global $wpdb;

        $table_name = $wpdb->prefix."duplicator_packages";

        //PRIMARY KEY must have 2 spaces before for dbDelta to work
        //see: https://codex.wordpress.org/Creating_Tables_with_Plugins
        $sql = "CREATE TABLE `{$table_name}` (
			   id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			   name VARCHAR(250) NOT NULL,
			   hash VARCHAR(50) NOT NULL,
			   status INT(11) NOT NULL,
			   created DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			   owner VARCHAR(60) NOT NULL,
			   package LONGTEXT NOT NULL,
			   PRIMARY KEY  (id),
			   KEY hash (hash))";

        $abs_path = duplicator_get_abs_path();
        require_once($abs_path.'/wp-admin/includes/upgrade.php');
        @dbDelta($sql);
    }
}