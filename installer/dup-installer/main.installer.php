<?php
/*
 * Duplicator Website Installer
 * Copyright (C) 2018, Snap Creek LLC
 * website: snapcreek.com
 *
 * Duplicator (Pro) Plugin is distributed under the GNU General Public License, Version 3,
 * June 2007. Copyright (C) 2007 Free Software Foundation, Inc., 51 Franklin
 * St, Fifth Floor, Boston, MA 02110, USA
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/** IDE HELPERS */
/* @var $GLOBALS['DUPX_AC'] DUPX_ArchiveConfig */

/** Absolute path to the Installer directory. - necessary for php protection */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

date_default_timezone_set('UTC'); // Some machines don’t have this set so just do it here.

$GLOBALS['DUPX_DEBUG'] = (isset($_GET['debug']) && $_GET['debug'] == 1) ? true : false;
$GLOBALS['DUPX_ROOT']  = str_replace("\\", '/', (realpath(dirname(__FILE__) . '/..')));
$GLOBALS['DUPX_INIT']  = "{$GLOBALS['DUPX_ROOT']}/dup-installer";
$GLOBALS['DUPX_ENFORCE_PHP_INI']  = false;

if (!isset($_GET['archive'])) {
	// RSR TODO: Fail gracefully
	die("Archive parameter not specified");
}

if (!isset($_GET['bootloader'])) {
	// RSR TODO: Fail gracefully
	die("Bootloader parameter not specified");
}

require_once($GLOBALS['DUPX_INIT'].'/lib/snaplib/snaplib.all.php');
require_once($GLOBALS['DUPX_INIT'].'/classes/config/class.constants.php');
require_once($GLOBALS['DUPX_INIT'].'/classes/config/class.archive.config.php');
require_once($GLOBALS['DUPX_INIT'].'/classes/config/class.conf.wp.php');
require_once($GLOBALS['DUPX_INIT'].'/classes/class.installer.state.php');

$GLOBALS['DUPX_AC'] = DUPX_ArchiveConfig::getInstance();
if ($GLOBALS['DUPX_AC'] == null) {
	// RSR TODO: Fail 'gracefully'
	die("Can't initialize config globals");
}

if($GLOBALS["VIEW"] == "step1") {
	$init_state = true;
} else {
	$init_state = false;
}


// TODO: If this is the very first step
$GLOBALS['DUPX_STATE'] = DUPX_InstallerState::getInstance($init_state);
if ($GLOBALS['DUPX_STATE'] == null) {
	// RSR TODO: Fail 'gracefully'
	die("Can't initialize installer state");
}

require_once($GLOBALS['DUPX_INIT'] . '/classes/utilities/class.u.php');
require_once($GLOBALS['DUPX_INIT'] . '/classes/class.db.php');
require_once($GLOBALS['DUPX_INIT'] . '/classes/class.logging.php');
require_once($GLOBALS['DUPX_INIT'] . '/classes/class.http.php');
require_once($GLOBALS['DUPX_INIT'] . '/classes/class.server.php');
require_once($GLOBALS['DUPX_INIT'] . '/classes/config/class.conf.srv.php');
require_once($GLOBALS['DUPX_INIT'] . '/classes/config/class.conf.wp.php');
require_once($GLOBALS['DUPX_INIT'] . '/classes/class.engine.php');

$GLOBALS['_CURRENT_URL_PATH'] = $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$GLOBALS['_HELP_URL_PATH']    = "?view=help&archive={$GLOBALS['FW_PACKAGE_NAME']}&bootloader={$GLOBALS['BOOTLOADER_NAME']}&basic";
$GLOBALS['NOW_TIME']		  = @date("His");

if (!chdir($GLOBALS['DUPX_INIT'])) {
	// RSR TODO: Can't change directories
	echo "Can't change to directory ".$GLOBALS['DUPX_INIT'];
	exit(1);
}

