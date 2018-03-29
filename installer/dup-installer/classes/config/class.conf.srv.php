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
	public static $timestamp;

	/**
	 *  Setup this classes properties
	 */
	public static function init()
	{
		self::$timestamp = date("ymdHis");
	}

	/**
	 * Creates a copy of the original server config file and resets the original to blank
	 *
	 * @param string $path		The root path to the location of the server config files
	 *
	 * @return null
	 */
	public static function reset($path)
	{
		$time = self::$timestamp;
		DUPX_Log::info("\nWEB SERVER CONFIGURATION FILE STATUS:");

		//Apache
		if (self::runReset($path, '.htaccess')) {
			file_put_contents("{$path}/.htaccess", "#This file has been reset by Duplicator Pro. See .htaccess-{$time}.orig for the original file");
			@chmod("{$path}/.htaccess", 0644);
		}
		
		//.user.ini - For WordFence
		self::runReset($path, '.user.ini');

		//IIS: This is reset because on some instances of IIS having old values cause issues
		//Recommended fix for users who want it because errors are triggered is to have
		//them check the box for ignoring the web.config files on step 1 of installer
		if (self::runReset($path, 'web.config')) {
			$xml_contents  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			$xml_contents .= "<!-- Reset by Duplicator Installer.  Original can be found in web.config.{$time}.orig -->\n";
			$xml_contents .=  "<configuration></configuration>\n";
			@file_put_contents("{$path}/web.config", $xml_contents);
		}

	}

    /**
     * Copies the code in htaccess.orig to .htaccess
     *
     * @param $path					The root path to the location of the server config files
	 * @param $new_htaccess_name	New name of htaccess (either .htaccess or a backup name)
     *
     * @return bool					Returns true if the .htaccess file was retained successfully
     */

	public static function renameHtaccess($path, $new_htaccess_name){
        $status = false;

		if(!@rename($path.'/htaccess.orig', $path.'/' . $new_htaccess_name)){
            $status = true;
        }

        return $status;
    }

	/**
	 * Sets up the web config file based on the inputs from the installer forms.
	 *
	 * @param int $mu_mode		Is this site a specific multi-site mode
	 * @param object $dbh		The database connection handle for this request
	 * @param string $path		The path to the config file
	 *
	 * @return null
	 */
	public static function setup($mu_mode, $mu_generation, $dbh, $path)
	{
		DUPX_Log::info("\nWEB SERVER CONFIGURATION FILE UPDATED:");

		$timestamp = date("Y-m-d H:i:s");
		$newdata = parse_url($_POST['url_new']);
		$newpath = DUPX_U::addSlash(isset($newdata['path']) ? $newdata['path'] : "");
		$update_msg  = "# This file was updated by Duplicator Pro on {$timestamp}.\n";
		$update_msg .= (file_exists("{$path}/.htaccess")) ? "# See htaccess.orig for the .htaccess original file."	: "";


		//===============================================
		//BASIC SITE NO MU
		//===============================================
		if ($mu_mode == 0) {
			// no multisite
			$empty_htaccess	 = false;
			$query_result	 = @mysqli_query($dbh, "SELECT option_value FROM `{$GLOBALS['DUPX_AC']->wp_tableprefix}options` WHERE option_name = 'permalink_structure' ");

			if ($query_result) {
				$row = @mysqli_fetch_array($query_result);
				if ($row != null) {
					$permalink_structure = trim($row[0]);
					$empty_htaccess		 = empty($permalink_structure);
				}
			}


			if ($empty_htaccess) {
				$tmp_htaccess = '';
			} else {
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
				DUPX_Log::info("- Preparing .htaccess file with basic setup.");
			}


		//===============================================
		//MULTISITE WITH MU
		//===============================================
		} else if ($mu_mode == 1) {

			// multisite subdomain
if($mu_generation == 1) {
    // Pre-3.5
    $tmp_htaccess = <<<HTACCESS
{$update_msg}
# BEGIN WordPress (Pre 3.5 Multisite Subdomain)
RewriteEngine On
RewriteBase {$newpath}
RewriteRule ^index\.php$ - [L]

# uploaded files
RewriteRule ^files/(.+) wp-includes/ms-files.php?file=$1 [L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule . index.php [L]
# END WordPress
HTACCESS;
} else
{
    // 3.5+
			$tmp_htaccess = <<<HTACCESS
{$update_msg}
# BEGIN WordPress (3.5+ Multisite Subdomain)
RewriteEngine On
RewriteBase {$newpath}
RewriteRule ^index\.php$ - [L]

# add a trailing slash to /wp-admin
RewriteRule ^wp-admin$ wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^(wp-(content|admin|includes).*) $1 [L]
RewriteRule ^(.*\.php)$ $1 [L]
RewriteRule . index.php [L]
# END WordPress
HTACCESS;
}

                DUPX_Log::info("- Preparing .htaccess file with multisite subdomain setup.");

		} else {

			// multisite subdirectory
if($mu_generation == 1)
{
    // Pre 3.5
    $tmp_htaccess = <<<HTACCESS
{$update_msg}
# BEGIN WordPress (Pre 3.5 Multisite Subdirectory)
RewriteEngine On
RewriteBase {$newpath}
RewriteRule ^index\.php$ - [L]

# uploaded files
RewriteRule ^([_0-9a-zA-Z-]+/)?files/(.+) wp-includes/ms-files.php?file=$2 [L]

# add a trailing slash to /wp-admin
RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^[_0-9a-zA-Z-]+/(wp-(content|admin|includes).*) $1 [L]
RewriteRule ^[_0-9a-zA-Z-]+/(.*\.php)$ $1 [L]
RewriteRule . index.php [L]
# END WordPress
HTACCESS;
}
else {
    // 3.5+

			$tmp_htaccess = <<<HTACCESS
{$update_msg}
# BEGIN WordPress (3.5+ Multisite Subdirectory)
RewriteEngine On
RewriteBase {$newpath}
RewriteRule ^index\.php$ - [L]

# add a trailing slash to /wp-admin
RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]
RewriteRule . index.php [L]
# END WordPress
HTACCESS;
}
			DUPX_Log::info("- Preparing .htaccess file with multisite subdirectory setup.");

		}

		if (@file_put_contents("{$path}/.htaccess", $tmp_htaccess) === FALSE) {
			DUPX_Log::info("WARNING: Unable to update the .htaccess file! Please check the permission on the root directory and make sure the .htaccess exists.");
		} else {
			DUPX_Log::info("- Successfully updated the .htaccess file setting.");
		}
		@chmod('.htaccess', 0644);

		
	}


	/**
	 * Creates a copy of the original server config file and resets the original to blank per file
	 *
	 * @param string $path		The root path to the location of the server config file
	 * @param string $file_name	The file name of the config file
	 *
	 * @return bool		Returns true if the file was backed-up and reset.
	 */
	private static function runReset($path, $file_name)
	{
		$status = false;
		$file	= "{$path}/{$file_name}";
		$time	= self::$timestamp;

		if (file_exists($file)) {
			if (copy($file, "{$file}-{$time}.orig")) {
				$status = @unlink("{$path}/{$file_name}");
			}
		}
		
		($status)
			? DUPX_Log::info("- {$file_name} was reset and a backup made to {$file_name}-{$time}.orig.")
			: DUPX_Log::info("- {$file_name} file was not reset or backed up.");

		return $status;
	}
}

DUPX_ServerConfig::init();
