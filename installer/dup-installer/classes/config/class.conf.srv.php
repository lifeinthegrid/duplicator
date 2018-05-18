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
		self::$filehash = date("ymdHis") . uniqid();
	}

	/**
	 * After the archive is extracted run a series of back and remove checks
	 *
	 * @return null
	 */
	public static function afterExtractionSetup()
	{
		$hash = self::$filehash;

		//---------------------
		//APACHE
		//No need to make update to htaccess.org file

		 //---------------------
		//WORDFENCE
		$file_name = '.user.ini';
		$file_path = "{$GLOBALS['DUPX_ROOT']}/{$file_name}";
		self::removeFile($file_path)
		   ? DUPX_Log::info("- PASS: WordFence {$file_name} was removed")
		   : DUPX_Log::info("- WARN: WordFence {$file_name} was not removed, a possible permission error?");


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
	 * Before the archive is extracted run a series of back and remove checks
	 *
	 * @return null
	 */
	public static function beforeExtractionSetup()
	{
		$time = self::$filehash;

		//---------------------
		//APACHE
		$file_name = '.htaccess';
		$file_path = "{$GLOBALS['DUPX_ROOT']}/{$file_name}";
		 if (self::createBackup($file_path)) {
			 DUPX_Log::info("- PASS: Apache {$file_name} was backed-up to {$file_name}-{$time}.bak");
			 self::removeFile($file_path)
				? DUPX_Log::info("- PASS: Apache {$file_name} was removed")
				: DUPX_Log::info("- WARN: Apache $file_path not removed, a possible permission error?");
		 } else {
			 DUPX_Log::info("- PASS: Apache {$file_name} file was not found in root directory");
		 }

		 //---------------------
		//WORDFENCE
		$file_name = '.user.ini';
		$file_path = "{$GLOBALS['DUPX_ROOT']}/{$file_name}";
		 if (self::createBackup($file_path)) {
			 DUPX_Log::info("- PASS: WordFence {$file_name} was backed-up to {$file_name}-{$time}.bak");
			 self::removeFile($file_path)
				? DUPX_Log::info("- PASS: WordFence {$file_name} was removed")
				: DUPX_Log::info("- WARN: WordFence {$file_name} not removed, a possible permission error?");
		 } else {
			 DUPX_Log::info("- PASS: WordFence {$file_name} file was not found in root directory");
		 }

		 //---------------------
		//MICROSOFT IIS
		$file_name = 'web.config';
		$file_path = "{$GLOBALS['DUPX_ROOT']}/{$file_name}";
		 if (self::createBackup($file_path)) {
			 DUPX_Log::info("- PASS: Microsoft IIS {$file_name} was backed-up to {$file_name}-{$time}.bak");
			 self::removeFile($file_path)
				? DUPX_Log::info("- PASS: Microsoft IIS {$file_name} was removed")
				: DUPX_Log::info("- WARN: Microsoft IIS {$file_name} not removed, a possible permission error?");
		 } else {
			 DUPX_Log::info("- PASS: Microsoft IIS {$file_name} file was not found in root directory");
		 }
	}

    /**
     * Copies the code in htaccess.orig to .htaccess
     *
     * @param $path					The root path to the location of the server config files
     *
     * @return bool					Returns true if the .htaccess file was retained successfully
     */
	public static function renameHtaccessOrigFile($path)
	{
		return rename("{$path}/htaccess.orig", "{$path}/.htaccess");
    }

	/**
	 * Sets up the web config file based on the inputs from the installer forms.
	 *
	 * @param object $dbh		The database connection handle for this request
	 * @param string $path		The path to the config file
	 *
	 * @return null
	 */
	public static function makeBasicHtaccess($path)
	{
		DUPX_Log::info("\nAPACHE CONFIGURATION FILE UPDATED:");

		$timestamp = date("Y-m-d H:i:s");
		$newdata = parse_url($_POST['url_new']);
		$newpath = DUPX_U::addSlash(isset($newdata['path']) ? $newdata['path'] : "");
		$update_msg  = "# This file was created by Duplicator on {$timestamp}.\n";
		$update_msg .= (file_exists("{$path}/.htaccess")) ? "# See htaccess.bak for a backup of .htaccess that was present before install ran."	: "";

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
        

		if (@file_put_contents("{$path}/.htaccess", $tmp_htaccess) === FALSE) {
			DUPX_Log::info("- WARN: Unable to update the .htaccess file! Please check the permission on the root directory and make sure the .htaccess exists.");
		} else {
			DUPX_Log::info("- PASS: Successfully updated the .htaccess file setting.");
			@chmod('.htaccess', 0644);
		}

    }
	
	/**
	 * Creates a copy of any existing file and hashes it with a .bak extension
	 *
	 * @param string $file_path		The full path of the config file
	 *
	 * @return bool		Returns true if the file was backed-up and reset.
	 */
	private static function createBackup($file_path)
	{
		$status		= false;
		//$file_name	= SnapLibIOU::getFileName($file_path);
		$hash		= self::$filehash;

		if (is_file($file_path)) {
			$status = copy($file_path, "{$file_path}-{$hash}.bak");
		}

		return $status;
	}

	private static function removeFile($file_path)
	{
		$status = false;
		if (is_file($file_path)) {
			chmod($file_path, 0777);
			$status = unlink($file_path);
		}

		return $status;
	}

}

DUPX_ServerConfig::init();
