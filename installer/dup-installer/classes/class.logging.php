<?php
/**
 * Class used to log information
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2 Full Documentation
 *
 * @package SC\DUPX\Log
 *
 */
defined('ABSPATH') || defined('DUPXABSPATH') || exit;

/**
 * DUPX_Log
 * Class used to log information  */
class DUPX_Log
{

    /**
     * Maximum length of the log on the log. 
     * Prevents uncontrolled increase in log size. This dimension should never be reached
     */
    const MAX_LENGTH_FWRITE = 50000;
    const LV_DEFAULT        = 1;
    const LV_DETAILED       = 2;
    const LV_DEBUG          = 3;
    const LV_HARD_DEBUG     = 4;

    /**
     * if true throw exception on error else die on error
     * @var bool
     */
    private static $thowExceptionOnError = false;

    /**
     * log level
     * @var int 
     */
    private static $logLevel = self::LV_DEFAULT;

    /**
     * num of \t before log string.
     * @var int
     */
    private static $indentation = 0;

    /**
     *
     * @var float
     */
    private static $microtimeStart = 0;

    /**
     *
     * @var callable 
     */
    private static $postprocessCallback = null;

    /**
     * set log level from param manager
     */
    public static function setLogLevel()
    {
        self::$logLevel = isset($_POST['logging']) ? $_POST['logging'] : DUPX_Log::LV_DEFAULT;
    }

    /** METHOD: LOG
     *  Used to write debug info to the text log file
     *  @param string $msg		Any text data
     *  @param int $logging	Log level
     *  @param bool if true flush file log
     *
     */
    public static function info($msg, $logging = self::LV_DEFAULT, $flush = false)
    {
        if ($logging <= self::$logLevel) {
            if (isset($GLOBALS['LOG_FILE_HANDLE']) && is_resource($GLOBALS['LOG_FILE_HANDLE'])) {
                $preLog = '';
                if (self::$indentation) {
                    $preLog .= str_repeat("\t", self::$indentation);
                }
                if (self::$logLevel >= self::LV_DETAILED) {
                    $preLog .= sprintf('[DELTA:%10.5f] ', microtime(true) - self::$microtimeStart);
                }
                if (is_callable(self::$postprocessCallback)) {
                    $msg = call_user_func(self::$postprocessCallback, $msg);
                }
                @fwrite($GLOBALS["LOG_FILE_HANDLE"], $preLog.$msg."\n", self::MAX_LENGTH_FWRITE);
            }

            if ($flush) {
                self::flush();
            }
        }
    }

    /**
     * 
     * @param callable $callback
     */
    public static function setPostProcessCallback($callback)
    {
        if (is_callable($callback)) {
            self::$postprocessCallback = $callback;
        } else {
            self::$postprocessCallback = null;
        }
    }

    /**
     * set $microtimeStart  at current time
     */
    public static function resetTime($logging = self::LV_DEFAULT)
    {
        self::$microtimeStart = microtime(true);
        if ($logging > self::$logLevel) {
            return;
        }
        $callers = debug_backtrace();
        $file    = $callers[0]['file'];
        $line    = $callers[0]['line'];
        DUPX_Log::info('LOG-TIME['.$file.':'.$line.'] RESET TIME', $logging);
    }

    /**
     * log time delta from last resetTime call
     * 
     * @return void
     */
    public static function logTime($msg = '', $logging = self::LV_DEFAULT)
    {
        if ($logging > self::$logLevel) {
            return;
        }
        $callers = debug_backtrace();
        $file    = $callers[0]['file'];
        $line    = $callers[0]['line'];
        DUPX_Log::info(sprintf('LOG-TIME[%s:%s][DELTA:%10.5f] ', $file, $line, microtime(true) - self::$microtimeStart).(empty($msg) ? '' : ' MESSAGE:'.$msg), $logging);
    }

    public static function incIndent()
    {
        self::$indentation++;
    }

    public static function decIndent()
    {
        if (self::$indentation > 0) {
            self::$indentation--;
        }
    }

