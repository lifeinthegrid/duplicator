<?php
/**
 * wpengine custom hosting class
 *
 * Standard: PSR-2
 *
 * @package SC\DUPX\HOST
 * @link http://www.php-fig.org/psr/psr-2/
 *
 */
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

class DUP_Liquidweb_Host implements DUP_Host_interface
{

    public static function getIdentifier()
    {
        return DUP_Custom_Host_Manager::HOST_LIQUIDWEB;
    }

    public function isHosting()
    {
        return apply_filters('duplicator_liquidweb_host_check', file_exists(DupLiteSnapLibIOU::safePathUntrailingslashit(WPMU_PLUGIN_DIR).'/liquid-web.php'));
    }

    public function init()
    {

    }
}