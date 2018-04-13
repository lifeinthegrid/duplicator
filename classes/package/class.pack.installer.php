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

	 public function build($package)
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
            DUP_Log::error("ERROR ADDING INSTALLER", "Marking build progress as failed because couldn't add installer files", false);
            $package->BuildProgress->failed = true;
            $package->setStatus(DUP_PackageStatus::ERROR);
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

        DUP_Log::Info("#### writing installer contents to $installer_filepath");
      //  DUP_Log::Info("#### contents:" . $installer_contents);
        
        if (@file_put_contents($installer_filepath, $installer_contents) === false) {
            DUP_Log::error(__('Error writing installer contents', 'duplicator'), __("Couldn't write to $installer_filepath", 'duplicator'));
            $success = false;
        }

        DUP_Log::Info("#### test");

        $yn = file_exists($installer_filepath) ? 'yes' : 'no';
        DUP_Log::Info("#### installer wrote. Does it exist? " . $yn);
        

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

        //BRAND
        $ac->brand   = null;

        //LICENSING
        $ac->license_limit = -2;

		$ac->plugin_type = 0;
        $ac->plugin_name = 'Duplicator';

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

//    private function add_extra_files_using_shellexec($zip_filepath, $installer_filepath, $scan_filepath, $sql_filepath, $archive_config_filepath, $is_compressed)
//    {
//        $success = false;
//        $global  = DUP_Global_Entity::get_instance();
//
//        $installer_source_directory      = DUPLICATOR_PLUGIN_PATH.'installer/';
//        $installer_dpro_source_directory = "$installer_source_directory/dup-installer";
//        $extras_directory                = DUP_U::safePath(DUPLICATOR_PRO_SSDIR_PATH_TMP).'/extras';
//        $extras_installer_directory      = $extras_directory.'/dup-installer';
//        $extras_lib_directory            = $extras_installer_directory.'/lib';
//
//        $snaplib_source_directory        = DUPLICATOR_PRO_LIB_PATH.'/snaplib';
//        $fileops_source_directory        = DUPLICATOR_PRO_LIB_PATH.'/fileops';
//        $extras_snaplib_directory        = $extras_installer_directory.'/lib/snaplib';
//        $extras_fileops_directory        = $extras_installer_directory.'/lib/fileops';
//
//        $installer_backup_filepath = "$extras_directory/".$global->get_installer_backup_filename();
//
//        $dest_sql_filepath            = "$extras_directory/database.sql";
//        $dest_archive_config_filepath = "$extras_installer_directory/archive.cfg";
//        $dest_scan_filepath           = "$extras_directory/scan.json";
//
//		$htaccess_filepath = DUPLICATOR_PRO_WPROOTPATH . '.htaccess';
//		$dest_htaccess_orig_filepath  = "{$extras_directory}/" . DUPLICATOR_PRO_HTACCESS_ORIG_FILENAME;
//
//		$wpconfig_filepath = DUPLICATOR_PRO_WPROOTPATH . 'wp-config.php';
//		$dest_wpconfig_ark_filepath  = "{$extras_directory}/" . DUPLICATOR_PRO_WPCONFIG_ARK_FILENAME;
//
//        if (file_exists($extras_directory)) {
//            if (DUP_IO::deleteTree($extras_directory) === false) {
//                DUP_Log::error("Error deleting $extras_directory", '', false);
//                return false;
//            }
//        }
//
//        if (!@mkdir($extras_directory)) {
//            DUP_Log::error("Error creating extras directory", "Couldn't create $extras_directory", false);
//            return false;
//        }
//
//        if (!@mkdir($extras_installer_directory)) {
//            DUP_Log::error("Error creating extras directory", "Couldn't create $extras_installer_directory", false);
//            return false;
//        }
//
//        if (@copy($installer_filepath, $installer_backup_filepath) === false) {
//            DUP_Log::error("Error copying $installer_filepath to $installer_backup_filepath", '', false);
//            return false;
//        }
//
//        if (@copy($sql_filepath, $dest_sql_filepath) === false) {
//            DUP_Log::error("Error copying $sql_filepath to $dest_sql_filepath", '', false);
//            return false;
//        }
//
//        if (@copy($archive_config_filepath, $dest_archive_config_filepath) === false) {
//            DUP_Log::error("Error copying $archive_config_filepath to $dest_archive_config_filepath", '', false);
//            return false;
//        }
//
//        if (@copy($scan_filepath, $dest_scan_filepath) === false) {
//            DUP_Log::error("Error copying $scan_filepath to $dest_scan_filepath", '', false);
//            return false;
//        }
//
//		if(file_exists($htaccess_filepath)) {
//			DUP_Log::Info("{$htaccess_filepath} exists so copying to {$dest_htaccess_orig_filepath}");
//			@copy($htaccess_filepath, $dest_htaccess_orig_filepath);
//		}
//
//		if(file_exists($wpconfig_filepath)) {
//			DUP_Log::Info("{$wpconfig_filepath} exists so copying to {$dest_wpconfig_ark_filepath}");
//			@copy($wpconfig_filepath, $dest_wpconfig_ark_filepath);
//		}
//
//        $one_stage_add = strtoupper($global->get_installer_extension()) == 'PHP';
//
//        if ($one_stage_add) {
//
//            if (!@mkdir($extras_snaplib_directory, 0755, true)) {
//                DUP_Log::error("Error creating extras snaplib directory", "Couldn't create $extras_snaplib_directory", false);
//                return false;
//            }
//
//            if (!@mkdir($extras_fileops_directory, 0755, true)) {
//                DUP_Log::error("Error creating extras fileops directory", "Couldn't create $extras_fileops_directory", false);
//                return false;
//            }
//
//            // If the installer has the PHP extension copy the installer files to add all extras in one shot since the server supports creation of PHP files
//            if (DUP_IO::copyDir($installer_dpro_source_directory, $extras_installer_directory) === false) {
//                DUP_Log::error("Error copying installer file directory to extras directory", "Couldn't copy $installer_dpro_source_directory to $extras_installer_directory", false);
//                return false;
//            }
//
//            if (DUP_IO::copyDir($snaplib_source_directory, $extras_snaplib_directory) === false) {
//                DUP_Log::error("Error copying installer snaplib directory to extras directory", "Couldn't copy $snaplib_source_directory to $extras_snaplib_directory", false);
//                return false;
//            }
//
//            if (DUP_IO::copyDir($fileops_source_directory, $extras_fileops_directory) === false) {
//                DUP_Log::error("Error copying installer fileops directory to extras directory", "Couldn't copy $fileops_source_directory to $extras_fileops_directory", false);
//                return false;
//            }
//        }
//
//        //-- STAGE 1 ADD
//        $compression_parameter = DUP_Shell_U::getCompressionParam($is_compressed);
//
//        $command = 'cd '.escapeshellarg(DUP_U::safePath($extras_directory));
//        $command .= ' && '.escapeshellcmd(DUP_Zip_U::getShellExecZipPath())." $compression_parameter".' -g -rq ';
//        $command .= escapeshellarg($zip_filepath).' ./*';
//
//        DUP_Log::Info("Executing Shell Exec Zip Stage 1 to add extras: $command");
//
//        $stderr = shell_exec($command);
//
//        //-- STAGE 2 ADD - old code until we can figure out how to add the snaplib library within dup-installer/lib/snaplib
//        if ($stderr == '') {
//            if (!$one_stage_add) {
//                // Since we didn't bundle the installer files in the earlier stage we have to zip things up right from the plugin source area
//                $command = 'cd '.escapeshellarg($installer_source_directory);
//                $command .= ' && '.escapeshellcmd(DUP_Zip_U::getShellExecZipPath())." $compression_parameter".' -g -rq ';
//                $command .= escapeshellarg($zip_filepath).' dup-installer/*';
//
//                DUP_Log::Info("Executing Shell Exec Zip Stage 2 to add installer files: $command");
//                $stderr = shell_exec($command);
//
//                $command = 'cd '.escapeshellarg(DUPLICATOR_PRO_LIB_PATH);
//                $command .= ' && '.escapeshellcmd(DUP_Zip_U::getShellExecZipPath())." $compression_parameter".' -g -rq ';
//                $command .= escapeshellarg($zip_filepath).' snaplib/* fileops/*';
//
//                DUP_Log::Info("Executing Shell Exec Zip Stage 2 to add installer files: $command");
//                $stderr = shell_exec($command);
//            }
//        }
//
//  //rsr temp      DUP_IO::deleteTree($extras_directory);
//
//        if ($stderr == '') {
//            if (DUP_U::getExeFilepath('unzip') != NULL) {
//                $installer_backup_filename = basename($installer_backup_filepath);
//
//                // Verify the essential extras got in there
//                $extra_count_string = "unzip -Z1 '$zip_filepath' | grep '$installer_backup_filename\|scan.json\|database.sql\|archive.cfg' | wc -l";
//
//                DUP_Log::Info("Executing extra count string $extra_count_string");
//
//                $extra_count = DUP_Shell_U::runAndGetResponse($extra_count_string, 1);
//
//                if (is_numeric($extra_count)) {
//                    // Accounting for the sql and installer back files
//                    if ($extra_count >= 4) {
//                        // Since there could be files with same name accept when there are m
//                        DUP_Log::Info("Core extra files confirmed to be in the archive");
//                        $success = true;
//                    } else {
//                        DUP_Log::error("Tried to verify core extra files but one or more were missing. Count = $extra_count", '', false);
//                    }
//                } else {
//                    DUP_Log::Info("Executed extra count string of $extra_count_string");
//                    DUP_Log::error("Error retrieving extra count in shell zip ".$extra_count, '', false);
//                }
//            } else {
//                DUP_Log::Info("unzip doesn't exist so not doing the extra file check");
//                $success = true;
//            }
//        } else {
//            $error_text = DUP_U::__("Unable to add installer extras to archive $stderr.");
//            $fix_text   = DUP_U::__("Go to: Settings > Packages Tab > Set Archive Engine to ZipArchive.");
//
//            DUP_Log::error("$error_text  **RECOMMENDATION: $fix_text", '', false);
//
//            $system_global = DUP_System_Global_Entity::get_instance();
//
//            $system_global->add_recommended_text_fix($error_text, $fix_text);
//
//            $system_global->save();
//        }
//
//        return $success;
//    }

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


    /**
     *  Build the installer script
     *
     *  @param obj $package A reference to the package that this installer object belongs to
     *
     *  @return null
     */
