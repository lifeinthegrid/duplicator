<?php

class DUPX_ViewEvents {

    public static function init()
    {
        DUPX_EventManager::registerEvent('view_s2_display_alternate_cpanel_tab', 'DUPX_ViewEvents::displaycPanelTab');
        DUPX_EventManager::registerEvent('view_s2_deployment_triggered', 'DUPX_ViewEvents::displaycPanelDeployJS');
        DUPX_EventManager::registerEvent('view_s3_extra_settings', 'DUPX_ViewEvents::displayS3ExtraSettings');
    }

    public static function displayS3ExtraSettings($params)
    {
        require_once(dirname(__FILE__) . '/view.s3.search.replace.php');
    }

    public static function displaycPanelTab($params)
    {
		$cpnl_supported = $params['cpnl_supported'];

        require_once(dirname(__FILE__) . '/view.s2.cpnl.php');
    }

    public static function displaycPanelDeployJS($params)
    {
echo <<<CPANELDEPLOY
        DUPX.cpnlSetResults();

        var dbhost = $("#dbhost").val();
		var dbname = $("#dbname").val();
		var dbuser = $("#dbuser").val();
		var dbchunk = $("#dbchunk").val();

		if ($('#s2-input-form-mode').val() == 'cpnl')  {
			dbhost = $("#cpnl-dbhost").val();
			dbname = $("#cpnl-dbname-result").val();
			dbuser = $("#cpnl-dbuser-result").val();
			dbchunk = $("#cpnl-dbchunk").val();
		}
CPANELDEPLOY;
    }
}

DUPX_ViewEvents::init();