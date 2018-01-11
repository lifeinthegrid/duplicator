<?php
defined("ABSPATH") or die("");
/** IDE HELPERS */
/* @var $GLOBALS['DUPX_AC'] DUPX_ArchiveConfig */
?>

<!-- =========================================
C-PANEL PANEL -->
<?php if (! $cpnl_supported) :?>	
	<div class='s2-cpnl-panel-no-support'>
		cPanel Requires PHP 5.3+ <br/>
		Please contact your host to upgrade this server.
	</div>
<?php else: ?>

	<div class="hdr-sub1 toggle-hdr" data-type="toggle" data-target="#s2-cpnl-area">
		<a href="javascript:void(0)"><i class="fa fa-minus-square"></i> cPanel Login: </a>
		<a id="s2-cpnl-status-msg" href="javascript:void(0)" onclick="$('#s2-cpnl-status-details').toggle()"></a>
	</div>

	<div id="s2-cpnl-area">
		<table class="dupx-opts">
			<tr>
				<td>Host:</td>
				<td>
					<input type="text" name="cpnl-host" id="cpnl-host" required="true" value="<?php echo $GLOBALS['DUPX_AC']->cpnl_host; ?>" placeholder="cPanel url" />
					 <a id="cpnl-host-get-lnk" href="javascript:DUPX.getcPanelURL('cpnl-host')" style="font-size:12px">get</a>
					<div id="cpnl-host-warn">
						Caution: The cPanel host name and URL in the browser address bar do not match, in rare cases this may be intentional.
						Please be sure this is the correct server to avoid data loss.
					</div>
				</td>
			</tr>
			<tr>
				<td>Username:</td>
				<td><input type="text" name="cpnl-user" id="cpnl-user" required="true" data-parsley-type="alphanum" value="<?php echo htmlspecialchars($GLOBALS['DUPX_AC']->cpnl_user); ?>" placeholder="cPanel username" /></td>
			</tr>
			<tr><td>Password:</td><td><input type="text" name="cpnl-pass" id="cpnl-pass" value="<?php echo htmlspecialchars($GLOBALS['DUPX_AC']->cpnl_pass); ?>"  placeholder="cPanel password" required="true" /></td></tr>
		</table>

		<div id="s2-cpnl-connect">
			<input type="button" id="s2-cpnl-connect-btn" class="default-btn" onclick="DUPX.cpnlConnect()" value="Connect" />
			<input type="button" id="s2-cpnl-change-btn" onclick="DUPX.cpnlToggleLogin()" value="Change" class="default-btn"  style="display:none" />
			<div id="s2-cpnl-status-details" style="display:none">
				<div id="s2-cpnl-status-details-msg">
					Please click the connect button to connect to your cPanel.
				</div>
				<small style="font-style: italic">
					<a href="javascript:void()" onclick="$('#s2-cpnl-status-details').hide()">[Hide Message]</a> &nbsp;
					<a href='https://snapcreek.com/wordpress-hosting/' target='_blank'>[cPanel Supported Hosts]</a>
				</small>
			</div>
		</div>
	</div><br/><br/>
			
	<!-- =========================================
	CPNL DB SETUP -->
	<div class="hdr-sub1 toggle-hdr" data-type="toggle" data-target="#s2-cpnl-db-opts">
		<a href="javascript:void(0)"><i class="fa fa-minus-square"></i>Setup</a>
		<span id="s2-cpnl-db-opts-lbl">cPanel login required to enable</span>
	</div>

	<input type="hidden" name="cpnl-dbname-result" id="cpnl-dbname-result" />
	<input type="hidden" name="cpnl-dbuser-result" id="cpnl-dbuser-result" />
	<table id="s2-cpnl-db-opts" class="dupx-opts">
		<tr>
			<td>Action:</td>
			<td>
				<select name="cpnl-dbaction" id="cpnl-dbaction">
					<option value="create">Create New Database</option>
					<option value="empty">Connect and Remove All Data</option>
					<option value="rename">Connect and Backup Any Existing Data</option>
					<option value="manual">Manual SQL Execution (Advanced)</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>Host:</td>
			<td><input type="text" name="cpnl-dbhost" id="cpnl-dbhost" required="true" value="<?php echo htmlspecialchars($GLOBALS['DUPX_AC']->cpnl_dbhost); ?>" placeholder="localhost" /></td>
		</tr>
		<tr>
			<td>Database:</td>
			<td>
				<!-- EXISTING CPNL DB -->
				<div id="s2-cpnl-dbname-area1">
					<select name="cpnl-dbname-select" id="cpnl-dbname-select" required="true" data-parsley-pattern="^((?!-- Select Database --).)*$"></select>
					<div class="s2-warning-emptydb">
						Warning: The selected 'Action' above will remove <u>all data</u> from this database!
					</div>
				</div>
				<!-- NEW CPNL DB -->
				<div id="s2-cpnl-dbname-area2">
					<table>
						<tr>
							<td id="cpnl-prefix-dbname"></td>
							<td>
								<input type="text" name="cpnl-dbname-txt" id="cpnl-dbname-txt" required="true" data-parsley-pattern="/^[a-zA-Z0-9-_]+$/"
									   data-parsley-errors-container="#cpnl-dbname-txt-error"
									   value="<?php echo htmlspecialchars($GLOBALS['DUPX_AC']->cpnl_dbname); ?>"
									   placeholder="new or existing database name"  />
							</td>
						</tr>
					</table>
					<div id="cpnl-dbname-txt-error"></div>
				</div>
				<div class="s2-warning-renamedb">
					Notice: The selected 'Action' will rename <u>all existing tables</u> from the database name above with a prefix '<?php echo $GLOBALS['DB_RENAME_PREFIX']; ?>'.
					The prefix is only applied to existing tables and not the new tables that will be installed.
				</div>
				<div class="s2-warning-manualdb">
					Notice: The 'Manual SQL execution' action will prevent the SQL script in the archive from running. The database name above should already be
					pre-populated with data which will be updated in the next step.  No data in the database will be modified until after Step 2 runs.
				</div>
			</td>
		</tr>
		<tr>
			<td></td>
			<td><input type="checkbox" name="cpnl-dbuser-chk" id="cpnl-dbuser-chk" value="1" style="margin-left:5px" /> <label for="cpnl-dbuser-chk">Create New Database User</label> </td>
		</tr>
		<tr>
			<td>User:</td>
			<td>
				<div id="s2-cpnl-dbuser-area1">
					<select name="cpnl-dbuser-select" id="cpnl-dbuser-select" required="true" data-parsley-pattern="^((?!-- Select User --).)*$"></select>
				</div>
				<div id="s2-cpnl-dbuser-area2">
					<table>
						<tr>
							<td id="cpnl-prefix-dbuser"></td>
							<td>
								<input type="text" name="cpnl-dbuser-txt" id="cpnl-dbuser-txt" required="true" data-parsley-pattern="/^[a-zA-Z0-9-_]+$/"
									   data-parsley-errors-container="#cpnl-dbuser-txt-error" data-parsley-cpnluser="16"
									   value="<?php echo htmlspecialchars($GLOBALS['DUPX_AC']->cpnl_dbuser); ?>" placeholder="valid database username" />
							</td>
						</tr>
					</table>
					<div id="cpnl-dbuser-txt-error"></div>
				</div>
			</td>
		</tr>
		<tr><td>Password:</td><td><input type="text" name="cpnl-dbpass" id="cpnl-dbpass" required="true" placeholder="valid database user password" /></td></tr>

	</table>
	<br/><br/>

	<!-- =========================================
	OPTIONS -->
	<div class="hdr-sub1 toggle-hdr" id="s2-opts-hdr-cpnl" data-type="toggle" data-target="#s2-adv-opts">
		<a href="javascript:void(0)"><i class="fa fa-plus-square"></i>Options</a>
	</div>
	<div id='s2-adv-opts' class="s2-opts" style="display:none;padding-top:0">
		<div class="help-target"><a href="<?php echo $GLOBALS['_HELP_URL_PATH'];?>#help-s2" target="help">
			<i class="fa fa-question-circle"></i></a>
		</div>

		<table class="dupx-opts dupx-advopts">
			<tr>
				<td>Prefix:</td>
				<td>
					<input type="checkbox" name="cpnl_ignore_prefix"  id="cpnl_ignore_prefix" value="1" onclick="DUPX.cpnlPrefixIgnore()" />
					<label for="cpnl_ignore_prefix">Ignore cPanel Prefix</label>
				</td>
			</tr>
			<tr>
				<td>Legacy:</td>
				<td>
					<input type="checkbox" name="cpnl-dbcollatefb" id="cpnl-dbcollatefb" value="1" />
					<label for="cpnl-dbcollatefb">Apply legacy collation fallback support for unknown collations types</label>
				</td>
			</tr>
            <tr style="display:none;">
                <td>Memory:</td>
                <td><input type="checkbox" name="cpnl-dbchunk" id="cpnl-dbchunk" value="1" /> <label for="cpnl-dbchunk">Stream the sql file in chunks</label></td>
            </tr>
			<tr>
				<td>Spacing:</td>
				<td><input type="checkbox" name="cpnl-dbnbsp" id="cpnl-dbnbsp" value="1" /> <label for="cpnl-dbnbsp">Fix non-breaking space characters</label></td>
			</tr>
			<tr>
			<td style="vertical-align:top">Mode:</td>
				<td>
					<input type="radio" name="cpnl-dbmysqlmode" id="cpnl-dbmysqlmode_1" checked="true" value="DEFAULT"/> <label for="cpnl-dbmysqlmode_1">Default</label> &nbsp;
					<input type="radio" name="cpnl-dbmysqlmode" id="cpnl-dbmysqlmode_2" value="DISABLE"/> <label for="cpnl-dbmysqlmode_2">Disable</label> &nbsp;
					<input type="radio" name="cpnl-dbmysqlmode" id="cpnl-dbmysqlmode_3" value="CUSTOM"/> <label for="cpnl-dbmysqlmode_3">Custom</label> &nbsp;
					<div id="cpnl-dbmysqlmode_3_view" style="display:none; padding:5px">
						<input type="text" name="cpnl-dbmysqlmode_opts" value="" /><br/>
						<small>Separate additional <a href="<?php echo $GLOBALS['_HELP_URL_PATH'];?>#help-mysql-mode" target="_blank">sql modes</a> with commas &amp; no spaces.<br/>
							Example: <i>NO_ENGINE_SUBSTITUTION,NO_ZERO_IN_DATE,...</i>.</small>
					</div>
				</td>
			</tr>

		</table>

		<table class="dupx-opts dupx-advopts">
			<tr>
				<td style="width:130px">Objects:</td>
				<td>
					<input type="checkbox" name="cpnl-dbobj_views" id="cpnl-dbobj_views" checked="true" />
					<label for="cpnl-dbobj_views">Enable View Creation</label>
				</td>
			</tr>
			<tr>
				<td></td>
				<td>
					<input type="checkbox" name="cpnl-dbobj_procs" id="cpnl-dbobj_procs" checked="true" />
					<label for="cpnl-dbobj_procs">Enable Stored Procedure Creation</label>
				</td>
			</tr>
			<tr><td>Charset:</td><td><input type="text" name="cpnl-dbcharset" id="cpnl-dbcharset" value="<?php echo $_POST['dbcharset'] ?>" /> </td></tr>
			<tr><td>Collation: </td><td><input type="text" name="cpnl-dbcollate" id="cpnl-dbcollate" value="<?php echo $_POST['dbcollate'] ?>" /> </tr>
		</table>

	</div>
	<br/><br/>


	<!-- =========================================
	CPNL: DB VALIDATION -->
	<div class="hdr-sub1 toggle-hdr" data-type="toggle" data-target="#s2-dbtest-area-cpnl">
		<a href="javascript:void(0)"><i class="fa fa-minus-square"></i>Validation</a>
	</div>


	<div id='s2-dbtest-area-cpnl' class="s2-dbtest-area">
		<div id="s2-dbrefresh-cpnl">
			<a href="javascript:void(0)" onclick="DUPX.testDBConnect()"><i class="fa fa-refresh"></i> Retry Test</a>
		</div>
		<div style="clear:both"></div>
		<div id="s2-dbtest-hb-cpnl" class="s2-dbtest-hb">
			<div class="message">
				To continue click the 'Test Database' button <br/>
				to	perform a database integrity check for cPanel
			</div>
		</div>
	</div>
	<br/><br/><br/>
	<br/><br/><br/>

	<div class="footer-buttons">
		<button id="s2-dbtest-btn-cpnl" type="button" onclick="DUPX.testDBConnect()" class="default-btn" /><i class="fa fa-database"></i> Test Database</button>
		<button id="s2-next-btn-cpnl" type="button" onclick="DUPX.confirmDeployment()" class="default-btn disabled" disabled="true"
				title="The 'Test Database' connectivity requirments must pass to continue with install!">
			Next <i class="fa fa-caret-right"></i>
		</button>
	</div>

