<?php
/**
 * Class that collects the functions of initial checks on the requirements to run the plugin
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2
 *
 * @package Duplicator
 * @copyright (c) 2021, Snapcreek LLC
 *
 */

namespace Duplicator\Lite;

defined('ABSPATH') || exit;

class Requirements
{
    const DUP_PRO_PLUGIN_KEY = 'duplicator-pro/duplicator-pro.php';

    /**
     * 
     * @var string // current plugin file full path
     */
    protected static $pluginFile = '';

    /**
     * 
     * @var string // message on deactivation
     */
    protected static $deactivationMessage = '';

    /**
     * This function checks the requirements to run Duplicator.
     * At this point WordPress is not yet completely initialized so functionality is limited.
     * It need to hook into "admin_init" to get the full functionality of WordPress.
     * 
     * @param string $pluginFile    // main plugin file path
     * @return boolean              // true if plugin can be executed
     */
    public static function canRun($pluginFile)
    {
        $result           = true;
        self::$pluginFile = $pluginFile;

        if ($result === true && is_multisite()) {
            /* Deactivation of the plugin disabled in favor of a notification for the next version
             * Uncomment this to enable the logic. 
             * 
              add_action('admin_init', array(__CLASS__, 'addMultisiteNotice'));
              self::$deactivationMessage = __('Can\'t enable Duplicator LITE in a multisite installation', 'duplicator');
              $result                    = false;
             */

            // TEMP WARNING NOTICE, remove this when the deactiovation logic is enable
            add_action('admin_init', array(__CLASS__, 'addTempWarningMultisiteNotice'));
        }


        if ($result === true && self::isPluginActive(self::DUP_PRO_PLUGIN_KEY)) {
            /* Deactivation of the plugin disabled in favor of a notification for the next version
             * Uncomment this to enable the logic. */
              add_action('admin_init', array(__CLASS__, 'addProEnableNotice'));
              self::$deactivationMessage = __('The "Duplicator Lite" and "Duplicator Pro" plugins cannot both be active at the same time.', 'duplicator') . '<br/>'
                  . __('Please deactivate one of them, then reactivate either Lite or Pro from the ', 'duplicator')
                  ."<a href='plugins.php'>" . __('plugins page', 'duplicator') . ".</a>";
              $result = false;
        }

        if ($result === false) {
            register_activation_hook($pluginFile, array(__CLASS__, 'deactivateOnActivation'));
        }

        return $result;
    }

    /**
     * 
     * @param string $plugin
     * @return boolean // return true if plugin key is active and plugin file exists
     */
    protected static function isPluginActive($plugin)
    {
        $isActive = false;
        if (in_array($plugin, (array) get_option('active_plugins', array()))) {
            $isActive = true;
        }

        if (is_multisite()) {
            $plugins = get_site_option('active_sitewide_plugins');
            if (isset($plugins[$plugin])) {
                $isActive = true;
            }
        }

        return ($isActive && file_exists(WP_PLUGIN_DIR . '/' . $plugin));
    }

    /**
     * display admin notice only if user can manage plugins.
     */
    public static function addProEnableNotice()
    {
        if (current_user_can('activate_plugins')) {
            add_action('admin_notices', array(__CLASS__, 'proEnabledNotice'));
        }
    }

    /**
     * display admin notice 
     */
    public static function addMultisiteNotice()
    {
        if (current_user_can('activate_plugins')) {
            add_action('admin_notices', array(__CLASS__, 'multisiteNotice'));
        }
    }

    public static function addTempWarningMultisiteNotice()
    {
        if (current_user_can('activate_plugins') && !is_plugin_active_for_network(plugin_basename(self::$pluginFile))  && false === get_option(\DUP_UI_Notice::OPTION_KEY_IS_MU_NOTICE_DISMISSED, false)) {
            add_action('admin_notices', array(__CLASS__, 'tempWarningMultisiteNotice'));
        }
    }

    /**
     * deactivate current plugin on activation
     */
    public static function deactivateOnActivation()
    {
        deactivate_plugins(plugin_basename(self::$pluginFile));
        wp_die(self::$deactivationMessage);
    }

    /**
     * Display admin notice if duplicator pro is enabled
     */
    public static function proEnabledNotice()
    {
        ?>
        <div class="error notice">
            <p>
                <?php
                    echo '<span class="dashicons dashicons-warning"></span>&nbsp;';
                    echo '<b>' . __('Duplicator Notice:', 'duplicator') . '</b>&nbsp; ';
                    echo __('The "Duplicator Lite" and "Duplicator Pro" plugins cannot both be active at the same time.  ', 'duplicator');
                    echo '</br>';
                    echo __('To use "Duplicator Lite" please deactivate "Duplicator Pro" from the ', 'duplicator');
                    echo "<a href='plugins.php'>" . __('plugins page', 'duplicator') . ".</a>";
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Display admin notice if duplicator pro is enabled
     */
    public static function multisiteNotice()
    {
        ?>
        <div class="error notice">
            <p>
                <?php
                echo 'DUPLICATOR LITE: ' . __('Duplicator LITE can\'t work on multisite installation.', 'duplicator');
                ?>
            </p>
            <p>
                <?php
                echo __('The PRO version also works in multi-site installations.', 'duplicator');
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Display admin notice if duplicator pro is enabled
     */
    public static function tempWarningMultisiteNotice()
    {
        ?>
        <div class="notice notice-warning duplicator-admin-notice is-dismissible" data-to-dismiss="<?php echo \DUP_UI_Notice::OPTION_KEY_IS_MU_NOTICE_DISMISSED;?>">
            <p>
                <?php
                echo '<i class="fas fa-exclamation-circle"></i> ' . __('NOTICE: Duplicator Lite cannot be activated individually in the sub-sites of a multisite installation but must be activated only on the network. '
                    . 'Please deactivate Duplicator Lite at this site and activate it on the network.', 'duplicator');
                ?>
            </p>
        </div>
        <?php
    }
}
