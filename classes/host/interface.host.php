<?php
/**
 * interface for specific hostings class
 *
 * Standard: PSR-2
 *
 * @package SC\DUPX\HOST
 * @link http://www.php-fig.org/psr/psr-2/
 *
 */
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

interface DUP_Host_interface
{

    /**
     * return the current host itentifier
     *
     * @return string
     */
    public static function getIdentifier();

    /**
     * @return bool true if is current host
     */
    public function isHosting();

    /**
     * the init function.
     * is called only if isHosting is true
     *
     * @return void
     */
    public function init();
}