<?php endif; ?>
<br/>


<script>
/**
 * Returns the windows active url */
DUPX.getcPanelURL = function(id)
{
	var loc      = window.location;
	var newVal	 = loc.protocol + '//' + loc.hostname + ':2038';
	$("#" + id).val(newVal);
};


/**
 *  Performs cpnl connection and updates UI */
DUPX.cpnlConnect = function ()
{
	var $formInput = $('#s2-input-form');
	$formInput.parsley().validate();
	if (!$formInput.parsley().isValid()) {
		return;
	}

	$('#s2-cpnl-connect-btn').attr('readonly', 'true').val('Connecting... Please Wait!');
	$('a#s2-cpnl-status-msg').hide();

	var apiAccountActive = function(data)
	{
		var html	= "";
		var error	= "Unknown Error";
		var prefix	= "";
		var validHost  = false;
		var validUser  = false;

		if (typeof data == 'undefined')	{
			error = "Unknown error, unable to retrive data request.";
			CPNL_CONNECTED = false;
		}
		else if (data.hasOwnProperty('status') && data.status == 0)	{
			error = data.hasOwnProperty('statusText') ? data.statusText : "Unknown error, unable to retrive status text.";
			CPNL_CONNECTED = false;
		}
		else if (data.hasOwnProperty('result')) {
			validHost		= data.result.valid_host;
			validUser		= data.result.valid_user;
			CPNL_DBINFO		= data.result.hasOwnProperty('dbinfo')  ? data.result.dbinfo  : null;
			CPNL_DBUSERS	= data.result.hasOwnProperty('dbusers') ? data.result.dbusers : null;
			CPNL_CONNECTED	= validHost && validUser;
		}

		html += validHost	? "<b>Host:</b>  <div class='dupx-pass'>Success</div> &nbsp; "
							: "<b>Host:</b>  <div class='dupx-fail'>Unable to Connect</div> &nbsp;";
		html += validUser	? "<b>Account:</b> <div class='dupx-pass'>Found</div><br/>"
							: "<b>Account:</b> <div class='dupx-fail'>Not Found</div><br/>";

		if (CPNL_CONNECTED)
		{
			var setupDBName = '<?php echo strlen($GLOBALS['DUPX_AC']->cpnl_dbname) > 0 ? $GLOBALS['DUPX_AC']->cpnl_dbname : 'null'; ?>';
			var setupDBUser = '<?php echo strlen($GLOBALS['DUPX_AC']->cpnl_dbuser) > 0 ? $GLOBALS['DUPX_AC']->cpnl_dbuser : 'null'; ?>';
			var $dbNameSelect = $("#cpnl-dbname-select");
			var $dbUserSelect = $("#cpnl-dbuser-select");

			//Set Prefix data
			if(data.result.is_prefix_on.status)
			{
				prefix = $('#cpnl-user').val() + "_";
				var dbnameTxt = $("#cpnl-dbname-txt").val();
				var dbuserTxt = $("#cpnl-dbuser-txt").val();

				$("#cpnl-prefix-dbname, #cpnl-prefix-dbuser").show().html(prefix + "&nbsp;");
				if (dbnameTxt.indexOf(prefix) != -1) {
					$("#cpnl-dbname-txt").val(dbnameTxt.replace(prefix, ''));
				}
				if (dbuserTxt.indexOf(prefix) != -1) {
					$("#cpnl-dbuser-txt").val(dbuserTxt.replace(prefix, ''));
				}
				CPNL_PREFIX = true;
			} else {
				$("#cpnl-prefix-dbname, #cpnl-prefix-dbuser").hide().html("");
				$('#cpnl_ignore_prefix').attr('checked', 'true');
				$('#cpnl_ignore_prefix').attr('onclick', 'return false;');
				$('#cpnl_ignore_prefix').attr('onkeydown', 'return false;');
				var $label = $('label[for="cpnl_ignore_prefix"]');
				$label.css('color', 'gray');
				$label.html($label.text() + ' <i>(this option has been set to readonly by host)</i>');
				CPNL_PREFIX = false;
			}

			//Enable database inputs and show header green go icon
			DUPX.cpnlToggleLogin('on');
			$('a#s2-cpnl-status-msg').html('<div class="status-badge-pass">success</div>');
			$('div#s2-cpnl-status-details-msg').html(html);
			$("div[data-target='#s2-cpnl-area']").trigger('click');

			//Load DB Names
			$dbNameSelect.find('option').remove().end();
			$dbNameSelect.append($("<option selected></option>").val("-- Select Database --").text("-- Select Database --"));
			$.each(CPNL_DBINFO, function (key, value)
			{
				(setupDBName == value.db)
					? $dbNameSelect.append($("<option selected></option>").val(value.db).text(value.db))
					: $dbNameSelect.append($("<option></option>").val(value.db).text(value.db));
			});

			//Load DB Users
			$dbUserSelect.find('option').remove().end();
			$dbUserSelect.append($("<option selected></option>").val("-- Select User --").text("-- Select User --"));
			$.each(CPNL_DBUSERS, function (key, value)
			{
				(setupDBUser == value.user)
					? $dbUserSelect.append($("<option selected></option>").val(value.user).text(value.user))
					: $dbUserSelect.append($("<option></option>").val(value.user).text(value.user));
			});

			 //Warn on host name mismatch
			 var address = window.location.hostname.replace('www.', '');
			 ($('#cpnl-host').val().indexOf(address) == -1)
				? $('#cpnl-host-warn').show()
				: $('#cpnl-host-warn').hide();
		}
		else
		{
			//Auto message display
			html += "<b>Details:</b> Unable to connect. Error status is: '" + error + "'. <br/>";
			$('a#s2-cpnl-status-msg').html('<div class="status-badge-fail">failed</div>');
			$('div#s2-cpnl-status-details-msg').html(html);
			$('div#s2-cpnl-status-details').show(500);
			//Inputs
			DUPX.cpnlToggleLogin('off');
		}
		$('a#s2-cpnl-status-msg').show(200);
		$('#s2-cpnl-connect-btn').removeAttr('readonly').val('Connect');
		DUPX.cpnlSetResults();
	}

	DUPX.requestAPI({
		operation: '/cpnl/create_token/',
		timeout: 10000,
		params: {
			host: $('#cpnl-host').val(),
			user: $('#cpnl-user').val(),
			pass: $('#cpnl-pass').val()
		},
		callback: function (data) {
			CPNL_TOKEN = data.result;
			DUPX.requestAPI({
				operation: '/cpnl/get_setup_data/',
				timeout: 30000,
				params: {token: data.result},
				callback: apiAccountActive
			});
		}
	});
};

