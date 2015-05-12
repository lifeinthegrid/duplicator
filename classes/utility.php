<?php
if ( ! defined( 'DUPLICATOR_VERSION' ) ) exit; // Exit if accessed directly

class DUP_Util {
	
	/**
	*  PHP_SAPI for fcgi requires a data flush of at least 256
	*  bytes every 40 seconds or else it forces a script hault
	*/
	static public function FcgiFlush() {
		echo(str_repeat(' ', 300));
		@flush();
	}

	/**
	*  returns the snapshot url
	*/
	static public function SSDirURL() {
		 return get_site_url(null, '', is_ssl() ? 'https' : 'http') . '/' . DUPLICATOR_SSDIR_NAME . '/';
	}

	/**
	*  Returns the last N lines of a file
	*  Equivelent to tail command
	*/
	static public function TailFile($filepath, $lines = 2) {

		// Open file
		$f = @fopen($filepath, "rb");
		if ($f === false) return false;

		// Sets buffer size
		$buffer = 256;

		// Jump to last character
		fseek($f, -1, SEEK_END);

		// Read it and adjust line number if necessary
		// (Otherwise the result would be wrong if file doesn't end with a blank line)
		if (fread($f, 1) != "\n") $lines -= 1;
		
		// Start reading
		$output = '';
		$chunk = '';

		// While we would like more
		while (ftell($f) > 0 && $lines >= 0) {
			// Figure out how far back we should jump
			$seek = min(ftell($f), $buffer);
			// Do the jump (backwards, relative to where we are)
			fseek($f, -$seek, SEEK_CUR);
			// Read a chunk and prepend it to our output
			$output = ($chunk = fread($f, $seek)) . $output;
			// Jump back to where we started reading
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
			// Decrease our line counter
			$lines -= substr_count($chunk, "\n");
		}

		// While we have too many lines
		// (Because of buffer size we might have read too many)
		while ($lines++ < 0) {
			// Find first newline and remove all text before that
			$output = substr($output, strpos($output, "\n") + 1);
		}
		fclose($f);
		return trim($output);
	}

	

	/**
	*  Runs the APC cache to pre-cache the php files
	*  returns true if all files where cached
	*/
	static public function RunAPC() {
	   if(function_exists('apc_compile_file')){
		   $file01 = @apc_compile_file(DUPLICATOR_PLUGIN_PATH . "duplicator.php");
		   return ($file01);
	   } else {
		   return false;
	   }
	}

	/**
	*  Display human readable byte sizes
	*  @param string $size		The size in bytes
	*/
	static public function ByteSize($size) {
		try {
			$units = array('B', 'KB', 'MB', 'GB', 'TB');
			for ($i = 0; $size >= 1024 && $i < 4; $i++)
				$size /= 1024;
			return round($size, 2) . $units[$i];
		} catch (Exception $e) {
			return "n/a";
		}
	}

	/**
	*  Makes path safe for any OS
	*  Paths should ALWAYS READ be "/"
	* 		uni: /home/path/file.xt
	* 		win:  D:/home/path/file.txt 
	*  @param string $path		The path to make safe
	*/
	static public function SafePath($path) {
		return str_replace("\\", "/", $path);
	}

	/** 
	 * Get current microtime as a float. Can be used for simple profiling.
	 */
	static public function GetMicrotime() {
		return microtime(true);
	}

	/** 
	 * Append the value to the string if it doesn't already exist
	 */
	static public function StringAppend($string, $value ) {
	   return $string . (substr($string, -1) == $value ? '' : $value);
	}

	/** 
	 * Return a string with the elapsed time.
	 * Order of $end and $start can be switched. 
	 */
	static public function ElapsedTime($end, $start) {
		return sprintf("%.2f sec.", abs($end - $start));
	}

	/**
	 * Get the MySQL system variables
	 * @param conn $dbh Database connection handle
	 * @return string the server variable to query for
	 */
	static public function MysqlVariableValue($variable) {
		global $wpdb;
		$row = $wpdb->get_row("SHOW VARIABLES LIKE '{$variable}'", ARRAY_N);
		return isset($row[1]) ? $row[1] : null;
	}

	/**
	 * List all of the files of a path
	 * @path path to a system directory
	 * @return array of all files in that path
	 */
	static public function ListFiles($path = '.') {
		$files = array();
		
		//GLOB_BRACE is not an option on some systems
		//{,.}*  allows for hidden files to be shown
		if (defined("GLOB_BRACE")) {
			$files	= glob("{$path}/{,.}*", GLOB_NOSORT | GLOB_BRACE);
		} else {
			foreach (new DirectoryIterator($path) as $file) {
				$files[] = DUP_Util::SafePath($file->getPathname());
			}
		}
		return $files;
	}
	
	/**
	 * List all of the directories of a path
	 * @path path to a system directory
	 * @return array of all directories in that path
	 */
	static public function ListDirs($path = '.') {
		$dirs = array();

		foreach (new DirectoryIterator($path) as $file) {
			if ($file->isDir() && !$file->isDot()) {
				$dirs[] = DUP_Util::SafePath($file->getPathname());
			}
		}
		return $dirs;
	}

	/** 
	 * Does the directory have content
	 */
	static public function IsDirectoryEmpty($dir) {
		if (!is_readable($dir)) return NULL; 
		return (count(scandir($dir)) == 2);
	}
	
