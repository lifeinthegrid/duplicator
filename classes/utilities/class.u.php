<?php
/**
 * Recursivly scans a directory and finds all sym-links and unreadable files
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2
 *
 * @package Duplicator
 * @subpackage classes/utilites
 * @copyright (c) 2017, Snapcreek LLC
 * @since 1.1.0
 *
 * @todo Refactor out IO methods into class.io.php file
 */

// Exit if accessed directly
if (!defined('DUPLICATOR_VERSION')) {
    exit;
}

class DUP_Util
{
    /**
     * Is PHP 5.2.9 or better running
     */
    public static $on_php_529_plus;

    /**
     * Is PHP 5.3 or better running
     */
    public static $on_php_53_plus;

    /**
     * Is PHP 5.4 or better running
     */
    public static $on_php_54_plus;

	/**
     * Is PHP 7 or better running
     */
    public static $PHP7_plus;

    /**
     *  Initialized on load (see end of file)
     */
    public static function init()
    {
        self::$on_php_529_plus = version_compare(PHP_VERSION, '5.2.9') >= 0;
        self::$on_php_53_plus  = version_compare(PHP_VERSION, '5.3.0') >= 0;
        self::$on_php_54_plus  = version_compare(PHP_VERSION, '5.4.0') >= 0;
		self::$PHP7_plus = version_compare(PHP_VERSION, '7.0.0', '>=');
    }
    
//    public static function objectCopy($source, $dest)
//    {
//        foreach(get_object_vars($source) as $key => $value) {
//            $dest->$key = $value;
//        }
//    }

    public static function objectCopy($srcObject, $destObject, $skipMemberArray = null)
    {
        foreach ($srcObject as $member_name => $member_value) {
            if (!is_object($member_value) && (($skipMemberArray == null) || !in_array($member_name, $skipMemberArray))) {
                // Skipping all object members
                $destObject->$member_name = $member_value;
            }
        }
    }

    public static function getWPCoreDirs()
    {
        $wp_core_dirs = array(get_home_path().'wp-admin',get_home_path().'wp-includes');

        //if wp_content is overrided
        $wp_path = get_home_path()."wp-content";
        if(get_home_path().'wp-content' != WP_CONTENT_DIR){
            $wp_path = WP_CONTENT_DIR;
        }
        $wp_path = str_replace("\\", "/", $wp_path);

        $wp_core_dirs[] = $wp_path;
        $wp_core_dirs[] = $wp_path.'/plugins';
        $wp_core_dirs[] = $wp_path.'/themes';


        return $wp_core_dirs;
    }
    /**
     * return absolute path for the files that are core directories
     * @return string array
     */
    public static function getWPCoreFiles()
    {
        $wp_cored_dirs = array(get_home_path().'wp-config.php');
        return $wp_cored_dirs;
    }

	/**
	 * Groups an array into arrays by a given key, or set of keys, shared between all array members.
	 *
	 * Based on {@author Jake Zatecky}'s {@link https://github.com/jakezatecky/array_group_by array_group_by()} function.
	 * This variant allows $key to be closures.
	 *
	 * @param array $array   The array to have grouping performed on.
	 * @param mixed $key,... The key to group or split by. Can be a _string_, an _integer_, a _float_, or a _callable_.
	 *                       - If the key is a callback, it must return a valid key from the array.
	 *                       - If the key is _NULL_, the iterated element is skipped.
	 *                       - string|int callback ( mixed $item )
	 *
	 * @return array|null Returns a multidimensional array or `null` if `$key` is invalid.
	 */
	public static function array_group_by(array $array, $key)
	{
		if (!is_string($key) && !is_int($key) && !is_float($key) && !is_callable($key) ) {
			trigger_error('array_group_by(): The key should be a string, an integer, or a callback', E_USER_ERROR);
			return null;
		}
		$func = (!is_string($key) && is_callable($key) ? $key : null);
		$_key = $key;
		// Load the new array, splitting by the target key
		$grouped = array();
		foreach ($array as $value) {
			$key = null;
			if (is_callable($func)) {
				$key = call_user_func($func, $value);
			} elseif (is_object($value) && isset($value->{$_key})) {
				$key = $value->{$_key};
			} elseif (isset($value[$_key])) {
				$key = $value[$_key];
			}
			if ($key === null) {
				continue;
			}
			$grouped[$key][] = $value;
		}
		// Recursively build a nested grouping if more parameters are supplied
		// Each grouped array value is grouped according to the next sequential key
		if (func_num_args() > 2) {
			$args = func_get_args();
			foreach ($grouped as $key => $value) {
				$params = array_merge(array( $value ), array_slice($args, 2, func_num_args()));
				$grouped[$key] = call_user_func_array('DUP_Util::array_group_by', $params);
			}
		}
		return $grouped;
	}

