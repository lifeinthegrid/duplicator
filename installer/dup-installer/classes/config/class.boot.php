<?php
/**
 * Boot class
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2 Full Documentation
 *
 * @package SC\DUPX\Constants
 *
 */
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

/**
 * This class manages all the initialization of the installer by performing security tests, log initialization and global variables.
 * 
 */
class DUPX_Boot
{

    const ARCHIVE_PREFIX      = 'dup-archive__';
    const ARCHIVE_EXTENSION   = '.txt';
    const MINIMUM_PHP_VERSION = '5.3.8';

    /**
     * this variable becomes false after the installer is initialized by skipping the shutdown function defined in the boot class
     * 
     * @var bool  
     */
    private static $shutdownFunctionEnabled = true;

    /**
     * inizialize all
     */
    public static function init()
    {
        self::phpVersionCheck();

        $GLOBALS['DUPX_ENFORCE_PHP_INI'] = false;

        // INIT ERROR LOG FILE (called before evrithing)
        if (function_exists('register_shutdown_function')) {
            register_shutdown_function(array(__CLASS__, 'bootShutdown'));
        }
        if (self::initPhpErrorLog(false) === false) {
            // Enable this only for debugging. Generate a log too alarmist.            
            error_log('DUPLICATOR CAN\'T CHANGE THE PATH OF PHP ERROR LOG FILE', E_USER_NOTICE);
        }

        // includes main files
        self::includes();
        // set log post-proccessor
        DUPX_Log::setPostProcessCallback(array('DUPX_CTRL', 'renderPostProcessings'));
        // set time for logging time
        DUPX_Log::resetTime();
        // set all PHP.INI settings
        self::phpIni();
        self::initParamsBase();

        /*
         * INIZIALIZE
         */
        // init global values
        DUPX_Constants::init();

        //init managed host manager
        DUPX_Custom_Host_Manager::getInstance()->init();

        // init ERR defines
        DUPX_Constants::initErrDefines();
        // init error handler after constant
        DUPX_Handler::initErrorHandler();

        self::initArchive();

        $pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        DUPX_Log::info("\n\n"
            ."==============================================\n"
            ."= BOOT INIT OK [".$pathInfo."]\n"
            ."==============================================\n", DUPX_Log::LV_DETAILED);
    }

    /**
     * init ini_set and default constants
     *
     * @throws Exception
     */
    public static function phpIni()
    {
        /** Absolute path to the Installer directory. - necessary for php protection */
        if (!defined('KB_IN_BYTES')) {
            define('KB_IN_BYTES', 1024);
        }
        if (!defined('MB_IN_BYTES')) {
            define('MB_IN_BYTES', 1024 * KB_IN_BYTES);
        }
        if (!defined('GB_IN_BYTES')) {
            define('GB_IN_BYTES', 1024 * MB_IN_BYTES);
        }
        if (!defined('DUPLICATOR_PHP_MAX_MEMORY')) {
            define('DUPLICATOR_PHP_MAX_MEMORY', 4096 * MB_IN_BYTES);
        }

        date_default_timezone_set('UTC'); // Some machines don’t have this set so just do it here.
        @ignore_user_abort(true);

        @set_time_limit(3600);

        $defaultCharset = ini_get("default_charset");
        if (empty($defaultCharset) && DupLiteSnapLibUtil::wp_is_ini_value_changeable('default_charset')) {
            @ini_set("default_charset", 'utf-8');
        }
        if (DupLiteSnapLibUtil::wp_is_ini_value_changeable('memory_limit')) {
            @ini_set('memory_limit', DUPLICATOR_PHP_MAX_MEMORY);
        }
        if (DupLiteSnapLibUtil::wp_is_ini_value_changeable('max_input_time')) {
            @ini_set('max_input_time', '-1');
        }
        if (DupLiteSnapLibUtil::wp_is_ini_value_changeable('pcre.backtrack_limit')) {
            @ini_set('pcre.backtrack_limit', PHP_INT_MAX);
        }

        //PHP INI SETUP: all time in seconds
        if (!isset($GLOBALS['DUPX_ENFORCE_PHP_INI']) || !$GLOBALS['DUPX_ENFORCE_PHP_INI']) {
            if (DupLiteSnapLibUtil::wp_is_ini_value_changeable('mysql.connect_timeout')) {
                @ini_set('mysql.connect_timeout', '5000');
            }
            if (DupLiteSnapLibUtil::wp_is_ini_value_changeable('max_execution_time')) {
                @ini_set("max_execution_time", '5000');
            }
            if (DupLiteSnapLibUtil::wp_is_ini_value_changeable('max_input_time')) {
                @ini_set("max_input_time", '5000');
            }
            if (DupLiteSnapLibUtil::wp_is_ini_value_changeable('default_socket_timeout')) {
                @ini_set('default_socket_timeout', '5000');
            }
            @set_time_limit(0);
        }
    }

