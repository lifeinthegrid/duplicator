<?php
defined("ABSPATH") or die("");

/**
 * Class used to update and edit web server configuration files
 * for .htaccess, web.config and user.ini
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2 Full Documentation
 *
 * @package SC\DUPX\ServerConfig
 *
 */
class DUPX_ServerConfig
{
	/* @var $GLOBALS['DUPX_AC'] DUPX_ArchiveConfig */

	/**
	 *  Common timestamp of all members of this class
	 */
	public static $filehash;

	/**
	 *  Setup this classes properties
	 */
	public static function init()
	{
		self::$filehash = date("ymdHis") . '-' . uniqid();
	}

	/**
	 * After the archive is extracted run setup checks
	 *
	 * @return null
	 */
	public static function afterExtractionSetup()
	{
		if ($_POST['config_mode'] == 'NEW') {
			//APACHE
			$file_path = "{$GLOBALS['DUPX_ROOT']}/htaccess.orig";
			self::removeFile($file_path, 'Apache');

			//MICROSOFT IIS
			$file_path = "{$GLOBALS['DUPX_ROOT']}/web.config.orig";
			self::removeFile($file_path, 'Microsoft IIS');

			//WORDFENCE
			$file_path = "{$GLOBALS['DUPX_ROOT']}/.user.ini";
			self::removeFile($file_path, 'WordFence');
		}
	}

	/**
	 * Before the archive is extracted run a series of back and remove checks
	 *
	 * @return void
	 */
	public static function beforeExtractionSetup()
	{
		//---------------------
		//APACHE
		$source    = 'Apache';
		$file_path = "{$GLOBALS['DUPX_ROOT']}/.htaccess";
		if (self::createBackup($file_path, $source))
			self::removeFile($file_path, $source);

		//---------------------
		//MICROSOFT IIS
		$source    = 'Microsoft IIS';
		$file_path = "{$GLOBALS['DUPX_ROOT']}/web.config";
		if (self::createBackup($file_path, $source))
			 self::removeFile($file_path, $source);

		//---------------------
		//WORDFENCE
		$source    = 'WordFence';
		$file_path = "{$GLOBALS['DUPX_ROOT']}/.user.ini";
		if (self::createBackup($file_path, $source))
			 self::removeFile($file_path, $source);
	}

    /**
     * Copies the code in htaccess.orig to .htaccess
     *
	 * @return void
     *
     */
	public static function renameOrigConfigs()
	{
		//APACHE
		if(rename("{$GLOBALS['DUPX_ROOT']}/htaccess.orig", "{$GLOBALS['DUPX_ROOT']}/.htaccess")){
			DUPX_Log::info("\n- PASS: The orginal htaccess.orig was renamed");
		} else {
			DUPX_Log::info("\n- WARN: The orginal htaccess.orig was NOT renamed");
		}

		//IIS
		if(rename("{$GLOBALS['DUPX_ROOT']}/web.config.orig", "{$GLOBALS['DUPX_ROOT']}/web.config")){
			DUPX_Log::info("\n- PASS: The orginal htaccess.orig was renamed");
		} else {
			DUPX_Log::info("\n- WARN: The orginal htaccess.orig was NOT renamed");
		}
    }

	/**
	 * Sets up the web config file based on the inputs from the installer forms.
	 *
	 * @return null
	 */
	public static function createNewApacheConfig()
	{
		DUPX_Log::info("\nAPACHE CONFIGURATION FILE UPDATED:");

		$timestamp = date("Y-m-d H:i:s");
		$newdata = parse_url($_POST['url_new']);
		$newpath = DUPX_U::addSlash(isset($newdata['path']) ? $newdata['path'] : "");
		$update_msg  = "# This file was created by Duplicator on {$timestamp}.\n";
		$update_msg .= (file_exists("{$GLOBALS['DUPX_ROOT']}/.htaccess")) ? "# See htaccess.bak for a backup of .htaccess that was present before install ran."	: "";

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
        

		if (@file_put_contents("{$GLOBALS['DUPX_ROOT']}/.htaccess", $tmp_htaccess) === FALSE) {
			DUPX_Log::info("- WARN: Unable to update the .htaccess file! Please check the permission on the root directory and make sure the .htaccess exists.");
		} else {
			DUPX_Log::info("- PASS: Successfully updated the .htaccess file setting.");
			@chmod('.htaccess', 0644);
		}

    }

	/**
	 * Sets up the web config file based on the inputs from the installer forms.
	 *
	 * @return null
	 */
	public static function createNewIISConfig()
	{
		 //---------------------
		//MICROSOFT IIS
		$file_name = 'web.config';
		$file_path = "{$GLOBALS['DUPX_ROOT']}/{$file_name}";
		 if (file_exists($file_path)) {
			$xml_contents  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			$xml_contents .= "<!-- Reset by Duplicator Installer.  Original can be found in web.config.{$hash}.orig -->\n";
			$xml_contents .=  "<configuration></configuration>\n";
			@file_put_contents($file_path, $xml_contents);
		 }
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
		$hash		= self::$filehash;
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
			@chmod($file_path, 0777);
			$status = @unlink($file_path);
			$status ? DUPX_Log::info("- PASS: Existing {$source} '{$file_name}' was removed")
					: DUPX_Log::info("- WARN: Existing {$source} '{$file_path}' not removed, a possible permission error?");
		}
		return $status;
	}

}

DUPX_ServerConfig::init();
