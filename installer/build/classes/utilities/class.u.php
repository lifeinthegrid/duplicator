<?php
/**
 * Various Static Utility methods for working with the installer
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2 Full Documentation
 *
 * @package SC\DUPX\U
 *
 */
defined("DUPXABSPATH") or die("");

class DUPX_U
{
    /**
     * Adds a slash to the end of a file or directory path
     *
     * @param string $path		A path
     *
     * @return string The original $path with a with '/' added to the end.
     */
    public static function addSlash($path)
    {
        $last_char = substr($path, strlen($path) - 1, 1);
        if ($last_char != '/') {
            $path .= '/';
        }
        return $path;
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
        return sprintf("%.4f sec.", abs($end - $start));
    }

    /**
     *  Returns 256 spaces
     *
     *  PHP_SAPI for fcgi requires a data flush of at least 256
     *  bytes every 40 seconds or else it forces a script halt
     *
     * @return string A series of 256 spaces ' '
     */
    public static function fcgiFlush()
    {
        echo(str_repeat(' ', 256));
        @flush();
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
     *  Returns the active plugins for the WordPress website in the package
     *
     *  @param  obj    $dbh	 A database connection handle
	 *
     *  @return array  $list A list of active plugins
     */
    public static function getActivePlugins($dbh)
    {
        $query = @mysqli_query($dbh, "SELECT option_value FROM `".mysqli_real_escape_string($dbh, $GLOBALS['FW_TABLEPREFIX'])."options` WHERE option_name = 'active_plugins' ");
        if ($query) {
            $row         = @mysqli_fetch_array($query);
            $all_plugins = unserialize($row[0]);
            if (is_array($all_plugins)) {
                return $all_plugins;
            }
        }
        return array();
    }

    /**
     *  Check to see if the internet is accessible
     *
     *  Note: fsocketopen on windows doesn't seem to honor $timeout setting.
     *
     *  @param string $url		A url e.g without prefix "ajax.googleapis.com"
     *  @param string $port		A valid port number
     *
     *  @return bool	Returns true PHP can request the URL
     */
    public static function isURLActive($url, $port, $timeout = 5)
    {
        if (function_exists('fsockopen')) {
            @ini_set("default_socket_timeout", 5);
            $port      = isset($port) && is_integer($port) ? $port : 80;
            $connected = @fsockopen($url, $port, $errno, $errstr, $timeout); //website and port
            if ($connected) {
                @fclose($connected);
                return true;
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Does a string have non ascii characters
     *
     * @param string $string Any string blob
     *
     * @return bool Returns true if any non ascii character is found in the blob
     */
    public static function isNonASCII($string)
    {
        return preg_match('/[^\x20-\x7f]/', $string);
    }


	/**
     * Is the string JSON
     *
     * @param string $string Any string blob
     *
     * @return bool Returns true if the string is JSON encoded
     */
    public static function isJSON($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) ? true : false;
    }

    /**
     *  The characters that are special in the replacement value of preg_replace are not the
     *  same characters that are special in the pattern.  Allows for '$' to be safely passed.
     *
     *  @param string $str		The string to replace on
    public static function pregSpecialChars($str)
    {
        return preg_replace('/(\$|\\\\)(?=\d)/', '\\\\\1', $str);
    }
	 * */

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

    /**
     * Converts shorthand memory notation value to bytes
     *
     * @param $val Memory size shorthand notation string such as 10M, 1G
     *
     * @returns int The byte representation of the shorthand $val
     */
    public static function getBytes($val)
    {
        $val  = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
                break;
            default :
                $val = null;
        }
        return $val;
    }

    /**
     *  Makes path safe for any OS for PHP
     *
     *  Paths should ALWAYS READ be "/"
     * 		uni:  /home/path/file.txt
     * 		win:  D:/home/path/file.txt
     *
     *  @param string $path		The path to make safe
     *
     *  @return string The original $path with a with all slashes facing '/'.
     */
    public static function setSafePath($path)
    {
        return str_replace("\\", "/", $path);
    }

    /**
     *  Looks for a list of strings in a string and returns each list item that is found
     *
     *  @param array  $list		An array of strings to search for
     *  @param string $haystack	The string blob to search through
     *
     *  @return array An array of strings from the $list array found in the $haystack
     */
    public static function getListValues($list, $haystack)
    {
        $found = array();
        foreach ($list as $var) {
            if (strstr($haystack, $var) !== false) {
                array_push($found, $var);
            }
        }
        return $found;
    }

    /**
     *  Makes path unsafe for any OS for PHP used primarily to show default
     *  Windows OS path standard
     *
     *  @param string $path		The path to make unsafe
     *
     *  @return string The original $path with a with all slashes facing '\'.
     */
    public static function unsetSafePath($path)
    {
        return str_replace("/", "\\", $path);
    }

	/**
     *  Filter the string to escape the quote
     *
     *  @param string $val		The value to escape quote
     *
     *  @return string Returns the input value escaped
     */
    public static function safeQuote($val)
    {
		$val = addslashes($val);
        return $val;
    }

     /**
     *  Check PHP version
     *
     *  @param string $version		PHP version we looking for
     *
     *  @return boolean Returns true if version is same or above.
     */
    public static function isVersion($version)
    {
        return (version_compare(PHP_VERSION, $version) >= 0);
    }

    // START ESCAPING AND SANITIZATION
	/**
	 * Escaping for HTML blocks.
	 *
	 * @since 2.8.0
	 *
	 * @param string $text
	 * @return string
	 */
	public static function esc_html( $text ) {
		$safe_text = self::wp_check_invalid_utf8( $text );
		$safe_text = self::_wp_specialchars( $safe_text, ENT_QUOTES );
		/**
		 * Filters a string cleaned and escaped for output in HTML.
		 *
		 * Text passed to esc_html() is stripped of invalid or special characters
		 * before output.
		 *
		 * @since 2.8.0
		 *
		 * @param string $safe_text The text after it has been escaped.
		 * @param string $text      The text prior to being escaped.
		*/
		return $safe_text;
	}

	/**
	 * Escape single quotes, htmlspecialchar " < > &, and fix line endings.
	 *
	 * Escapes text strings for echoing in JS. It is intended to be used for inline JS
	 * (in a tag attribute, for example onclick="..."). Note that the strings have to
	 * be in single quotes. The {@see 'js_escape'} filter is also applied here.
	 *
	 * @since 2.8.0
	 *
	 * @param string $text The text to be escaped.
	 * @return string Escaped text.
	 */
	public static function esc_js( $text ) {
		$safe_text = self::wp_check_invalid_utf8( $text );
		$safe_text = self::_wp_specialchars( $safe_text, ENT_COMPAT );
		$safe_text = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", stripslashes( $safe_text ) );
		$safe_text = str_replace( "\r", '', $safe_text );
		$safe_text = str_replace( "\n", '\\n', addslashes( $safe_text ) );
		/**
		 * Filters a string cleaned and escaped for output in JavaScript.
		 *
		 * Text passed to esc_js() is stripped of invalid or special characters,
		 * and properly slashed for output.
		 *
		 * @since 2.0.6
		 *
		 * @param string $safe_text The text after it has been escaped.
		 * @param string $text      The text prior to being escaped.
		*/
		return $safe_text;
	}

	/**
	 * Escaping for HTML attributes.
	 *
	 * @since 2.8.0
	 *
	 * @param string $text
	 * @return string
	 */
	public static function esc_attr( $text ) {
		$safe_text = self::wp_check_invalid_utf8( $text );
		$safe_text = self::_wp_specialchars( $safe_text, ENT_QUOTES );
		/**
		 * Filters a string cleaned and escaped for output in an HTML attribute.
		 *
		 * Text passed to esc_attr() is stripped of invalid or special characters
		 * before output.
		 *
		 * @since 2.0.6
		 *
		 * @param string $safe_text The text after it has been escaped.
		 * @param string $text      The text prior to being escaped.
		*/
		return $safe_text;
	}

	/**
	 * Escaping for textarea values.
	 *
	 * @since 3.1.0
	 *
	 * @param string $text
	 * @return string
	 */
	public static function esc_textarea( $text ) {
		// $safe_text = htmlspecialchars( $text, ENT_QUOTES, get_option( 'blog_charset' ) );
		$safe_text = htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );		
		/**
		 * Filters a string cleaned and escaped for output in a textarea element.
		 *
		 * @since 3.1.0
		 *
		 * @param string $safe_text The text after it has been escaped.
		 * @param string $text      The text prior to being escaped.
		*/
		return $safe_text;
	}

	/**
	 * Escape an HTML tag name.
	 *
	 * @since 2.5.0
	 *
	 * @param string $tag_name
	 * @return string
	 */
	function tag_escape( $tag_name ) {
		$safe_tag = strtolower( preg_replace('/[^a-zA-Z0-9_:]/', '', $tag_name) );
		/**
		 * Filters a string cleaned and escaped for output as an HTML tag.
		 *
		 * @since 2.8.0
		 *
		 * @param string $safe_tag The tag name after it has been escaped.
		 * @param string $tag_name The text before it was escaped.
		*/
		return $safe_tag;
	}

	/**
	 * Converts a number of special characters into their HTML entities.
	 *
	 * Specifically deals with: &, <, >, ", and '.
	 *
	 * $quote_style can be set to ENT_COMPAT to encode " to
	 * &quot;, or ENT_QUOTES to do both. Default is ENT_NOQUOTES where no quotes are encoded.
	 *
	 * @since 1.2.2
	 * @access private
	 *
	 * @staticvar string $_charset
	 *
	 * @param string     $string         The text which is to be encoded.
	 * @param int|string $quote_style    Optional. Converts double quotes if set to ENT_COMPAT,
	 *                                   both single and double if set to ENT_QUOTES or none if set to ENT_NOQUOTES.
	 *                                   Also compatible with old values; converting single quotes if set to 'single',
	 *                                   double if set to 'double' or both if otherwise set.
	 *                                   Default is ENT_NOQUOTES.
	 * @param string     $charset        Optional. The character encoding of the string. Default is false.
	 * @param bool       $double_encode  Optional. Whether to encode existing html entities. Default is false.
	 * @return string The encoded text with HTML entities.
	 */
	public static function _wp_specialchars( $string, $quote_style = ENT_NOQUOTES, $charset = false, $double_encode = false ) {
		$string = (string) $string;

		if ( 0 === strlen( $string ) )
			return '';

		// Don't bother if there are no specialchars - saves some processing
		if ( ! preg_match( '/[&<>"\']/', $string ) )
			return $string;

		// Account for the previous behaviour of the function when the $quote_style is not an accepted value
		if ( empty( $quote_style ) )
			$quote_style = ENT_NOQUOTES;
		elseif ( ! in_array( $quote_style, array( 0, 2, 3, 'single', 'double' ), true ) )
			$quote_style = ENT_QUOTES;

		// Store the site charset as a static to avoid multiple calls to wp_load_alloptions()
		if ( ! $charset ) {
			static $_charset = null;
			if ( ! isset( $_charset ) ) {
				$_charset = '';
			}
			$charset = $_charset;
		}

		if ( in_array( $charset, array( 'utf8', 'utf-8', 'UTF8' ) ) )
			$charset = 'UTF-8';

		$_quote_style = $quote_style;

		if ( $quote_style === 'double' ) {
			$quote_style = ENT_COMPAT;
			$_quote_style = ENT_COMPAT;
		} elseif ( $quote_style === 'single' ) {
			$quote_style = ENT_NOQUOTES;
		}

		if ( ! $double_encode ) {
			// Guarantee every &entity; is valid, convert &garbage; into &amp;garbage;
			// This is required for PHP < 5.4.0 because ENT_HTML401 flag is unavailable.
			$string = self::wp_kses_normalize_entities( $string );
		}

		$string = @htmlspecialchars( $string, $quote_style, $charset, $double_encode );

		// Back-compat.
		if ( 'single' === $_quote_style )
			$string = str_replace( "'", '&#039;', $string );

		return $string;
	}

	/**
	 * Converts a number of HTML entities into their special characters.
	 *
	 * Specifically deals with: &, <, >, ", and '.
	 *
	 * $quote_style can be set to ENT_COMPAT to decode " entities,
	 * or ENT_QUOTES to do both " and '. Default is ENT_NOQUOTES where no quotes are decoded.
	 *
	 * @since 2.8.0
	 *
	 * @param string     $string The text which is to be decoded.
	 * @param string|int $quote_style Optional. Converts double quotes if set to ENT_COMPAT,
	 *                                both single and double if set to ENT_QUOTES or
	 *                                none if set to ENT_NOQUOTES.
	 *                                Also compatible with old _wp_specialchars() values;
	 *                                converting single quotes if set to 'single',
	 *                                double if set to 'double' or both if otherwise set.
	 *                                Default is ENT_NOQUOTES.
	 * @return string The decoded text without HTML entities.
	 */
	public static function wp_specialchars_decode( $string, $quote_style = ENT_NOQUOTES ) {
		$string = (string) $string;

		if ( 0 === strlen( $string ) ) {
			return '';
		}

		// Don't bother if there are no entities - saves a lot of processing
		if ( strpos( $string, '&' ) === false ) {
			return $string;
		}

		// Match the previous behaviour of _wp_specialchars() when the $quote_style is not an accepted value
		if ( empty( $quote_style ) ) {
			$quote_style = ENT_NOQUOTES;
		} elseif ( !in_array( $quote_style, array( 0, 2, 3, 'single', 'double' ), true ) ) {
			$quote_style = ENT_QUOTES;
		}

		// More complete than get_html_translation_table( HTML_SPECIALCHARS )
		$single = array( '&#039;'  => '\'', '&#x27;' => '\'' );
		$single_preg = array( '/&#0*39;/'  => '&#039;', '/&#x0*27;/i' => '&#x27;' );
		$double = array( '&quot;' => '"', '&#034;'  => '"', '&#x22;' => '"' );
		$double_preg = array( '/&#0*34;/'  => '&#034;', '/&#x0*22;/i' => '&#x22;' );
		$others = array( '&lt;'   => '<', '&#060;'  => '<', '&gt;'   => '>', '&#062;'  => '>', '&amp;'  => '&', '&#038;'  => '&', '&#x26;' => '&' );
		$others_preg = array( '/&#0*60;/'  => '&#060;', '/&#0*62;/'  => '&#062;', '/&#0*38;/'  => '&#038;', '/&#x0*26;/i' => '&#x26;' );

		if ( $quote_style === ENT_QUOTES ) {
			$translation = array_merge( $single, $double, $others );
			$translation_preg = array_merge( $single_preg, $double_preg, $others_preg );
		} elseif ( $quote_style === ENT_COMPAT || $quote_style === 'double' ) {
			$translation = array_merge( $double, $others );
			$translation_preg = array_merge( $double_preg, $others_preg );
		} elseif ( $quote_style === 'single' ) {
			$translation = array_merge( $single, $others );
			$translation_preg = array_merge( $single_preg, $others_preg );
		} elseif ( $quote_style === ENT_NOQUOTES ) {
			$translation = $others;
			$translation_preg = $others_preg;
		}

		// Remove zero padding on numeric entities
		$string = preg_replace( array_keys( $translation_preg ), array_values( $translation_preg ), $string );

		// Replace characters according to translation table
		return strtr( $string, $translation );
	}

	/**
	 * Checks for invalid UTF8 in a string.
	 *
	 * @since 2.8.0
	 *
	 * @staticvar bool $is_utf8
	 * @staticvar bool $utf8_pcre
	 *
	 * @param string  $string The text which is to be checked.
	 * @param bool    $strip Optional. Whether to attempt to strip out invalid UTF8. Default is false.
	 * @return string The checked text.
	 */
	public static function wp_check_invalid_utf8( $string, $strip = false ) {
		$string = (string) $string;

		if ( 0 === strlen( $string ) ) {
			return '';
		}

		// Store the site charset as a static to avoid multiple calls to get_option()
		static $is_utf8 = null;
		if ( ! isset( $is_utf8 ) ) {
			// $is_utf8 = in_array( get_option( 'blog_charset' ), array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ) );
			$is_utf8 = true;
		}
		if ( ! $is_utf8 ) {
			return $string;
		}

		// Check for support for utf8 in the installed PCRE library once and store the result in a static
		static $utf8_pcre = null;
		if ( ! isset( $utf8_pcre ) ) {
			$utf8_pcre = @preg_match( '/^./u', 'a' );
		}
		// We can't demand utf8 in the PCRE installation, so just return the string in those cases
		if ( !$utf8_pcre ) {
			return $string;
		}

		// preg_match fails when it encounters invalid UTF8 in $string
		if ( 1 === @preg_match( '/^./us', $string ) ) {
			return $string;
		}

		// Attempt to strip the bad chars if requested (not recommended)
		if ( $strip && function_exists( 'iconv' ) ) {
			return iconv( 'utf-8', 'utf-8', $string );
		}

		return '';
	}

	/**
	 * Perform a deep string replace operation to ensure the values in $search are no longer present
	 *
	 * Repeats the replacement operation until it no longer replaces anything so as to remove "nested" values
	 * e.g. $subject = '%0%0%0DDD', $search ='%0D', $result ='' rather than the '%0%0DD' that
	 * str_replace would return
	 *
	 * @since 2.8.1
	 * @access private
	 *
	 * @param string|array $search  The value being searched for, otherwise known as the needle.
	 *                              An array may be used to designate multiple needles.
	 * @param string       $subject The string being searched and replaced on, otherwise known as the haystack.
	 * @return string The string with the replaced svalues.
	 */
	private static function _deep_replace( $search, $subject ) {
		$subject = (string) $subject;

		$count = 1;
		while ( $count ) {
			$subject = str_replace( $search, '', $subject, $count );
		}

		return $subject;
	}

	/**
	 * Converts and fixes HTML entities.
	 *
	 * This function normalizes HTML entities. It will convert `AT&T` to the correct
	 * `AT&amp;T`, `&#00058;` to `&#58;`, `&#XYZZY;` to `&amp;#XYZZY;` and so on.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string Content to normalize entities
	 * @return string Content with normalized entities
	 */
	public static function wp_kses_normalize_entities($string) {
		// Disarm all entities by converting & to &amp;
		$string = str_replace('&', '&amp;', $string);

		// Change back the allowed entities in our entity whitelist
		$string = preg_replace_callback('/&amp;([A-Za-z]{2,8}[0-9]{0,2});/', 'self::wp_kses_named_entities', $string);
		$string = preg_replace_callback('/&amp;#(0*[0-9]{1,7});/', 'self::wp_kses_normalize_entities2', $string);
		$string = preg_replace_callback('/&amp;#[Xx](0*[0-9A-Fa-f]{1,6});/', 'self::wp_kses_normalize_entities3', $string);

		return $string;
	}

	/**
	 * Callback for wp_kses_normalize_entities() regular expression.
	 *
	 * This function only accepts valid named entity references, which are finite,
	 * case-sensitive, and highly scrutinized by HTML and XML validators.
	 *
	 * @since 3.0.0
	 *
	 * @global array $allowedentitynames
	 *
	 * @param array $matches preg_replace_callback() matches array
	 * @return string Correctly encoded entity
	 */
	public static function wp_kses_named_entities($matches) {
		global $allowedentitynames;

		if ( empty($matches[1]) )
			return '';

		$i = $matches[1];
		return ( ! in_array( $i, $allowedentitynames ) ) ? "&amp;$i;" : "&$i;";
	}
    
    /**
    * Helper function to determine if a Unicode value is valid.
    *
    * @since 2.7.0
    *
    * @param int $i Unicode value
    * @return bool True if the value was a valid Unicode number
    */
    public static function wp_valid_unicode($i) {
        return ( $i == 0x9 || $i == 0xa || $i == 0xd ||
                ($i >= 0x20 && $i <= 0xd7ff) ||
                ($i >= 0xe000 && $i <= 0xfffd) ||
                ($i >= 0x10000 && $i <= 0x10ffff) );
    }

	/**
	 * Callback for wp_kses_normalize_entities() regular expression.
	 *
	 * This function helps wp_kses_normalize_entities() to only accept 16-bit
	 * values and nothing more for `&#number;` entities.
	 *
	 * @access private
	 * @since 1.0.0
	 *
	 * @param array $matches preg_replace_callback() matches array
	 * @return string Correctly encoded entity
	 */
	public static function wp_kses_normalize_entities2($matches) {
		if ( empty($matches[1]) )
			return '';

		$i = $matches[1];
		if (self::wp_valid_unicode($i)) {
			$i = str_pad(ltrim($i,'0'), 3, '0', STR_PAD_LEFT);
			$i = "&#$i;";
		} else {
			$i = "&amp;#$i;";
		}

		return $i;
	}

	/**
	 * Callback for wp_kses_normalize_entities() for regular expression.
	 *
	 * This function helps wp_kses_normalize_entities() to only accept valid Unicode
	 * numeric entities in hex form.
	 *
	 * @since 2.7.0
	 * @access private
	 *
	 * @param array $matches preg_replace_callback() matches array
	 * @return string Correctly encoded entity
	 */
	public static function wp_kses_normalize_entities3($matches) {
		if ( empty($matches[1]) )
			return '';

		$hexchars = $matches[1];
		return ( ! self::wp_valid_unicode( hexdec( $hexchars ) ) ) ? "&amp;#x$hexchars;" : '&#x'.ltrim($hexchars,'0').';';
	}

	/**
	 * Retrieve a list of protocols to allow in HTML attributes.
	 *
	 * @since 3.3.0
	 * @since 4.3.0 Added 'webcal' to the protocols array.
	 * @since 4.7.0 Added 'urn' to the protocols array.
	 *
	 * @see wp_kses()
	 * @see esc_url()
	 *
	 * @staticvar array $protocols
	 *
	 * @return array Array of allowed protocols. Defaults to an array containing 'http', 'https',
	 *               'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet',
	 *               'mms', 'rtsp', 'svn', 'tel', 'fax', 'xmpp', 'webcal', and 'urn'.
	 */
	public static function wp_allowed_protocols() {
		static $protocols = array();

		if ( empty( $protocols ) ) {
			$protocols = array( 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn' );
		}

		return $protocols;
	}

	/**
	 * Checks and cleans a URL.
	 *
	 * A number of characters are removed from the URL. If the URL is for displaying
	 * (the default behaviour) ampersands are also replaced. The {@see 'clean_url'} filter
	 * is applied to the returned cleaned URL.
	 *
	 * @since 2.8.0
	 *
	 * @param string $url       The URL to be cleaned.
	 * @param array  $protocols Optional. An array of acceptable protocols.
	 *		                    Defaults to return value of wp_allowed_protocols()
	* @param string $_context  Private. Use esc_url_raw() for database usage.
	* @return string The cleaned $url after the {@see 'clean_url'} filter is applied.
	*/
	public static function esc_url( $url, $protocols = null, $_context = 'display' ) {
		$original_url = $url;

		if ( '' == $url )
			return $url;

		$url = str_replace( ' ', '%20', $url );
		$url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url);

		if ( '' === $url ) {
			return $url;
		}

		if ( 0 !== stripos( $url, 'mailto:' ) ) {
			$strip = array('%0d', '%0a', '%0D', '%0A');
			$url = self::_deep_replace($strip, $url);
		}

		$url = str_replace(';//', '://', $url);
		/* If the URL doesn't appear to contain a scheme, we
		* presume it needs http:// prepended (unless a relative
		* link starting with /, # or ? or a php file).
		*/
		if ( strpos($url, ':') === false && ! in_array( $url[0], array( '/', '#', '?' ) ) &&
			! preg_match('/^[a-z0-9-]+?\.php/i', $url) )
			$url = 'http://' . $url;

		// Replace ampersands and single quotes only when displaying.
		if ( 'display' == $_context ) {
			$url = self::wp_kses_normalize_entities( $url );
			$url = str_replace( '&amp;', '&#038;', $url );
			$url = str_replace( "'", '&#039;', $url );
		}

		if ( ( false !== strpos( $url, '[' ) ) || ( false !== strpos( $url, ']' ) ) ) {

			$parsed = wp_parse_url( $url );
			$front  = '';

			if ( isset( $parsed['scheme'] ) ) {
				$front .= $parsed['scheme'] . '://';
			} elseif ( '/' === $url[0] ) {
				$front .= '//';
			}

			if ( isset( $parsed['user'] ) ) {
				$front .= $parsed['user'];
			}

			if ( isset( $parsed['pass'] ) ) {
				$front .= ':' . $parsed['pass'];
			}

			if ( isset( $parsed['user'] ) || isset( $parsed['pass'] ) ) {
				$front .= '@';
			}

			if ( isset( $parsed['host'] ) ) {
				$front .= $parsed['host'];
			}

			if ( isset( $parsed['port'] ) ) {
				$front .= ':' . $parsed['port'];
			}

			$end_dirty = str_replace( $front, '', $url );
			$end_clean = str_replace( array( '[', ']' ), array( '%5B', '%5D' ), $end_dirty );
			$url       = str_replace( $end_dirty, $end_clean, $url );

		}

		if ( '/' === $url[0] ) {
			$good_protocol_url = $url;
		} else {
			if ( ! is_array( $protocols ) )
				$protocols = self::wp_allowed_protocols();
			$good_protocol_url = self::wp_kses_bad_protocol( $url, $protocols );
			if ( strtolower( $good_protocol_url ) != strtolower( $url ) )
				return '';
		}

		/**
		 * Filters a string cleaned and escaped for output as a URL.
		 *
		 * @since 2.3.0
		 *
		 * @param string $good_protocol_url The cleaned URL to be returned.
		 * @param string $original_url      The URL prior to cleaning.
		 * @param string $_context          If 'display', replace ampersands and single quotes only.
		 */
		return $good_protocol_url;
	}

	
	/**
	 * Removes any invalid control characters in $string.
	 *
	 * Also removes any instance of the '\0' string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string
	 * @param array $options Set 'slash_zero' => 'keep' when '\0' is allowed. Default is 'remove'.
	 * @return string
	 */
	public static function wp_kses_no_null( $string, $options = null ) {
		if ( ! isset( $options['slash_zero'] ) ) {
			$options = array( 'slash_zero' => 'remove' );
		}

		$string = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $string );
		if ( 'remove' == $options['slash_zero'] ) {
			$string = preg_replace( '/\\\\+0+/', '', $string );
		}

		return $string;
	}


