<?php

class DUP_JSON
{
	protected static $_messages = array(
		JSON_ERROR_NONE => 'No error has occurred',
		JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
		JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
		JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
		JSON_ERROR_SYNTAX => 'Syntax error',
		JSON_ERROR_UTF8 => 'Malformed UTF-8 characters. To resolve see https://snapcreek.com/duplicator/docs/faqs-tech/?utm_source=duplicator_free&utm_medium=wordpress_plugin&utm_campaign=problem_resolution&utm_content=malformed_utf8#faq-package-170-q'
	);

	public static function customEncode($value, $iteration = 1)
	{
		if (DUP_Util::$on_php_53_plus) {

			$encoded = json_encode($value);

			switch (json_last_error()) {
				case JSON_ERROR_NONE:
					return $encoded;
				case JSON_ERROR_DEPTH:
					throw new RuntimeException('Maximum stack depth exceeded');
				case JSON_ERROR_STATE_MISMATCH:
					throw new RuntimeException('Underflow or the modes mismatch');
				case JSON_ERROR_CTRL_CHAR:
					throw new RuntimeException('Unexpected control character found');
				case JSON_ERROR_SYNTAX:
					throw new RuntimeException('Syntax error, malformed JSON');
				case JSON_ERROR_UTF8:
					if ($iteration == 1) {
						$clean = self::makeUTF8($value);
						return self::customEncode($clean, $iteration + 1);
					} else {
						throw new RuntimeException('UTF-8 error loop');
					}
				default:
					throw new RuntimeException('Unknown error');
			}
		} else {
			return self::oldCustomEncode($value);
		}
	}

	public static function encode($value, $options = 0)
	{
		$result = json_encode($value, $options);

		if ($result !== FALSE) {
			return $result;
		}

		if (function_exists('json_last_error')) {
			$message = self::$_messages[json_last_error()];
		} else {
			$message = esc_html__("One or more filenames isn't compatible with JSON encoding", 'duplicator');
		}

		throw new RuntimeException($message);
	}

	public static function safeEncode($value)
	{
		$jsonString = json_encode($value);

		if (($jsonString === false) || trim($jsonString) == '') {
			$jsonString = self::customEncode($value);

			if (($jsonString === false) || trim($jsonString) == '') {
				throw new Exception('Unable to generate JSON from object');
			}
		}

		return $jsonString;
	}

	public static function decode($json, $assoc = false)
	{
		$result = json_decode($json, $assoc);

		if ($result) {
			return $result;
		}

		if (function_exists('json_last_error')) {
			throw new RuntimeException(self::$_messages[json_last_error()]);
		} else {
			throw new RuntimeException("DUP_JSON decode error");
		}
		
	}

	private static function makeUTF8($mixed)
	{
		if (is_array($mixed)) {
			foreach ($mixed as $key => $value) {
				$mixed[$key] = self::makeUTF8($value);
			}
		} else if (is_string($mixed)) {
			return utf8_encode($mixed);
		}
		return $mixed;
	}

	private static function escapeString($str)
	{
		return addcslashes($str, "\v\t\n\r\f\"\\/");
	}

	private static function oldCustomEncode($in)
	{
		$out = "";

		if (is_object($in)) {
			$arr[$key]	 = "\"".self::escapeString($key)."\":\"{$val}\"";
			$in			 = get_object_vars($in);
		}

		if (is_array($in)) {
			$obj = false;
			$arr = array();

			foreach ($in AS $key => $val) {
				if (!is_numeric($key)) {
					$obj = true;
				}
				$arr[$key] = self::oldCustomEncode($val);
			}

			if ($obj) {
				foreach ($arr AS $key => $val) {
					$arr[$key] = "\"".self::escapeString($key)."\":{$val}";
				}
				$val = implode(',', $arr);
				$out .= "{{$val}}";
			} else {
				$val = implode(',', $arr);
				$out .= "[{$val}]";
			}
		} elseif (is_bool($in)) {
			$out .= $in ? 'true' : 'false';
		} elseif (is_null($in)) {
			$out .= 'null';
		} elseif (is_string($in)) {
			$out .= "\"".self::escapeString($in)."\"";
		} else {
			$out .= $in;
		}

		return "{$out}";
	}
}