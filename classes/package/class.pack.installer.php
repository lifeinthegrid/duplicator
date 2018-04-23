<?php
if (!defined('DUPLICATOR_VERSION')) exit; // Exit if accessed directly

require_once(DUPLICATOR_PLUGIN_PATH . '/classes/class.archive.config.php');
require_once(DUPLICATOR_PLUGIN_PATH . '/classes/utilities/class.u.zip.php');

class DUP_Installer
{
    //PUBLIC
    public $File;
    public $Size = 0;
    public $OptsDBHost;
    public $OptsDBPort;
    public $OptsDBName;
    public $OptsDBUser;
    //PROTECTED
    protected $Package;

    public $numFilesAdded = 0;
    public $numDirsAdded = 0;

    /**
     *  Init this object
     */
    function __construct($package)
    {
        $this->Package = $package;
    }

	 public function build($package, $die_on_fail = true)
    {
        DUP_Log::Info("building installer");

        $this->Package = $package;
        $success       = false;

        if ($this->create_enhanced_installer_files()) {
            $success = $this->add_extra_files($package);
        } else {
            DUP_Log::Info("error creating enhanced installer files");
        }


        if ($success) {
            $package->BuildProgress->installer_built = true;
        } else {
            $error_message = 'Error adding installer';
            DUP_Log::error($error_message, "Marking build progress as failed because couldn't add installer files", $die_on_fail);
            //$package->BuildProgress->failed = true;
            //$package->setStatus(DUP_PackageStatus::ERROR);
            $package->BuildProgress->set_failed($error_message);
            $package->Update();
        }

		return $success;
    }

    private function create_enhanced_installer_files()
    {
        $success = false;

        if ($this->create_enhanced_installer()) {
            $success = $this->create_archive_config_file();
        }

        return $success;
    }

    private function create_enhanced_installer()
    {
        $success = true;

		$archive_filepath        = DUP_Util::safePath("{$this->Package->StorePath}/{$this->Package->Archive->File}");
        $installer_filepath     = DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP)."/{$this->Package->NameHash}_installer.php";
        $template_filepath      = DUPLICATOR_PLUGIN_PATH.'/installer/installer.tpl';
        $mini_expander_filepath = DUPLICATOR_PLUGIN_PATH.'/lib/dup_archive/classes/class.duparchive.mini.expander.php';

        // Replace the @@ARCHIVE@@ token
        $installer_contents = file_get_contents($template_filepath);

        if (DUP_Settings::Get('archive_build_mode') == DUP_Archive_Build_Mode::DupArchive) {
            $mini_expander_string = file_get_contents($mini_expander_filepath);

            if ($mini_expander_string === false) {
                DUP_Log::error(DUP_U::__('Error reading DupArchive mini expander'), DUP_U::__('Error reading DupArchive mini expander'), false);
                return false;
            }
        } else {
            $mini_expander_string = '';
        }

        $search_array  = array('@@ARCHIVE@@', '@@VERSION@@', '@@ARCHIVE_SIZE@@', '@@DUPARCHIVE_MINI_EXPANDER@@');

        $replace_array = array($this->Package->Archive->File, DUPLICATOR_PRO_VERSION, @filesize($archive_filepath), $mini_expander_string);

        $installer_contents = str_replace($search_array, $replace_array, $installer_contents);
        
        if (@file_put_contents($installer_filepath, $installer_contents) === false) {
            DUP_Log::error(__('Error writing installer contents', 'duplicator'), __("Couldn't write to $installer_filepath", 'duplicator'));
            $success = false;
        }
        
        $yn = file_exists($installer_filepath) ? 'yes' : 'no';        

        if ($success) {
            $storePath  = "{$this->Package->StorePath}/{$this->File}";
            $this->Size = @filesize($storePath);
        }

