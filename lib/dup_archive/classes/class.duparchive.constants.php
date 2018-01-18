<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class DupArchiveConstants
{
    public static $DARoot;
    public static $LibRoot;

    public static function init() {

        self::$LibRoot = dirname(__FILE__).'/../../';
        self::$DARoot = dirname(__FILE__).'/../';
    }
}

class DupArchiveExceptionCodes
{
    const NonFatal = 0;
    const Fatal = 1;
}

DupArchiveConstants::init();
