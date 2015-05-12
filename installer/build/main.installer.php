<?php
/*
  Copyright 2011-12 Cory Lamle  lifeinthegrid.com

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
  David Coveney of Interconnect IT Ltd
  https://github.com/interconnectit/Search-Replace-DB/
 */

if (file_exists('dtoken.php')) {
    //This is most likely inside the snapshot folder.
    
    //DOWNLOAD ONLY: (Only enable download from within the snapshot directory)
    if (isset($_GET['get']) && isset($_GET['file'])) {
        //Clean the input, strip out anything not alpha-numeric or "_.", so restricts
        //only downloading files in same folder, and removes risk of allowing directory
        //separators in other charsets (vulnerability in older IIS servers), also
        //strips out anything that might cause it to use an alternate stream since
        //that would require :// near the front.
    	$filename = preg_replace('/[^a-zA-Z0-9_.]*/','',$_GET['file']);
    	if (strlen($filename) && file_exists($filename) && (strstr($filename, '_installer.php'))) {
            //Attempt to push the file to the browser
    	    header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=installer.php');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filename));
            //FIXME: We should consider removing all error supression like this
            //as it makes troubleshooting a wild goose chase for times that the
            //script failes on such a line.  The same can and should be accomplished
            //at the server level by turning off displaying errors in PHP.
            @ob_clean();
            @flush();
            if (@readfile($filename) == false) {
                $data = file_get_contents($filename);
                if ($data == false) {
                    die("Unable to read installer file.  The server currently has readfile and file_get_contents disabled on this server.  Please contact your server admin to remove this restriction");
                } else {
                    print $data;
                }
            }
        } else {
            header("HTTP/1.1 404 Not Found", true, 404);
            header("Status: 404 Not Found");
        }
    }

	//Prevent Access from rovers or direct browsing in snapshop directory, or when
    //requesting to download a file, should not go past this point.
    exit;
}
?>

<?php if (false) : ?>
    <!DOCTYPE html>
    <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <title>Error: PHP is not running</title>
        </head>
        <body>
            <h2>Error: PHP is not running</h2>
            <p>Duplicator requires that your web server is running PHP. Your server does not have PHP installed, or PHP is turned off.</p>
        </body>
    </html>
<?php endif; ?> 


<?php
/* ==============================================================================================
ADVANCED FEATURES - Allows admins to perform aditional logic on the import.

$GLOBALS['TABLES_SKIP_COLS']
	Add Known column names of tables you don't want the search and replace logic to run on.

$GLOBALS['REPLACE_LIST']
	Add additional search and replace items to step 2 for the serialize engine.  
	Place directly below $GLOBALS['REPLACE_LIST'] variable below your items
	EXAMPLE:
		array_push($GLOBALS['REPLACE_LIST'], array('search' => 'https://oldurl/',  'replace' => 'https://newurl/'));
		array_push($GLOBALS['REPLACE_LIST'], array('search' => 'ftps://oldurl/',   'replace' => 'ftps://newurl/'));
  ================================================================================================= */

$GLOBALS['FW_TABLEPREFIX'] = '%fwrite_wp_tableprefix%';
$GLOBALS['FW_URL_OLD'] = '%fwrite_url_old%';
$GLOBALS['FW_URL_NEW'] = '%fwrite_url_new%';
$GLOBALS['FW_PACKAGE_NAME'] = '%fwrite_package_name%';
$GLOBALS['FW_PACKAGE_NOTES'] = '%fwrite_package_notes%';
$GLOBALS['FW_SECURE_NAME'] = '%fwrite_secure_name%';
$GLOBALS['FW_DBHOST'] = '%fwrite_dbhost%';
$GLOBALS['FW_DBHOST'] = empty($GLOBALS['FW_DBHOST']) ? 'localhost' : $GLOBALS['FW_DBHOST'];
$GLOBALS['FW_DBNAME'] = '%fwrite_dbname%';
$GLOBALS['FW_DBUSER'] = '%fwrite_dbuser%';
$GLOBALS['FW_DBPASS'] = '%fwrite_dbpass%';
$GLOBALS['FW_SSL_ADMIN'] = '%fwrite_ssl_admin%';
$GLOBALS['FW_SSL_LOGIN'] = '%fwrite_ssl_login%';
$GLOBALS['FW_CACHE_WP'] = '%fwrite_cache_wp%';
$GLOBALS['FW_CACHE_PATH'] = '%fwrite_cache_path%';
$GLOBALS['FW_BLOGNAME'] = '%fwrite_blogname%';
$GLOBALS['FW_WPROOT'] = '%fwrite_wproot%';
$GLOBALS['FW_DUPLICATOR_VERSION'] = '%fwrite_duplicator_version%';
$GLOBALS['FW_OPTS_DELETE'] = json_decode("%fwrite_opts_delete%", true);

