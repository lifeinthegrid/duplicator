<?php
/**
 * These functions are performed before including any other Duplicator file so
 * do not use any Duplicator library or feature and use code compatible with PHP 5.2
 *
 */
defined('ABSPATH') || exit;

// In the future it will be included on both PRO and LITE so you need to check if the define exists.
if (!class_exists('DuplicatorPhpVersionCheck')) {

    class DuplicatorPhpVersionCheck
    {

        protected static $minVer       = null;
        protected static $suggestedVer = null;

        public static function check($minVer, $suggestedVer)
        {
            self::$minVer       = $minVer;
            self::$suggestedVer = $suggestedVer;

            if (version_compare(PHP_VERSION, self::$minVer, '<')) {
                if (is_multisite()) {
                    add_action('network_admin_notices', array(__CLASS__, 'notice'));
                } else {
                    add_action('admin_notices', array(__CLASS__, 'notice'));
                }
                return false;
            } else {
                return true;
            }
        }

        public static function notice()
        {
            ?>
            <div class="error notice">
                <p>
                    <?php
                    $str = 'DUPLICATOR: '.__('Your system is running a very old version of PHP (%s) that is no longer supported by Duplicator.  ', 'duplicator');
                    printf($str, PHP_VERSION);
                    
                    $str = __('Please ask your host or server administrator to update to PHP %1s or greater.') . '<br/>';
                    $str .= __('If this is not possible, please visit the FAQ link titled ', 'duplicator');
                    $str .= '<a href="https://snapcreek.com/duplicator/docs/faqs-tech/#faq-licensing-017-q" target="blank">';
                    $str .= __('"What version of PHP Does Duplicator Support?"', 'duplicator');
                    $str .= '</a>';
                    $str .= __(' for instructions on how to download a previous version of Duplicator compatible with PHP %2s.', 'duplicator');
                    printf($str, self::$suggestedVer, PHP_VERSION);
                    ?>
                </p>
            </div>
            <?php
        }
    }
}
