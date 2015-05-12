<?php
if ( ! defined( 'DUPLICATOR_VERSION' ) ) exit; // Exit if accessed directly

require_once (DUPLICATOR_PLUGIN_PATH . 'classes/package.archive.php');
require_once (DUPLICATOR_PLUGIN_PATH . 'classes/package.installer.php');
require_once (DUPLICATOR_PLUGIN_PATH . 'classes/package.database.php');
require_once (DUPLICATOR_PLUGIN_PATH . 'classes/utility.php');

final class DUP_PackageStatus {
   private function __construct() {}
   const START		= 10;
   const DBSTART	= 20;
   const DBDONE		= 30;
   const ARCSTART	= 40;
   const ARCDONE	= 50;
   const COMPLETE	= 100;
}

final class DUP_PackageType {
   private function __construct() {}
   const MANUAL		= 0;
   const SCHEDULED	= 1;
}

/**
 * Class used to store and process all Package logic
 * @package Dupicator\classes
 */
class DUP_Package {
	
	const OPT_ACTIVE   = 'duplicator_package_active';
	
	//Properties
	public $ID;
	public $Name;
	public $Hash;
	public $NameHash;
	public $Version;
	public $Type;
	public $Notes;
	public $StorePath;
	public $StoreURL;
	public $ScanFile;
	public $Runtime;
	public $ExeSize;
	public $ZipSize;
	//Objects
	public $Archive;
	public $Installer;
	public $Database;

	 /**
     *  Manages the Package Process
     */
    function __construct() {
		
		$this->ID			= null;
		$this->Version		= DUPLICATOR_VERSION;
		$this->Type			= DUP_PackageType::MANUAL;
		$this->Name			= self::GetDefaultName();
		$this->Notes		= null;
		$this->StoreURL     = DUP_Util::SSDirURL();
		$this->StorePath    = DUPLICATOR_SSDIR_PATH_TMP;
		$this->Database		= new DUP_Database($this);
		$this->Archive		= new DUP_Archive($this);
		$this->Installer	= new DUP_Installer($this);
		
	}
	
	/**
	 * Generates a scan report
	 * @return array of scan results
	 */
	public function Scan() {
		
		$timerStart = DUP_Util::GetMicrotime();
		$report = array();
		$this->ScanFile  = "{$this->NameHash}_scan.json";
		
		$report['RPT']['ScanTime'] = "0";
		$report['RPT']['ScanFile'] = $this->ScanFile;

		//SERVER
		$srv = DUP_Server::GetChecks();
		$report['SRV']['WEB']['ALL']	  = $srv['SRV']['WEB']['ALL'];
		$report['SRV']['WEB']['model']	  = $srv['SRV']['WEB']['model'];

		$report['SRV']['PHP']['ALL']	  = $srv['SRV']['PHP']['ALL'];
		$report['SRV']['PHP']['openbase'] = $srv['SRV']['PHP']['openbase'];
		$report['SRV']['PHP']['maxtime']  = $srv['SRV']['PHP']['maxtime'];
		$report['SRV']['PHP']['mysqli']   = $srv['SRV']['PHP']['mysqli'];

		$report['SRV']['WP']['ALL']		  = $srv['SRV']['WP']['ALL'];
		$report['SRV']['WP']['version']	  = $srv['SRV']['WP']['version'];
		$report['SRV']['WP']['core']	  = $srv['SRV']['WP']['core'];
		$report['SRV']['WP']['cache']	  = $srv['SRV']['WP']['cache'];
		
		//FILES
		$this->Archive->Stats();
		$report['ARC']['Size']				= DUP_Util::ByteSize($this->Archive->Size)  or "unknown";
		$report['ARC']['DirCount']			= number_format(count($this->Archive->Dirs));
		$report['ARC']['FileCount']			= number_format(count($this->Archive->Files));
		$report['ARC']['LinkCount']			= number_format(count($this->Archive->Links));
		$report['ARC']['WarnFileName']		= is_array($this->Archive->WarnFileName) ? $this->Archive->WarnFileName : "unknown";
		$report['ARC']['WarnFileSize']		= is_array($this->Archive->WarnFileSize)  ? $this->Archive->WarnFileSize  : "unknown";
		$report['ARC']['Status']['Size']	= ($this->Archive->Size > DUPLICATOR_SCAN_SITE) ? 'Warn' : 'Good';
		$report['ARC']['Status']['Names']	= count($this->Archive->WarnFileName) ? 'Warn' : 'Good';
		$report['ARC']['Status']['Big']		= count($this->Archive->WarnFileSize)  ? 'Warn' : 'Good';
		$report['ARC']['Dirs']				= $this->Archive->Dirs;
		$report['ARC']['Files']				= $this->Archive->Files;
		$report['ARC']['OmitFiles']			= $this->Archive->OmitFiles;
		$report['ARC']['OmitDirs']			= $this->Archive->OmitDirs;
		
		//DATABASE
		$db = $this->Database->Stats();
		$report['DB']['Status']		= $db['Status'];
		$report['DB']['Size']		= DUP_Util::ByteSize($db['Size'])	or "unknown";
		$report['DB']['Rows']		= number_format($db['Rows'])		or "unknown";
		$report['DB']['TableCount']	= $db['TableCount']					or "unknown";
		$report['DB']['TableList']	= $db['TableList']					or "unknown";
		
		$report['RPT']['ScanTime'] = DUP_Util::ElapsedTime(DUP_Util::GetMicrotime(), $timerStart);
		$fp = fopen(DUPLICATOR_SSDIR_PATH_TMP . "/{$this->ScanFile}", 'w');
		fwrite($fp, json_encode($report));
		fclose($fp);

		return $report;
	}
	
