<?php
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

/**
 * DUPX_cPanel  
 * Wrapper Class for cPanel API  */
class DUPX_Server
{
	/**
	 * Returns true if safe mode is enabled
	 */
	public static $php_safe_mode_on = false;

	/**
	 * The servers current PHP version
	 */
	public static $php_version = 0;

	/**
	 * The minimum PHP version the installer will support
	 */
	public static $php_version_min = "5.3.8";

	/**
	 * Is the current servers version of PHP safe to use with the installer
	 */
	public static $php_version_safe = false;

	/**
	 * A list of the core WordPress directories
	 */
	public static $wpCoreDirsList = "wp-admin,wp-includes,wp-content";

	public static function _init()
	{
		self::$php_safe_mode_on	 = in_array(strtolower(@ini_get('safe_mode')), array('on', 'yes', 'true', 1, "1"));
		self::$php_version		 = phpversion();
		self::$php_version_safe	 = (version_compare(phpversion(), self::$php_version_min) >= 0);
	}

	/**
	 *  Display human readable byte sizes
	 *  @param string $size		The size in bytes
	 */
	public static function is_dir_writable($path)
	{
		if (!@is_writeable($path)) return false;

		if (is_dir($path)) {
			if ($dh = @opendir($path)) {
				closedir($dh);
			} else {
				return false;
			}
		}

		return true;
	}

	/**
	 *  Can this server process in shell_exec mode
	 *  @return bool
	 */
	public static function is_shell_exec_available()
	{
		if (array_intersect(array('shell_exec', 'escapeshellarg', 'escapeshellcmd', 'extension_loaded'), array_map('trim', explode(',', @ini_get('disable_functions'))))) return false;

		//Suhosin: http://www.hardened-php.net/suhosin/
		//Will cause PHP to silently fail.
		if (extension_loaded('suhosin')) return false;

		// Can we issue a simple echo command?
		if (!@shell_exec('echo duplicator')) return false;

		return true;
	}

	/**
	 *  Returns the path this this server where the zip command can be called
	 *  @return string	The path to where the zip command can be processed
	 */
	public static function get_unzip_filepath()
	{
		$filepath = null;
		if (self::is_shell_exec_available()) {
			if (shell_exec('hash unzip 2>&1') == NULL) {
				$filepath = 'unzip';
			} else {
				$possible_paths = array('/usr/bin/unzip', '/opt/local/bin/unzip');
				foreach ($possible_paths as $path) {
					if (file_exists($path)) {
						$filepath = $path;
						break;
					}
				}
			}
		}
		return $filepath;
	}
    
    /**
     * 
     * @return string[]
     */
    public static function getWpAddonsSiteLists()
    {
        $addonsSites  = array();
        $pathsToCheck = $GLOBALS['DUPX_ROOT'];
        
        if (is_scalar($pathsToCheck)) {
            $pathsToCheck = array($pathsToCheck);
        }

        foreach ($pathsToCheck as $mainPath) {
            DupLiteSnapLibIOU::regexGlobCallback($mainPath, function ($path) use (&$addonsSites) {
                if (DupLiteSnapLibUtilWp::isWpHomeFolder($path)) {
                    $addonsSites[] = $path;
                }
            }, array(
                'regexFile' => false,
                'recursive' => true
            ));
        }

        return $addonsSites;
    }

	/**
	* Does the site look to be a WordPress site
	*
	* @return bool		Returns true if the site looks like a WP site
	*/
	public static function isWordPress()
	{
		$search_list  = explode(',', self::$wpCoreDirsList);
		$root_files   = scandir($GLOBALS['DUPX_ROOT']);
		$search_count = count($search_list);
		$file_count   = 0;
		foreach ($root_files as $file) {
			if (in_array($file, $search_list)) {
				$file_count++;
			} 
		}
		return ($search_count == $file_count);
	}

    public static function parentWordfencePath()
    {
        $scanPath = $GLOBALS['DUPX_ROOT'];
        $rootPath = DupLiteSnapLibIOU::getMaxAllowedRootOfPath($scanPath);

        if ($rootPath === false) {
            //$scanPath is not contained in open_basedir paths skip
            return false;
        }

        DUPX_Handler::setMode(DUPX_Handler::MODE_OFF);
        $continueScan = true;
        while ($continueScan) {
            if (self::wordFenceFirewallEnabled($scanPath)) {
                return $scanPath;
                break;
            }
            $continueScan = $scanPath !== $rootPath && $scanPath != dirname($scanPath);
            $scanPath     = dirname($scanPath);
        }
        DUPX_Handler::setMode();

        return false;
    }

    protected static function wordFenceFirewallEnabled($path)
    {
        $configFiles = array(
            'php.ini',
            '.user.ini',
            '.htaccess'
        );

        foreach ($configFiles as $configFile) {
            $file = $path.'/'.$configFile;

            if (!@is_readable($file)) {
                continue;
            }

            if (($content = @file_get_contents($file)) === false) {
                continue;
            }

            if (strpos($content, 'wordfence-waf.php') !== false) {
                return true;
            }
        }

        return false;
    }
	
	/**
	* Is the web server IIS
	*
	* @return bool		Returns true if web server is IIS
	*/
    public static function isIISRunning()
	{
		$sSoftware = strtolower( $_SERVER["SERVER_SOFTWARE"] );
		if ( strpos($sSoftware, "microsoft-iis") !== false ) {
			return true;
		} else {
			return false;
		}
	}



}
//INIT Class Properties
DUPX_Server::_init();
