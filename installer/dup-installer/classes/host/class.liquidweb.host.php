<?php
/**
 * liquidweb custom hosting class
 *
 * Standard: PSR-2
 *
 * @package SC\DUPX\DB
 * @link http://www.php-fig.org/psr/psr-2/
 *
 */
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

class DUPX_Liquidweb_Host implements DUPX_Host_interface
{

    /**
     * return the current host itentifier
     *
     * @return string
     */
    public static function getIdentifier()
    {
        return DUPX_Custom_Host_Manager::HOST_LIQUIDWEB;
    }

    /**
     * @return bool true if is current host
     */
    public function isHosting()
    {
        // check only mu plugin file exists
        
        $testFile = $GLOBALS['DUPX_ROOT'].'/wp-content/mu-plugins/liquid-web.php';
        return file_exists($testFile);
    }

    /**
     * the init function.
     * is called only if isHosting is true
     *
     * @return void
     */
    public function init()
    {
        
    }

    /**
     * return the label of current hosting
     * 
     * @return string
     */
    public function getLabel()
    {
        return 'Liquid Web';
    }
}