	/**
	 * Starts the package build process
	 * @return DUP_Package
	 */
	public function Build() {
		
		global $wp_version;
		global $wpdb;
		global $current_user;

		$timerStart = DUP_Util::GetMicrotime();
		
		$this->Archive->File	  = "{$this->NameHash}_archive.zip";
		$this->Installer->File    = "{$this->NameHash}_installer.php";
		$this->Database->File     = "{$this->NameHash}_database.sql";
		
		//START LOGGING
		DUP_Log::Open($this->NameHash);
		$php_max_time	= @ini_get("max_execution_time");
		$php_max_memory = @ini_set('memory_limit', DUPLICATOR_PHP_MAX_MEMORY);
		$php_max_time	= ($php_max_time == 0)        ? "(0) no time limit imposed" : "[{$php_max_time}] not allowed";
		$php_max_memory = ($php_max_memory === false) ? "Unabled to set php memory_limit" :  DUPLICATOR_PHP_MAX_MEMORY . " ({$php_max_memory} default)";
		
		$info  = "********************************************************************************\n";
		$info .= "PACKAGE-LOG: " . @date("Y-m-d H:i:s") . "\n";
		$info .= "NOTICE: Do NOT post to public sites or forums \n";
		$info .= "********************************************************************************\n";
		$info .= "VERSION:\t" . DUPLICATOR_VERSION . "\n";
		$info .= "WORDPRESS:\t{$wp_version}\n";
		$info .= "PHP INFO:\t" . phpversion() . ' | ' . 'SAPI: ' . php_sapi_name() . "\n";
		$info .= "SERVER:\t\t{$_SERVER['SERVER_SOFTWARE']} \n";
		$info .= "PHP TIME LIMIT: {$php_max_time} \n";
		$info .= "PHP MAX MEMORY: {$php_max_memory} \n";
		$info .= "MEMORY STACK: " . DUP_Server::GetPHPMemory();
		DUP_Log::Info($info);
		$info = null;
		
		//CREATE DB RECORD
		$packageObj = serialize($this);
		if (! $packageObj) {
			DUP_Log::Error("Unable to serialize pacakge object while building record.");
		}
		
		$this->ID = $this->FindHashKey($this->Hash);
		
		if ($this->ID != 0){
			 $this->SetStatus(DUP_PackageStatus::START);
		} else {
			$results = $wpdb->insert($wpdb->prefix . "duplicator_packages", array(
				'name'    => $this->Name,
				'hash'	  => $this->Hash,
				'status'  => DUP_PackageStatus::START,
				'created' => current_time('mysql', get_option('gmt_offset', 1)),
				'owner'	  => isset($current_user->user_login) ? $current_user->user_login : 'unknown',
				'package' => $packageObj)
			);
			if ($results == false) {
				$error_result = $wpdb->print_error();
				DUP_Log::Error("Duplicator is unable to insert a package record into the database table.", "'{$error_result}'");
			}
			$this->ID = $wpdb->insert_id;
		}

		//START BUILD
		//PHPs serialze method will return the object, but the ID above is not passed
		//for one reason or another so passing the object back in seems to do the trick
		$this->Database->Build($this);
		$this->Archive->Build($this);
		$this->Installer->Build($this);

		
		//INTEGRITY CHECKS
		DUP_Log::Info("\n********************************************************************************");
		DUP_Log::Info("INTEGRITY CHECKS:");
		DUP_Log::Info("********************************************************************************");
		$dbSizeRead	 = DUP_Util::ByteSize($this->Database->Size);
		$zipSizeRead = DUP_Util::ByteSize($this->Archive->Size);
		$exeSizeRead = DUP_Util::ByteSize($this->Installer->Size);

		DUP_Log::Info("SQL File: {$dbSizeRead}");
		DUP_Log::Info("Installer File: {$exeSizeRead}");
		DUP_Log::Info("Archive File: {$zipSizeRead} ");
		
		if ( !($this->Archive->Size && $this->Database->Size && $this->Installer->Size)) {
			DUP_Log::Error("A required file contains zero bytes.", "Archive Size: {$zipSizeRead} | SQL Size: {$dbSizeRead} | Installer Size: {$exeSizeRead}");
		}
		
		//Validate SQL files completed
		$sql_tmp_path = DUP_UTIL::SafePath(DUPLICATOR_SSDIR_PATH_TMP . '/'. $this->Database->File);
		$sql_complete_txt = DUP_Util::TailFile($sql_tmp_path, 3);
		if (! strstr($sql_complete_txt, 'DUPLICATOR_MYSQLDUMP_EOF')) {
			DUP_Log::Error("ERROR: SQL file not complete.  The end of file marker was not found.  Please try to re-create the package.");
		}

		$timerEnd = DUP_Util::GetMicrotime();
		$timerSum = DUP_Util::ElapsedTime($timerEnd, $timerStart);
		
		$this->Runtime  = $timerSum;
		$this->ExeSize  = $exeSizeRead;
		$this->ZipSize  = $zipSizeRead;
		
		$this->buildCleanup();
		
		//FINAL REPORT
		$info = "\n********************************************************************************\n";
		$info .= "RECORD ID:[{$this->ID}]\n";
		$info .= "TOTAL PROCESS RUNTIME: {$timerSum}\n";
		$info .= "PEAK PHP MEMORY USED: " . DUP_Server::GetPHPMemory(true) . "\n";
		$info .= "DONE PROCESSING => {$this->Name} " . @date("Y-m-d H:i:s") . "\n";
	
		DUP_Log::Info($info);
		DUP_Log::Close();
		
		$this->SetStatus(DUP_PackageStatus::COMPLETE);
		return $this;
	}
	