        return $success;
    }

    /* Create archive.cfg file */
    private function create_archive_config_file()
    {
        global $wpdb;
       
        $success                 = true;
        $archive_config_filepath = DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP)."/{$this->Package->NameHash}_archive.cfg";
        $ac                      = new DUP_Archive_Config();
        $extension               = strtolower($this->Package->Archive->Format);

        //READ-ONLY: COMPARE VALUES
        $ac->created     = $this->Package->Created;
        $ac->version_dup = DUPLICATOR_VERSION;
        $ac->version_wp  = $this->Package->VersionWP;
        $ac->version_db  = $this->Package->VersionDB;
        $ac->version_php = $this->Package->VersionPHP;
        $ac->version_os  = $this->Package->VersionOS;
        $ac->dbInfo      = $this->Package->Database->info;

        //READ-ONLY: GENERAL
       // $ac->installer_base_name  = $global->installer_base_name;
		$ac->installer_base_name  = 'installer.php';
        $ac->package_name         = "{$this->Package->NameHash}_archive.{$extension}";
        $ac->package_notes        = $this->Package->Notes;
        $ac->url_old              = get_option('siteurl');
        $ac->opts_delete          = json_encode($GLOBALS['DUPLICATOR_OPTS_DELETE']);
        $ac->blogname             = esc_html(get_option('blogname'));
        $ac->wproot               = DUPLICATOR_WPROOTPATH;
        $ac->relative_content_dir = str_replace(ABSPATH, '', WP_CONTENT_DIR);
		$ac->exportOnlyDB		  = $this->Package->Archive->ExportOnlyDB;
		$ac->wplogin_url		  = wp_login_url();

        //PRE-FILLED: GENERAL
        $ac->secure_on   = $this->Package->Installer->OptsSecureOn;
        $ac->secure_pass = '';
        $ac->skipscan    = false;
        $ac->dbhost      = $this->Package->Installer->OptsDBHost;
        $ac->dbname      = $this->Package->Installer->OptsDBName;
        $ac->dbuser      = $this->Package->Installer->OptsDBUser;
        $ac->dbpass      = '';
        $ac->cache_wp    = $this->Package->Installer->OptsCacheWP;
        $ac->cache_path  = $this->Package->Installer->OptsCachePath;

        //PRE-FILLED: CPANEL
        $ac->cpnl_host     = '';
        $ac->cpnl_user     = '';
        $ac->cpnl_pass     = '';
        $ac->cpnl_enable   = false;
        $ac->cpnl_connect  = false;
        $ac->cpnl_dbaction = 'create';
        $ac->cpnl_dbhost   = '';
        $ac->cpnl_dbname   = '';
        $ac->cpnl_dbuser   = '';

		$ac->wp_tableprefix = $wpdb->base_prefix;

        $ac->subsites = array();

        $json = json_encode($ac);

        DUP_Log::TraceObject('json', $json);

        if (file_put_contents($archive_config_filepath, $json) === false) {
            DUP_Log::error("Error writing archive config", "Couldn't write archive config at $archive_config_filepath", false);
            $success = false;
        }

        return $success;
    }

	/**
     *  createZipBackup
     *  Puts an installer zip file in the archive for backup purposes.
     */
    private function add_extra_files($package)
    {
        $success                 = false;
        $installer_filepath      = DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP)."/{$this->Package->NameHash}_installer.php";
        $scan_filepath           = DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP)."/{$this->Package->NameHash}_scan.json";
        $sql_filepath            = DUP_Util::safePath("{$this->Package->StorePath}/{$this->Package->Database->File}");
        $archive_filepath        = DUP_Util::safePath("{$this->Package->StorePath}/{$this->Package->Archive->File}");
        $archive_config_filepath = DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP)."/{$this->Package->NameHash}_archive.cfg";

        DUP_Log::Info("add_extra_files1");

        if (file_exists($installer_filepath) == false) {
            DUP_Log::error("Installer $installer_filepath not present", '', false);
            return false;
        }

        DUP_Log::Info("add_extra_files2");
        if (file_exists($sql_filepath) == false) {
            DUP_Log::error("Database SQL file $sql_filepath not present", '', false);
            return false;
        }

        DUP_Log::Info("add_extra_files3");
        if (file_exists($archive_config_filepath) == false) {
            DUP_Log::error("Archive configuration file $archive_config_filepath not present", '', false);
            return false;
        }

        DUP_Log::Info("add_extra_files4");
        if ($package->Archive->file_count != 2) {
            DUP_Log::Info("Doing archive file check");
            // Only way it's 2 is if the root was part of the filter in which case the archive won't be there
            DUP_Log::Info("add_extra_files5");
            if (file_exists($archive_filepath) == false) {

                DUP_Log::error("$error_text. **RECOMMENDATION: $fix_text", '', false);

                return false;
            }
            DUP_Log::Info("add_extra_files6");
        }

        DUP_Log::TraceObject("archive format", $package->Archive->Format);

        if($package->Archive->Format == 'DAF') {
            DUP_Log::Info("add_extra_files7");
            $success = $this->add_extra_files_using_duparchive($installer_filepath, $scan_filepath, $sql_filepath, $archive_filepath, $archive_config_filepath);
        } else {
            DUP_Log::Info("add_extra_files8");
            $success = $this->add_extra_files_using_ziparchive($installer_filepath, $scan_filepath, $sql_filepath, $archive_filepath, $archive_config_filepath);
        }
		
        // No sense keeping the archive config around
        @unlink($archive_config_filepath);

        $package->Archive->Size = @filesize($archive_filepath);

        return $success;
    }

    private function add_extra_files_using_duparchive($installer_filepath, $scan_filepath, $sql_filepath, $archive_filepath, $archive_config_filepath)
    {
        $success = false;

        try {
            DUP_Log::Info("add_extra_files_using_da1");
			$htaccess_filepath = DUPLICATOR_WPROOTPATH . '.htaccess';
			$wpconfig_filepath = DUPLICATOR_WPROOTPATH . 'wp-config.php';

            $logger = new DUP_DupArchive_Logger();

            DupArchiveEngine::init($logger, 'DUP_Log::profile');

            DupArchiveEngine::addRelativeFileToArchiveST($archive_filepath, $scan_filepath, DUPLICATOR_EMBEDDED_SCAN_FILENAME);
            $this->numFilesAdded++;

			if(file_exists($htaccess_filepath)) {
				try
				{
					DupArchiveEngine::addRelativeFileToArchiveST($archive_filepath, $htaccess_filepath, DUPLICATOR_HTACCESS_ORIG_FILENAME);
					$this->numFilesAdded++;
				}
				catch (Exception $ex)
				{
					// Non critical so bury exception
				}
			}

			if(file_exists($wpconfig_filepath)) {
				DupArchiveEngine::addRelativeFileToArchiveST($archive_filepath, $wpconfig_filepath, DUPLICATOR_WPCONFIG_ARK_FILENAME);
				$this->numFilesAdded++;
			}

            $this->add_installer_files_using_duparchive($archive_filepath, $installer_filepath, $archive_config_filepath);

            $success = true;
        } catch (Exception $ex) {
            DUP_Log::Error("Error adding installer files to archive. ", $ex->getMessage());
        }

        return $success;
    }

    private function add_installer_files_using_duparchive($archive_filepath, $installer_filepath, $archive_config_filepath)
    {
        /* @var $global DUP_Global_Entity */
     //   $global                    = DUP_Global_Entity::get_instance();
       // $installer_backup_filename = $global->get_installer_backup_filename();
        $installer_backup_filename = 'installer-backup.php';

		$installer_backup_filepath = dirname($installer_filepath) . "/{$installer_backup_filename}";

        DUP_Log::Info('Adding enhanced installer files to archive using DupArchive');

		SnapLibIOU::copy($installer_filepath, $installer_backup_filepath);

		DupArchiveEngine::addFileToArchiveUsingBaseDirST($archive_filepath, dirname($installer_backup_filepath), $installer_backup_filepath);

		SnapLibIOU::rm($installer_backup_filepath);

        $this->numFilesAdded++;

        $base_installer_directory = DUPLICATOR_PLUGIN_PATH.'installer';
        $installer_directory      = "$base_installer_directory/dup-installer";

        $counts = DupArchiveEngine::addDirectoryToArchiveST($archive_filepath, $installer_directory, $base_installer_directory, true);
        $this->numFilesAdded += $counts->numFilesAdded;
        $this->numDirsAdded += $counts->numDirsAdded;

        $archive_config_relative_path = 'dup-installer/archive.cfg';

        DupArchiveEngine::addRelativeFileToArchiveST($archive_filepath, $archive_config_filepath, $archive_config_relative_path);
        $this->numFilesAdded++;

        // Include dup archive
        $duparchive_lib_directory = DUPLICATOR_PLUGIN_PATH.'lib/dup_archive';
        $duparchive_lib_counts = DupArchiveEngine::addDirectoryToArchiveST($archive_filepath, $duparchive_lib_directory, DUPLICATOR_PLUGIN_PATH, true, 'dup-installer/');
        $this->numFilesAdded += $duparchive_lib_counts->numFilesAdded;
        $this->numDirsAdded += $duparchive_lib_counts->numDirsAdded;

        // Include snaplib
        $snaplib_directory = DUPLICATOR_PLUGIN_PATH.'lib/snaplib';
        $snaplib_counts = DupArchiveEngine::addDirectoryToArchiveST($archive_filepath, $snaplib_directory, DUPLICATOR_PLUGIN_PATH, true, 'dup-installer/');
        $this->numFilesAdded += $snaplib_counts->numFilesAdded;
        $this->numDirsAdded += $snaplib_counts->numDirsAdded;

        // Include fileops
        $fileops_directory = DUPLICATOR_PLUGIN_PATH.'lib/fileops';
        $fileops_counts = DupArchiveEngine::addDirectoryToArchiveST($archive_filepath, $fileops_directory, DUPLICATOR_PLUGIN_PATH, true, 'dup-installer/');
        $this->numFilesAdded += $fileops_counts->numFilesAdded;
        $this->numDirsAdded += $fileops_counts->numDirsAdded;
    }

    private function add_extra_files_using_ziparchive($installer_filepath, $scan_filepath, $sql_filepath, $zip_filepath, $archive_config_filepath)
    {
		$htaccess_filepath = DUPLICATOR_WPROOTPATH . '.htaccess';
		$wpconfig_filepath = DUPLICATOR_WPROOTPATH . 'wp-config.php';

        $success = false;

        $zipArchive = new ZipArchive();

        if ($zipArchive->open($zip_filepath, ZIPARCHIVE::CREATE) === TRUE) {
            DUP_Log::Info("Successfully opened zip $zip_filepath");

			if(file_exists($htaccess_filepath)) {
				DUP_Zip_U::addFileToZipArchive($zipArchive, $htaccess_filepath, DUPLICATOR_HTACCESS_ORIG_FILENAME, true);
			}

			if(file_exists($wpconfig_filepath)) {
				DUP_Zip_U::addFileToZipArchive($zipArchive, $wpconfig_filepath, DUPLICATOR_WPCONFIG_ARK_FILENAME, true);
			}

            //  if ($zipArchive->addFile($scan_filepath, DUPLICATOR_PRO_EMBEDDED_SCAN_FILENAME)) {
            if (DUP_Zip_U::addFileToZipArchive($zipArchive, $scan_filepath, DUPLICATOR_EMBEDDED_SCAN_FILENAME, true)) {
                if ($this->add_installer_files_using_zip_archive($zipArchive, $installer_filepath, $archive_config_filepath, true)) {
                    DUP_Log::info("Installer files added to archive");
                    DUP_Log::info("Added to archive");

                    $success = true;
                } else {
                    DUP_Log::error("Unable to add enhanced enhanced installer files to archive.", '', false);
                }
            } else {
                DUP_Log::error("Unable to add scan file to archive.", '', false);
            }

            if ($zipArchive->close() === false) {
                DUP_Log::error("Couldn't close archive when adding extra files.");
                $success = false;
            }

            DUP_Log::Info('After ziparchive close when adding installer');
        }

        return $success;
    }

    // Add installer directory to the archive and the archive.cfg
    private function add_installer_files_using_zip_archive(&$zip_archive, $installer_filepath, $archive_config_filepath, $is_compressed)
    {
        $success                   = false;
        /* @var $global DUP_Global_Entity */
       // $global                    = DUP_Global_Entity::get_instance();
//        $installer_backup_filename = $global->get_installer_backup_filename();
		$installer_backup_filename = 'installer-backup.php';

        DUP_Log::Info('Adding enhanced installer files to archive using ZipArchive');

        //   if ($zip_archive->addFile($installer_filepath, $installer_backup_filename)) {
        if (DUP_Zip_U::addFileToZipArchive($zip_archive, $installer_filepath, $installer_backup_filename, true)) {
            DUPLICATOR_PLUGIN_PATH.'installer/';

            $installer_directory = DUPLICATOR_PLUGIN_PATH.'installer/dup-installer';


            if (DUP_Zip_U::addDirWithZipArchive($zip_archive, $installer_directory, true, '', $is_compressed)) {
                $archive_config_local_name = 'dup-installer/archive.cfg';

                // if ($zip_archive->addFile($archive_config_filepath, $archive_config_local_name)) {
                if (DUP_Zip_U::addFileToZipArchive($zip_archive, $archive_config_filepath, $archive_config_local_name, true)) {

                    $snaplib_directory = DUPLICATOR_PLUGIN_PATH . 'lib/snaplib';
                 //   $fileops_directory = DUPLICATOR_PLUGIN_PATH . 'lib/fileops';

                    //DupArchiveEngine::addDirectoryToArchiveST($archive_filepath, $snaplib_directory, DUPLICATOR_PLUGIN_PATH, true, 'dup-installer/');
                    if (DUP_Zip_U::addDirWithZipArchive($zip_archive, $snaplib_directory, true, 'dup-installer/lib/', $is_compressed))// &&
                 //       DUP_Zip_U::addDirWithZipArchive($zip_archive, $fileops_directory, true, 'dup-installer/lib/', $is_compressed)) {
					{
                        $success = true;
                    } else {
                      //  DUP_Log::error("Error adding directory {$snaplib_directory} or {$fileops_directory} to zipArchive", '', false);
						  DUP_Log::error("Error adding directory {$snaplib_directory} to zipArchive", '', false);
                    }
                } else {
                    DUP_Log::error("Error adding $archive_config_filepath to zipArchive", '', false);
                }
            } else {
                DUP_Log::error("Error adding directory $installer_directory to zipArchive", '', false);
            }
        } else {
            DUP_Log::error("Error adding backup installer file to zipArchive", '', false);
        }

        return $success;
    }
}
?>
