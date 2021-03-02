<?php
/**
 * godaddy custom hosting class
 *
 * Standard: PSR-2
 *
 * @package SC\DUPX\DB
 * @link http://www.php-fig.org/psr/psr-2/
 *
 */
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

/**
 * class for wordpress.com managed hosting
 * 
 * @todo not yet implemneted
 * 
 */
class DUPX_WordpressCom_Host implements DUPX_Host_interface
{

    /**
     * return the current host itentifier
     *
     * @return string
     */
    public static function getIdentifier()
    {
        return DUPX_Custom_Host_Manager::HOST_WORDPRESSCOM;
    }

    /**
     * @return bool true if is current host
     */
    public function isHosting()
    {
        // check only mu plugin file exists

        $testFile = $GLOBALS['DUPX_ROOT'].'/wp-content/mu-plugins/wpcomsh-loader.php';
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
     * 
     * @return string
     */
    public function getLabel()
    {
        return 'Wordpress.com';
    }
}