	/**
	 *  Saves the active options associted with the active(latest) package.
	 *  @param $_POST $post The Post server object
	 *  @see DUP_Package::GetActive
	 *  @return void */
	public function SaveActive($post = null) {

		if (isset($post)) {
			$post = stripslashes_deep($post);
			
			$name_chars = array(".", "-");
			$name = ( isset($post['package-name']) && ! empty($post['package-name'])) ? $post['package-name'] : self::GetDefaultName();
			$name = substr(sanitize_file_name($name), 0 , 40);
			$name = str_replace($name_chars, '', $name);

			$filter_dirs   = isset($post['filter-dirs']) ? $this->parseDirectoryFilter($post['filter-dirs']) : '';
			$filter_exts   = isset($post['filter-exts']) ? $this->parseExtensionFilter($post['filter-exts']) : '';
			$tablelist     = isset($post['dbtables'])    ? implode(',', $post['dbtables']) : '';

			//PACKAGE
			$this->Version		= DUPLICATOR_VERSION;
			$this->Name			= $name;
			$this->Hash			= $post['package-hash'];
			$this->NameHash		= "{$this->Name}_{$this->Hash}";;
			$this->Notes		= esc_html($post['package-notes']);
			//ARCHIVE
			$this->Archive->PackDir			= rtrim(DUPLICATOR_WPROOTPATH, '/');
			$this->Archive->Format			= 'ZIP';
			$this->Archive->FilterOn		= isset($post['filter-on'])   ? 1 : 0;
			$this->Archive->FilterDirs		= esc_html($filter_dirs);
			$this->Archive->FilterExts		= str_replace(array('.' ,' '), "", esc_html($filter_exts));
			//INSTALLER
			$this->Installer->OptsDBHost		= esc_html($post['dbhost']);
			$this->Installer->OptsDBName		= esc_html($post['dbname']);
			$this->Installer->OptsDBUser		= esc_html($post['dbuser']);
			$this->Installer->OptsSSLAdmin		= isset($post['ssl-admin'])		? 1 : 0;
			$this->Installer->OptsSSLLogin		= isset($post['ssl-login'])		? 1 : 0;
			$this->Installer->OptsCacheWP		= isset($post['cache-wp'])		? 1 : 0;
			$this->Installer->OptsCachePath		= isset($post['cache-path'])	? 1 : 0;
			$this->Installer->OptsURLNew		= esc_html($post['url-new']);
			//DATABASE
			$this->Database->FilterOn		= isset($post['dbfilter-on'])   ? 1 : 0;
			$this->Database->FilterTables	= esc_html($tablelist);

			update_option(self::OPT_ACTIVE, $this);
		}
	}
	
	/**
	 *  Save any property of this class through reflection
	 *  @param $property A valid public property in this class
	 *  @param $value	 The value for the new dynamic property
	 *  @return void */
	public function SaveActiveItem($property, $value) {
		$package = self::GetActive();
	
		$reflectionClass = new ReflectionClass($package);
		$reflectionClass->getProperty($property)->setValue($package, $value);
		update_option(self::OPT_ACTIVE, $package);
	}
	