/**
 *  Enables/Disables database setup and cPanel login inputs  */
DUPX.cpnlToggleLogin = function (state)
{
	//Change btn enabled
	if (state == 'on') {
		$('#cpnl-host, #cpnl-user, #cpnl-pass').addClass('readonly').attr('readonly', 'true');
		$('#s2-cpnl-connect-btn').addClass('disabled').attr('disabled', 'true');
		$('#s2-cpnl-change-btn').removeAttr('disabled').removeClass('disabled').show();
		//Enable cPanel Database
		$('#s2-cpnl-db-opts td').css('color', 'black');
		$('#s2-cpnl-db-opts input, #s2-cpnl-db-opts select').removeAttr('disabled');
		$('#cpnl-host-get-lnk').hide();
	}
	//Change btn disabled
	else {
		$('#cpnl-host, #cpnl-user, #cpnl-pass').removeClass('readonly').removeAttr('readonly');
		$('#s2-cpnl-connect-btn').removeAttr('disabled', 'true').removeClass('disabled');
		$('#s2-cpnl-change-btn').addClass('disabled').attr('disabled', 'true');
		//Disable cPanel Database
		$('#s2-cpnl-db-opts td').css('color', 'silver');
		$('#s2-cpnl-db-opts input, #s2-cpnl-db-opts select').attr('disabled', 'true');
		$('#cpnl-host-get-lnk').show();
	}
}

