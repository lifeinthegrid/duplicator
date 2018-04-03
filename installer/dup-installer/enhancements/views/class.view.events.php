<?php

class DUPX_ViewEvents {

    public static function init()
    {
        DUPX_EventManager::registerEvent('display_cpanel_tab', 'DUPX_ViewEvents::displaycPanel');
    }

    public static function displaycPanel($params)
    {
		$cpnl_supported = $params['cpnl_supported'];

        require_once(dirname(__FILE__) . '/view.s2.cpnl.php');
    }
}

DUPX_ViewEvents::init();