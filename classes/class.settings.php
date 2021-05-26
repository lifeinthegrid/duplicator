<?php
defined('ABSPATH') || defined('DUPXABSPATH') || exit;
// Exit if accessed directly
if (!defined('DUPLICATOR_VERSION'))
    exit;

abstract class DUP_Archive_Build_Mode
{

    const Unconfigured = -1;
    const Auto         = 0; // should no longer be used
    //  const Shell_Exec   = 1;
    const ZipArchive   = 2;
    const DupArchive   = 3;

}

class DUP_Settings
{

    const OPT_SETTINGS                  = 'duplicator_settings';
    const INSTALLER_NAME_MODE_WITH_HASH = 'withhash';
    const INSTALLER_NAME_MODE_SIMPLE    = 'simple';
    const STORAGE_POSITION_LECAGY       = 'legacy';
    const STORAGE_POSITION_WP_CONTENT   = 'wpcont';
    const SSDIR_NAME_LEGACY             = 'wp-snapshots';
    const SSDIR_NAME_NEW                = 'backups-dup-lite';

    protected static $Data;
    protected static $ssDirPath = null;
    protected static $ssDirUrl  = null;

    /**
     *  Class used to manage all the settings for the plugin
     */
    public static function init()
    {
        self::$Data = get_option(self::OPT_SETTINGS);
        //when the plugin updated, this will be true
        if (empty(self::$Data) || empty(self::$Data['version']) || version_compare(DUPLICATOR_VERSION, self::$Data['version'], '>')) {
            self::SetDefaults();
        }
    }

    /**
     *  Find the setting value
     *  @param string $key	The name of the key to find
     *  @return The value stored in the key returns null if key does not exist
     */
    public static function Get($key = '')
    {
        $result = null;
        if (isset(self::$Data[$key])) {
            $result = self::$Data[$key];
        } else {
            $defaults = self::GetAllDefaults();
            if (isset($defaults[$key])) {
                $result = $defaults[$key];
            }
        }
        return $result;
    }

    /**
     *  Set the settings value in memory only
     *  @param string $key		The name of the key to find
     *  @param string $value		The value to set
     *  remarks:	 The Save() method must be called to write the Settings object to the DB
     */
    public static function Set($key, $value)
    {
        if (isset(self::$Data[$key])) {
            self::$Data[$key] = ($value == null) ? '' : $value;
        } elseif (!empty($key)) {
            self::$Data[$key] = ($value == null) ? '' : $value;
        }
    }

    public static function setStoragePosition($newPosition)
    {
        $legacyPath = self::getSsdirPathLegacy();
        $wpContPath = self::getSsdirPathWpCont();

        $oldStoragePost = self::Get('storage_position');

        self::resetPositionVars();

        switch ($newPosition) {
            case self::STORAGE_POSITION_LECAGY:
                self::$Data['storage_position'] = self::STORAGE_POSITION_LECAGY;
                if (!DUP_Util::initSnapshotDirectory()) {
                    self::resetPositionVars();
                    self::$Data['storage_position'] = $oldStoragePost;
                    return false;
                }
                if (is_dir($wpContPath)) {
                    if (DupLiteSnapLibIOU::moveContentDirToTarget($wpContPath, $legacyPath, true)) {
                        DupLiteSnapLibIOU::rrmdir($wpContPath);
                    }
                }
                break;
            case self::STORAGE_POSITION_WP_CONTENT:
                self::$Data['storage_position'] = self::STORAGE_POSITION_WP_CONTENT;
                if (!DUP_Util::initSnapshotDirectory()) {
                    self::resetPositionVars();
                    self::$Data['storage_position'] = $oldStoragePost;
                    return false;
                }
                if (is_dir($legacyPath)) {
                    if (DupLiteSnapLibIOU::moveContentDirToTarget($legacyPath, $wpContPath, true)) {
                        DupLiteSnapLibIOU::rrmdir($legacyPath);
                    }
                }
                break;
            default:
                throw new Exception('Unknown storage position');
        }

        return true;
    }

    protected static function resetPositionVars()
    {
        self::$ssDirPath = null;
        self::$ssDirUrl  = null;
    }

    /**
     *  Saves all the setting values to the database
     *  @return True if option value has changed, false if not or if update failed.
     */
    public static function Save()
    {
        return update_option(self::OPT_SETTINGS, self::$Data);
    }

    /**
     *  Deletes all the setting values to the database
     *  @return True if option value has changed, false if not or if update failed.
     */
    public static function Delete()
    {
        return delete_option(self::OPT_SETTINGS);
    }

    /**
     *  Sets the defaults if they have not been set
     *  @return True if option value has changed, false if not or if update failed.
     */
    public static function SetDefaults()
    {
        $defaults   = self::GetAllDefaults();
        self::$Data = apply_filters('duplicator_defaults_settings', $defaults);
        return self::Save();
    }

    /**
     *  DeleteWPOption: Cleans up legacy data
     */
    public static function DeleteWPOption($optionName)
    {
        if (in_array($optionName, $GLOBALS['DUPLICATOR_OPTS_DELETE'])) {
            return delete_option($optionName);
        }
        return false;
    }

