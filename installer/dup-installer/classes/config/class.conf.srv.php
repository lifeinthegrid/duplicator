<?php
defined("ABSPATH") or die("");

/**
 * Class used to update and edit web server configuration files
 * for .htaccess, web.config and .user.ini
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2 Full Documentation
 *
 */
class DUPX_ServerConfig
{
	protected static $fileHash;
	protected static $timeStamp;
	protected static $confFileApache;
	protected static $confFileApacheOrig;
	protected static $confFileIIS;
	protected static $confFileIISOrig;
	protected static $confFileWordFence;
	protected static $configMode;
	protected static $newSiteURL;

	/**
	 *  Setup this classes properties
	 */
	public static function init()
	{
		self::$fileHash				= date("ymdHis") . '-' . uniqid();
		self::$timeStamp			= date("Y-m-d H:i:s");
		self::$confFileApache		= "{$GLOBALS['DUPX_ROOT']}/.htaccess";
		self::$confFileApacheOrig	= "{$GLOBALS['DUPX_ROOT']}/htaccess.orig";
		self::$confFileIIS			= "{$GLOBALS['DUPX_ROOT']}/web.config";
		self::$confFileIISOrig		= "{$GLOBALS['DUPX_ROOT']}/web.config.orig";
		self::$confFileWordFence	= "{$GLOBALS['DUPX_ROOT']}/.user.ini";
		self::$configMode           = isset($_POST['config_mode']) ? $_POST['config_mode']  : null;
		self::$newSiteURL           = isset($_POST['url_new'])	   ? $_POST['url_new']		: null;
	}

	/**
	 * After the archive is extracted run setup checks
	 *
	 * @return null
	 */
	public static function afterExtractionSetup()
	{
		if (self::$configMode  != 'IGNORE') {
			//WORDFENCE: Only the WordFence file needs to be removed
			//completly from setup to avoid any issues
			self::removeFile(self::$confFileWordFence, 'WordFence');
		} else {
			DUPX_Log::info("** CONFIG FILE SET TO IGNORE ALL CHANGES **");
		}
	}

	/**
	 * Before the archive is extracted run a series of back and remove checks
	 * This is for existing config files that may exist before the ones in the
	 * archive are extracted.
	 *
	 * @return void
	 */
	public static function beforeExtractionSetup()
	{
		if (self::$configMode == 'IGNORE') {
			DUPX_Log::info("\nWARNING: Ignoring to update .htaccess, .user.ini and web.config files may cause");
			DUPX_Log::info("issues with the initial setup of your site.  If you run into issues with your site or");
			DUPX_Log::info("during the install process please change the 'Config Files' mode to 'Create New'.");
			DUPX_Log::info("This option is only for advanced users.");
		} else {
			//---------------------
			//APACHE
			$source    = 'Apache';
			if (self::createBackup(self::$confFileApache, $source))
				self::removeFile(self::$confFileApache, $source);

			//---------------------
			//MICROSOFT IIS
			$source    = 'Microsoft IIS';
			if (self::createBackup(self::$confFileIIS, $source))
				 self::removeFile(self::$confFileIIS, $source);

			//---------------------
			//WORDFENCE
			$source    = 'WordFence';
			if (self::createBackup(self::$confFileWordFence, $source))
				 self::removeFile(self::$confFileWordFence, $source);
		}
	}

    /**
     * Copies the code in htaccess.orig and web.config.orig
	 * to .htaccess and web.config
     *
	 * @return void
     */
	public static function renameOrigConfigs()
	{
		//APACHE
		if(rename(self::$confFileApacheOrig, self::$confFileApache)){
			DUPX_Log::info("\n- PASS: The orginal htaccess.orig was renamed");
		} else {
			DUPX_Log::info("\n- WARN: The orginal htaccess.orig was NOT renamed");
		}

		//IIS
		if(rename(self::$confFileIISOrig, self::$confFileIIS)){
			DUPX_Log::info("\n- PASS: The orginal htaccess.orig was renamed");
		} else {
			DUPX_Log::info("\n- WARN: The orginal htaccess.orig was NOT renamed");
		}
    }

