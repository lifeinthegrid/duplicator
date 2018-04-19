<?php
if ( ! defined('DUPLICATOR_VERSION') ) exit; // Exit if accessed directly

/**
 * Helper Class for logging
 * @package Dupicator\classes
 */
class DUP_Log {

	static $debugging = true;

    private static $traceEnabled;

	/**
	 * The file handle used to write to the log file
	 * @var file resource 
	 */
	public static $logFileHandle = null;

    /**
     * Init this static object
     */
    public static function Init()
    {
        self::$traceEnabled = DUP_Settings::Get('trace_log_enabled') == 1;
    }

	/**
	 *  Open a log file connection for writing
	 *  @param string $name Name of the log file to create
	 */
	static public function Open($name) {
		if (! isset($name)) throw new Exception("A name value is required to open a file log.");
		//self::$logFileHandle = @fopen(DUPLICATOR_SSDIR_PATH . "/{$name}.log", "c+");
        self::$logFileHandle = @fopen(DUPLICATOR_SSDIR_PATH . "/{$name}.log", "a+");
	}
	
	/**
	 *  Close the log file connection
	 */
	static public function Close() {
		 @fclose(self::$logFileHandle);
	}
	
	/**
	 *  General information logging
	 *  @param string $msg	The message to log
	 * 
	 *  REPLACE TO DEBUG: Memory consuption as script runs	
	 *	$results = DUP_Util::byteSize(memory_get_peak_usage(true)) . "\t" . $msg;
	 *	@fwrite(self::$logFileHandle, "{$results} \n"); 
	 */
	static public function Info($msg) {
        self::Trace($msg);
        
		@fwrite(self::$logFileHandle, "{$msg} \n"); 
	}

    /**
     * Does the trace file exists
     *
     * @return bool Returns true if an active trace file exists
     */
    public static function TraceFileExists()
    {
        $file_path = self::getTraceFilepath();

        return file_exists($file_path);
    }

     /**
     * Gets the current file size of the active trace file
     *
     * @return string   Returns a human readable file size of the active trace file
     */
    public static function getTraceStatus()
    {
        $file_path   = DUP_Log::getTraceFilepath();
        $backup_path = DUP_Log::getBackupTraceFilepath();

        if (file_exists($file_path)) {
            $filesize = filesize($file_path);

            if (file_exists($backup_path)) {
                $filesize += filesize($backup_path);
            }

            $message = sprintf('%1$s', DUP_Util::byteSize($filesize));
        } else {
            $message = __('No Log', 'duplicator');
        }

        return $message;
    }
    
    // RSR TODO: Swap trace logic out for real trace later
    static public function Trace($message, $calling_function_override = null) {

        if (self::$traceEnabled) {
            $unique_id               = sprintf("%08x", abs(crc32($_SERVER['REMOTE_ADDR'].$_SERVER['REQUEST_TIME'].$_SERVER['REMOTE_PORT'])));

            if ($calling_function_override == null) {
				$calling_function = SnapLibUtil::getCallingFunctionName();
			} else {
				$calling_function = $calling_function_override;
			}

			if (is_object($message)) {
				$ov = get_object_vars($message);
				$message = print_r($ov, true);
			} else if (is_array($message)) {
				$message = print_r($message, true);
			}

			$logging_message           = "{$unique_id}|{$calling_function} | {$message}";
            $ticks                     = time() + ((int) get_option('gmt_offset') * 3600);
            $formatted_time            = date('d-m-H:i:s', $ticks);
			$formatted_logging_message = "{$formatted_time}|DUP|{$logging_message} \r\n";

            // Always write to error log - if they don't want the info they can turn off WordPress error logging or tracing
            self::ErrLog($logging_message);

            // Everything goes to the plugin log, whether it's part of package generation or not.
            self::WriteToTrace($formatted_logging_message);
        }
    }

    public static function errLog($message)
    {
        $message = 'DUP:'. $message;
        error_log($message);
    }
        

	static public function TraceObject($msg, $o) {
        if(self::$traceEnabled) {
            self::Trace($msg . ':' . print_r($o, true));
        }
	}


