<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class SnapLibUIU
{
    public static function echoBoolean($val)
    {
        echo $val ? 'true' : 'false';
    }

    public static function echoChecked($val)
    {
        echo $val ? 'checked' : '';
    }

    public static function echoDisabled($val)
    {
        echo $val ? 'disabled' : '';
    }

    public static function echoSelected($val)
    {
        echo $val ? 'selected' : '';
    }

    public static function getSelected($val)
    {
        return ($val ? 'selected' : '');
    }
}
