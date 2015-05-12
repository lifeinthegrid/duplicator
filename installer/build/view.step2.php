<?php
	// Exit if accessed directly
	if (! defined('DUPLICATOR_INIT')) {
		$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
		$_baseURL =  "http://" . $_baseURL;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $_baseURL");
		exit; 
	}
	$dbh = @mysqli_connect($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass'], $_POST['dbname']);

	$all_tables     = DupUtil::get_database_tables($dbh);
	$active_plugins = DupUtil::get_active_plugins($dbh);
	

	$old_path = $GLOBALS['FW_WPROOT'];
	$new_path = DupUtil::set_safe_path($GLOBALS['CURRENT_ROOT_PATH']);
	$new_path = ((strrpos($old_path, '/') + 1) == strlen($old_path)) ? DupUtil::add_slash($new_path) : $new_path; 
?>

<script type="text/javascript">
	/** **********************************************
	* METHOD:  
	* Timeout (10000000 = 166 minutes) */
	Duplicator.runUpdate = function() {
		
		//Validation
		var wp_username = $.trim($("#wp_username").val()).length || 0;
		var wp_password = $.trim($("#wp_password").val()).length || 0;
		
		if ( $.trim($("#url_new").val()) == "" )  {alert("The 'New URL' field is required!"); return false;}
		if ( $.trim($("#siteurl").val()) == "" )  {alert("The 'Site URL' field is required!"); return false;}
		if (wp_username >= 1 && wp_username < 4) {alert("The New Admin Account 'Username' must be four or more characters"); return false;}
		if (wp_username >= 4 && wp_password < 6) {alert("The New Admin Account 'Password' must be six or more characters"); return false;}

		$.ajax({
			type: "POST",
			timeout: 10000000,
			dataType: "json",			
			url: window.location.href,
			data: $('#dup-step2-input-form').serialize(),
			beforeSend: function() {
				Duplicator.showProgressBar();
				$('#dup-step2-input-form').hide();
				$('#dup-step2-result-form').show();
			},			
			success: function(data){ 
				if (typeof(data) != 'undefined' && data.step2.pass == 1) {
					$("#ajax-url_new").val($("#url_new").val());
					$("#ajax-json").val(escape(JSON.stringify(data)));
					setTimeout(function(){$('#dup-step2-result-form').submit();}, 100);
					$('#progress-area').fadeOut(1800);
				} else {
					Duplicator.hideProgressBar();
				}
			},
			error: function(xhr) { 
				var status = "<b>server code:</b> " + xhr.status + "<br/><b>status:</b> " + xhr.statusText + "<br/><b>response:</b> " +  xhr.responseText;
				$('#ajaxerr-data').html(status);
				Duplicator.hideProgressBar();
			}
		});
	};

	/** **********************************************
	* METHOD: Returns the windows active url */
	Duplicator.getNewURL = function(id) {
		var filename= window.location.pathname.split('/').pop() || 'installer.php' ;
		$("#" + id).val(window.location.href.replace(filename, ''));
	};
	
	/** **********************************************
	* METHOD: Allows user to edit the package url  */
	Duplicator.editOldURL = function() {
		var msg = 'This is the URL that was generated when the package was created.\n';
		msg += 'Changing this value may cause issues with the install process.\n\n';
		msg += 'Only modify  this value if you know exactly what the value should be.\n';
		msg += 'See "General Settings" in the WordPress Administrator for more details.\n\n';
		msg += 'Are you sure you want to continue?';
	
		if (confirm(msg)) {
			$("#url_old").removeAttr('readonly');
			$("#url_old").removeClass('readonly');
			$('#edit_url_old').hide('slow');
		}
	};
	
	/** **********************************************
	* METHOD: Allows user to edit the package path  */
	Duplicator.editOldPath = function() {
		var msg = 'This is the SERVER URL that was generated when the package was created.\n';
		msg += 'Changing this value may cause issues with the install process.\n\n';
		msg += 'Only modify  this value if you know exactly what the value should be.\n';
		msg += 'Are you sure you want to continue?';
	
		if (confirm(msg)) {
			$("#path_old").removeAttr('readonly');
			$("#path_old").removeClass('readonly');
			$('#edit_path_old').hide('slow');
		}
	};
	
	/** **********************************************
	* METHOD: Go back on AJAX result view */
	Duplicator.hideErrorResult2 = function() {
		$('#dup-step2-result-form').hide();			
		$('#dup-step2-input-form').show(200);
	};
	
	//DOCUMENT LOAD
	$(document).ready(function() {
		Duplicator.getNewURL('url_new');
		Duplicator.getNewURL('siteurl');
		
		$("#wp_password").passStrength({
				shortPass: 		"top_shortPass",
				badPass:		"top_badPass",
				goodPass:		"top_goodPass",
				strongPass:		"top_strongPass",
				baseStyle:		"top_testresult",
				userid:			"#wp_username",
				messageloc:		1	});
	});
</script>


<!-- =========================================
VIEW: STEP 2- INPUT -->
<form id='dup-step2-input-form' method="post" class="content-form">
	<input type="hidden" name="action_ajax"	 value="2" />
	<input type="hidden" name="action_step"	 value="2" />
	<input type="hidden" name="logging"		 value="<?php echo $_POST['logging'] ?>" />
	<input type="hidden" name="package_name" value="<?php echo $_POST['package_name'] ?>" />
	<input type="hidden" name="json"		 value="<?php echo $_POST['json']; ?>" />
	<input type="hidden" name="dbhost"		 value="<?php echo $_POST['dbhost'] ?>" />
	<input type="hidden" name="dbuser" 		 value="<?php echo $_POST['dbuser'] ?>" />
	<input type="hidden" name="dbpass" 		 value="<?php echo htmlentities($_POST['dbpass']) ?>" />
	<input type="hidden" name="dbname" 		 value="<?php echo $_POST['dbname'] ?>" />
	<input type="hidden" name="dbcharset" 	 value="<?php echo $_POST['dbcharset'] ?>" />
	<input type="hidden" name="dbcollate" 	 value="<?php echo $_POST['dbcollate'] ?>" />
	
	
	<div class="dup-logfile-link"><a href="installer-log.txt" target="_blank">installer-log.txt</a></div>
	<h3>Step 2: Files &amp; Database</h3>
	<hr size="1" /><br />

	<div class="title-header">Old Settings</div>
	<table class="table-inputs-step2">
		<tr valign="top">
			<td style="width:80px">URL</td>
			<td>
				<input type="text" name="url_old" id="url_old" value="<?php echo $GLOBALS['FW_URL_OLD'] ?>" readonly="readonly"  class="readonly" />
				<a href="javascript:Duplicator.editOldURL()" id="edit_url_old" style="font-size:12px">edit</a>		
			</td>
		</tr>
		<tr valign="top">
			<td>Path</td>
			<td>
				<input type="text" name="path_old" id="path_old" value="<?php echo $old_path ?>" readonly="readonly"  class="readonly" />
				<a href="javascript:Duplicator.editOldPath()" id="edit_path_old" style="font-size:12px">edit</a>		
			</td>
		</tr>
	</table>

	<div class="title-header" style="margin-top:8px">New Settings</div>
	<table class="table-inputs-step2">		
		<tr>
			<td style="width:80px">URL</td>
			<td>
				<input type="text" name="url_new" id="url_new" value="<?php echo $GLOBALS['FW_URL_NEW'] ?>" />
				<a href="javascript:Duplicator.getNewURL('url_new')" style="font-size:12px">get</a>
			</td>
		</tr>
		<tr>
			<td>Path</td>
			<td><input type="text" name="path_new" id="path_new" value="<?php echo $new_path ?>" /></td>
		</tr>	
		<tr>
			<td>Title</td>
			<td><input type="text" name="blogname" id="blogname" value="<?php echo $GLOBALS['FW_BLOGNAME'] ?>" /></td>
		</tr>
	</table><br/>
	
	<!-- ==========================
    CREATE NEW USER -->
	<a href="javascript:void(0)" onclick="$('#dup-step2-user-opts').toggle(0)"><b>New Admin Account...</b></a>
	<div id='dup-step2-user-opts' style="display:none;">
	<table class="table-inputs-step2" style="margin-top:7px">
		<tr><td colspan="2"><i style="color:gray;font-size: 11px">This feature is optional.  If the username already exists the account will NOT be created or updated.</i></td></tr>
		<tr>
			<td>Username </td>
			<td><input type="text" name="wp_username" id="wp_username" value="" title="4 characters minimum" placeholder="(4 or more characters)" /></td>
		</tr>	
		<tr>
			<td valign="top">Password</td>
			<td><input type="text" name="wp_password" id="wp_password" value="" title="6 characters minimum"  placeholder="(6 or more characters)" /></td>
		</tr>
	</table>
	</div><br/><br/>
	
	
	<!-- ==========================
    ADVANCED OPTIONS -->
	<a href="javascript:void(0)" onclick="$('#dup-step2-adv-opts').toggle(0)"><b>Advanced Options...</b></a>
	<div id='dup-step2-adv-opts' style="display:none;">
		<table style="width: 100%;">
			<tr>
				<td valign="top" style="width:80px">Site URL</td>
				<td>
					<input type="text" name="siteurl" id="siteurl" value="" />
					<a href="javascript:Duplicator.getNewURL('siteurl')" style="font-size:12px">get</a><br/>
				</td>
			</tr>
		</table><br/>
		

		<table>
			<tr>
				<td style="padding-right:10px">
					Scan Tables
					<div class="dup-step2-allnonelinks">
						<a href="javascript:void(0)" onclick="$('#tables option').prop('selected',true);">[All]</a> 
						<a href="javascript:void(0)" onclick="$('#tables option').prop('selected',false);">[None]</a>
					</div><br style="clear:both" />
					<select id="tables" name="tables[]" multiple="multiple" style="width:315px; height:100px">
						<?php
						foreach( $all_tables as $table ) {
							echo '<option selected="selected" value="' . DupUtil::esc_html_attr( $table ) . '">' . $table . '</option>';
						} 
						?>
					</select>

				</td>
				<td valign="top">
					Activate Plugins
					<div class="dup-step2-allnonelinks">
						<a href="javascript:void(0)" onclick="$('#plugins option').prop('selected',true);">[All]</a> 
						<a href="javascript:void(0)" onclick="$('#plugins option').prop('selected',false);">[None]</a>
					</div><br style="clear:both" />
					<select id="plugins" name="plugins[]" multiple="multiple" style="width:315px; height:100px">
						<?php
						foreach ($active_plugins as $plugin) {
							echo '<option selected="selected" value="' . DupUtil::esc_html_attr( $plugin ) . '">' . dirname($plugin) . '</option>';
						} 
						?>
					</select>
				</td>
			</tr>							
		</table><br/>
		
		<input type="checkbox" name="postguid" id="postguid" value="1" /> <label for="postguid">Keep Post GUID unchanged</label><br/>
		
		<input type="checkbox" name="fullsearch" id="fullsearch" value="1" /> <label for="fullsearch">Enable Full Search <small>(very slow)</small> </label><br/>
		<br/><br/><br/><br/>	
		
	</div>

	<div class="dup-footer-buttons">
		<input id="dup-step2-next" type="button" value=" Run Update " onclick="Duplicator.runUpdate()"  />
	</div>	
</form>


<!-- =========================================
VIEW: STEP 2 - AJAX RESULT  -->
<form id='dup-step2-result-form' method="post" class="content-form" style="display:none">
	<input type="hidden" name="action_step"  value="3" />
	<input type="hidden" name="package_name" value="<?php echo $_POST['package_name'] ?>" />
	<!-- Set via jQuery -->
	<input type="hidden" name="url_new" id="ajax-url_new"  />
	<input type="hidden" name="json"    id="ajax-json" />	
	
	<div class="dup-logfile-link"><a href="installer-log.txt" target="_blank">installer-log.txt</a></div>
	<h3>Step 2: Update Table Data</h3>
	<hr size="1" />
	
	<!--  PROGRESS BAR -->
	<div id="progress-area">
		<div style="width:500px; margin:auto">
			<h3>Processing Data Replacement Please Wait...</h3>
			<div id="progress-bar"></div>
			<i>This may take several minutes</i>
		</div>
	</div>
	
	<!--  AJAX SYSTEM ERROR -->
	<div id="ajaxerr-area" style="display:none">
		<p>Please try again an issue has occurred.</p>
		<div style="padding: 0px 10px 10px 10px;">
			<div id="ajaxerr-data">An unknown issue has occurred with the data replacement setup process.  Please see the installer-log.txt file for more details.</div>
			<div style="text-align:center; margin:10px auto 0px auto">
				<input type="button" onclick='Duplicator.hideErrorResult2()' value="&laquo; Try Again" /><br/><br/>
				<i style='font-size:11px'>See online help for more details at <a href='http://lifeinthegrid.com/support' target='_blank'>support.lifeinthegrid.com</a></i>
			</div>
		</div>
	</div>
</form>