    public static function GetDefaultKey()
    {
        $auth_key = defined('AUTH_KEY') ? AUTH_KEY : 'atk';
        $auth_key .= defined('DB_HOST') ? DB_HOST : 'dbh';
        $auth_key .= defined('DB_NAME') ? DB_NAME : 'dbn';
        $auth_key .= defined('DB_USER') ? DB_USER : 'dbu';

        return hash('md5', $auth_key);
    }

     public static function GetBackupTraceFilepath()
    {
        $default_key = self::getDefaultKey();
        $backup_log_filename = "dup_$default_key.log1";
        $backup_path = DUPLICATOR_SSDIR_PATH."/".$backup_log_filename;

        return $backup_path;
    }

    /**
     * Gets the active trace file path
     *
     * @return string   Returns the full path to the active trace file (i.e. dup-pro_hash.log)
     */
    public static function GetTraceFilepath()
    {
        $default_key  = self::getDefaultKey();
        $log_filename = "dup_$default_key.log";
        $file_path    = DUPLICATOR_SSDIR_PATH."/".$log_filename;

        return $file_path;
    }

    /**
     * Deletes the trace log and backup trace log files
     *
     * @return null
     */
    public static function DeleteTraceLog()
    {
        $file_path   = self::GetTraceFilepath();
        $backup_path = self::GetBackupTraceFilepath();

        self::trace("deleting $file_path");
        @unlink($file_path);
        self::trace("deleting $backup_path");
        @unlink($backup_path);
    }

	/**
	*  Called when an error is detected and no further processing should occur
	*  @param string $msg The message to log
	*  @param string $details Additional details to help resolve the issue if possible
	*/
	static public function Error($msg, $detail, $shouldDie = true) {
		
        error_log($msg . 'DETAIL:'. $detail);
        
		$source = self::getStack(debug_backtrace());
		
		$err_msg  = "\n==================================================================================\n";
		$err_msg .= "DUPLICATOR ERROR\n";
		$err_msg .= "Please try again! If the error persists see the Duplicator 'Help' menu.\n";
		$err_msg .= "---------------------------------------------------------------------------------\n";
		$err_msg .= "MESSAGE:\n\t{$msg}\n";
		if (strlen($detail)) {
			$err_msg .= "DETAILS:\n\t{$detail}\n";
		}
		$err_msg .= "TRACE:\n{$source}";
		$err_msg .= "==================================================================================\n\n";
		@fwrite(self::$logFileHandle, "{$err_msg}"); 
        
        if($shouldDie) {
            die("DUPLICATOR ERROR: Please see the 'Package Log' file link below.");
        }
	}
	
	
	/** 
	 * The current strack trace of a PHP call
	 * @param $stacktrace The current debug stack
	 * @return string 
	 */ 
    public static function getStack($stacktrace) {
        $output = "";
        $i = 1;
        foreach($stacktrace as $node) {
            $output .= "\t $i. ".basename($node['file']) ." : " .$node['function'] ." (" .$node['line'].")\n";
            $i++;
        }
		return $output;
    } 

    /**
     * Manages writing the active or backup log based on the size setting
     *
     * @return null
     */
    private static function WriteToTrace($formatted_logging_message)
    {
        $log_filepath = self::GetTraceFilepath();

        if (@filesize($log_filepath) > DUPLICATOR_MAX_LOG_SIZE) {
            $backup_log_filepath = self::GetBackupTraceFilepath();

            if (file_exists($backup_log_filepath)) {
                if (@unlink($backup_log_filepath) === false) {
                    DUP_PRO_Low_U::errLog("Couldn't delete backup log $backup_log_filepath");
                }
            }

            if (@rename($log_filepath, $backup_log_filepath) === false) {
                self::errLog("Couldn't rename log $log_filepath to $backup_log_filepath");
            }
        }

        if (@file_put_contents($log_filepath, $formatted_logging_message, FILE_APPEND) === false) {
            // Not en error worth reporting
        }
    }
}

DUP_Log::Init();
?>