/**
 *  Updates action status  */
DUPX.cpnlDBActionChange = function ()
{
	var action = $('#cpnl-dbaction').val();
	$('#s2-cpnl-db-opts .s2-warning-manualdb').hide();
	$('#s2-cpnl-db-opts .s2-warning-emptydb').hide();
	$('#s2-cpnl-db-opts .s2-warning-renamedb').hide();
	$('#s2-cpnl-dbname-area1, #s2-cpnl-dbname-area2').hide();

	switch (action) {
		case 'create' :	 $('#s2-cpnl-dbname-area2').show(300);	break;
		case 'empty' :
			$('#s2-cpnl-dbname-area1').show(300);
			$('#s2-cpnl-db-opts .s2-warning-emptydb').show(300);
		break;
		case 'rename' :
			$('#s2-cpnl-dbname-area1').show(300);
			$('#s2-cpnl-db-opts .s2-warning-renamedb').show(300);
		break;
		case 'manual' :
			$('#s2-cpnl-dbname-area1').show(300);
			$('#s2-cpnl-db-opts .s2-warning-manualdb').show(300);
		break;
	}
};

/**
 *  Set the cpnl dbname and dbuser result hidden fields  */
DUPX.cpnlSetResults = function()
{
   var action = $('#cpnl-dbaction').val();
   var dbname = $("#cpnl-dbname-txt").val();
   var dbuser = $("#cpnl-dbuser-txt").val();
   var prefix = $('#cpnl-user').val() + "_";

	if (CPNL_PREFIX) {
		dbname = prefix + $("#cpnl-dbname-txt").val();
		dbuser = prefix + $("#cpnl-dbuser-txt").val();
	}

   (action == 'create')
		? $('#cpnl-dbname-result').val(dbname)
		: $('#cpnl-dbname-result').val($('#cpnl-dbname-select').val());

	($('#cpnl-dbuser-chk').is(':checked'))
		? $('#cpnl-dbuser-result').val(dbuser)
		: $('#cpnl-dbuser-result').val($('#cpnl-dbuser-select').val());
}