    public static function resetIndent()
    {
        self::$indentation = 0;
    }

    public static function isLevel($logging)
    {
        return $logging <= self::$logLevel;
    }

    public static function infoObject($msg, &$object, $logging = self::LV_DEFAULT)
    {
        $msg = $msg."\n".print_r($object, true);
        self::info($msg, $logging);
    }

    public static function flush()
    {
        if (is_resource($GLOBALS['LOG_FILE_HANDLE'])) {
            @fflush($GLOBALS['LOG_FILE_HANDLE']);
        }
    }

    public static function close()
    {
        if (is_resource($GLOBALS['LOG_FILE_HANDLE'])) {
            @fclose($GLOBALS["LOG_FILE_HANDLE"]);
            $GLOBALS["LOG_FILE_HANDLE"] = null;
        }
    }

    public static function getFileHandle()
    {
        return is_resource($GLOBALS["LOG_FILE_HANDLE"]) ? $GLOBALS["LOG_FILE_HANDLE"] : false;
    }

    public static function error($errorMessage)
    {
        $breaks  = array("<br />", "<br>", "<br/>");
        $spaces  = array("&nbsp;");
        $log_msg = str_ireplace($breaks, "\r\n", $errorMessage);
        $log_msg = str_ireplace($spaces, " ", $log_msg);
        $log_msg = strip_tags($log_msg);

        self::info("\nINSTALLER ERROR:\n{$log_msg}\n");

        if (self::$thowExceptionOnError) {
            throw new Exception($errorMessage);
        } else {
            self::close();
            die("<div class='dupx-ui-error'><hr size='1' /><b style='color:#B80000;'>INSTALL ERROR!</b><br/>{$errorMessage}</div>");
        }
    }

    /**
     * 
     * @param Exception $e
     * @param string $title
     */
    public static function logException($e, $logging = self::LV_DEFAULT, $title = 'EXCEPTION ERROR: ')
    {
        if ($logging <= self::$logLevel) {
            DUPX_Log::info("\n".$title.' '.$e->getMessage());
            DUPX_Log::info("\tFILE:".$e->getFile().'['.$e->getLIne().']');
            DUPX_Log::info("\tTRACE:\n".$e->getTraceAsString()."\n");
        }
    }

    /**
     *
     * @param boolean $set
     */
    public static function setThrowExceptionOnError($set)
    {
        self::$thowExceptionOnError = (bool) $set;
    }

    /**
     *
     * @param mixed $var
     * @param bool $checkCallable // if true check if var is callable and display it
     * @return string
     */
    public static function varToString($var, $checkCallable = false)
    {
        if ($checkCallable && is_callable($var)) {
            return '(callable) '.print_r($var, true);
        }
        switch (gettype($var)) {
            case "boolean":
                return $var ? 'true' : 'false';
            case "integer":
            case "double":
                return (string) $var;
            case "string":
                return '"'.$var.'"';
            case "array":
            case "object":
                return print_r($var, true);
            case "resource":
            case "resource (closed)":
            case "NULL":
            case "unknown type":
            default:
                return gettype($var);
        }
    }
}

class DUPX_Handler
{

    const MODE_OFF         = 0; // don't write in log
    const MODE_LOG         = 1; // write errors in log file
    const MODE_VAR         = 2; // put php errors in $varModeLog static var
    const SHUTDOWN_TIMEOUT = 'tm';

    public static function initErrorHandler()
    {
        DUPX_Boot::disableBootShutdownFunction();

        @set_error_handler(array(__CLASS__, 'error'));
        @register_shutdown_function(array(__CLASS__, 'shutdown'));
    }
    /**
     *
     * @var array
     */
    private static $shutdownReturns = array(
        'tm' => 'timeout'
    );

    /**
     *
     * @var int
     */
    private static $handlerMode = self::MODE_LOG;

    /**
     *
     * @var bool // print code reference and errno at end of php error line  [CODE:10|FILE:test.php|LINE:100]
     */
    private static $codeReference = true;