	/**
     * PHP_SAPI for fcgi requires a data flush of at least 256
     * bytes every 40 seconds or else it forces a script hault
     *
     * @return string A series of 256 space characters
     */
    public static function fcgiFlush()
    {
        echo(str_repeat(' ', 300));
        @flush();
		@ob_flush();
    }

    /**
     * Returns the wp-snapshot url
     *
     * @return string The full url of the duplicators snapshot storage directory
     */
    public static function snapshotURL()
    {
        return get_site_url(null, '', is_ssl() ? 'https' : 'http').'/'.DUPLICATOR_SSDIR_NAME.'/';
    }

    /**
     * Returns the last N lines of a file. Equivelent to tail command
     *
     * @param string $filepath The full path to the file to be tailed
     * @param int $lines The number of lines to return with each tail call
     *
     * @return string The last N parts of the file
     */
    public static function tailFile($filepath, $lines = 2)
    {

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
        $chunk  = '';

        // While we would like more
        while (ftell($f) > 0 && $lines >= 0) {
            // Figure out how far back we should jump
            $seek   = min(ftell($f), $buffer);
            // Do the jump (backwards, relative to where we are)
            fseek($f, -$seek, SEEK_CUR);
            // Read a chunk and prepend it to our output
            $output = ($chunk  = fread($f, $seek)).$output;
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
     * Runs the APC cache to pre-cache the php files
     *
     * @returns bool True if all files where cached
     */
    public static function runAPC()
    {
        if (function_exists('apc_compile_file')) {
            $file01 = @apc_compile_file(DUPLICATOR_PLUGIN_PATH."duplicator.php");
            return ($file01);
        } else {
            return false;
        }
    }

    /**
     * Display human readable byte sizes
     *
     * @param int $size    The size in bytes
     *
     * @return string The size of bytes readable such as 100KB, 20MB, 1GB etc.
     */
    public static function byteSize($size, $roundBy = 2)
    {
        try {
            $units = array('B', 'KB', 'MB', 'GB', 'TB');
            for ($i = 0; $size >= 1024 && $i < 4; $i++) {
                $size /= 1024;
            }
            return round($size, $roundBy).$units[$i];
        } catch (Exception $e) {
            return "n/a";
        }
    }

    /**
     * Makes path safe for any OS
     *      Paths should ALWAYS READ be "/"
     *          uni: /home/path/file.xt
     *          win:  D:/home/path/file.txt
     *
     * @param string $path		The path to make safe
     *
     * @return string A path with all slashes facing "/"
     */
    public static function safePath($path)
    {
        return str_replace("\\", "/", $path);
    }

    /**
     * Get current microtime as a float.  Method is used for simple profiling
     *
     * @see elapsedTime
     *
     * @return  string   A float in the form "msec sec", where sec is the number of seconds since the Unix epoch
     */
    public static function getMicrotime()
    {
        return microtime(true);
    }

    /**
     * Append the value to the string if it doesn't already exist
     *
     * @param string $string The string to append to
     * @param string $value The string to append to the $string
     *
     * @return string Returns the string with the $value appended once
     */
    public static function appendOnce($string, $value)
    {
        return $string.(substr($string, -1) == $value ? '' : $value);
    }

    /**
     * Return a string with the elapsed time
     *
     * @see getMicrotime()
     *
     * @param mixed number $end     The final time in the sequence to measure
     * @param mixed number $start   The start time in the sequence to measure
     *
     * @return  string   The time elapsed from $start to $end
     */
    public static function elapsedTime($end, $start)
    {
        return sprintf("%.2f sec.", abs($end - $start));
    }

    /**
     * List all of the files of a path
     *
     * @param string $path The full path to a system directory
     *
     * @return array of all files in that path
     * 
     * Notes:
     * 	- Avoid using glob() as GLOB_BRACE is not an option on some operating systems
     * 	- Pre PHP 5.3 DirectoryIterator will crash on unreadable files
	 *  - Scandir will not crash on unreadable items, but will not return results
     */
    public static function listFiles($path = '.')
    {
		try {
			$files = array();
			foreach (new DirectoryIterator($path) as $file) {
				$files[] = str_replace("\\", '/', $file->getPathname());
			}
			return $files;

		} catch (Exception $exc) {

			$result = array();
			$files = @scandir($path);
			if (is_array($files)) {
				foreach ($files as $file) {
					$result[] = str_replace("\\", '/', $path) . $file;
				}
			}
			return $result;
		}
    }

    /**
     * List all of the directories of a path
     *
     * @param string $path The full path to a system directory
     *
     * @return array of all dirs in the $path
     */
    public static function listDirs($path = '.')
    {
        $dirs = array();

        foreach (new DirectoryIterator($path) as $file) {
            if ($file->isDir() && !$file->isDot()) {
                $dirs[] = DUP_Util::safePath($file->getPathname());
            }
        }
        return $dirs;
    }

    /**
     * Does the directory have content
     *
     * @param string $path The full path to a system directory
     *
     * @return bool Returns true if directory is empty
     */
    public static function isDirectoryEmpty($path)
    {
        if (!is_readable($path)) return NULL;
        return (count(scandir($path)) == 2);
    }

    /**
     * Size of the directory recursively in bytes
     *
     * @param string $path The full path to a system directory
     *
     * @return int Returns the size of the directory in bytes
     *
     */
    public static function getDirectorySize($path)
    {
        if (!file_exists($path)) return 0;
        if (is_file($path)) return filesize($path);

        $size = 0;
        $list = glob($path."/*");
        if (!empty($list)) {
            foreach ($list as $file)
                $size += self::getDirectorySize($file);
        }
        return $size;
    }

    /**
     * Can shell_exec be called on this server
     *
     * @return bool Returns true if shell_exec can be called on server
     *
     */
    public static function hasShellExec()
    {
        $cmds = array('shell_exec', 'escapeshellarg', 'escapeshellcmd', 'extension_loaded');

        //Function disabled at server level
        if (array_intersect($cmds, array_map('trim', explode(',', @ini_get('disable_functions'))))) return false;

        //Suhosin: http://www.hardened-php.net/suhosin/
        //Will cause PHP to silently fail
        if (extension_loaded('suhosin')) {
            $suhosin_ini = @ini_get("suhosin.executor.func.blacklist");
            if (array_intersect($cmds, array_map('trim', explode(',', $suhosin_ini)))) return false;
        }

        // Can we issue a simple echo command?
        if (!@shell_exec('echo duplicator')) return false;

        return true;
    }

    /**
     * Is the server running Windows operating system
     *
     * @return bool Returns true if operating system is Windows
     *
     */
    public static function isWindows()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return true;
        }
        return false;
    }

