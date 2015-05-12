<?php

/*
  Plugin Name: Duplicator
  Plugin URI: http://www.lifeinthegrid.com/duplicator/
  Description: Create a backup of your WordPress files and database. Duplicate and move an entire site from one location to another in a few steps. Create a full snapshot of your site at any point in time.
  Version: 0.5.13
  Author: LifeInTheGrid
  Author URI: http://www.lifeinthegrid.com
  License: GPLv2 or later
 */

/* ================================================================================ 
  Copyright 2011-2013  Cory Lamle

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

  SOURCE CONTRIBUTORS:
  Gaurav Aggarwal
  Jonathan Foote
  ================================================================================ */

require_once("define.php");

if (is_admin() == true) {

    require_once 'classes/logging.php';
    require_once 'classes/utility.php';
    require_once 'classes/ui.php';
    require_once 'classes/settings.php';
    require_once 'classes/server.php';
    require_once 'classes/package.php';
    require_once 'views/actions.php';

    /* ACTIVATION 
      Only called when plugin is activated */
    function duplicator_activate() {

        global $wpdb;

        //Only update database on version update
        if (DUPLICATOR_VERSION != get_option("duplicator_version_plugin")) {
            $table_name = $wpdb->prefix . "duplicator_packages";

            //PRIMARY KEY must have 2 spaces before for dbDelta to work
            $sql = "CREATE TABLE `{$table_name}` (
			   `id`			BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT  PRIMARY KEY,
			   `name`		VARCHAR(250)	NOT NULL,
			   `hash`		VARCHAR(50)		NOT NULL,
			   `status`		INT(11)			NOT NULL,
			   `created`	DATETIME		NOT NULL DEFAULT '0000-00-00 00:00:00',
			   `owner`		VARCHAR(60)		NOT NULL,
			   `package`	MEDIUMBLOB		NOT NULL,
			    KEY `hash` (`hash`))";

            require_once(DUPLICATOR_WPROOTPATH . 'wp-admin/includes/upgrade.php');
            @dbDelta($sql);
        }

        //WordPress Options Hooks
        update_option('duplicator_version_plugin', DUPLICATOR_VERSION);

        //Setup All Directories
        DUP_Util::InitSnapshotDirectory();
    }

    /* UPDATE 
      register_activation_hook is not called when a plugin is updated
      so we need to use the following function */
    function duplicator_update() {
        if (DUPLICATOR_VERSION != get_option("duplicator_version_plugin")) {
            duplicator_activate();
        }
        load_plugin_textdomain('wpduplicator', FALSE, dirname(plugin_basename(__FILE__)) . '/lang/');
    }

    /* DEACTIVATION / UNINSTALL 
     * Only called when plugin is deactivated.
     * For uninstall see uninstall.php */
    function duplicator_deactivate() {
        //No actions needed yet
    }

    /* META LINK ADDONS
      Adds links to the plugins manager page */
    function duplicator_meta_links($links, $file) {
        $plugin = plugin_basename(__FILE__);
        // create link
        if ($file == $plugin) {
            $links[] = '<a href="admin.php?page=duplicator-help" title="' . __('Get Help', 'wpduplicator') . '" >' . __('Help', 'wpduplicator') . '</a>';
            $links[] = '<a href="admin.php?page=duplicator-about" title="' . __('Support the Plugin', 'wpduplicator') . '">' . __('About', 'wpduplicator') . '</a>';
            return $links;
        }
        return $links;
    }

    //HOOKS 
    register_activation_hook(__FILE__, 'duplicator_activate');
    register_deactivation_hook(__FILE__, 'duplicator_deactivate');

    //ACTIONS
    add_action('plugins_loaded', 'duplicator_update');
    add_action('admin_init', 'duplicator_init');
    add_action('admin_menu', 'duplicator_menu');
    add_action('wp_ajax_duplicator_package_scan', 'duplicator_package_scan');
    add_action('wp_ajax_duplicator_package_build', 'duplicator_package_build');
    add_action('wp_ajax_duplicator_package_delete', 'duplicator_package_delete');
    add_action('wp_ajax_duplicator_package_report', 'duplicator_package_report');
    add_action('wp_ajax_DUP_UI_SaveViewStateByPost', array('DUP_UI', 'SaveViewStateByPost'));
    add_action('admin_notices', array('DUP_UI', 'ShowReservedFilesNotice'));
    add_action('plugins_loaded', 'duplicator_wpfront_integrate');

    //FILTERS
    add_filter('plugin_action_links', 'duplicator_manage_link', 10, 2);
    add_filter('plugin_row_meta', 'duplicator_meta_links', 10, 2);

    function duplicator_wpfront_integrate() {
        if (DUP_Settings::Get('wpfront_integrate')) {
            do_action('wpfront_user_role_editor_duplicator_init', array('export', 'manage_options', 'read'));
        }
    }

    /**
     *  DUPLICATOR_INIT
     *  Init routines  */
    function duplicator_init() {
        /* CSS */
        wp_register_style('jquery-ui', DUPLICATOR_PLUGIN_URL . 'assets/css/jquery-ui.css', null, "1.11.2");
        wp_register_style('font-awesome', DUPLICATOR_PLUGIN_URL . 'assets/css/font-awesome.min.css', null, '4.1.0');
        wp_register_style('duplicator_style', DUPLICATOR_PLUGIN_URL . 'assets/css/style.css', null, DUPLICATOR_VERSION);
        /* JS */
        wp_register_script('parsley', DUPLICATOR_PLUGIN_URL . 'assets/js/parsley-standalone.min.js', array('jquery'), '1.1.18');
    }

    //PAGE VIEWS
    function duplicator_get_menu() {
        $current_page = isset($_REQUEST['page']) ? esc_html($_REQUEST['page']) : 'duplicator';
        switch ($current_page) {
            case 'duplicator': include('views/packages/controller.php');
                break;
            case 'duplicator-settings': include('views/settings/controller.php');
                break;
            case 'duplicator-tools': include('views/tools/controller.php');
                break;
            case 'duplicator-help': include('views/help/help.php');
                break;
            case 'duplicator-about': include('views/help/about.php');
                break;
        }
    }

    /**
     *  DUPLICATOR_MENU
     *  Loads the menu item into the WP tools section and queues the actions for only this plugin */
    function duplicator_menu() {
        $wpfront_caps_translator = 'wpfront_user_role_editor_duplicator_translate_capability';

        //Main Menu
        $perms = 'export';
        $perms = apply_filters($wpfront_caps_translator, $perms);
        $main_menu = add_menu_page('Duplicator Plugin', 'Duplicator', $perms, 'duplicator', 'duplicator_get_menu', plugins_url('duplicator/assets/img/create.png'));

        $perms = 'export';
        $perms = apply_filters($wpfront_caps_translator, $perms);
        $page_packages = add_submenu_page('duplicator', __('Packages', 'wpduplicator'), __('Packages', 'wpduplicator'), $perms, 'duplicator', 'duplicator_get_menu');

        $perms = 'manage_options';
        $perms = apply_filters($wpfront_caps_translator, $perms);
        $page_settings = add_submenu_page('duplicator', __('Settings', 'wpduplicator'), __('Settings', 'wpduplicator'), $perms, 'duplicator-settings', 'duplicator_get_menu');

        $perms = 'manage_options';
        $perms = apply_filters($wpfront_caps_translator, $perms);
        $page_tools = add_submenu_page('duplicator', __('Tools', 'wpduplicator'), __('Tools', 'wpduplicator'), $perms, 'duplicator-tools', 'duplicator_get_menu');

        $perms = 'read';
        $perms = apply_filters($wpfront_caps_translator, $perms);
        $page_help = add_submenu_page('duplicator', __('Help', 'wpduplicator'), __('Help', 'wpduplicator'), $perms, 'duplicator-help', 'duplicator_get_menu');

        $perms = 'read';
        $perms = apply_filters($wpfront_caps_translator, $perms);
        $page_about = add_submenu_page('duplicator', __('About', 'wpduplicator'), __('About', 'wpduplicator'), $perms, 'duplicator-about', 'duplicator_get_menu');

        //Apply Scripts
        add_action('admin_print_scripts-' . $page_packages, 'duplicator_scripts');
        add_action('admin_print_scripts-' . $page_settings, 'duplicator_scripts');
        add_action('admin_print_scripts-' . $page_help, 'duplicator_scripts');
        add_action('admin_print_scripts-' . $page_tools, 'duplicator_scripts');
        add_action('admin_print_scripts-' . $page_about, 'duplicator_scripts');

        //Apply Styles
        add_action('admin_print_styles-' . $page_packages, 'duplicator_styles');
        add_action('admin_print_styles-' . $page_settings, 'duplicator_styles');
        add_action('admin_print_styles-' . $page_help, 'duplicator_styles');
        add_action('admin_print_styles-' . $page_tools, 'duplicator_styles');
        add_action('admin_print_styles-' . $page_about, 'duplicator_styles');
    }

    /**
     *  DUPLICATOR_SCRIPTS
     *  Loads the required javascript libs only for this plugin  */
    function duplicator_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-progressbar');
        wp_enqueue_script('parsley');
    }

    /**
     *  DUPLICATOR_STYLES
     *  Loads the required css links only for this plugin  */
    function duplicator_styles() {
        wp_enqueue_style('jquery-ui');
        wp_enqueue_style('duplicator_style');
        wp_enqueue_style('font-awesome');
    }

    /**
     *  DUPLICATOR_MANAGE_LINK
     *  Adds the manage link in the plugins list */
    function duplicator_manage_link($links, $file) {
        static $this_plugin;
        if (!$this_plugin)
            $this_plugin = plugin_basename(__FILE__);

        if ($file == $this_plugin) {
            $settings_link = '<a href="admin.php?page=duplicator">' . __("Manage", 'wpduplicator') . '</a>';
            array_unshift($links, $settings_link);
        }
        return $links;
    }

}
?>