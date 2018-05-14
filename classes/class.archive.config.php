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
    
    // MULTISITE
    public $mu_mode;
    
    public $wp_tableprefix;
}