		 /**
     * Wrap to prevent malware scanners from reporting false/positive
     * Switched from our old method to avoid WordFence reporting a false positive
     *
     * @param string $string The string to decrypt i.e. base64_decode
     *
     * @return string Returns the string base64 decoded
     */
    public static function installerUnscramble($string)
    {
        return base64_decode($string);
    }

	/**
     * Wrap to prevent malware scanners from reporting false/positive
     * Switched from our old method to avoid WordFence reporting a false positive
     *
     * @param string $string The string to decrypt i.e. base64_encode
     *
     * @return string Returns the string base64 encode
     */
    public static function installerScramble($string)
    {
        return base64_encode($string);
    }

    /**
     * Does the current user have the capability
     *
     * @return null Dies if user doesn't have the correct capability
     */
    public static function hasCapability($permission = 'read')
    {
        $capability = $permission;
        $capability = apply_filters('wpfront_user_role_editor_duplicator_translate_capability', $capability);

        if (!current_user_can($capability)) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'duplicator'));
            return;
        }
    }

    /**
     *  Gets the name of the owner of the current PHP script
     *
     * @return string The name of the owner of the current PHP script
     */
    public static function getCurrentUser()
    {
        $unreadable = 'Undetectable';
        if (function_exists('get_current_user') && is_callable('get_current_user')) {
            $user = get_current_user();
            return strlen($user) ? $user : $unreadable;
        }
        return $unreadable;
    }

    /**
     * Gets the owner of the PHP process
     *
     * @return string Gets the owner of the PHP process
     */
    public static function getProcessOwner()
    {
        $unreadable = 'Undetectable';
        $user       = '';
        try {
            if (function_exists('exec')) {
                $user = exec('whoami');
            }

            if (!strlen($user) && function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
                $user = posix_getpwuid(posix_geteuid());
                $user = $user['name'];
            }

            return strlen($user) ? $user : $unreadable;
        } catch (Exception $ex) {
            return $unreadable;
        }
    }

    /**
     * Creates the snapshot directory if it doesn't already exisit
     *
     * @return null
     */
    public static function initSnapshotDirectory()
    {
        $path_wproot = DUP_Util::safePath(DUPLICATOR_WPROOTPATH);
        $path_ssdir  = DUP_Util::safePath(DUPLICATOR_SSDIR_PATH);
        $path_plugin = DUP_Util::safePath(DUPLICATOR_PLUGIN_PATH);

        //--------------------------------
        //CHMOD DIRECTORY ACCESS
        //wordpress root directory
        @chmod($path_wproot, 0755);

        //snapshot directory
        @mkdir($path_ssdir, 0755);
        @chmod($path_ssdir, 0755);

        //snapshot tmp directory
        $path_ssdir_tmp = $path_ssdir.'/tmp';
        @mkdir($path_ssdir_tmp, 0755);
        @chmod($path_ssdir_tmp, 0755);

        //plugins dir/files
        @chmod($path_plugin.'files', 0755);

        //--------------------------------
        //FILE CREATION
        //SSDIR: Create Index File
        $ssfile = @fopen($path_ssdir.'/index.php', 'w');
        @fwrite($ssfile,
                '<?php error_reporting(0);  if (stristr(php_sapi_name(), "fcgi")) { $url  =  "http://" . $_SERVER["HTTP_HOST"]; header("Location: {$url}/404.html");} else { header("HTTP/1.1 404 Not Found", true, 404);} exit(); ?>');
        @fclose($ssfile);

        //SSDIR: Create token file in snapshot
        $tokenfile = @fopen($path_ssdir.'/dtoken.php', 'w');
        @fwrite($tokenfile,
                '<?php error_reporting(0);  if (stristr(php_sapi_name(), "fcgi")) { $url  =  "http://" . $_SERVER["HTTP_HOST"]; header("Location: {$url}/404.html");} else { header("HTTP/1.1 404 Not Found", true, 404);} exit(); ?>');
        @fclose($tokenfile);

        //SSDIR: Create .htaccess
        $storage_htaccess_off = DUP_Settings::Get('storage_htaccess_off');
        if ($storage_htaccess_off) {
            @unlink($path_ssdir.'/.htaccess');
        } else {
            $htfile   = @fopen($path_ssdir.'/.htaccess', 'w');
            $htoutput = "Options -Indexes";
            @fwrite($htfile, $htoutput);
            @fclose($htfile);
        }

        //SSDIR: Robots.txt file
        $robotfile = @fopen($path_ssdir.'/robots.txt', 'w');
        @fwrite($robotfile, "User-agent: * \nDisallow: /".DUPLICATOR_SSDIR_NAME.'/');
        @fclose($robotfile);

        //PLUG DIR: Create token file in plugin
        $tokenfile2 = @fopen($path_plugin.'installer/dtoken.php', 'w');
        @fwrite($tokenfile2,
                '<?php @error_reporting(0); @require_once("../../../../wp-admin/admin.php"); global $wp_query; $wp_query->set_404(); header("HTTP/1.1 404 Not Found", true, 404); header("Status: 404 Not Found"); @include(get_template_directory () . "/404.php"); ?>');
        @fclose($tokenfile2);
    }

    /**
     * Attempts to get the file zip path on a users system
     *
     * @return null
     */
    public static function getZipPath()
    {
        $filepath = null;

        if (self::hasShellExec()) {
            if (shell_exec('hash zip 2>&1') == NULL) {
                $filepath = 'zip';
            } else {
                $possible_paths = array(
                    '/usr/bin/zip',
                    '/opt/local/bin/zip'
                    //'C:/Program\ Files\ (x86)/GnuWin32/bin/zip.exe');
                );

                foreach ($possible_paths as $path) {
                    if (@file_exists($path)) {
                        $filepath = $path;
                        break;
                    }
                }
            }
        }

        return $filepath;
    }
    
    /**
     * Is the server PHP 5.3 or better
     *
     * @return  bool    Returns true if the server PHP 5.3 or better
     */
    public static function PHP53()
    {
        return version_compare(PHP_VERSION, '5.3.2', '>=');
    }


	/**
     * Returns an array of the WordPress core tables.
     *
     * @return array  Returns all WP core tables
     */
    public static function getWPCoreTables()
    {
		global $wpdb;
		return array(
			"{$wpdb->prefix}commentmeta",
			"{$wpdb->prefix}comments",
			"{$wpdb->prefix}links",
			"{$wpdb->prefix}options",
			"{$wpdb->prefix}postmeta",
			"{$wpdb->prefix}posts",
			"{$wpdb->prefix}term_relationships",
			"{$wpdb->prefix}term_taxonomy",
			"{$wpdb->prefix}termmeta",
			"{$wpdb->prefix}terms",
			"{$wpdb->prefix}usermeta",
			"{$wpdb->prefix}users");
    }
	
	/**
     * Runs esc_html and sanitize_textarea_field on a string
	 *
	 * @param string   The string to process
     *
     * @return string  Returns and escaped and sanitized string
     */
    public static function escSanitizeTextAreaField($string)
    {
		if (!function_exists('sanitize_textarea_field')) {
			return esc_html(sanitize_text_field($string));
		} else {
			return esc_html(sanitize_textarea_field($string));
		}	
    }

	/**
     * Runs esc_html and sanitize_text_field on a string
	 *
	 * @param string   The string to process
     *
     * @return string  Returns and escaped and sanitized string
     */
    public static function escSanitizeTextField($string)
    {
		return esc_html(sanitize_text_field($string));
    }

	  /**
    * Finds if its a valid executable or not
    * @param type $exe A non zero length executable path to find if that is executable or not.
    * @param type $expectedValue expected value for the result
    * @return boolean
    */
    public static function isExecutable($cmd)
    {
        if (strlen($cmd) < 1) return false;

        if (@is_executable($cmd)){
            return true;
        }

        $output = shell_exec($cmd);
        if (!is_null($output)) {
            return true;
        }

        $output = shell_exec($cmd . ' -?');
        if (!is_null($output)) {
            return true;
        }

        return false;
    }

	/**
	 * Display human readable byte sizes
	 *
	 * @param string $size	The size in bytes
	 *
	 * @return string Human readable bytes such as 50MB, 1GB
	 */
	public static function readableByteSize($size)
	{
		try {
			$units = array('B', 'KB', 'MB', 'GB', 'TB');
			for ($i = 0; $size >= 1024 && $i < 4; $i++)
				$size /= 1024;
			return round($size, 2).$units[$i];
		} catch (Exception $e) {
			return "n/a";
		}
	}
}
DUP_Util::init();