    /**
     * include default utils files and constants
     *
     * @throws Exception
     */
    public static function includes()
    {
        require_once($GLOBALS['DUPX_INIT'].'/lib/snaplib/snaplib.all.php');
        require_once($GLOBALS['DUPX_INIT'].'/classes/utilities/class.u.exceptions.php');
        require_once($GLOBALS['DUPX_INIT'].'/classes/utilities/class.u.php');
        require_once($GLOBALS['DUPX_INIT'].'/classes/utilities/class.u.notices.manager.php');
        require_once($GLOBALS['DUPX_INIT'].'/classes/utilities/class.u.html.php');
        require_once($GLOBALS['DUPX_INIT'].'/classes/config/class.constants.php');
        require_once($GLOBALS['DUPX_INIT'].'/classes/class.package.php');
        require_once($GLOBALS['DUPX_INIT'].'/ctrls/ctrl.base.php');
        require_once($GLOBALS['DUPX_INIT'].'/classes/config/class.archive.config.php');
        require_once($GLOBALS['DUPX_INIT'].'/classes/class.logging.php');
        require_once($GLOBALS['DUPX_INIT'].'/classes/host/class.custom.host.manager.php');
    }

    /**
     * init archive config
     * 
     * @throws Exception
     */
    public static function initArchive()
    {
        $GLOBALS['DUPX_AC'] = DUPX_ArchiveConfig::getInstance();
        if (empty($GLOBALS['DUPX_AC'])) {
            throw new Exception("Can't initialize config globals");
        }
    }

    /**
     * This function moves the error_log.php into the dup-installer directory.
     * It is called before including any other file so it uses only native PHP functions.
     * 
     * !!! Don't use any Duplicator function within this function. !!!
     * 
     * @param bool $reset
     * @return boolean
     */
    public static function initPhpErrorLog($reset = false)
    {
        if (!function_exists('ini_set')) {
            return false;
        }

        $logFile = $GLOBALS['DUPX_INIT'].'/php_error__'.self::getPackageHash().'.log';

        if (file_exists($logFile) && !is_writable($logFile)) {
            if (!is_writable($logFile)) {
                return false;
            } else if ($reset && function_exists('unlink')) {
                @unlink($logFile);
            }
        }

        if (function_exists('error_reporting')) {
            error_reporting(E_ALL | E_STRICT);  // E_STRICT for PHP 5.3
        }

        @ini_set("log_errors", 1);
        if (@ini_set("error_log", $logFile) === false) {
            return false;
        }

        if (!file_exists($logFile)) {
            error_log("PHP ERROR LOG INIT");
        }

        return true;
    }

    /**
     * It is called before including any other file so it uses only native PHP functions.
     * 
     * !!! Don't use any Duplicator function within this function. !!!
     * 
     * @staticvar bool|string $packageHash
     * @return bool|string      // package hash or false if fail
     */
    public static function getPackageHash()
    {
        static $packageHash = null;
        if (is_null($packageHash)) {
            $searchStr    = $GLOBALS['DUPX_INIT'].'/'.self::ARCHIVE_PREFIX.'*'.self::ARCHIVE_EXTENSION;
            $config_files = glob($searchStr);
            if (empty($config_files)) {
                $packageHash = false;
            } else {
                $config_file_absolute_path = array_pop($config_files);
                $config_file_name          = basename($config_file_absolute_path, self::ARCHIVE_EXTENSION);
                $packageHash               = substr($config_file_name, strlen(self::ARCHIVE_PREFIX));
            }
        }
        return $packageHash;
    }

    /**
     *  This function init all params before read from request
     * 
     */
    protected static function initParamsBase()
    {
        DUPX_Log::setLogLevel();
        $GLOBALS['DUPX_DEBUG'] = isset($_POST['logging']) ? $_POST['logging'] : DUPX_Log::LV_DEFAULT;
    }

    /**
     * this function disables the shutdown function defined in the boot class
     */
    public static function disableBootShutdownFunction()
    {
        self::$shutdownFunctionEnabled = false;
    }

    /**
     * This function sets the shutdown function before the installer is initialized.
     * Prevents blank pages.
     * 
     * After the plugin is initialized it will be set as a shudwon ​​function DUPX_Handler::shutdown
     * 
     * !!! Don't use any Duplicator function within this function. !!!
     * 
     */
    public static function bootShutdown()
    {
        if (!self::$shutdownFunctionEnabled) {
            return;
        }

        if (($error = error_get_last())) {
            ?>
            <h1>BOOT SHUTDOWN FATAL ERROR</H1>
            <pre><?php
                echo 'Error: '.htmlspecialchars($error['message'])."\n\n\n".
                'Type: '.htmlspecialchars($error['type'])."\n".
                'File: '.htmlspecialchars($error['file'])."\n".
                'Line: '.htmlspecialchars($error['line'])."\n";
                ?>
            </pre>
            <?php
        }
    }

    /**
     * this function is called before anything else. do not use duplicator functions because nothing is included at this level.
     * 
     * @return boolean
     */
    public static function phpVersionCheck()
    {
        if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '>=')) {
            return true;
        }
        $match = null;
        if (preg_match("#^\d+(\.\d+)*#", PHP_VERSION, $match)) {
            $phpVersion = $match[0];
        } else {
            $phpVersion = PHP_VERSION;
        }
        $minPHPVersion = self::MINIMUM_PHP_VERSION;
        
        echo '<div style="line-height:25px">';

        echo "NOTICE: This web server is running <b>PHP: {$phpVersion}</b>.&nbsp; A minimum of PHP {$minPHPVersion} is required to run the installer and PHP 7.0+ is recommended.<br/>";
        echo "Please contact your host or server administrator and let them know you would like to upgrade your PHP version.<br/>";
        
        echo '<i>For more information on this topic see the FAQ titled '
        . '<a href="https://snapcreek.com/duplicator/docs/faqs-tech/#faq-licensing-017-q" target="_blank">What version of PHP Does Duplicator Support?</a></i>';

        echo '</div>';
        
        die();
    }
}