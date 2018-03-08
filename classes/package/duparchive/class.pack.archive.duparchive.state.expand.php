<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once (DUPLICATOR_PLUGIN_PATH.'lib/dup_archive/classes/states/class.duparchive.state.expand.php');

class DUP_DupArchive_Expand_State extends DupArchiveExpandState
{
    public static function getInstance($reset = false)
    {   
        $instance = new DUP_DupArchive_Expand_State();
        
        if ($reset) {
         
            $instance->initMembers();
        } else {
            $data = DUP_Settings::Get('duparchive_expand_state');
        
            DUP_LOG::traceObject("****RAW EXPAND STATE LOADED****", $data);
            DUP_Util::objectCopy($data, $instance);
        }

        return $instance;
    }

    private function setFromData($data)
    {
        $this->currentFileHeader     = $data->currentFileHeader;
        $this->archiveHeader         = $data->archiveHeader;
        $this->archiveOffset         = $data->archiveOffset;
        $this->archivePath           = $data->archivePath;
        $this->basePath              = $data->basePath;
        $this->currentFileOffset     = $data->currentFileOffset;
        $this->failures              = $data->failures;
        $this->isCompressed          = $data->isCompressed;
        $this->startTimestamp        = $data->startTimestamp;
        $this->timeSliceInSecs       = $data->timeSliceInSecs;
        $this->validateOnly          = $data->validateOnly;
        $this->fileWriteCount        = $data->fileWriteCount;
        $this->directoryWriteCount   = $data->directoryWriteCount;
        $this->working               = $data->working;
        $this->directoryModeOverride = $data->directoryModeOverride;
        $this->fileModeOverride      = $data->fileModeOverride;
        $this->throttleDelayInUs     = $data->throttleDelayInUs;
    }

    public function save()
    {
        DUP_LOG::trace("****SAVING EXPAND STATE****");
        DUP_Settings::Set('duparchive_expand_state', $this);
        DUP_Settings::Save();
    }

    private function initMembers()
    {
        $this->currentFileHeader = null;

        $this->archiveOffset         = 0;
        $this->archiveHeader         = 0;
        $this->archivePath           = null;
        $this->basePath              = null;
        $this->currentFileOffset     = 0;
        $this->failures              = array();
        $this->isCompressed          = false;
        $this->startTimestamp        = time();
        $this->timeSliceInSecs       = -1;
        $this->working               = false;
        $this->validateOnly          = false;
        $this->directoryModeOverride = -1;
        $this->fileModeOverride      = -1;
        $this->throttleDelayInUs     = 0;
    }
}
