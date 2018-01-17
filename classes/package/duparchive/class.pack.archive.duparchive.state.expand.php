<?php
defined("ABSPATH") or die("");
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once (DUPLICATOR_PRO_PLUGIN_PATH.'lib/dup_archive/classes/states/class.duparchive.state.expand.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH.'classes/entities/class.duparchive.expandstate.entity.php');

class DUP_PRO_DupArchive_Expand_State extends DupArchiveExpandState
{
    /* @var $entity DUP_PRO_DupArchive_Expand_State_Entity */
    public $entity;

    public function __construct($expandStateEntity)
    {
        $this->entity = $expandStateEntity;

        DUP_PRO_U::objectCopy($this->entity, $this, array('id', 'type', 'dirty', 'table_name', 'verifiers', 'parent_id', 'archiveHeaderString', 'currentFileHeaderString', 'failuresString'));

        if($this->entity->archiveHeaderString != null)
        {
            $this->archiveHeader = DUP_PRO_JSON_U::decode($this->entity->archiveHeaderString);
        }
        else
        {
            $this->archiveHeader = null;
        }

        if($this->entity->currentFileHeaderString)
        {
            $this->currentFileHeader = DUP_PRO_JSON_U::decode($this->entity->currentFileHeaderString);
        }
        else
        {
            $this->currentFileHeader = null;
        }
        
        if($this->entity->failuresString)
        {
            $this->failures = DUP_PRO_JSON_U::decode($this->entity->failuresString);
        }
        else
        {
            $this->failures = array();
        }
    }

    public function save()
    {
        DUP_PRO_U::objectCopy($this, $this->entity);

        if($this->archiveHeader != null)
        {
            $this->entity->archiveHeaderString = DUP_PRO_JSON_U::safeEncode($this->archiveHeader);
        }
        else
        {
            $this->entity->archiveHeaderString = null;
        }

        if($this->currentFileHeader != null)
        {
            $this->entity->currentFileHeaderString = DUP_PRO_JSON_U::safeEncode($this->currentFileHeader);
        }
        else
        {
            $this->entity->currentFileHeaderString = null;
        }
        
        $this->entity->failuresString = DUP_PRO_JSON_U::safeEncode($this->failures);

        $this->entity->save();
    }
}