DUPX.cpnlPrefixIgnore = function()
{
	if ($('#cpnl_ignore_prefix').prop('checked')) {
		CPNL_PREFIX = false;
		$("#cpnl-prefix-dbname, #cpnl-prefix-dbuser").hide();
	}
	else {
		CPNL_PREFIX = true;
		$("#cpnl-prefix-dbname, #cpnl-prefix-dbuser").show();
	}
	DUPX.cpnlSetResults();
}

/**
 *  Toggle the DB user name type  */
DUPX.cpnlDBUserToggle = function ()
{
	$('#s2-cpnl-dbuser-area1, #s2-cpnl-dbuser-area2').hide();
	 $('#cpnl-dbuser-txt, #cpnl-dbuser-select').removeAttr('disabled');
	 $('#cpnl-dbuser-txt, #cpnl-dbuser-select').removeAttr('required');

	//Use existing
	if ($('#cpnl-dbuser-chk').prop('checked')) {
		$('#s2-cpnl-dbuser-area2').show();
		$('#cpnl-dbuser-select').attr('disabled', 'true');
		$('#cpnl-dbuser-txt').attr('required', 'true');
		$('#cpnl-dbpass').attr('required', 'true');
		$('#cpnl-dbpass').attr('data-parsley-minlength', '5');
	//Create New
	} else {
		$('#s2-cpnl-dbuser-area1').show();
		$('#cpnl-dbuser-select').attr('required', 'true');
		$('#cpnl-dbuser-txt').attr('disabled', 'true');
		$('#cpnl-dbpass').removeAttr('required');
		$('#cpnl-dbpass').removeAttr('data-parsley-minlength');
	}
	DUPX.cpnlSetResults();
}