	/**
	 * Sanitize string from bad protocols.
	 *
	 * This function removes all non-allowed protocols from the beginning of
	 * $string. It ignores whitespace and the case of the letters, and it does
	 * understand HTML entities. It does its work in a while loop, so it won't be
	 * fooled by a string like "javascript:javascript:alert(57)".
	 *
	 * @since 1.0.0
	 *
	 * @param string $string            Content to filter bad protocols from
	 * @param array  $allowed_protocols Allowed protocols to keep
	 * @return string Filtered content
	 */
	public static function wp_kses_bad_protocol($string, $allowed_protocols) {
		$string = self::wp_kses_no_null($string);
		$iterations = 0;

		do {
			$original_string = $string;
			$string = self::wp_kses_bad_protocol_once($string, $allowed_protocols);
		} while ( $original_string != $string && ++$iterations < 6 );

		if ( $original_string != $string )
			return '';

		return $string;
	}

	/**
	 * Sanitizes content from bad protocols and other characters.
	 *
	 * This function searches for URL protocols at the beginning of $string, while
	 * handling whitespace and HTML entities.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string            Content to check for bad protocols
	 * @param string $allowed_protocols Allowed protocols
	 * @return string Sanitized content
	 */
	public static function wp_kses_bad_protocol_once($string, $allowed_protocols, $count = 1 ) {
		$string2 = preg_split( '/:|&#0*58;|&#x0*3a;/i', $string, 2 );
		if ( isset($string2[1]) && ! preg_match('%/\?%', $string2[0]) ) {
			$string = trim( $string2[1] );
			$protocol = self::wp_kses_bad_protocol_once2( $string2[0], $allowed_protocols );
			if ( 'feed:' == $protocol ) {
				if ( $count > 2 )
					return '';
				$string = wp_kses_bad_protocol_once( $string, $allowed_protocols, ++$count );
				if ( empty( $string ) )
					return $string;
			}
			$string = $protocol . $string;
		}

		return $string;
	}

