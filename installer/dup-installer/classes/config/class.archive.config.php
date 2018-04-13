<?php
defined("ABSPATH") or die("");
/**
 * Class used to control values about the package meta data
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2 Full Documentation
 *
 * @package SC\DUPX\ArchiveConfig
 *
 */

abstract class DUPX_LicenseType
{
	const Unlicensed	 = 0;
	const Personal	 = 1;
	const Freelancer	 = 2;
	const BusinessGold = 3;
}

class DUPX_ArchiveConfig
{
	const Config_Filename = 'archive.cfg';

	//READ-ONLY: COMPARE VALUES
	public $created;
	public $version_dup;
	public $version_wp;
	public $version_db;
	public $version_php;
	public $version_os;
	public $dbInfo;

	//GENERAL
	public $secure_on;
	public $secure_pass;
	public $skipscan;
	public $package_name;
	public $package_notes;
	public $wp_tableprefix;
	public $blogname;
    public $wplogin_url;
	public $relative_content_dir;
	public $blogNameSafe;
	public $exportOnlyDB;

	//BASIC DB
	public $dbhost;
	public $dbname;
	public $dbuser;
	public $dbpass;
	//CPANEL: Login
	public $cpnl_host;
	public $cpnl_user;
	public $cpnl_pass;
	public $cpnl_enable;
	public $cpnl_connect;
	//CPANEL: DB
	public $cpnl_dbaction;
	public $cpnl_dbhost;
	public $cpnl_dbname;
	public $cpnl_dbuser;
	//ADV OPTS
	public $cache_wp;
	public $cache_path;
	public $wproot;
	public $url_old;
	public $opts_delete;
	//MULTISITE
	public $mu_mode;
    public $mu_generation;
	public $subsites;
	public $mu_is_filtered;
	//LICENSING
	public $license_limit;
	public $debug_mode = false;

	private static $instance = null;

	/**
	 * Loads a usable object from the archive.cfg file found in the dup-installer root
	 *
	 * @param string $path		The root path to the location of the server config files
	 *
	 * @return obj	Returns an instance of DUPX_ArchiveConfig
	 */
	public static function getInstance()
	{
		if (self::$instance == null) {
			$config_filepath = realpath(dirname(__FILE__).'/../../'.self::Config_Filename);

			if (file_exists($config_filepath)) {
				self::$instance = new DUPX_ArchiveConfig();

				$file_contents = file_get_contents($config_filepath);
				$ac_data = json_decode($file_contents);

				foreach ($ac_data as $key => $value) {
					self::$instance->{$key} = $value;
				}

				if (isset($_GET['debug']) && ($_GET['debug'] == 1)) {
					self::$instance->debug_mode = true;
				}
                
 			} else {
				echo "$config_filepath doesn't exist<br/>";
			}
		}

		//Instance Updates:
		self::$instance->blogNameSafe	= preg_replace("/[^A-Za-z0-9?!]/", '', self::$instance->blogname);
		self::$instance->dbhost			= empty(self::$instance->dbhost)       ? 'localhost' : self::$instance->dbhost;
		self::$instance->cpnl_host		= empty(self::$instance->cpnl_host)    ? "https://{$GLOBALS['HOST_NAME']}:2083" : self::$instance->cpnl_host;
		self::$instance->cpnl_dbhost	= empty(self::$instance->cpnl_dbhost)  ? 'localhost' : self::$instance->cpnl_dbhost;
		self::$instance->cpnl_dbname	= strlen(self::$instance->cpnl_dbname) ? self::$instance->cpnl_dbname : '';

		return self::$instance;
	}


	/**
	 * Returns the license type this installer file is made of.
	 *
	 * @return obj	Returns an enum type of DUPX_LicenseType
	 */
	public function getLicenseType()
	{
		$license_type = DUPX_LicenseType::Personal;

		if ($this->license_limit < 0) {
			$license_type = DUPX_LicenseType::Unlicensed;
		} else if ($this->license_limit < 15) {
			$license_type = DUPX_LicenseType::Personal;
		} else if ($this->license_limit < 500) {
			$license_type = DUPX_LicenseType::Freelancer;
		} else if ($this->license_limit >= 500) {
			$license_type = DUPX_LicenseType::BusinessGold;
		}

		return $license_type;
	}

    public function isZipArchive()
    {
        //$extension = strtolower(pathinfo($this->package_name)['extension']);
		$extension = strtolower(pathinfo($this->package_name, PATHINFO_EXTENSION));
        
        return ($extension == 'zip');
    }
}