//DATABASE SETUP: all time in seconds	
$GLOBALS['DB_MAX_TIME'] = 5000;
$GLOBALS['DB_MAX_PACKETS'] = 268435456;
ini_set('mysql.connect_timeout', '5000');

//PHP SETUP: all time in seconds
ini_set('memory_limit', '5000M');
ini_set("max_execution_time", '5000');
ini_set("max_input_time", '5000');
ini_set('default_socket_timeout', '5000');
@set_time_limit(0);

$GLOBALS['DBCHARSET_DEFAULT'] = 'utf8';
$GLOBALS['DBCOLLATE_DEFAULT'] = 'utf8_general_ci';

//UPDATE TABLE SETTINGS
$GLOBALS['TABLES_SKIP_COLS'] = array('');
$GLOBALS['REPLACE_LIST'] = array();


/* ================================================================================================
  END ADVANCED FEATURES: Do not edit below here.
  =================================================================================================== */

//CONSTANTS
define("DUPLICATOR_INIT", 1); 
define("DUPLICATOR_SSDIR_NAME", 'wp-snapshots');  //This should match DUPLICATOR_SSDIR_NAME in duplicator.php

//SHARED POST PARMS
$_POST['action_step'] = isset($_POST['action_step']) ? $_POST['action_step'] : "1";
$_POST['dbhost']	= isset($_POST['dbhost']) ? trim($_POST['dbhost']) : null;
$_POST['dbuser']	= isset($_POST['dbuser']) ? trim($_POST['dbuser']) : null;
$_POST['dbpass']	= isset($_POST['dbpass']) ? trim($_POST['dbpass']) : null;
$_POST['dbname']	= isset($_POST['dbname']) ? trim($_POST['dbname']) : null;
$_POST['dbcharset'] = isset($_POST['dbcharset'])  ? trim($_POST['dbcharset']) : $GLOBALS['DBCHARSET_DEFAULT'];
$_POST['dbcollate'] = isset($_POST['dbcollate'])  ? trim($_POST['dbcollate']) : $GLOBALS['DBCOLLATE_DEFAULT'];
$_POST['dbport']	= isset($_POST['dbhost'])	  ? parse_url($_POST['dbhost'], PHP_URL_PORT) : 3306;
$_POST['dbport']    = (! empty($_POST['dbport'])) ? $_POST['dbport'] : 3306;

//GLOBALS
$GLOBALS["SQL_FILE_NAME"] = "installer-data.sql";
$GLOBALS["LOG_FILE_NAME"] = "installer-log.txt";
$GLOBALS['SEPERATOR1'] = str_repeat("********", 10);
$GLOBALS['LOGGING'] = isset($_POST['logging']) ? $_POST['logging'] : 1;
$GLOBALS['CURRENT_ROOT_PATH'] = dirname(__FILE__);
$GLOBALS['CHOWN_ROOT_PATH'] = @chmod("{$GLOBALS['CURRENT_ROOT_PATH']}", 0755);
$GLOBALS['CHOWN_LOG_PATH'] = @chmod("{$GLOBALS['CURRENT_ROOT_PATH']}/{$GLOBALS['LOG_FILE_NAME']}", 0644);
$GLOBALS['URL_SSL'] = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') ? true : false;
$GLOBALS['URL_PATH'] = ($GLOBALS['URL_SSL']) ? "https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}" : "http://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}";
$GLOBALS['DB_PORT'] = parse_url($_POST['dbhost'], PHP_URL_PORT);



