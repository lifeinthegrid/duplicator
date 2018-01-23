<?php
defined("ABSPATH") or die("");
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once (DUPLICATOR_PRO_PLUGIN_PATH.'lib/dup_archive/classes/states/class.duparchive.state.create.php');

class DUP_DupArchive_Create_State extends DupArchiveCreateState
{
    /* @var $package DUP_Package */
  //  private $package;

//    public function setPackage(&$package)
     public function setPackage(&$package)
    {
  //      $this->package = &$package;
    }

    // Only one active package so straightforward
   // public static function createFromPackage(&$package)
    public static function load()
    {
   //     $instance = new DUP_Dup_Archive_Create_State();

        
        // RSR TODO: get from options table
//        $instance->setPackage($package);
//
//        $buildProgress = $package->build_progress;
//
//        $instance->archiveOffset         = $buildProgress->custom_data->archive_offset;
//        $instance->archivePath           = $buildProgress->custom_data->archive_path;
//        $instance->basePath              = $buildProgress->custom_data->base_path;
//        $instance->currentDirectoryIndex = $buildProgress->next_archive_dir_index;
//        $instance->currentFileIndex      = $buildProgress->next_archive_file_index;
//        $instance->failures              = $buildProgress->custom_data->failures;
//        $instance->globSize              = $buildProgress->custom_data->glob_size;
//        $instance->isCompressed          = $buildProgress->custom_data->is_compressed;
//        $instance->currentFileOffset     = $buildProgress->custom_data->current_file_offset;
//        $instance->timerEnabled          = true;
//        $instance->timeSliceInSecs       = $buildProgress->custom_data->time_slice_in_secs;
//        $instance->working               = $buildProgress->custom_data->working;
//        $instance->throttleDelayInUs     = $buildProgress->custom_data->throttle_delay_in_us;
//        $instance->skippedDirectoryCount = $buildProgress->custom_data->skipped_directory_count;
//        $instance->skippedFileCount      = $buildProgress->custom_data->skipped_file_count;

        $instance = DUP_Settings::Get('duparchive_create_state');
        
        $instance->startTimestamp = time();

        return $instance;
    }

    public static function createNew($package, $archivePath, $basePath, $timeSliceInSecs, $isCompressed, $setArchiveOffsetToEndOfArchive)
    {
        $instance = new DUP_Dup_Archive_Create_State();

     //   $instance->setPackage($package);

        /* @var $buildProgress DUP_PRO_Build_Progress */
        $buildProgress = &$package->build_progress;

        //$buildProgress->custom_data = new stdClass();

        if ($setArchiveOffsetToEndOfArchive) {
            $instance->archiveOffset = filesize($archivePath);
        } else {
            $instance->archiveOffset = 0;
        }

        $instance->archivePath           = $archivePath;
        $instance->basePath              = $basePath;
        $instance->currentDirectoryIndex = 0;
        $instance->currentFileOffset     = 0;
        $instance->currentFileIndex      = 0;
        $instance->failures              = array();
        $instance->globSize              = DupArchiveCreateState::DEFAULT_GLOB_SIZE;
        $instance->isCompressed          = $isCompressed;
        $instance->timeSliceInSecs       = $timeSliceInSecs;
        $instance->working               = true;
        $instance->skippedDirectoryCount = 0;
        $instance->skippedFileCount      = 0;

        $instance->startTimestamp = time();

        $instance->save();

        return $instance;
    }

    public function addFailure($type, $subject, $description, $isCritical = false)
    {
        $failure = parent::addFailure($type, $subject, $description, $isCritical);

        /* @var $buildProgress DUP_PRO_Build_Progress */
        $buildProgress = &$this->package->build_progress;

        if ($isCritical) {
            $buildProgress->failed = true;
        } else {
            $buildProgress->warnings[] = $this->getFailureString($failure);
        }
    }

    public function save()
    {
//        $this->package->build_progress->custom_data->archive_path            = $this->archivePath;
//        $this->package->build_progress->custom_data->time_slice_in_secs      = $this->timeSliceInSecs;
//        $this->package->build_progress->custom_data->base_path               = $this->basePath;
//        $this->package->build_progress->custom_data->glob_size               = $this->globSize;
//        $this->package->build_progress->custom_data->archive_offset          = $this->archiveOffset;
//        $this->package->build_progress->custom_data->failures                = $this->failures;
//        $this->package->build_progress->custom_data->working                 = $this->working;
//        $this->package->build_progress->custom_data->is_compressed           = $this->isCompressed;
//        $this->package->build_progress->custom_data->current_file_offset     = $this->currentFileOffset;
//        $this->package->build_progress->custom_data->throttle_delay_in_us    = $this->throttleDelayInUs;
//        $this->package->build_progress->custom_data->skipped_directory_count = $this->skippedDirectoryCount;
//        $this->package->build_progress->custom_data->skipped_file_count      = $this->skippedFileCount;
//
//        $this->package->build_progress->next_archive_dir_index  = $this->currentDirectoryIndex;
//        $this->package->build_progress->next_archive_file_index = $this->currentFileIndex;

//        $this->package->save();
        
        // RSR TODO: Save to options table
        
        DUP_Settings::Set('duparchive_create_state', $this);
    }
}