<?php

if(!class_exists('SnapLibURLU')) {
	return;
}

/**
 * Utility class used for working with URLs
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2
 *
 * @package SnapLib
 * @copyright (c) 2017, Snapcreek LLC
 * @license	https://opensource.org/licenses/GPL-3.0 GNU Public License
 *
 */
class SnapLibURLU
{

	/**
     * Append a new query value to the end of a URL
     *
     * @param string $url   The URL to append the new value to
     * @param string $key   The new key name
     * @param string $value The new key name value
     *
     * @return string Returns the new URL with with the query string name and value
     */
    public static function appendQueryValue($url, $key, $value)
    {
        $separator = (parse_url($url, PHP_URL_QUERY) == NULL) ? '?' : '&';
        $modified_url = $url."$separator$key=$value";

        return $modified_url;
    }

	/*
	 * Fetches current URL via php
	 *
	 * @param bool $queryString If true the query string will also be returned.
	 *
	 * @returns The current page url
	 */
    public static function getCurrentUrl($queryString = true) {
		$protocol = 'http';
		if ($_SERVER['SERVER_PORT'] == 443 || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
			$protocol .= 's';
			$protocolPort = $_SERVER['SERVER_PORT'];
		} else {
			$protocolPort = 80;
		}
		$host = $_SERVER['HTTP_HOST'];
		$port = $_SERVER['SERVER_PORT'];
		$request = $_SERVER['PHP_SELF'];

		$query = ($queryString === TRUE) ? $_SERVER['QUERY_STRING'] : "";
		$url = $protocol . '://' . $host . ($port == $protocolPort ? '' : ':' . $port) . $request . (empty($query) ? '' : '?' . $query);
		return $url;
	}

	/*
	 * Check to see if the URL is valid
	 *
	 *  @param string $url		A URL e.g without prefix "ajax.googleapis.com"
	 *  @param string $port		A valid port number
	 *
	 *  @returns True if http content exists
	 */
	public static function urlExists($url = '', $port, $timeout = 5)
    {

		if (function_exists('curl_version'))
		{
			// Stip is not
			if(empty($url) && !is_string($url))
				return false;

			$return = true;
			$curl = curl_init($url);
			//don't fetch the actual page, you only want to check the connection is ok
			curl_setopt($curl, CURLOPT_NOBODY, true);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT ,$timeout);
			curl_setopt($curl, CURLOPT_TIMEOUT , $timeout);
			$result = curl_exec($curl);

			//if request did not fail
			if ($result !== false) {
				//if request was ok, check response code
				$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				$return = ((int)$statusCode === 404);
			}
			
			curl_close($curl);
			return (!$return);
		}
		
		
		if (function_exists('fsockopen')) {
			$port		 = isset($port) && is_integer($port) ? $port : 80;
			$connected	 = @fsockopen($url, $port, $errno, $errstr, $timeout); //website and port
			if ($connected) {
				$is_conn = true;
				@fclose($connected);
			} else {
				$is_conn = false;
			}
			return $is_conn;
		}

		return false;
    }
	

}