//DOCUMENT LOAD
$(document).ready(function ()
{
	//Custom Validator
	window.Parsley.addValidator('cpnluser', {
		validateString: function(value) {
		  var prefix = CPNL_PREFIX
				? $('#cpnl-user').val() + "_" + value
				: value;
		  return (prefix.length <= 24);
		},
		messages: {
		  en: 'Database user cannot be more that 24 characters including prefix'
		}
	});

	//Attach Events
	$("#cpnl-dbaction").on("change", DUPX.cpnlDBActionChange);
	$("#cpnl-dbuser-chk").click(DUPX.cpnlDBUserToggle);
	$('#cpnl-dbname-select, #cpnl-dbname-txt').on("change", DUPX.cpnlSetResults);
	$('#cpnl-dbuser-select, #cpnl-dbuser-txt').on("change", DUPX.cpnlSetResults);

	<?php echo ($GLOBALS['DUPX_AC']->cpnl_connect) ? 'DUPX.cpnlConnect();' : ''; ?>
	$("#cpnl-dbaction").val(<?php echo strlen($GLOBALS['DUPX_AC']->cpnl_dbaction) > 0 ? "'{$GLOBALS['DUPX_AC']->cpnl_dbaction}'" : 'create'; ?>);
	DUPX.cpnlDBActionChange();
	DUPX.cpnlDBUserToggle();
	DUPX.cpnlToggleLogin('off');
	DUPX.cpnlSetResults();

	$("input[name='cpnl-dbmysqlmode']").click(function() {
		($(this).val() == 'CUSTOM')
			? $('#cpnl-dbmysqlmode_3_view').show()
			: $('#cpnl-dbmysqlmode_3_view').hide();
	});
	
});
</script>
