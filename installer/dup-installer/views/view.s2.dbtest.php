<?php
defined("ABSPATH") or die("");
/** IDE HELPERS */
/* @var $GLOBALS['DUPX_AC'] DUPX_ArchiveConfig */
?>

<script id="s2-dbtest-hb-template" type="text/x-handlebars-template">
	<!-- REQUIRMENTS -->
	<div class="hdr-sub4 s2-reqs-hdr" data-type="toggle" data-target="#s2-reqs-all" >
		<i class="fa fa-caret-right"></i> Requirements <small class='db-check'>(must pass)</small>
		<div class="{{reqStyle payload.reqsPass}}">{{reqText payload.reqsPass}}</div>
	</div>
	<div class="s2-reqs" id="s2-reqs-all" style="border-bottom:none">


		<!-- ==================================
		REQ 5: CREATE DB USER: CPNL -->
		{{#if payload.in.cpnlNewUser}}
			<div class="status {{reqStyle payload.reqs.5.pass}}">{{reqText payload.reqs.5.pass}}</div>
			<div class="title" data-type="toggle" data-target="#s2-reqs5"><i class="fa fa-caret-right"></i> {{payload.reqs.5.title}}</div>
			<div class="info s2-reqs5" id="s2-reqs5">
				<div class="sub-title">STATUS</div>
				{{{getInfo payload.reqs.5.pass payload.reqs.5.info}}}<br/>

				<div class="sub-title">DETAILS</div>
				This test checks that the cPanl API is allowed to create a database user. This option is only visible when cPanel is selected.
				<br/><br/>

				<div class="sub-title">TROUBLESHOOT</div>
				<ul>
					<li>Contact your host to make sure they support the cPanel API.</li>
					<li>Check with your host to make sure the user name provided meets the cPanel requirments.</li>
				</ul>
			</div>
		{{/if}}

		<!-- ==================================
		REQ 10: VERIFY HOST CONNECTION -->
		<div class="status {{reqStyle payload.reqs.10.pass}}">{{reqText payload.reqs.10.pass}}</div>
		<div class="title" data-type="toggle" data-target="#s2-reqs10"><i class="fa fa-caret-right"></i> {{payload.reqs.10.title}}</div>
		<div class="info s2-reqs10" id="s2-reqs10">
			<div class="sub-title">STATUS</div>
			{{{getInfo payload.reqs.10.pass payload.reqs.10.info}}}<br/>
		
			<div class="sub-title">DETAILS</div>
			This test checks that the database user is allowed to connect to the database server.  It validates on the user name, password and host values.
			The check does not take into account the database name or the user permissions. A database user must first exist and have access to the host
			database server before any additional checks can be made.

			<table>
				<tr>
					<td>Host:</td>
					<td>{{payload.in.dbhost}}</td>
				</tr>
				<tr>
					<td>User:</td>
					<td>{{payload.in.dbuser}}</td>
				</tr>
				<tr>
					<td>Password:</td>
					<td>{{{payload.in.dbpass}}}</td>
				</tr>
			</table><br/>

			<div class="sub-title">TROUBLESHOOT</div>
			<ul>
				<li>Check that the 'Host' name settings are correct via your hosts documentation.</li>
				<li>On some servers, the default name 'localhost' will not work. Be sure to contact your hosting provider.</li>
				<li>Triple check the 'User' and 'Password' values are correct.</li>
				<li>
					Check to make sure the 'User' has been added as a valid database user
					<ul class='vids'>
						<li><i class="fa fa-video-camera"></i> <a href="https://www.youtube.com/watch?v=FfX-B-h3vo0" target="_video">Add database user in phpMyAdmin</a></li>
						<li><i class="fa fa-video-camera"></i> <a href="https://www.youtube.com/watch?v=peLby12mi0Q" target="_video">Add database user in cPanel older versions</a></li>
						<li><i class="fa fa-video-camera"></i> <a href="https://www.youtube.com/watch?v=CHwxXGPnw48" target="_video">Add database user in cPanel newer versions</a></li>
					</ul>
				</li>
				<li>If using the 'Basic' option then try using the <a href="javascript:void(0)" onclick="DUPX.togglePanels('cpanel')">'cPanel'</a> option.</li>
				<li><i class="fa fa-file-code-o"> </i> <a href='{{{faqURL}}}#faq-installer-100-q' target='_help'>I'm running into issues with the Database what can I do?</a></li>
			</ul>
		</div>

		<!-- ==================================
		REQ 20: CHECK DATABASE VERSION -->
		<div class="status {{reqStyle payload.reqs.20.pass}}">{{reqText payload.reqs.20.pass}}</div>
		<div class="title" data-type="toggle" data-target="#s2-reqs20"><i class="fa fa-caret-right"></i> {{payload.reqs.20.title}}</div>
		<div class="info" id="s2-reqs20">
			<div class="sub-title">STATUS</div>
			{{{getInfo payload.reqs.20.pass payload.reqs.20.info}}}<br/>

			<div class="sub-title">DETAILS</div>
			The minimum supported database server is MySQL Server 5.0 or the <a href="https://mariadb.com/kb/en/mariadb/mariadb-vs-mysql-compatibility/" target="_blank">MariaDB equivalent</a>.
			Versions prior to MySQL 5.0 are over 10 years old and will not be compatible with Duplicator Pro.  If your host is using a legacy version, please ask them
			to upgrade the MySQL database engine to a more recent version.
			<br/><br/>

			<div class="sub-title">TROUBLESHOOT</div>
			<ul>
				<li>Contact your host and have them upgrade your MySQL server.</li>
				<li><i class="fa fa-file-code-o"></i> <a href='{{{faqURL}}}#faq-installer-100-q' target='_help'>I'm running into issues with the Database what can I do?</a></li>
			</ul>
		</div>

		<!-- ==================================
		REQ 30: Create New Database: BASIC -->
		{{#if_eq payload.in.dbaction "create"}}
			<div class="status {{reqStyle payload.reqs.30.pass}}">{{reqText payload.reqs.30.pass}}</div>
			<div class="title" data-type="toggle" data-target="#s2-reqs30"><i class="fa fa-caret-right"></i> {{payload.reqs.30.title}}</div>
			<div class="info" id="s2-reqs30">
				<div class="sub-title">STATUS</div>
				{{{getInfo payload.reqs.30.pass payload.reqs.30.info}}}
				<br/>

				<div class="sub-title">DETAILS</div>
				This test checks if the database can be created by the database user.  The test will attempt to create and drop the database name provided as part
				of the overall test.
				<br/><br/>

				<div class="sub-title">TROUBLESHOOT</div>
				<ul>
					<li>
						Check the database user privileges:
						<ul class='vids'>
							<li><i class="fa fa-video-camera"></i> <a href="https://www.youtube.com/watch?v=FfX-B-h3vo0" target="_video">Add database user in phpMyAdmin</a></li>
							<li><i class="fa fa-video-camera"></i> <a href="https://www.youtube.com/watch?v=peLby12mi0Q" target="_video">Add database user in cPanel older versions</a></li>
							<li><i class="fa fa-video-camera"></i> <a href="https://www.youtube.com/watch?v=CHwxXGPnw48" target="_video">Add database user in cPanel newer versions</a></li>
						</ul>
					</li>
					<li>If using the 'Basic' option then try using the <a href="javascript:void(0)" onclick="DUPX.togglePanels('cpanel')">'cPanel'</a> option.</li>
					<li><i class="fa fa-file-code-o"></i> <a href='{{{faqURL}}}#faq-installer-100-q' target='_help'>I'm running into issues with the Database what can I do?</a></li>
				</ul>

			</div>
		{{/if_eq}}


		<!-- ==================================
		REQ 40: CONFIRM DATABASE VISIBILITY -->
		{{#if_neq payload.in.dbaction "create"}}
			<div class="status {{reqStyle payload.reqs.40.pass}}">{{reqText payload.reqs.40.pass}}</div>
			<div class="title" data-type="toggle" data-target="#s2-reqs40"><i class="fa fa-caret-right"></i> {{payload.reqs.40.title}}</div>
			<div class="info s2-reqs40" id="s2-reqs40">
				<div class="sub-title">STATUS</div>
				{{{getInfo payload.reqs.40.pass payload.reqs.40.info}}}<br/>

				<div class="sub-title">DETAILS</div>
				This test checks if the database user is allowed to connect or view the database.   This test will not be ran if the 'Create New Database' action is selected.
				<br/><br/>

				<b>Databases visible to user [{{payload.in.dbuser}}]</b> <br/>
				<div class="db-list">
					{{#each payload.databases}}
						{{@index}}. {{this}}<br/>
					{{else}}
						<i>No databases are viewable to database user [{{payload.in.dbuser}}]</i> <br/>
					{{/each}}
				</div><br/>

				<div class="sub-title">TROUBLESHOOT</div>
				<ul>
					<li>Check the database user privileges.</li>
					<li>
						Check to make sure the 'User' has been added as a valid database user
						<ul class='vids'>
							<li><i class="fa fa-video-camera"></i> <a href="https://www.youtube.com/watch?v=FfX-B-h3vo0" target="_video">Add database user in phpMyAdmin</a></li>
							<li><i class="fa fa-video-camera"></i> <a href="https://www.youtube.com/watch?v=peLby12mi0Q" target="_video">Add database user in cPanel older versions</a></li>
							<li><i class="fa fa-video-camera"></i> <a href="https://www.youtube.com/watch?v=CHwxXGPnw48" target="_video">Add database user in cPanel newer versions</a></li>
						</ul>
					</li>
					<li><i class="fa fa-file-code-o"></i> <a href='{{{faqURL}}}#faq-installer-100-q' target='_help'>I'm running into issues with the Database what can I do?</a></li>
				</ul>
			</div>
		{{/if_neq}}

		<!-- ==================================
		REQ 50: Manual SQL Execution -->
		{{#if_eq payload.in.dbaction "manual"}}
			<div class="status {{reqStyle payload.reqs.50.pass}}">{{reqText payload.reqs.50.pass}}</div>
			<div class="title" data-type="toggle" data-target="#s2-reqs50"><i class="fa fa-caret-right"></i> {{payload.reqs.50.title}}</div>
			<div class="info" id="s2-reqs50">
				<div class="sub-title">STATUS</div>
				{{{getInfo payload.reqs.50.pass payload.reqs.50.info}}}
				<br/>

				<div class="sub-title">DETAILS</div>
				This test checks if the database looks to represents a base WordPress install. Since this option is advanced it is left upto the user to
				have the correct database tables installed.
				<br/><br/>

			</div>
		{{/if_eq}}


		<!-- ==================================
		REQ 60: VALIDATE USER PERMISSIONS -->
		<div class="status {{reqStyle payload.reqs.60.pass}}">{{reqText payload.reqs.60.pass}}</div>
		<div class="title" data-type="toggle" data-target="#s2-reqs60"><i class="fa fa-caret-right"></i> {{payload.reqs.60.title}}</div>
		<div class="info s2-reqs60" id="s2-reqs60">
			<div class="sub-title">STATUS</div>
			{{{getInfo payload.reqs.60.pass payload.reqs.60.info}}}<br/>

			<div class="sub-title">DETAILS</div>
			This test checks the privileges a user has when working with tables.  Below is a list of all the privileges that the user can currently view.  In order
			to successfully use Duplicator Pro all of the privileges are required.
			<br/><br/>

			<div class="sub-title">TABLE PRIVLEDGES ON [{{payload.in.dbname}}]</div>
			<div class="tbl-list">
				<b>Create:</b> {{{getTablePerms payload.tblPerms.[create]}}} <br/>
				<b>Select:</b> {{{getTablePerms payload.tblPerms.[select]}}} <br/>
				<b>Insert:</b> {{{getTablePerms payload.tblPerms.[insert]}}} <br/>
				<b>Update:</b> {{{getTablePerms payload.tblPerms.[update]}}} <br/>
				<b>Delete:</b> {{{getTablePerms payload.tblPerms.[delete]}}} <br/>
				<b>Drop:  </b> {{{getTablePerms payload.tblPerms.[drop]}}} <br/>
			</div><br/>

			<div class="sub-title">TROUBLESHOOT</div>
			<ul>
				<li>Validate that the database user is correct per your hosts documentation</li>
					<li>
						Check to make sure the 'User' has been granted the correct privileges
						<ul class='vids'>
							<li><i class="fa fa-video-camera"></i>  <a href='https://www.youtube.com/watch?v=UU9WCC_-8aI' target='_video'>How to grant user privileges in cPanel</a></li>
							<li><i class="fa fa-video-camera"></i> <a href="https://www.youtube.com/watch?v=FfX-B-h3vo0" target="_video">How to grant user privileges in phpMyAdmin</a></li>
						</ul>
					</li>
				<li><i class="fa fa-file-code-o"></i> <a href='{{{faqURL}}}#faq-installer-100-q' target='_help'>I'm running into issues with the Database what can I do?</a></li>
			</ul>
		</div>

	</div>



	<!-- ==================================
	NOTICES
	================================== -->
	<div class="hdr-sub4 s2-notices-hdr" data-type="toggle" data-target="#s2-notices-all">
		<i class="fa fa-caret-right"></i> Notices <small class='db-check'>(optional)</small>
		<div class="{{noticeStyle payload.noticesPass}}">{{noticeText payload.noticesPass}}</div>
	</div>
	<div class="s2-reqs" id="s2-notices-all">
		
		<!-- ==================================
		NOTICE 10: TABLE CASE CHECK-->
		<div class="status {{noticeStyle payload.notices.10.pass}}">{{noticeText payload.notices.10.pass}}</div>
		<div class="title" data-type="toggle" data-target="#s2-notice10" style="border-top:none"><i class="fa fa-caret-right"></i> {{payload.notices.10.title}}</div>
		<div class="info" id="s2-notice10">
			<div class="sub-title">STATUS</div>
			{{{getInfo payload.notices.10.pass payload.notices.10.info}}}<br/>

			<div class="sub-title">DETAILS</div>
			This test checks if any tables have upper case characters as part of the name.   On some systems creating tables with upper case can cause issues if the server
			setting for <a href="https://dev.mysql.com/doc/refman/5.7/en/identifier-case-sensitivity.html" target="_help">lower_case_table_names</a> is set to zero and upper case
			table names exist.
			<br/><br/>

			<div class="sub-title">TROUBLESHOOT</div>
			<ul>
				<li>
					In the my.cnf (my.ini) file set the <a href="https://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_lower_case_table_names" target="_help">lower_case_table_names</a>
					to 1 or 2 and restart the server.
				</li>
				<li><i class="fa fa-external-link"></i> <a href='http://www.inmotionhosting.com/support/website/general-server-setup/edit-mysql-my-cnf' target='_help'>How to edit MySQL config files my.cnf (linux) or my.ini (windows) files</a></li>
			</ul>
		</div>

		<!-- ==================================
		NOTICE 20: CHECK COLLATION CAPABILITY -->
		<div class="status {{noticeStyle payload.notices.20.pass}}">{{noticeText payload.notices.20.pass}}</div>
		<div class="title" data-type="toggle" data-target="#s2-notice20"><i class="fa fa-caret-right"></i> {{payload.notices.20.title}}</div>
		<div class="info s2-notice20" id="s2-notice20">
			<div class="sub-title">STATUS</div>
			{{{getInfo payload.notices.20.pass payload.notices.20.info}}}<br/>

			<div class="sub-title">DETAILS</div>
			This test checks to make sure this database can support the collations found in the database.sql script.
			<br/><br/>

			<b>Collations in database.sql</b> <br/>
			<table class="collation-list">
				{{#each payload.collationStatus as |item|}}
					<tr>
						<td>{{item.name}}:</td>
						<td>
							{{#if item.found}}
								<span class='dupx-pass'>Pass</span>
							{{else}}
								<span class='dupx-fail'>Fail</span>
							{{/if}}
						</td>
					</tr>
				{{else}}
					<tr><td style='font-weight:normal'>This test was not ran.</td></tr>
				{{/each}}
			</table><br/>

			<div class="sub-title">TROUBLESHOOT</div>
			<ul>
				<li><i class="fa fa-file-code-o"></i> <a href='{{{faqURL}}}#faq-installer-110-q' target='_help'>What is Compatibility mode & 'Unknown Collation' errors?</a></li>
			</ul>

		</div>
		
	</div>
</script>


<script>
//HANDLEBAR HOOKS
Handlebars.registerHelper('if_eq',		function(a, b, opts) { return (a == b) ? opts.fn(this) : opts.inverse(this);});
Handlebars.registerHelper('if_neq',		function(a, b, opts) { return (a != b) ? opts.fn(this) : opts.inverse(this);});
Handlebars.registerHelper('faqURL',		function() { return "https://snapcreek.com/duplicator/docs/faqs-tech/";});
Handlebars.registerHelper('reqText',	function(req)  { if  (req == -1)  {return ""}; return (req)  ? "Pass" : "Fail";});
Handlebars.registerHelper('reqStyle',	function(req)  { if  (req == -1)  {return ""}; return (req)  ? "status-badge-pass" : "status-badge-fail";});
Handlebars.registerHelper('noticeStyle',function(req)  { if  (req == -1)  {return ""}; return (req)  ? "status-badge-pass" : "status-badge-fail";});
Handlebars.registerHelper('noticeText', function(warn) { if  (warn == -1) {return ""}; return (warn) ? "Good" : "Warn";});
Handlebars.registerHelper('getInfo',	function(pass, info) {
	return (pass && pass != -1)
		? "<div class='success-msg'>" + info + "</div>"
		: "<div class='error-msg'>" + info + "</div>";
});
Handlebars.registerHelper('getTablePerms',	function(perm) {
	if (perm == -1) {
		return "<span class='dupx-warn'>Requires Dependency</span>";
	} else if (perm == 0) {
		return "<span class='dupx-fail'>Fail</span>";
	} else {
		return "<span class='dupx-pass'>Pass</span>";
	}
});


/**
 * Shows results of database connection
 * Timeout (45000 = 45 secs) */
DUPX.testDBConnect = function ()
{
	//Validate input data
	var $formInput = $('#s2-input-form');
	$formInput.parsley().validate();
	if (!$formInput.parsley().isValid()) {
		return;
	}

	var $dbArea;
	var $dbResult;
	var $dbButton;
	if ($('#s2-input-form-mode').val() == 'basic') {
		$dbArea   = $('#s2-basic-pane .s2-dbtest-area');
		$dbResult = $('#s2-dbtest-hb-basic');
		$dbButton = $('#s2-dbtest-btn-basic');

	} else {
		$dbArea = $('#s2-cpnl-pane  .s2-dbtest-area');
		$dbResult = $('#s2-dbtest-hb-cpnl');
		$dbButton = $('#s2-dbtest-btn-cpnl');
	}

	$dbArea.show(250);
	$dbResult.html("<div class='message'><i class='fa fa-circle-o-notch fa-spin fa-fw'></i>Running Database Validation. <br/>  Please wait...</div>");
	$dbButton.attr('disabled', 'true');

	$.ajax({
		type: "POST",
		timeout: 25000,
		url: window.location.href + '&' + 'dbtest=1',
		data: $('#s2-input-form').serialize(),
		success: function (data) {
			DUPX.intTestDBResults(data, $dbResult);
		},
		error: function (data) {
			console.log(data);
			var msg  = "<b>Error Processing Request</b> <br/> An error occurred while testing the database connection! Please Try Again...<br/> ";
			msg		+= "<small>If the error persists contact your host for database connection requirments.</small><br/> ";
			msg		+= "<small>Status details: " + data.statusText + "</small>";
			$dbResult.html("<div class='message dupx-fail'>" + msg + "</div>");
			<?php if ($GLOBALS['DUPX_DEBUG']) : ?>
				var jsonStr = JSON.stringify(data, null, 2);
				$('#debug-dbtest-json').val(jsonStr);
			<?php endif; ?>
		},
		complete: function (data) {
			$dbButton.removeAttr('disabled');
		}
	});


};

//Process Ajax Template
DUPX.intTestDBResults = function(data, result)
{
	//Patch for PHP 5.2 json_encode issues
	if(typeof data != 'object')
	{
	   var data = jQuery.parseJSON(data);
	}

    $('#s2-input-form input[name="dbcolsearchreplace"]').val(JSON.stringify(data.payload.collationReplaceList));

	var resultID = $(result).attr('id');
	var mode     = '-' + data.payload.in.mode;
	var template = $('#s2-dbtest-hb-template').html();
	var templateScript = Handlebars.compile(template);
	var html = templateScript(data);
	result.html(html);

	//Make all id attributes unique to basic or cpanel areas
	//otherwise id will no longer be unique
	$("div#" + resultID + " div[id]").each(function() {
		var attr = this.id;
		$(this).attr('id', attr + mode);
	});

	$("div#" + resultID + " div[data-target]").each(function() {
		var attr = $(this).attr('data-target');
		$(this).attr('data-target', attr + mode);
	});

	$("div#" + resultID + " *[data-type='toggle']").on('click', DUPX.toggleClick);
	

	var $divReqsAll		= $('div#s2-reqs-all' + mode);
	var $divNoticeAll	= $('div#s2-notices-all' + mode);
	var $btnNext		= $('#s2-next-btn' + mode);
	var $btnTestDB		= $('#s2-dbtest-btn' + mode);
	var $divRetry		= $('#s2-dbrefresh' + mode);

	$divRetry.show();
	$btnTestDB.removeAttr('disabled').removeClass('disabled');
	$btnNext.removeAttr('disabled').removeClass('disabled');
	
	
	if (data.payload.reqsPass == 1) {
		$btnTestDB.addClass('disabled').attr('disabled', 'true');
		$divReqsAll.hide()
	} else {
		$btnNext.addClass('disabled').attr('disabled', 'true');
		$divReqsAll.show();
	}
		
	data.payload.noticesPass ? $divNoticeAll.hide() : $divNoticeAll.show();

	if (data.payload.reqsPass == 1 && data.payload.noticesPass == 1) {
		$btnTestDB.addClass('disabled').attr('disabled', 'true');
	}

	$('div#s2-db-basic :input').on('keyup', {'mode': mode}, DUPX.resetDBTest);
	$('div#s2-db-basic select#dbaction').on('change', {'mode': mode}, DUPX.resetDBTest);
	$('table#s2-cpnl-db-opts :input').on('keyup', {'mode': mode}, DUPX.resetDBTest);
	$('table#s2-cpnl-db-opts select#cpnl-dbaction').on('change', {'mode': mode}, DUPX.resetDBTest);
	$('table#s2-cpnl-db-opts select#cpnl-dbuser-select').on('change', {'mode': mode}, DUPX.resetDBTest);
	$('table#s2-cpnl-db-opts input#cpnl-dbuser-chk').on('click', {'mode': mode}, DUPX.resetDBTest);

	<?php if ($GLOBALS['DUPX_DEBUG']) : ?>
		var jsonStr = JSON.stringify(data, null, 2);
		$('#debug-dbtest-json').val(jsonStr);
	<?php endif; ?>
}

DUPX.resetDBTest = function(e)
{
	var $btnNext		= $('#s2-next-btn' + e.data.mode);
	var $btnTestDB		= $('#s2-dbtest-btn' + e.data.mode);
	var $divTestArea	= $('#s2-dbtest-hb'+ e.data.mode);

	$btnTestDB.removeAttr('disabled').removeClass('disabled');
	$btnNext.addClass('disabled').attr('disabled', 'true');
	$divTestArea.html("<div class='sub-message'>To continue click the 'Test Database'<br/>button to retest the database setup.</div>");
}
</script>

