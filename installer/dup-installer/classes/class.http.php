<?php
defined("ABSPATH") or die("");
/**	 * *****************************************************
 *  CLASS::DUPX_Http
 *  Http Class Utility */
class DUPX_HTTP
{
	/**
	 *  Do an http post request with html form elements
	 *  @param string $url		A URL to post to
	 *  @param string $data		A valid key/pair combo $data = array('key1' => 'value1', 'key2' => 'value2')
	 * 							generated hidden form elements
	 *  @return string		    An html form that will automatically post itself
	 */
	public static function post_with_html($url, $data)
	{
		$id = uniqid();
		$html = "<form id='{$id}' method='post' action='{$url}' />\n";
		foreach ($data as $name => $value)
		{
			$html .= "<input type='hidden' name='{$name}' value='{$value}' />\n";
		}
		$html .= "</form>\n";
		$html .= "<script>$(document).ready(function() { $('#{$id}').submit(); });</script>";
		echo $html;
	}

	/**
	 *  Do an http post request with curl or php code
	 *  @param string $url		A URL to post to
	 *  @param string $params	A valid key/pair combo $data = array('key1' => 'value1', 'key2' => 'value2');
	 * 	@param string $headers	Optional header elements
	 *  @return a string or FALSE on failure.
	 */
	public static function post($url, $params = array(), $headers = null)
	{
		//PHP POST
		if (!function_exists('curl_init'))
		{
			return self::php_get_post($url, $params, $headers = null, 'POST');
		}

		//CURL POST
		$headers_on = isset($headers) && array_count_values($headers);
		$params = http_build_query($params);
		$ch = curl_init();

		// Return contents of transfer on curl_exec
		// Allow self-signed certs
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, $headers_on);

		if ($headers_on)
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		curl_setopt($ch, CURLOPT_POST, count($params));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

	/**
	 *  Do an http post request with curl or php code
	 *  @param string $url		A URL to get.  If $params is not null then all query strings will be removed.
	 *  @param string $params	A valid key/pair combo $data = array('key1' => 'value1', 'key2' => 'value2');
	 * 	@param string $headers	Optional header elements
	 *  @return a string or FALSE on failure.
	 */
	public static function get($url, $params = array(), $headers = null)
	{
		//PHP GET
		if (!function_exists('curl_init'))
		{
			return self::php_get_post($url, $params, $headers = null, 'GET');
		}

		//Remove query string if $params are passed
		$full_url = $url;
		if (count($params))
		{
			$url = preg_replace('/\?.*/', '', $url);
			$full_url = $url . '?' . http_build_query($params);
		}
		$headers_on = isset($headers) && array_count_values($headers);
		$ch = curl_init();

		// Return contents of transfer on curl_exec
		// Allow self-signed certs
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_URL, $full_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, $headers_on);
		if ($headers_on)
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

	/**
	 *  Gets the URL of the current request
	 *  @param bool $show_query		Include the query string in the URL
	 *  @return string	A URL
	 */
	public static function get_request_uri($show_query = true)
	{
		$isSecure = false;

		if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] == 443))
		{
			$isSecure = true;
		}
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
		{
			$isSecure = true;
		}
		$protocol = $isSecure ? 'https' : 'http';
		$url = "{$protocol}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
		$url = ($show_query) ? $url : preg_replace('/\?.*/', '', $url);
		return $url;
	}

	/**
	 *  Check to see if the internet is accessible
	 *  @param string $url		A URL e.g without prefix "ajax.googleapis.com"
	 *  @param string $port		A valid port number
	 *  @return bool
	 */
	public static function is_url_active($url, $port, $timeout = 5)
	{
		if (function_exists('fsockopen'))
		{
			$port = isset($port) && is_integer($port) ? $port : 80;
			$connected = @fsockopen($url, $port, $errno, $errstr, $timeout); //website and port
			if ($connected)
			{
				$is_conn = true;
				@fclose($connected);
			}
			else
			{
				$is_conn = false;
			}
			return $is_conn;
		}
		else
		{
			return false;
		}
	}

	public static function parse_host($url)
	{
		$url = parse_url(trim($url));
		if ($url == false)
		{
			return null;
		}
		return trim($url['host'] ? $url['host'] : array_shift(explode('/', $url['path'], 2)));
	}

	//PHP POST or GET requets
	private static function php_get_post($url, $params, $headers = null, $method)
	{
		$full_url = $url;
		if ($method == 'GET' && count($params))
		{
			$url = preg_replace('/\?.*/', '', $url);
			$full_url = $url . '?' . http_build_query($params);
		}

		$data = array('http' => array(
				'method' => $method,
				'content' => http_build_query($params)));

		if ($headers !== null)
		{
			$data['http']['header'] = $headers;
		}
		$ctx = stream_context_create($data);
		$fp = @fopen($full_url, 'rb', false, $ctx);
		if (!$fp)
		{
			throw new Exception("Problem with $full_url, $php_errormsg");
		}
		$response = @stream_get_contents($fp);
		if ($response === false)
		{
			throw new Exception("Problem reading data from $full_url, $php_errormsg");
		}
		return $response;
	}

}