	/**
	 * Convert all entities to their character counterparts.
	 *
	 * This function decodes numeric HTML entities (`&#65;` and `&#x41;`).
	 * It doesn't do anything with other entities like &auml;, but we don't
	 * need them in the URL protocol whitelisting system anyway.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string Content to change entities
	 * @return string Content after decoded entities
	 */
	public static function wp_kses_decode_entities($string) {
		$string = preg_replace_callback('/&#([0-9]+);/', 'self::_wp_kses_decode_entities_chr', $string);
		$string = preg_replace_callback('/&#[Xx]([0-9A-Fa-f]+);/', 'self::_wp_kses_decode_entities_chr_hexdec', $string);

		return $string;
	}

	/**
	 * Regex callback for wp_kses_decode_entities()
	 *
	 * @since 2.9.0
	 *
	 * @param array $match preg match
	 * @return string
	 */
	public static function _wp_kses_decode_entities_chr( $match ) {
		return chr( $match[1] );
	}

	/**
	 * Regex callback for wp_kses_decode_entities()
	 *
	 * @since 2.9.0
	 *
	 * @param array $match preg match
	 * @return string
	 */
	public static function _wp_kses_decode_entities_chr_hexdec( $match ) {
		return chr( hexdec( $match[1] ) );
	}

	/**
	 * Callback for wp_kses_bad_protocol_once() regular expression.
	 *
	 * This function processes URL protocols, checks to see if they're in the
	 * whitelist or not, and returns different data depending on the answer.
	 *
	 * @access private
	 * @since 1.0.0
	 *
	 * @param string $string            URI scheme to check against the whitelist
	 * @param string $allowed_protocols Allowed protocols
	 * @return string Sanitized content
	 */
	public static function wp_kses_bad_protocol_once2( $string, $allowed_protocols ) {
		$string2 = self::wp_kses_decode_entities($string);
		$string2 = preg_replace('/\s/', '', $string2);
		$string2 = self::wp_kses_no_null($string2);
		$string2 = strtolower($string2);

		$allowed = false;
		foreach ( (array) $allowed_protocols as $one_protocol )
			if ( strtolower($one_protocol) == $string2 ) {
				$allowed = true;
				break;
			}

		if ($allowed)
			return "$string2:";
		else
			return '';
	}

