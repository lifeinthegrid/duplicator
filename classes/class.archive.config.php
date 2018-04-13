<?php
defined("ABSPATH") or die("");

/**
 * @copyright 2016 Snap Creek LLC
 */
class DUP_Archive_Config
{
    //READ-ONLY: COMPARE VALUES
    public $created;
    public $version_dup;
    public $version_wp;
    public $version_db;
    public $version_php;
    public $version_os;
    public $dbInfo;
    //READ-ONLY: GENERAL
    public $url_old;
    public $opts_delete;
    public $blogname;
    public $wproot;
    public $wplogin_url;
	public $relative_content_dir;
	public $exportOnlyDB;

    //PRE-FILLED: GENERAL
    public $secure_on;
    public $secure_pass;
    public $skipscan;
    public $dbhost;
    public $dbname;
    public $dbuser;
    public $dbpass;
    public $cache_wp;
    public $cache_path;
    //PRE-FILLED: CPANEL
    public $cpnl_dbname;
    public $cpnl_host;
    public $cpnl_user;
    public $cpnl_pass;
    public $cpnl_enable;
    public $cpnl_connect;
    public $cpnl_dbaction;
    public $cpnl_dbhost;
    public $cpnl_dbuser;
    
    public $wp_tableprefix;

    //MISC
    public $license_limit;
	public $plugin_type;
    public $plugin_name;
    //BRAND
    public $brand;

    function __construct()
    {
        $this->subsites = array();
    }
}
