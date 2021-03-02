<?php

/**
 * Flywheel custom hosting class
 *
 * Standard: PSR-2
 *
 * @package SC\DUPX\HOST
 * @link http://www.php-fig.org/psr/psr-2/
 *
 */
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

class DUP_Flywheel_Host implements DUP_Host_interface
{

    public static function getIdentifier()
    {
        return DUP_Custom_Host_Manager::HOST_FLYWHEEL;
    }

    public function isHosting()
    {
        $path = duplicator_get_home_path().'/.fw-config.php';
        return apply_filters('duplicator_host_check', file_exists($path), self::getIdentifier());
    }

    public function init()
    {

    }
}