	/**
	 * Performs esc_url() for database usage.
	 *
	 * @since 2.8.0
	 *
	 * @param string $url       The URL to be cleaned.
	 * @param array  $protocols An array of acceptable protocols.
	 * @return string The cleaned URL.
	 */
	public static function esc_url_raw( $url, $protocols = null ) {
		return self::esc_url( $url, $protocols, 'db' );
	}

	// SANITIZE Functions
	
	/**
	 * Normalize EOL characters and strip duplicate whitespace.
	 *
	 * @since 2.7.0
	 *
	 * @param string $str The string to normalize.
	 * @return string The normalized string.
	 */
	public static function normalize_whitespace( $str ) {
		$str  = trim( $str );
		$str  = str_replace( "\r", "\n", $str );
		$str  = preg_replace( array( '/\n+/', '/[ \t]+/' ), array( "\n", ' ' ), $str );
		return $str;
	}

	/**
	 * Properly strip all HTML tags including script and style
	 *
	 * This differs from strip_tags() because it removes the contents of
	 * the `<script>` and `<style>` tags. E.g. `strip_tags( '<script>something</script>' )`
	 * will return 'something'. wp_strip_all_tags will return ''
	 *
	 * @since 2.9.0
	 *
	 * @param string $string        String containing HTML tags
	 * @param bool   $remove_breaks Optional. Whether to remove left over line breaks and white space chars
	 * @return string The processed string.
	 */
	public static function wp_strip_all_tags($string, $remove_breaks = false) {
		$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
		$string = strip_tags($string);

		if ( $remove_breaks )
			$string = preg_replace('/[\r\n\t ]+/', ' ', $string);

		return trim( $string );
	}