	 /**
     * Creates the new config file
     *
	 * @return void
     */
	public static function createNewConfigs()
	{
		//APACHE
		if(file_exists(self::$confFileApacheOrig)){
			self::createNewApacheConfig();
			self::removeFile(self::$confFileApacheOrig, 'Apache');
		}

		//IIS
		if(file_exists(self::$confFileIISOrig)){
			self::createNewIISConfig();
			self::removeFile(self::$confFileIISOrig, 'Microsoft IIS');
		}
    }

	/**
	 * Sets up the web config file based on the inputs from the installer forms.
	 *
	 * @return void
	 */
	private static function createNewApacheConfig()
	{
		DUPX_Log::info("\nAPACHE CONFIGURATION FILE UPDATED:");

		$timestamp   = self::$timeStamp;
		$newdata	 = parse_url(self::$newSiteURL);
		$newpath	 = DUPX_U::addSlash(isset($newdata['path']) ? $newdata['path'] : "");
		$update_msg  = "# This file was created by Duplicator Installer on {$timestamp}.\n";
		$update_msg .= (file_exists(self::$confFileApache)) ? "# See htaccess.bak for a backup of .htaccess that was present before install ran."	: "";

        $tmp_htaccess = <<<HTACCESS
{$update_msg}
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase {$newpath}
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . {$newpath}index.php [L]
</IfModule>
# END WordPress
HTACCESS;

		if (@file_put_contents(self::$confFileApache, $tmp_htaccess) === FALSE) {
			DUPX_Log::info("- WARN: Unable to update the .htaccess file! Please check the permission on the root directory and make sure the .htaccess exists.");
		} else {
			DUPX_Log::info("- PASS: Successfully updated the .htaccess file setting.");
			@chmod(self::$confFileApache, 0644);
		}
    }

	/**
	 * Sets up the web config file based on the inputs from the installer forms.
	 *
	 * @return void
	 */
	private static function createNewIISConfig()
	{
		$timestamp = self::$timeStamp;
		$xml_contents  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml_contents .= "<!-- This file was created by Duplicator Installer on {$timestamp}.  Original can be found in web.config.orig -->\n";
		$xml_contents .=  "<configuration></configuration>\n";
		@file_put_contents(self::$confFileIIS, $xml_contents);
    }
	
	/**
	 * Creates a copy of any existing file and hashes it with a .bak extension
	 *
	 * @param string $file_path		The full path of the config file
	 * @param string $source		The source name of the configuration
	 *
	 * @return bool		Returns true if the file was backed-up.
	 */
	private static function createBackup($file_path, $source)
	{
		$status		= false;
		$file_name  = SnapLibIOU::getFileName($file_path);
		$hash		= self::$fileHash;
		if (is_file($file_path)) {
			$status = copy($file_path, "{$file_path}-{$hash}.bak");
			$status ? DUPX_Log::info("- PASS: {$source} '{$file_name}' backed-up to {$file_name}-{$hash}.bak")
					: DUPX_Log::info("- WARN: {$source} '{$file_name}' unable to create backup copy, a possible permission error?");
		} else {
			DUPX_Log::info("- PASS: {$source} '{$file_name}' not found - no backup needed.");
		}

		return $status;
	}

	/**
	 * Removes the specified file
	 *
	 * @param string $file_path		The full path of the config file
	 * @param string $source		The source name of the configuration
	 *
	 * @return bool		Returns true if the file was removed
	 */
	private static function removeFile($file_path, $source)
	{
		$status = false;
		if (is_file($file_path)) {
			$file_name  = SnapLibIOU::getFileName($file_path);
			$status = @unlink($file_path);
			if ($status === FALSE) {
				@chmod($file_path, 0777);
				$status = @unlink($file_path);
			}
			$status ? DUPX_Log::info("- PASS: Existing {$source} '{$file_name}' was removed")
					: DUPX_Log::info("- WARN: Existing {$source} '{$file_path}' not removed, a possible permission error?");
		}
		return $status;
	}

}

DUPX_ServerConfig::init();
