<?php
// Exit if accessed directly
if (! defined('DUPLICATOR_INIT')) {
	$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	$_baseURL =  "http://" . $_baseURL;
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $_baseURL");
	exit; 
}

/** * *****************************************************
 * DUPX_Config 
 * Class used to update and edit web server configuration files  */

class DUPX_Config {
	

    /** 
     *  Clear .htaccess and web.config files and backup
     */
    static public function Reset() {
		
		DUPX_Log::Info("\nWEB SERVER CONFIGURATION FILE RESET:");

		//Apache
		@copy('.htaccess', '.htaccess.orig');
		@unlink('.htaccess');
		//IIS
		@copy('web.config', 'web.config.orig');
		@unlink('web.config');

		DUPX_Log::Info("- Backup of .htaccess/web.config made to .orig");
		DUPX_Log::Info("- Reset of .htaccess/web.config files");
		$tmp_htaccess = '# RESET FOR DUPLICATOR INSTALLER USEAGE';
		file_put_contents('.htaccess', $tmp_htaccess);
		@chmod('.htaccess', 0644);
    }
	
	
	    /** METHOD: ResetHTACCESS
     *  Resetst the .htaccess file
     */
    static public function Setup() {
		
		if (! isset($_POST['url_new'])) {
			return;
		}
		
		DUPX_Log::Info("\nWEB SERVER CONFIGURATION FILE BASIC SETUP:");
		$currdata = parse_url($_POST['url_old']);
		$newdata  = parse_url($_POST['url_new']);
		$currpath = DupUtil::add_slash(isset($currdata['path']) ? $currdata['path'] : "");
		$newpath  = DupUtil::add_slash(isset($newdata['path'])  ? $newdata['path'] : "");

		$tmp_htaccess = <<<HTACCESS
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase {$newpath}
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . {$newpath}index.php [L]
</IfModule>
# END WordPress
HTACCESS;

		file_put_contents('.htaccess', $tmp_htaccess);
		@chmod('.htaccess', 0644);
		DUPX_Log::Info("created basic .htaccess file.  If using IIS web.config this process will need to be done manually.");

    }
	
	
}
?>