//    public function build($package)
//    {
//
//        $this->Package = $package;
//
//        DUP_Log::Info("\n********************************************************************************");
//        DUP_Log::Info("MAKE INSTALLER:");
//        DUP_Log::Info("********************************************************************************");
//        DUP_Log::Info("Build Start");
//
//        $template_uniqid = uniqid('').'_'.time();
//        $template_path   = DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP."/installer.template_{$template_uniqid}.php");
//        $main_path       = DUP_Util::safePath(DUPLICATOR_PLUGIN_PATH.'installer/build/main.installer.php');
//        @chmod($template_path, 0777);
//        @chmod($main_path, 0777);
//
//        @touch($template_path);
//        $main_data       = file_get_contents("{$main_path}");
//        $template_result = file_put_contents($template_path, $main_data);
//        if ($main_data === false || $template_result == false) {
//            $err_info = "These files may have permission issues. Please validate that PHP has read/write access.\n";
//            $err_info .= "Main Installer: '{$main_path}' \nTemplate Installer: '$template_path'";
//            DUP_Log::Error("Install builder failed to generate files.", "{$err_info}");
//        }
//
//        $embeded_files = array(
//            "assets/inc.libs.css.php"				=> "@@INC.LIBS.CSS.PHP@@",
//            "assets/inc.css.php"					=> "@@INC.CSS.PHP@@",
//            "assets/inc.libs.js.php"				=> "@@INC.LIBS.JS.PHP@@",
//            "assets/inc.js.php"						=> "@@INC.JS.PHP@@",
//            "classes/utilities/class.u.php"			=> "@@CLASS.U.PHP@@",
//            "classes/class.server.php"				=> "@@CLASS.SERVER.PHP@@",
//            "classes/class.db.php"					=> "@@CLASS.DB.PHP@@",
//            "classes/class.logging.php"				=> "@@CLASS.LOGGING.PHP@@",
//            "classes/class.engine.php"				=> "@@CLASS.ENGINE.PHP@@",
//            "classes/config/class.conf.wp.php"		=> "@@CLASS.CONF.WP.PHP@@",
//            "classes/config/class.conf.srv.php"		=> "@@CLASS.CONF.SRV.PHP@@",
//			"ctrls/ctrl.step1.php"					=> "@@CTRL.STEP1.PHP@@",
//            "ctrls/ctrl.step2.php"					=> "@@CTRL.STEP2.PHP@@",
//            "ctrls/ctrl.step3.php"					=> "@@CTRL.STEP3.PHP@@",
//            "view.step1.php"						=> "@@VIEW.STEP1.PHP@@",
//            "view.step2.php"						=> "@@VIEW.STEP2.PHP@@",
//            "view.step3.php"						=> "@@VIEW.STEP3.PHP@@",
//            "view.step4.php"						=> "@@VIEW.STEP4.PHP@@",
//            "view.help.php"							=> "@@VIEW.HELP.PHP@@",);
//
//        foreach ($embeded_files as $name => $token) {
//            $file_path = DUPLICATOR_PLUGIN_PATH."installer/build/{$name}";
//            @chmod($file_path, 0777);
//
//            $search_data = @file_get_contents($template_path);
//            $insert_data = @file_get_contents($file_path);
//            file_put_contents($template_path, str_replace("${token}", "{$insert_data}", $search_data));
//            if ($search_data === false || $insert_data == false) {
//                DUP_Log::Error("Installer generation failed at {$token}.");
//            }
//            @chmod($file_path, 0644);
//        }
//
//        @chmod($template_path, 0644);
//        @chmod($main_path, 0644);
//
//        DUP_Log::Info("Build Finished");
//        $this->createFromTemplate($template_path);
//        $storePath  = "{$this->Package->StorePath}/{$this->File}";
//        $this->Size = @filesize($storePath);
//        $this->addBackup();
//    }
//
//    /**
//     *  Puts an installer zip file in the archive for backup purposes.
//     *
//     * @return null
//     */
//    private function addBackup()
//    {
//
//        $zipPath   = DUP_Util::safePath("{$this->Package->StorePath}/{$this->Package->Archive->File}");
//        $installer = DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP)."/{$this->Package->NameHash}_installer.php";
//
//        $zipArchive = new ZipArchive();
//        if ($zipArchive->open($zipPath, ZIPARCHIVE::CREATE) === TRUE) {
//            if ($zipArchive->addFile($installer, "installer-backup.php")) {
//                DUP_Log::Info("Added to archive");
//            } else {
//                DUP_Log::Info("Unable to add installer-backup.php to archive.", "Installer File Path [{$installer}]");
//            }
//            $zipArchive->close();
//        }
//    }
//
//    /**
//     * Generates the final installer file from the template file
//     *
//     * @param string $template The path to the installer template which is originally copied from main.installer.php
//     *
//     * @return null
//     */
//    private function createFromTemplate($template)
//    {
//
//        global $wpdb;
//
//        DUP_Log::Info("Preping for use");
//        $installer = DUP_Util::safePath(DUPLICATOR_SSDIR_PATH_TMP)."/{$this->Package->NameHash}_installer.php";
//
//        //Option values to delete at install time
//        $deleteOpts = $GLOBALS['DUPLICATOR_OPTS_DELETE'];
//
//		 DUP_Log::Info("PACK SIZE: {$this->Package->Size}");
//
//        $replace_items = Array(
//            //COMPARE VALUES
//            "fwrite_created" => $this->Package->Created,
//            "fwrite_version_dup" => DUPLICATOR_VERSION,
//            "fwrite_version_wp" => $this->Package->VersionWP,
//            "fwrite_version_db" => $this->Package->VersionDB,
//            "fwrite_version_php" => $this->Package->VersionPHP,
//            "fwrite_version_os" => $this->Package->VersionOS,
//            //GENERAL
//            "fwrite_url_old" => get_option('siteurl'),
//            "fwrite_archive_name" => "{$this->Package->NameHash}_archive.zip",
//			"fwrite_archive_onlydb" => $this->Package->Archive->ExportOnlyDB,
//            "fwrite_package_notes" => $this->Package->Notes,
//			"fwrite_package_size" => $this->Package->Archive->Size,
//            "fwrite_secure_name" => $this->Package->NameHash,
//            "fwrite_dbhost" => $this->Package->Installer->OptsDBHost,
//            "fwrite_dbport" => $this->Package->Installer->OptsDBPort,
//            "fwrite_dbname" => $this->Package->Installer->OptsDBName,
//            "fwrite_dbuser" => $this->Package->Installer->OptsDBUser,
//            "fwrite_dbpass" => '',
//            "fwrite_wp_tableprefix" => $wpdb->prefix,
//            "fwrite_opts_delete" => json_encode($deleteOpts),
//            "fwrite_blogname" => esc_html(get_option('blogname')),
//            "fwrite_wproot" => DUPLICATOR_WPROOTPATH,
//			"fwrite_wplogin_url" => wp_login_url(),
//            "fwrite_duplicator_version" => DUPLICATOR_VERSION);
//
//        if (file_exists($template) && is_readable($template)) {
//            $err_msg     = "ERROR: Unable to read/write installer. \nERROR INFO: Check permission/owner on file and parent folder.\nInstaller File = <{$installer}>";
//            $install_str = $this->parseTemplate($template, $replace_items);
//            (empty($install_str)) ? DUP_Log::Error("{$err_msg}", "DUP_Installer::createFromTemplate => file-empty-read") : DUP_Log::Info("Template parsed with new data");
//
//            //INSTALLER FILE
//            $fp = (!file_exists($installer)) ? fopen($installer, 'x+') : fopen($installer, 'w');
//            if (!$fp || !fwrite($fp, $install_str, strlen($install_str))) {
//                DUP_Log::Error("{$err_msg}", "DUP_Installer::createFromTemplate => file-write-error");
//            }
//
//            @fclose($fp);
//        } else {
//            DUP_Log::Error("Installer Template missing or unreadable.", "Template [{$template}]");
//        }
//        @unlink($template);
//        DUP_Log::Info("Complete [{$installer}]");
//    }

    /**
     *  Tokenize a file based on an array key 
     *
     *  @param string $filename		The filename to tokenize
     *  @param array  $data			The array of key value items to tokenize
     */
//    private function parseTemplate($filename, $data)
//    {
//        $q = file_get_contents($filename);
//        foreach ($data as $key => $value) {
//            //NOTE: Use var_export as it's probably best and most "thorough" way to
//            //make sure the values are set correctly in the template.  But in the template,
//            //need to make things properly formatted so that when real syntax errors
//            //exist they are easy to spot.  So the values will be surrounded by quotes
//
//            $find = array("'%{$key}%'", "\"%{$key}%\"");
//            $q    = str_replace($find, var_export($value, true), $q);
//            //now, account for places that do not surround with quotes...  these
//            //places do NOT need to use var_export as they are not inside strings
//            $q    = str_replace('%'.$key.'%', $value, $q);
//        }
//        return $q;
//    }
}
?>