//Restart log if user starts from step 1
if ($_POST['action_step'] == 1) {
    $GLOBALS['LOG_FILE_HANDLE'] = @fopen($GLOBALS['LOG_FILE_NAME'], "w+");
} else {
    $GLOBALS['LOG_FILE_HANDLE'] = @fopen($GLOBALS['LOG_FILE_NAME'], "a+");
}
?>
	
@@CLASS.LOGGING.PHP@@

@@CLASS.UTILS.PHP@@

@@CLASS.CONFIG.PHP@@

@@CLASS.SERIALIZER.PHP@@

<?php
if (isset($_POST['action_ajax'])) {
    switch ($_POST['action_ajax']) {
        case "1" :
            ?> @@AJAX.STEP1.PHP@@ <?php break;
        case "2" :
            ?> @@AJAX.STEP2.PHP@@ <?php
            break;
    }
    @fclose($GLOBALS["LOG_FILE_HANDLE"]);
    die("");
}
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex,nofollow">
	<title>Wordpress Duplicator</title>
	@@INC.LIBS.CSS.PHP@@	
	@@INC.CSS.PHP@@	
	@@INC.LIBS.JS.PHP@@
	@@INC.JS.PHP@@
</head>
<body>

<div id="content">
	<!-- =========================================
	HEADER TEMPLATE: Common header on all steps -->
	<table cellspacing="0" class="header-wizard">
		<tr>
			<td style="width:100%;">
				<div style="font-size:22px; padding:5px 0px 0px 0px">
					<!-- !!DO NOT CHANGE/EDIT OR REMOVE PRODUCT NAME!!
					If your interested in Private Label Rights please contact us at the URL below to discuss
					customizations to product labeling: http://lifeinthegrid.com	-->
					&nbsp; Duplicator - Installer
				</div>
			</td>
			<td style="white-space:nowrap; text-align:right">
				<select id="dup-hlp-lnk">
					<option value="null"> - Online Resources -</option>
					<option value="http://lifeinthegrid.com/duplicator-docs">&raquo; Knowledge Base</option>
					<option value="http://lifeinthegrid.com/duplicator-guide">&raquo; User Guide</option>
					<option value="http://lifeinthegrid.com/duplicator-faq">&raquo; Common FAQs</option>
					<option value="http://lifeinthegrid.com/duplicator-hosts">&raquo; Approved Hosts</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				
				<?php if (isset($_GET['help'])) :?>
				<div style="margin:4px 0px 10px 15px; font-size:18px">
					Help Overview
				</div>
				<?php else : ?>
					<?php
					$step1CSS = ($_POST['action_step'] <= 1) ? "active-step" : "complete-step";
					$step2CSS = ($_POST['action_step'] == 2) ? "active-step" : "";

					$step3CSS = "";
					if ($_POST['action_step'] == 3) {
						$step2CSS = "complete-step";
						$step3CSS = "active-step";
					}
					?>
					<div id="dup-wiz">
						<div id="dup-wiz-steps">
							<div class="<?php echo $step1CSS; ?>"><a><span>1</span> Deploy</a></div>
							<div class="<?php echo $step2CSS; ?>"><a><span>2</span> Update </a></div>
							<div class="<?php echo $step3CSS; ?>"><a><span>3</span> Test </a></div>
						</div>
					</div>
				<?php endif; ?>

			</td>
			<td style="white-space:nowrap">

				<i style='font-size:11px; color:#999'>
					version: <?php echo $GLOBALS['FW_DUPLICATOR_VERSION'] ?>&nbsp;&nbsp;<a href="installer.php?help=1" target="_blank">[Help]</a>
				</i> &nbsp;
				
			</td>
		</tr>
	</table>	

	<!-- =========================================
	FORM DATA: Data Steps -->
	<div id="content-inner">
<?php

if (! isset($_GET['help'])) {
switch ($_POST['action_step']) {
	case "1" :
	?> @@VIEW.STEP1.PHP@@ <?php
	break;
	case "2" :
	?> @@VIEW.STEP2.PHP@@ <?php
	break;
	case "3" :
	?> @@VIEW.STEP3.PHP@@ <?php
	break;
}
} else {
	?> @@VIEW.HELP.PHP@@ <?php
}
	
?>
	</div>
</div><br/>


</body>
</html>