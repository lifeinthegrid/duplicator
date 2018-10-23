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
 */
// Exit if accessed directly
if (!defined('DUPLICATOR_VERSION')) {
    exit;
}

class DUP_Validator
{
    /**
     * @var array $patterns
     */
    private static $patterns = array(
        //    'uri'           => '[A-Za-z0-9-\/_?&=]+',
        //    'url'           => '[A-Za-z0-9-:.\/_?&=#]+',
        'alpha' => '[\p{L}]+',
        'words' => '[\p{L}\s]+',
        'alphanum' => '[\p{L}0-9]+',
        'tel' => '[0-9+\s()-]+',
        'text' => '[\p{L}0-9\s-.,;:!"%&()?+\'°#\/@]+',
        'ffile' => '/^[\p{L}\s0-9-_!%&()=\[\]#@,.;+]+\.[A-Za-z0-9]{2,4}$/',
        'fdir' => '/^[\p{L}\s0-9-_!%&()=\[\]#@,.;+]+$/',
        'address' => '[\p{L}0-9\s.,()°-]+',
        'date_dmy' => '[0-9]{1,2}\-[0-9]{1,2}\-[0-9]{4}',
        'date_ymd' => '[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}',
        'email' => '[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+'
    );

    const FILTER_VALIDATE_FILE   = 'ffile';
    const FILTER_VALIDATE_FOLDER = 'fdir';

    /**
     * @var array $errors
     */
    private $errors = array();

    /**
     *
     */
    public function __construct()
    {
        $this->errors = array();
    }

    /**
     *
     */
    public function reset()
    {
        $this->errors = array();
    }

    /**
     *
     * @return bool
     */
    public function isSuccess()
    {
        return empty($this->errors);
    }

    /**
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     *
     * @return array
     */
    public function getErrorsMsg()
    {
        $result = array();
        foreach ($this->errors as $err) {
            $result[] = $err['msg'];
        }
        return $result;
    }

    /**
     *
     * @param string $format
     * @param bool $echo
     * @return void|string
     */
    public function getErrorsFormat($format = "%s\n", $echo = true)
    {
        $msgs = $this->getErrorsMsg();

        ob_start();
        foreach ($msgs as $msg) {
            printf($format, $msg);
        }

        if ($echo) {
            ob_end_flush();
        } else {
            return ob_get_clean();
        }
    }

    /**
     *
     * @param string $key
     * @param string $msg
     */
    protected function addError($key, $msg)
    {
        $this->errors[] = array(
            'key' => $key,
            'msg' => $msg
        );
    }

    /**
     *
     * @param mixed $variable
     * @param int $filter
     * @param array $options
     * @return mixed
     */
    public function filter_var($variable, $filter = FILTER_DEFAULT, $options = array())
    {
        $success = true;
        $result  = null;

        if ($filter === FILTER_VALIDATE_BOOLEAN) {
            $options['flags'] = FILTER_NULL_ON_FAILURE;

            $result = filter_var($variable, $filter, $options);

            if (is_null($result)) {
                $success = false;
            }
        } else {
            $result = filter_var($variable, $filter, $options);

            if ($result === false) {
                $success = false;
            }
        }

        if (!$success) {
            $key = isset($options['valkey']) ? $options['valkey'] : '';

            if (isset($options['errmsg'])) {
                $msg = sprintf($options['errmsg'], $variable);
            } else {
                $msg = sprintf('%1$s isn\'t a valid value', $variable);
            }

            $this->addError($key, $msg);
        }

        return $result;
    }

    /**
     *
     * @param mixed $variable
     * @param string $filter
     * @param array $options
     * @return type
     * @throws Exception
     */
    public function filter_custom($variable, $filter, $options = array())
    {

        if (!isset(self::$patterns[$filter])) {
            throw new Exception('Filter not valid');
        }

        $options = array_merge($options, array(
            'options' => array(
                'regexp' => self::$patterns[$filter])
            )
        );

        //$options['regexp'] = self::$patterns[$filter];

        return $this->filter_var($variable, FILTER_VALIDATE_REGEXP, $options);
    }
}