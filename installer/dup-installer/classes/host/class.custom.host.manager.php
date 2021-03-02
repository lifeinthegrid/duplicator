<?php
/**
 * custom hosting manager
 * singleton class
 *
 * Standard: PSR-2
 *
 * @package SC\DUPX\DB
 * @link http://www.php-fig.org/psr/psr-2/
 *
 */
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

require_once ($GLOBALS['DUPX_INIT'].'/classes/host/interface.host.php');
require_once ($GLOBALS['DUPX_INIT'].'/classes/host/class.godaddy.host.php');
require_once ($GLOBALS['DUPX_INIT'].'/classes/host/class.wpengine.host.php');
require_once ($GLOBALS['DUPX_INIT'].'/classes/host/class.wordpresscom.host.php');
require_once ($GLOBALS['DUPX_INIT'].'/classes/host/class.liquidweb.host.php');
require_once ($GLOBALS['DUPX_INIT'].'/classes/host/class.pantheon.host.php');
require_once ($GLOBALS['DUPX_INIT'].'/classes/host/class.flywheel.host.php');

class DUPX_Custom_Host_Manager
{

    const HOST_GODADDY      = 'godaddy';
    const HOST_WPENGINE     = 'wpengine';
    const HOST_WORDPRESSCOM = 'wordpresscom';
    const HOST_LIQUIDWEB    = 'liquidweb';
    const HOST_PANTHEON     = 'pantheon';
    const HOST_FLYWHEEL     = 'flywheel';

    /**
     *
     * @var self
     */
    protected static $instance = null;

    /**
     * this var prevent multiple params inizialization. 
     * it's useful on development to prevent an infinite loop in class constructor
     * 
     * @var bool
     */
    private $initialized = false;

    /**
     * custom hostings list 
     * 
     * @var DUPX_Host_interface[]
     */
    private $customHostings = array();

    /**
     * active custom hosting in current server
     * 
     * @var string[]
     */
    private $activeHostings = array();

    /**
     *
     * @return self
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * init custom histings
     */
    private function __construct()
    {
        $this->customHostings[DUPX_WPEngine_Host::getIdentifier()]     = new DUPX_WPEngine_Host();
        $this->customHostings[DUPX_GoDaddy_Host::getIdentifier()]      = new DUPX_GoDaddy_Host();
        $this->customHostings[DUPX_WordpressCom_Host::getIdentifier()] = new DUPX_WordpressCom_Host();
        $this->customHostings[DUPX_Liquidweb_Host::getIdentifier()]    = new DUPX_Liquidweb_Host();
        $this->customHostings[DUPX_Pantheon_Host::getIdentifier()]     = new DUPX_Pantheon_Host();
        $this->customHostings[DUPX_FlyWheel_Host::getIdentifier()]     = new DUPX_FlyWheel_Host();
    }

    /**
     * execute the active custom hostings inizialization only one time.
     * 
     * @return boolean
     * @throws Exception
     */
    public function init()
    {
        if ($this->initialized) {
            return true;
        }
        foreach ($this->customHostings as $cHost) {
            if (!($cHost instanceof DUPX_Host_interface)) {
                throw new Exception('Host must implemnete DUPX_Host_interface');
            }
            if ($cHost->isHosting()) {
                $this->activeHostings[] = $cHost->getIdentifier();
                $cHost->init();
            }
        }
        $this->initialized = true;
        return true;
    }

    /**
     * return the lisst of current custom active hostings
     * 
     * @return DUPX_Host_interface[]
     */
    public function getActiveHostings()
    {
        $result = array();
        foreach ($this->customHostings as $cHost) {
            if ($cHost->isHosting()) {
                $result[] = $cHost->getIdentifier();
            }
        }
        return $result;
    }

    /**
     * return true if current identifier hostoing is active
     * 
     * @param string $identifier
     * @return bool
     */
    public function isHosting($identifier)
    {
        return isset($this->customHostings[$identifier]) && $this->customHostings[$identifier]->isHosting();
    }

    /**
     * 
     * @return boolean|string return false if isn't managed manage hosting of manager hosting 
     */
    public function isManaged()
    {
        if ($this->isHosting(self::HOST_WPENGINE)) {
            return self::HOST_WPENGINE;
        } else if ($this->isHosting(self::HOST_LIQUIDWEB)) {
            return self::HOST_LIQUIDWEB;
        } else if ($this->isHosting(self::HOST_GODADDY)) {
            return self::HOST_GODADDY;
        } else if ($this->isHosting(self::HOST_WORDPRESSCOM)) {
            return self::HOST_WORDPRESSCOM;
        } else if ($this->isHosting(self::HOST_PANTHEON)) {
            return self::HOST_PANTHEON;
        } else if ($this->isHosting(self::HOST_FLYWHEEL)) {
            return self::HOST_FLYWHEEL;
        } else if ($this->wpConfigIsNotWriteable() || $this->notAccessibleCoreDirPresent()) {
            return true;
        } else {
            return false;
        }
    }


    public function wpConfigIsNotWriteable()
    {
        $path = $GLOBALS['DUPX_ROOT'].'/wp-config.php';

        return file_exists($path) && !is_writeable($path);
    }

    public function notAccessibleCoreDirPresent()
    {
        $coreDirs = array(
            $GLOBALS['DUPX_ROOT'].'/wp-admin',
            $GLOBALS['DUPX_ROOT'].'/wp-includes',
            $GLOBALS['DUPX_ROOT'].'/wp-content'
        );

        foreach ($coreDirs as $coreDir) {
            if (file_exists($coreDir) && !is_writeable($coreDir)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 
     * @param type $identifier
     * @return boolean|DUPX_Host_interface
     */
    public function getHosting($identifier)
    {
        if ($this->isHosting($identifier)) {
            return $this->customHostings[$identifier];
        } else {
            return false;
        }
    }
}