	/**
	 * Sanitizes a string from user input or from the database.
	 *
	 * - Checks for invalid UTF-8,
	 * - Converts single `<` characters to entities
	 * - Strips all tags
	 * - Removes line breaks, tabs, and extra whitespace
	 * - Strips octets
	 *
	 * @since 2.9.0
	 *
	 * @see sanitize_textarea_field()
	 * @see wp_check_invalid_utf8()
	 * @see wp_strip_all_tags()
	 *
	 * @param string $str String to sanitize.
	 * @return string Sanitized string.
	 */
	public static function sanitize_text_field( $str ) {
		$filtered = self::_sanitize_text_fields( $str, false );

		/**
		 * Filters a sanitized text field string.
		 *
		 * @since 2.9.0
		 *
		 * @param string $filtered The sanitized string.
		 * @param string $str      The string prior to being sanitized.
		 */
		return $filtered;
	}

	/**
	 * Sanitizes a multiline string from user input or from the database.
	 *
	 * The function is like sanitize_text_field(), but preserves
	 * new lines (\n) and other whitespace, which are legitimate
	 * input in textarea elements.
	 *
	 * @see sanitize_text_field()
	 *
	 * @since 4.7.0
	 *
	 * @param string $str String to sanitize.
	 * @return string Sanitized string.
	 */
	public static function sanitize_textarea_field( $str ) {
		$filtered = self::_sanitize_text_fields( $str, true );

		/**
		 * Filters a sanitized textarea field string.
		 *
		 * @since 4.7.0
		 *
		 * @param string $filtered The sanitized string.
		 * @param string $str      The string prior to being sanitized.
		 */
		return $filtered;
	}

