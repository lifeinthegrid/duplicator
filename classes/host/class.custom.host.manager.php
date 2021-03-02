<?php

/**
 * custom hosting manager
 * singleton class
 *
 * Standard: PSR-2
 *
 * @package SC\DUPX\HOST
 * @link http://www.php-fig.org/psr/psr-2/
 *
 */
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

require_once (DUPLICATOR_PLUGIN_PATH . '/classes/host/interface.host.php');
require_once (DUPLICATOR_PLUGIN_PATH . '/classes/host/class.godaddy.host.php');
require_once (DUPLICATOR_PLUGIN_PATH . '/classes/host/class.wpengine.host.php');
require_once (DUPLICATOR_PLUGIN_PATH . '/classes/host/class.wordpresscom.host.php');
require_once (DUPLICATOR_PLUGIN_PATH . '/classes/host/class.liquidweb.host.php');
require_once (DUPLICATOR_PLUGIN_PATH . '/classes/host/class.pantheon.host.php');
require_once (DUPLICATOR_PLUGIN_PATH . '/classes/host/class.flywheel.host.php');

class DUP_Custom_Host_Manager
{
    const HOST_GODADDY      = 'godaddy';
    const HOST_WPENGINE     = 'wpengine';
    const HOST_WORDPRESSCOM = 'wordpresscom';
    const HOST_LIQUIDWEB    = 'liquidweb';
    const HOST_PANTHEON     = 'pantheon';
    const HOST_FLYWHEEL     = 'flywheel';

    /**
     *
     * @var DUP_Custom_Host_Manager
     */
    protected static $instance = null;

    /**
     *
     * @var bool
     */
    private $initialized = false;

    /**
     *
     * @var DUP_Host_interface[]
     */
    private $customHostings = array();

    /**
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

    private function __construct()
    {
        $this->customHostings[DUP_WPEngine_Host::getIdentifier()]     = new DUP_WPEngine_Host();
        $this->customHostings[DUP_GoDaddy_Host::getIdentifier()]      = new DUP_GoDaddy_Host();
        $this->customHostings[DUP_WordpressCom_Host::getIdentifier()] = new DUP_WordpressCom_Host();
        $this->customHostings[DUP_Liquidweb_Host::getIdentifier()]    = new DUP_Liquidweb_Host();
        $this->customHostings[DUP_Pantheon_Host::getIdentifier()]     = new DUP_Pantheon_Host();
        $this->customHostings[DUP_Flywheel_Host::getIdentifier()]     = new DUP_Flywheel_Host();
    }

    public function init()
    {
        if ($this->initialized) {
            return true;
        }
        foreach ($this->customHostings as $cHost) {
            if (!($cHost instanceof DUP_Host_interface)) {
                throw new Exception('Host must implement DUP_Host_interface');
            }
            if ($cHost->isHosting()) {
                $this->activeHostings[] = $cHost->getIdentifier();
                $cHost->init();
            }
        }
        $this->initialized = true;
        return true;
    }

    public function getActiveHostings()
    {
        return $this->activeHostings;
    }

    public function isHosting($identifier)
    {
        return in_array($identifier, $this->activeHostings);
    }

    public function isManaged()
    {
        if ($this->isHosting(self::HOST_WORDPRESSCOM)) {
            return true;
        }

        if ($this->isHosting(self::HOST_GODADDY)) {
            return true;
        }

        if ($this->isHosting(self::HOST_WPENGINE)) {
            return true;
        }

        if ($this->isHosting(self::HOST_LIQUIDWEB)) {
            return true;
        }

        if ($this->isHosting(self::HOST_PANTHEON)) {
            return true;
        }

        if ($this->isHosting(self::HOST_FLYWHEEL)) {
            return true;
        }

        if ($this->WPConfigIsNotWriteable()) {
            return true;
        }

        if ($this->notAccessibleCoreDirPresent()) {
            return true;
        }

        return false;
    }

    public function WPConfigIsNotWriteable()
    {
        $wpConfigPath = duplicator_get_abs_path()."/wp-config.php";

        return file_exists($wpConfigPath) && !is_writeable($wpConfigPath);
    }

    public function notAccessibleCoreDirPresent()
    {
        $absPath = duplicator_get_abs_path();
        $coreDirs = array(
            $absPath.'/wp-admin',
            $absPath.'/wp-includes',
            WP_CONTENT_DIR
        );

        foreach ($coreDirs as $coreDir) {
            if (file_exists($coreDir) && !is_writeable($coreDir)) {
                return true;
            }
        }

        return false;
    }

    public function getHosting($identifier)
    {
        if ($this->isHosting($identifier)) {
            return $this->activeHostings[$identifier];
        } else {
            return false;
        }
    }
}