	/**
	 *  Sets the status to log the state of the build
	 *  @param $status The status level for where the package is
	 *  @return void */
	public function SetStatus($status) {
		global $wpdb;
		
		$packageObj = serialize($this);
		
		if (! isset($status)) {
			DUP_Log::Error("Package SetStatus did not receive a proper code.");
		}

		if (! $packageObj) {
			DUP_Log::Error("Package SetStatus was unable to serialize package object while updating record.");
		}
		
		$wpdb->flush();
		$table = $wpdb->prefix . "duplicator_packages";
		$sql   = "UPDATE `{$table}` SET  status = {$status}, package = '{$packageObj}'	WHERE ID = {$this->ID}";
		$wpdb->query($sql);
	}
	
	/**
	 * Does a hash already exisit
	 * @return int Returns 0 if no has is found, if found returns the table ID
	 */
	public function FindHashKey($hash) {
		
		global $wpdb;
		
		$table = $wpdb->prefix . "duplicator_packages";
		$qry   = $wpdb->get_row("SELECT ID, hash FROM `{$table}` WHERE hash = '{$hash}'" );
		if ( strlen($qry->hash) == 0)  {
			return 0;
		} else {
			return $qry->ID;
		}
		
	}
	
	/**
	 *  Makes the hashkey for the package files
	 *  @return string A unique hashkey */
	public function MakeHash() {
		return uniqid() . mt_rand(1000, 9999) . date("ymdHis");
	}

	/**
	 * Gets the active package.  The active package is defined as the package that was lasted saved.
	 * Do to cache issues with the built in WP function get_option moved call to a direct DB call.
	 * @see DUP_Package::SaveActive
	 * @return DUP_Package
	 */
	public static function GetActive() {
		
		global $wpdb;
		$obj = new DUP_Package();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM `{$wpdb->options}` WHERE option_name = %s LIMIT 1", self::OPT_ACTIVE ) );
		if (is_object($row)) {
			$obj =  @unserialize($row->option_value);
		}
		//Incase unserilaize fails
		$obj = (is_object($obj)) ? $obj : new DUP_Package();
		return $obj;
	}
	
	/** 
	*  Creates a default name
	*  @return string   A default packagename
	*/
	public static function GetDefaultName() {
		//Remove specail_chars from final result
		$special_chars = array(".", "-");
		$name = date('Ymd') . '_' . sanitize_title(get_bloginfo( 'name', 'display' ));
		$name = substr(sanitize_file_name($name), 0 , 40);
		$name = str_replace($special_chars, '', $name);
		return $name;
		
	}
	
	/** 
	*  Cleanup all tmp files
	*  @param all empty all contents
	*  @return void
	*/
	public static function TmpCleanup($all = false) {
		
		//Delete all files now
		if ($all){
			$dir = DUPLICATOR_SSDIR_PATH_TMP . "/*";
			foreach (glob($dir) as $file) {
				unlink($file);
			}
		} 
		//Remove scan files that are 24 hours old
		else {
			$dir = DUPLICATOR_SSDIR_PATH_TMP . "/*_scan.json";
			foreach (glob($dir) as $file) {
				if (filemtime($file) <= time() - 86400) {
					unlink($file);
				}
			}
		}
	}
	
	private function buildCleanup() {
		
		$files = DUP_Util::ListFiles(DUPLICATOR_SSDIR_PATH_TMP);
		$newPath = DUPLICATOR_SSDIR_PATH;
		
		if (function_exists('rename')) {
			foreach($files as $file){
				$name = basename($file);
				if (strstr($name, $this->NameHash)) {
					rename($file,"{$newPath}/{$name}");
				}
			}
		} else {
			foreach($files as $file){
				$name = basename($file);
				if (strstr($name, $this->NameHash)) {	
					copy($file,"{$newPath}/{$name}");
					unlink($file);
				}
			}
		}
	}
	
	private function parseDirectoryFilter($dirs = "") {
		$dirs = str_replace(array("\n", "\t", "\r"), '', $dirs);
		$filter_dirs = "";
		$dir_array = array_unique(explode(";", $dirs));
		foreach ($dir_array as $val) {
			if (strlen($val) >= 2) {
				$filter_dirs .= DUP_Util::SafePath(trim(rtrim($val, "/\\"))) . ";";
			}
		}
		return $filter_dirs;
	}
	
	private function parseExtensionFilter($extensions = "") {
		$filter_exts = "";
		if (strlen($extensions) >= 1 && $extensions != ";") {
			$filter_exts   = str_replace(array(' ', '.'), '', $extensions);
			$filter_exts   = str_replace(",", ";", $filter_exts);
			$filter_exts   = DUP_Util::StringAppend($extensions, ";");
		}
		return $filter_exts;
	}
	
}
?>