	/**
	 * Internal helper function to sanitize a string from user input or from the db
	 *
	 * @since 4.7.0
	 * @access private
	 *
	 * @param string $str String to sanitize.
	 * @param bool $keep_newlines optional Whether to keep newlines. Default: false.
	 * @return string Sanitized string.
	 */
	public static function _sanitize_text_fields( $str, $keep_newlines = false ) {
		$filtered = self::wp_check_invalid_utf8( $str );

		if ( strpos($filtered, '<') !== false ) {
			$filtered = self::wp_pre_kses_less_than( $filtered );
			// This will strip extra whitespace for us.
			$filtered = self::wp_strip_all_tags( $filtered, false );

			// Use html entities in a special case to make sure no later
			// newline stripping stage could lead to a functional tag
			$filtered = str_replace("<\n", "&lt;\n", $filtered);
		}

		if ( ! $keep_newlines ) {
			$filtered = preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
		}
		$filtered = trim( $filtered );

		$found = false;
		while ( preg_match('/%[a-f0-9]{2}/i', $filtered, $match) ) {
			$filtered = str_replace($match[0], '', $filtered);
			$found = true;
		}

		if ( $found ) {
			// Strip out the whitespace that may now exist after removing the octets.
			$filtered = trim( preg_replace('/ +/', ' ', $filtered) );
		}

		return $filtered;
	}

	/**
	 * Convert lone less than signs.
	 *
	 * KSES already converts lone greater than signs.
	 *
	 * @since 2.3.0
	 *
	 * @param string $text Text to be converted.
	 * @return string Converted text.
	 */
	public static function wp_pre_kses_less_than( $text ) {
		return preg_replace_callback('%<[^>]*?((?=<)|>|$)%', array('self', 'wp_pre_kses_less_than_callback'), $text);
	}

	/**
	 * Callback function used by preg_replace.
	 *
	 * @since 2.3.0
	 *
	 * @param array $matches Populated by matches to preg_replace.
	 * @return string The text returned after esc_html if needed.
	 */
	public static function wp_pre_kses_less_than_callback( $matches ) {
		if ( false === strpos($matches[0], '>') )
			return esc_html($matches[0]);
		return $matches[0];
	}
}
?>