    public static function GetAllDefaults()
    {
        $default            = array();
        $default['version'] = DUPLICATOR_VERSION;

        //Flag used to remove the wp_options value duplicator_settings which are all the settings in this class
        $default['uninstall_settings'] = isset(self::$Data['uninstall_settings']) ? self::$Data['uninstall_settings'] : true;
        //Flag used to remove entire wp-snapshot directory
        $default['uninstall_files']    = isset(self::$Data['uninstall_files']) ? self::$Data['uninstall_files'] : true;
        //Flag used to remove all tables
        $default['uninstall_tables']   = isset(self::$Data['uninstall_tables']) ? self::$Data['uninstall_tables'] : true;

        //Flag used to show debug info
        $default['package_debug']            = isset(self::$Data['package_debug']) ? self::$Data['package_debug'] : false;
        //Flag used to enable mysqldump
        $default['package_mysqldump']        = isset(self::$Data['package_mysqldump']) ? self::$Data['package_mysqldump'] : true;
        //Optional mysqldump search path
        $default['package_mysqldump_path']   = isset(self::$Data['package_mysqldump_path']) ? self::$Data['package_mysqldump_path'] : '';
        //Optional mysql limit size
        $default['package_phpdump_qrylimit'] = isset(self::$Data['package_phpdump_qrylimit']) ? self::$Data['package_phpdump_qrylimit'] : "100";
        //Optional mysqldump search path
        $default['package_zip_flush']        = isset(self::$Data['package_zip_flush']) ? self::$Data['package_zip_flush'] : false;
        //Optional mysqldump search path
        $default['installer_name_mode']      = isset(self::$Data['installer_name_mode']) ? self::$Data['installer_name_mode'] : self::INSTALLER_NAME_MODE_SIMPLE;
        // storage position
        $default['storage_position']         = isset(self::$Data['storage_position']) ? self::$Data['storage_position'] : self::STORAGE_POSITION_WP_CONTENT;

        //Flag for .htaccess file
        $default['storage_htaccess_off'] = isset(self::$Data['storage_htaccess_off']) ? self::$Data['storage_htaccess_off'] : false;
        // Initial archive build mode
        if (isset(self::$Data['archive_build_mode'])) {
            $default['archive_build_mode'] = self::$Data['archive_build_mode'];
        } else {
            $is_ziparchive_available       = apply_filters('duplicator_is_ziparchive_available', class_exists('ZipArchive'));
            $default['archive_build_mode'] = $is_ziparchive_available ? DUP_Archive_Build_Mode::ZipArchive : DUP_Archive_Build_Mode::DupArchive;
        }

        // $default['package_zip_flush'] = apply_filters('duplicator_package_zip_flush_default_setting', '0');
        //Skip scan archive
        $default['skip_archive_scan']      = isset(self::$Data['skip_archive_scan']) ? self::$Data['skip_archive_scan'] : false;
        $default['unhook_third_party_js']  = isset(self::$Data['unhook_third_party_js']) ? self::$Data['unhook_third_party_js'] : false;
        $default['unhook_third_party_css'] = isset(self::$Data['unhook_third_party_css']) ? self::$Data['unhook_third_party_css'] : false;

        $default['active_package_id'] = -1;

        return $default;
    }

    public static function get_create_date_format()
    {
        static $ui_create_frmt = null;
        if (is_null($ui_create_frmt)) {
            $ui_create_frmt = is_numeric(self::Get('package_ui_created')) ? self::Get('package_ui_created') : 1;
        }
        return $ui_create_frmt;
    }

    public static function getSsdirPathLegacy()
    {
        return DupLiteSnapLibIOU::safePathTrailingslashit(duplicator_get_abs_path()).self::SSDIR_NAME_LEGACY;
    }

    public static function getSsdirPathWpCont()
    {
        return DupLiteSnapLibIOU::safePathTrailingslashit(WP_CONTENT_DIR).self::SSDIR_NAME_NEW;
    }

    public static function getSsdirPath()
    {
        if (is_null(self::$ssDirPath)) {
            if (self::Get('storage_position') === self::STORAGE_POSITION_LECAGY) {
                self::$ssDirPath = self::getSsdirPathLegacy();
            } else {
                self::$ssDirPath = self::getSsdirPathWpCont();
            }
        }
        return self::$ssDirPath;
    }

    public static function getSsdirUrl()
    {
        if (is_null(self::$ssDirUrl)) {
            if (self::Get('storage_position') === self::STORAGE_POSITION_LECAGY) {
                self::$ssDirUrl = DupLiteSnapLibIOU::trailingslashit(DUPLICATOR_SITE_URL).self::SSDIR_NAME_LEGACY;
            } else {
                self::$ssDirUrl = DupLiteSnapLibIOU::trailingslashit(content_url()).self::SSDIR_NAME_NEW;
            }
        }
        return self::$ssDirUrl;
    }

    public static function getSsdirTmpPath()
    {
        return self::getSsdirPath().'/tmp';
    }

    public static function getSsdirInstallerPath()
    {
        return self::getSsdirPath().'/installer';
    }
}