	/** 
	 * Size of the directory recuresivly in bytes
	 */
	static public function GetDirectorySize($dir) {
		if(!file_exists($dir)) 
			return 0;
		if(is_file($dir)) 
			return filesize($dir);
		
		$size = 0;
		$list = glob($dir."/*");
		if (! empty($list)) {
			foreach($list as $file)
				$size += self::GetDirectorySize($file);
		}
		return $size;
	}
	
	
	public static function IsShellExecAvailable() {
		
		if (array_intersect(array('shell_exec', 'escapeshellarg', 'escapeshellcmd', 'extension_loaded'), array_map('trim', explode(',', @ini_get('disable_functions')))))
			return false;
		
		//Suhosin: http://www.hardened-php.net/suhosin/
		//Will cause PHP to silently fail.
		if (extension_loaded('suhosin'))
		  return false;

		// Can we issue a simple echo command?
		if (!@shell_exec('echo duplicator'))
			return false;

		return true;
	}
	
	public static function IsOSWindows() {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			return true;
		}
		return false;
	}
	
	public static function CheckPermissions($permission = 'read') {
		$capability = $permission;
		$capability = apply_filters('wpfront_user_role_editor_duplicator_translate_capability', $capability);

		if(!current_user_can($capability)) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'wpduplicator'));
			return;
		}
	}

	/**
	*  Creates the snapshot directory if it doesn't already exisit
	*/
	public static function GetCurrentUser() {
		$unreadable =  'Undetectable';
		if (function_exists('get_current_user') && is_callable('get_current_user')) {
			$user = get_current_user(); 
			return strlen($user) ? $user : $unreadable;
		}
		return $unreadable;
	}
	
	/**
	*  Gets the owner of the PHP process
	*/
	public static function GetProcessOwner() {
		$unreadable = 'Undetectable';
		$user = '';
		try {
			if (function_exists('exec')) {
				$user = exec('whoami');
			} 
			
			if (! strlen($user) && function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
				$user = posix_getpwuid(posix_geteuid());
				$user = $user['name'];  
			}
			
			return strlen($user) ? $user : $unreadable;

		} catch (Exception $ex) {
			return $unreadable;
		}
	}
	
	/**
	*  Creates the snapshot directory if it doesn't already exisit
	*/
	static public function InitSnapshotDirectory() {
		$path_wproot	= DUP_Util::SafePath(DUPLICATOR_WPROOTPATH);
		$path_ssdir		= DUP_Util::SafePath(DUPLICATOR_SSDIR_PATH);
		$path_plugin	= DUP_Util::SafePath(DUPLICATOR_PLUGIN_PATH);

		//--------------------------------
		//CHMOD DIRECTORY ACCESS
		//wordpress root directory
		@chmod($path_wproot, 0755);

		//snapshot directory
		@mkdir($path_ssdir, 0755);
		@chmod($path_ssdir, 0755);
		
		//snapshot tmp directory
		$path_ssdir_tmp = $path_ssdir . '/tmp';
		@mkdir($path_ssdir_tmp, 0755);
		@chmod($path_ssdir_tmp, 0755);

		//plugins dir/files
		@chmod($path_plugin . 'files', 0755);

		//--------------------------------
		//FILE CREATION	
		//SSDIR: Create Index File
		$ssfile = @fopen($path_ssdir . '/index.php', 'w');
		@fwrite($ssfile, '<?php error_reporting(0);  if (stristr(php_sapi_name(), "fcgi")) { $url  =  "http://" . $_SERVER["HTTP_HOST"]; header("Location: {$url}/404.html");} else { header("HTML/1.1 404 Not Found", true, 404);} exit(); ?>');
		@fclose($ssfile);

		//SSDIR: Create token file in snapshot
		$tokenfile = @fopen($path_ssdir . '/dtoken.php', 'w');
		@fwrite($tokenfile, '<?php error_reporting(0);  if (stristr(php_sapi_name(), "fcgi")) { $url  =  "http://" . $_SERVER["HTTP_HOST"]; header("Location: {$url}/404.html");} else { header("HTML/1.1 404 Not Found", true, 404);} exit(); ?>');
		@fclose($tokenfile);

		//SSDIR: Create .htaccess
		$storage_htaccess_off = DUP_Settings::Get('storage_htaccess_off');
		if ($storage_htaccess_off) {
			@unlink($path_ssdir . '/.htaccess');
		} else {
			$htfile = @fopen($path_ssdir . '/.htaccess', 'w');
			$htoutput = "Options -Indexes" ;
			@fwrite($htfile, $htoutput);
			@fclose($htfile);
		}

		//SSDIR: Robots.txt file
		$robotfile = @fopen($path_ssdir . '/robots.txt', 'w');
		@fwrite($robotfile, "User-agent: * \nDisallow: /" . DUPLICATOR_SSDIR_NAME . '/');
		@fclose($robotfile);

		//PLUG DIR: Create token file in plugin
		$tokenfile2 = @fopen($path_plugin . 'installer/dtoken.php', 'w');
		@fwrite($tokenfile2, '<?php @error_reporting(0); @require_once("../../../../wp-admin/admin.php"); global $wp_query; $wp_query->set_404(); header("HTML/1.1 404 Not Found", true, 404); header("Status: 404 Not Found"); @include(get_template_directory () . "/404.php"); ?>');
		@fclose($tokenfile2);
	}

}
?>