    /**
     *
     * @var bool // print prefix in php error line [PHP ERR][WARN] MSG: .....
     */
    private static $errPrefix = true;

    /**
     *
     * @var string // php errors in MODE_VAR
     */
    private static $varModeLog = '';

    /**
     * Error handler
     *
     * @param  integer $errno   Error level
     * @param  string  $errstr  Error message
     * @param  string  $errfile Error file
     * @param  integer $errline Error line
     * @return void
     */
    public static function error($errno, $errstr, $errfile, $errline)
    {
        switch (self::$handlerMode) {
            case self::MODE_OFF:
                if ($errno == E_ERROR) {
                    $log_message = self::getMessage($errno, $errstr, $errfile, $errline);
                    DUPX_Log::error($log_message);
                }
                break;
            case self::MODE_VAR:
                self::$varModeLog .= self::getMessage($errno, $errstr, $errfile, $errline)."\n";
                break;
            case self::MODE_LOG:
            default:
                switch ($errno) {
                    case E_ERROR :
                        $log_message = self::getMessage($errno, $errstr, $errfile, $errline);
                        DUPX_Log::error($log_message);
                        break;
                    case E_NOTICE :
                    case E_WARNING :
                    default :
                        $log_message = self::getMessage($errno, $errstr, $errfile, $errline);
                        DUPX_Log::info($log_message);
                        break;
                }
        }
    }

    private static function getMessage($errno, $errstr, $errfile, $errline)
    {
        $result = '';

        if (self::$errPrefix) {
            $result = '[PHP ERR]';
            switch ($errno) {
                case E_ERROR :
                    $result .= '[FATAL]';
                    break;
                case E_WARNING :
                    $result .= '[WARN]';
                    break;
                case E_NOTICE :
                    $result .= '[NOTICE]';
                    break;
                default :
                    $result .= '[ISSUE]';
                    break;
            }
            $result .= ' MSG:';
        }

        $result .= $errstr;

        if (self::$codeReference) {
            $result .= ' [CODE:'.$errno.'|FILE:'.$errfile.'|LINE:'.$errline.']';
            if (DUPX_Log::isLevel(DUPX_Log::LV_DEBUG)) {
                ob_start();
                debug_print_backtrace();
                $result .= "\n".ob_get_clean();
            }
        }

        return $result;
    }

    /**
     * if setMode is called without params set as default
     *
     * @param int $mode
     * @param bool $errPrefix // print prefix in php error line [PHP ERR][WARN] MSG: .....
     * @param bool $codeReference // print code reference and errno at end of php error line  [CODE:10|FILE:test.php|LINE:100]
     */
    public static function setMode($mode = self::MODE_LOG, $errPrefix = true, $codeReference = true)
    {
        switch ($mode) {
            case self::MODE_OFF:
            case self::MODE_VAR:
                self::$handlerMode = $mode;
                break;
            case self::MODE_LOG:
            default:
                self::$handlerMode = self::MODE_LOG;
        }

        self::$varModeLog    = '';
        self::$errPrefix     = $errPrefix;
        self::$codeReference = $codeReference;
    }

    /**
     *
     * @return string // return var log string in MODE_VAR
     */
    public static function getVarLog()
    {
        return self::$varModeLog;
    }

    /**
     *
     * @return string // return var log string in MODE_VAR and clean var
     */
    public static function getVarLogClean()
    {
        $result           = self::$varModeLog;
        self::$varModeLog = '';
        return $result;
    }

    /**
     *
     * @param string $status // timeout
     * @param string $string
     */
    public static function setShutdownReturn($status, $str)
    {
        self::$shutdownReturns[$status] = $str;
    }

    /**
     * Shutdown handler
     *
     * @return void
     */
    public static function shutdown()
    {
        if (($error = error_get_last())) {
            if (preg_match('/^Maximum execution time (?:.+) exceeded$/i', $error['message'])) {
                echo self::$shutdownReturns[self::SHUTDOWN_TIMEOUT];
            }
            DUPX_Handler::error($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}