if (isset($_POST['ctrl_action'])) {
	require_once($GLOBALS['DUPX_INIT'].'/ctrls/ctrl.base.php');

	switch ($_POST['ctrl_action']) {
		case "ctrl-step1" :
			require_once($GLOBALS['DUPX_INIT'].'/ctrls/ctrl.s1.php');
			break;

		case "ctrl-step2" :
			require_once($GLOBALS['DUPX_INIT'].'/ctrls/ctrl.s2.dbtest.php');
			require_once($GLOBALS['DUPX_INIT'].'/ctrls/ctrl.s2.dbinstall.php');
			require_once($GLOBALS['DUPX_INIT'].'/ctrls/ctrl.s2.base.php');
			break;

		case "ctrl-step3" :
            require_once($GLOBALS['DUPX_INIT'].'/ctrls/ctrl.s3.php');            
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
	<title>Duplicator</title>
	<link rel='stylesheet' href='assets/font-awesome/css/font-awesome.min.css' type='text/css' media='all' />
	<?php
		require_once($GLOBALS['DUPX_INIT'] . '/assets/inc.libs.css.php');
		require_once($GLOBALS['DUPX_INIT'] . '/assets/inc.css.php');
		require_once($GLOBALS['DUPX_INIT'] . '/assets/inc.libs.js.php');
		require_once($GLOBALS['DUPX_INIT'] . '/assets/inc.js.php');
	?>
</head>
<body>

<div id="content">

<!-- HEADER TEMPLATE: Common header on all steps -->
<table cellspacing="0" class="header-wizard">
	<tr>
		<td style="width:100%;">
			<div class="dupx-branding-header">Duplicator</div>
		</td>
		<td class="wiz-dupx-version">
			version:	<?php echo $GLOBALS['DUPX_AC']->version_dup ?> <br/>
			&raquo; <a href="javascript:void(0)" onclick="DUPX.openServerDetails()">info</a>&nbsp;
			&raquo; <a href="?view=help&archive=<?php echo $GLOBALS['FW_ENCODED_PACKAGE_PATH']?>&bootloader=<?php echo $GLOBALS['BOOTLOADER_NAME']?>&basic" target="_blank">help</a>&nbsp;
			<a href="<?php echo $GLOBALS['_HELP_URL_PATH'];?>" target="_blank"><i class="fa fa-question-circle"></i></a>
		</td>
	</tr>
</table>

<div class="dupx-modes">
	<?php
		$php_enforced_txt = ($GLOBALS['DUPX_ENFORCE_PHP_INI']) ? '<i style="color:red"><br/>*PHP ini enforced*</i>' : '';
		$db_only_txt = ($GLOBALS['DUPX_AC']->exportOnlyDB) ? ' - Database Only' : '';
		$db_only_txt = $db_only_txt . $php_enforced_txt;

		echo  ($GLOBALS['DUPX_STATE']->mode === DUPX_InstallerMode::OverwriteInstall)
			? "<span class='dupx-overwrite'>Mode: Overwrite Install {$db_only_txt}</span>"
			: "Mode: Standard Install {$db_only_txt}";
	?>
</div>

<!-- =========================================
FORM DATA: User-Interface views -->
<div id="content-inner">
	<?php
		switch ($GLOBALS["VIEW"]) {
			case "secure" :
                require_once($GLOBALS['DUPX_INIT'] . '/views/view.init1.php');
				break;

			case "step1"   :
				require_once($GLOBALS['DUPX_INIT'] . '/views/view.s1.base.php');
				break;

			case "step2" :
				require_once($GLOBALS['DUPX_INIT'] . '/views/view.s2.base.php');
				break;

			case "step3" :
				require_once($GLOBALS['DUPX_INIT'] . '/views/view.s3.php');
				break;

			case "step4"   :
				require_once($GLOBALS['DUPX_INIT'] . '/views/view.s4.php');
				break;

			case "help"   :
				require_once($GLOBALS['DUPX_INIT'] . '/views/view.help.php');
				break;

			default :
				echo "Invalid View Requested";
		}
	?>
</div>
</div>


<!-- SERVER INFO DIALOG -->
<div id="dialog-server-details" title="Setup Information" style="display:none">
	<!-- DETAILS -->
	<div class="dlg-serv-info">
		<?php
			$ini_path 		= php_ini_loaded_file();
			$ini_max_time 	= ini_get('max_execution_time');
			$ini_memory 	= ini_get('memory_limit');
			$ini_error_path = ini_get('error_log');
		?>
         <div class="hdr">Server Information</div>
		<label>Try CDN Request:</label> 		<?php echo ( DUPX_U::tryCDN("ajax.aspnetcdn.com", 443) && DUPX_U::tryCDN("ajax.googleapis.com", 443)) ? 'Yes' : 'No'; ?> <br/>
		<label>Web Server:</label>  			<?php echo $_SERVER['SERVER_SOFTWARE']; ?><br/>
        <label>PHP Version:</label>  			<?php echo DUPX_Server::$php_version; ?><br/>
		<label>PHP INI Path:</label> 			<?php echo empty($ini_path ) ? 'Unable to detect loaded php.ini file' : $ini_path; ?>	<br/>
		<label>PHP SAPI:</label>  				<?php echo php_sapi_name(); ?><br/>
		<label>PHP ZIP Archive:</label> 		<?php echo class_exists('ZipArchive') ? 'Is Installed' : 'Not Installed'; ?> <br/>
		<label>PHP max_execution_time:</label>  <?php echo $ini_max_time === false ? 'unable to find' : $ini_max_time; ?><br/>
		<label>PHP memory_limit:</label>  		<?php echo empty($ini_memory)      ? 'unable to find' : $ini_memory; ?><br/>
		<label>Error Log Path:</label>  		<?php echo empty($ini_error_path)      ? 'unable to find' : $ini_error_path; ?><br/>

        <br/>
        <div class="hdr">Package Build Information</div>
        <label>Plugin Version:</label>  		<?php echo $GLOBALS['DUPX_AC']->version_dup; ?><br/>
        <label>WordPress Version:</label>  		<?php echo $GLOBALS['DUPX_AC']->version_wp; ?><br/>
        <label>PHP Version:</label>             <?php echo $GLOBALS['DUPX_AC']->version_php; ?><br/>
        <label>Database Version:</label>        <?php echo $GLOBALS['DUPX_AC']->version_db; ?><br/>
        <label>Operating System:</label>        <?php echo $GLOBALS['DUPX_AC']->version_os; ?><br/>

	</div>
</div>

<script>
DUPX.openServerDetails = function ()
{
	$("#dialog-server-details").dialog({
	  resizable: false,
	  height: "auto",
	  width: 700,
	  modal: true,
	  position: { my: 'top', at: 'top+150' },
	  buttons: {"OK": function() {$(this).dialog("close");} }
	});
}

$(document).ready(function ()
{
	//Disable href for toggle types
	$("a[data-type='toggle']").each(function() {
		$(this).attr('href', 'javascript:void(0)');
	});

});
</script>


<?php if ($GLOBALS['DUPX_DEBUG']) :?>
	<form id="form-debug" method="post" action="?debug=1">
		<input id="debug-view" type="hidden" name="view" />
		<br/><hr size="1" />
		DEBUG MODE ON
		<br/><br/>
		<a href="javascript:void(0)"  onclick="$('#debug-vars').toggle()"><b>PAGE VARIABLES</b></a>
		<pre id="debug-vars"><?php print_r($GLOBALS); ?></pre>
	</form>

	<script>
		DUPX.debugNavigate = function(view)
		{
		//TODO: Write app that captures all ajax requets and logs them to custom console.
		}
	</script>
<?php endif; ?>


<!-- Used for integrity check do not remove:
DUPLICATOR_INSTALLER_EOF -->
</body>
</html>
