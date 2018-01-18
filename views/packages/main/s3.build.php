<?php
	//Nonce Check
	if (! isset( $_POST['dup_form_opts_nonce_field'] ) || ! wp_verify_nonce( $_POST['dup_form_opts_nonce_field'], 'dup_form_opts' ) ) {
		DUP_UI_Notice::redirect('admin.php?page=duplicator&tab=new1');
	}

	$Package = DUP_Package::getActive();
	$ajax_nonce	= wp_create_nonce('dup_package_build');

	//Help support Duplicator
	$atext0  = __('Help', 'duplicator') . "&nbsp;<a target='_blank' href='https://wordpress.org/support/plugin/duplicator/reviews/?filter=5'>";
	$atext0 .= __('review the plugin', 'duplicator') . '</a>!';

	//Get even more power & features with Duplicator Pro
	$atext1 = __('Want more power?  Try', 'duplicator');
	$atext1 .= "&nbsp;<a target='_blank' href='https://snapcreek.com/duplicator/?utm_source=duplicator_free&utm_medium=wordpress_plugin&utm_content=package_build_more_power&utm_campaign=duplicator_pro'>";
	$atext1 .=  __('Duplicator Pro', 'duplicator') . '</a>!';

	$rand_txt = array();
	$rand_txt[0] = $atext0;
	//$rand_txt[1] = $atext1;
?>

<style>
	div#dup-progress-area {text-align:center; max-width:800px; min-height:200px;  border:1px solid silver; border-radius:5px; margin:25px auto 10px auto; padding:0px; box-shadow: 0 8px 6px -6px #999;}
	div.dup-progress-title {font-size:22px;padding:5px 0 20px 0; font-weight: bold}
	div#dup-progress-area div.inner {padding:10px; line-height:22px}
	div#dup-progress-area h2.title {background-color:#efefef; margin:0px}
	div#dup-progress-area span.label {font-weight:bold}
	div#dup-msg-success {color:#18592A; padding:5px;}
	
	div.dup-msg-success-stats{color:#999;margin:5px 0; font-size:11px; line-height:13px}
	div.dup-msg-success-links {margin:20px 5px 5px 5px; font-size: 13px;}
	div#dup-progress-area div.done-title {font-size:18px; font-weight:bold; margin:0px 0px 10px 0px}
	div#dup-progress-area div.dup-panel-title {background-color: #dfdfdf;}
	div.hdr-pack-complete {font-size:18px; color:green; font-weight: bold}

	div#dup-create-area-nolink, div#dup-create-area-link {float:right; font-weight: bold; margin: 0; padding: 0}
	div#dup-create-area-link {display:none; margin-left: -5px}
	div#dup-progress-area div.dup-panel-panel { border-top: 1px solid silver}
	fieldset.download-area {border:2px dashed #dfdfdf; padding:20px 20px 10px 20px; border-radius:9px; margin: auto; width:400px }
	fieldset.download-area legend {font-weight: bold; font-size: 16px}
	button#dup-btn-installer, button#dup-btn-archive {min-width: 150px}
	div.one-click-download {margin:15px 0 10px 0; font-size:16px; font-weight: bold}
	div.one-click-download i.fa-bolt{padding-right: 5px}
	div.one-click-download i.fa-file-archive-o{padding-right: 5px}

	div.dup-button-footer {text-align:right; margin:20px 10px 0px 0px}
	button.button {font-size:16px !important; height:30px !important; font-weight:bold; padding:0px 10px 5px 10px !important; min-width: 150px }
	span.dup-btn-size {font-size:11px;font-weight: normal}
	p.get-pro {font-size:13px; color:#999; border-top:1px solid #eeeeee; padding:5px 0 0 0; margin:0; font-style:italic}
	div.dup-howto-exe {font-size:16px; font-style: italic; font-weight: bold; margin:45px 0 45px 0}

	/*HOST TIMEOUT */
	div#dup-msg-error {color:maroon; padding:5px;}
	div.dup-box-title {text-align: left; background-color:#F6F6F6}
	div.dup-box-title:hover { background-color:#efefef}
	div.dup-box-panel {text-align: left}
	div.no-top {border-top: none}
	div.dup-box-panel b.opt-title {font-size:18px}
	div.dup-msg-error-area {overflow-y: scroll; padding:5px 15px 15px 15px; max-height:170px; width:95%; border: 1px solid silver; border-radius: 4px; line-height: 22px}
	div#dup-logs {text-align:center; margin:auto; padding:5px; width:350px;}
	div#dup-logs a {display:inline-block;}
	span.sub-data {display: inline-block; padding-left:20px}
</style>

<!-- =========================================
TOOL BAR: STEPS -->
<table id="dup-toolbar">
	<tr valign="top">
		<td style="white-space: nowrap">
			<div id="dup-wiz">
				<div id="dup-wiz-steps">
					<div class="completed-step"><a>1-<?php _e('Setup', 'duplicator'); ?></a></div>
					<div class="completed-step"><a>2-<?php _e('Scan', 'duplicator'); ?> </a></div>
					<div class="active-step"><a>3-<?php _e('Build', 'duplicator'); ?> </a></div>
				</div>
				<div id="dup-wiz-title">
					<?php _e('Step 3: Build Package', 'duplicator'); ?>
				</div> 
			</div>
		</td>
		<td style="padding-bottom:4px">
			<div id="dup-create-area-nolink"><?php _e("Create New", 'duplicator'); ?></div>
			<div id="dup-create-area-link"><a href="admin.php?page=duplicator&tab=new1" class="add-new-h2"><?php _e("Create New", 'duplicator'); ?></a></div>
			<div style="float:right;margin: 0px 5px;"><a href="?page=duplicator" class="add-new-h2"><i class="fa fa-archive"></i> <?php _e("Packages", 'duplicator'); ?></a></div>
		</td>
	</tr>
</table>		
<hr class="dup-toolbar-line">


<form id="form-duplicator" method="post" action="?page=duplicator">
<?php wp_nonce_field('dup_form_opts', 'dup_form_opts_nonce_field', false); ?>

<!--  PROGRESS BAR -->
<div id="dup-progress-bar-area">
	<div class="dup-progress-title"><i class="fa fa-cog fa-spin"></i> <?php _e('Building Package', 'duplicator'); ?></div>
	<div id="dup-progress-bar"></div>
	<b><?php _e('Please Wait...', 'duplicator'); ?></b><br/><br/>
	<i><?php _e('Keep this window open during the build process.', 'duplicator'); ?></i><br/>
	<i><?php _e('This may take several minutes.', 'duplicator'); ?></i><br/>
</div>

<div id="dup-progress-area" class="dup-panel" style="display:none">
	<div class="dup-panel-title"><b style="font-size:22px"><?php _e('Build Status', 'duplicator'); ?></b></div>
	<div class="dup-panel-panel">

		<!--  =========================
		SUCCESS MESSAGE -->
		<div id="dup-msg-success" style="display:none">
			<div class="hdr-pack-complete">
				<i class="fa fa-check-square-o fa-lg"></i> <?php _e('Package Completed', 'duplicator'); ?>
			</div>

			<div class="dup-msg-success-stats">
				<!--b><?php _e('Name', 'duplicator'); ?>:</b> <span id="data-name-hash"></span><br/-->
				<b><?php _e('Process Time', 'duplicator'); ?>:</b> <span id="data-time"></span><br/>
			</div><br/>

			<!-- DOWNLOAD FILES -->
			<fieldset class="download-area">
				<legend>
					&nbsp; <?php _e("Download Files", 'duplicator') ?> <i class="fa fa-download"></i> &nbsp;
				</legend>
				<button id="dup-btn-installer" class="button button-primary button-large" title="<?php _e("Click to download installer file", 'duplicator') ?>">
					<i class="fa fa-bolt"></i> <?php _e("Installer", 'duplicator') ?> &nbsp;
				</button> &nbsp;
				<button id="dup-btn-archive" class="button button-primary button-large" title="<?php _e("Click to download archive file", 'duplicator') ?>">
					<i class="fa fa-file-archive-o"></i> <?php _e("Archive", 'duplicator') ?>
					<span id="dup-btn-archive-size" class="dup-btn-size"></span> &nbsp;
				</button>
				<div class="one-click-download">
					<a href="javascript:void(0)" id="dup-link-download-both" title="<?php _e("Click to download both files", 'duplicator') ?>"><i class="fa fa-bolt"></i><i class="fa fa-file-archive-o"></i><?php _e("One-Click Download", 'duplicator') ?></a>
					<sup><i class="fa fa-question-circle" style='font-size:11px'
					   data-tooltip-title="<?php _e("One Click:", 'duplicator'); ?>"
					   data-tooltip="<?php _e('Clicking this link will open both the installer and archive download prompts at the same time. '
					   . 'On some browsers you may have to disable pop-up warnings on this domain for this to work correctly.', 'duplicator'); ?>">
					</i></sup>
				</div>
			</fieldset>

			 <div class="dup-howto-exe">
                <a href="https://snapcreek.com/duplicator/docs/quick-start/?utm_source=duplicator_free&utm_medium=wordpress_plugin&utm_content=package_built_install_help&utm_campaign=duplicator_free#quick-040-q" target="_blank">
					<?php _e('How do I install this Package?', 'duplicator'); ?>
				</a>
            </div>
			
			<p class="get-pro">
				<?php echo $rand_txt[array_rand($rand_txt, 1)]; ?>
			</p>
		</div>

		<!--  =========================
		ERROR MESSAGE -->
		<div id="dup-msg-error" style="display:none; color:#000">
			<div class="done-title"><i class="fa fa-chain-broken"></i> <?php _e('Host Build Interrupt', 'duplicator'); ?></div>
			<b><?php _e('This server cannot complete the build due to setup constraints.', 'duplicator'); ?></b><br/>
			<i><?php _e("To help get you past this hosts limitation consider these three options:", 'duplicator'); ?></i>
			<br/><br/><br/>

			<!-- OPTION 1: TRY AGAIN -->
			<div class="dup-box">
				<div class="dup-box-title">
					<i class="fa fa-reply"></i>&nbsp;<?php _e('Try Again', 'duplicator'); ?>
					<div class="dup-box-arrow"><i class="fa fa-caret-down"></i></div>
				</div>
				<div class="dup-box-panel" id="dup-pack-build-try1" style="display:none">
					<b class="opt-title"><?php _e('OPTION 1:', 'duplicator'); ?></b><br/>

					<?php _e('The first pass for reading files on some budget hosts is slow and may conflict with strict timeout settings '
						. 'set up by the hosting provider.  If this is the case its recommended to retry the build.  <i>If the problem persists then consider the other options below.</i>', 'duplicator'); ?><br/><br/>

					<div style="text-align: center; margin: 10px">
						<input type="button" class="button-large button-primary" value="<?php _e('Retry Package Build', 'duplicator'); ?>" onclick="window.location = 'admin.php?page=duplicator&tab=new1&retry=1'" />
					</div>

					<div style="color:#777; padding: 15px 5px 5px 5px">
						<b> <?php _e('Notice', 'duplicator'); ?></b><br/>
						<?php printf('<b><i class="fa fa-folder-o"></i> %s %s</b> <br/> %s',
							__('Build Folder:'),
								DUPLICATOR_SSDIR_PATH_TMP,
							__("On some servers the build will continue to run in the background. To validate if a build is still running; open the 'tmp' folder above and see "
							. "if the archive file is growing in size or check the main packages screen to see if the package completed. If it is not then your server "
							. "has strict timeout constraints.", 'duplicator')
							);
						?> 
					</div>
				</div>
			</div>

			<!-- OPTION 2: Two-Part Install -->
			<div class="dup-box no-top">
				<div class="dup-box-title">
					<i class="fa fa-random"></i>&nbsp;<?php _e('Two-Part Install', 'duplicator'); ?>
					<div class="dup-box-arrow"><i class="fa fa-caret-down"></i></div>
				</div>
				<div class="dup-box-panel" id="dup-pack-build-try2" style="display:none">
					<b class="opt-title"><?php _e('OPTION 2:', 'duplicator'); ?></b><br/>

					<?php _e('A two-part install minimizes server load and can avoid I/O and CPU issues encountered on some budget hosts. With this procedure you simply build a '
						. '\'database-only\' archive, manually move the website files, and then run the installer to complete the process.', 'duplicator'); ?><br/><br/>

					<b><?php _e('<i class="fa fa-file-text-o"></i> Overview', 'duplicator'); ?></b><br/>
					<?php _e('Please follow these steps:', 'duplicator'); ?><br/>
					<ol>
						<li><?php _e('Click the button below to go back to Step 1.', 'duplicator'); ?></li>
						<li><?php _e('On Step 1 the "Archive Only the Database" checkbox will be auto checked.', 'duplicator'); ?></li>
						<li>
							<?php _e('Complete the package build and follow the ', 'duplicator'); ?>
							<?php
								printf('%s "<a href="https://snapcreek.com/duplicator/docs/quick-start/?utm_source=duplicator_free&utm_medium=wordpress_plugin&utm_content=host_interupt_2partlink&utm_campaign=build_issues#quick-060-q" target="faq">%s</a>".',
								__('', 'duplicator'),
								__('Quick Start Two-Part Install Instructions', 'duplicator'));
							?>
						</li>
					</ol> <br/>

					<div style="text-align: center; margin: 10px">
						<input type="checkbox" id="dup-two-part-check" onclick="Duplicator.Pack.ToggleTwoPart()">
						<label for="dup-two-part-check"><?php _e('Yes. I have read the above overview and would like to continue!', 'duplicator'); ?></label><br/><br/>
						<button id="dup-two-part-btn"  type="button" class="button-large button-primary" disabled="true" onclick="window.location = 'admin.php?page=duplicator&tab=new1&retry=2'">
							<i class="fa fa-random"></i> <?php _e('Start Two-Part Install Process', 'duplicator'); ?>
						</button>
					</div><br/>
				</div>
			</div>

			<!-- OPTION 3: DIAGNOSE SERVER -->
			<div class="dup-box no-top">
				<div class="dup-box-title">
					<i class="fa fa-cog"></i>&nbsp;<?php _e('Configure Server', 'duplicator'); ?>
					<div class="dup-box-arrow"><i class="fa fa-caret-down"></i></div>
				</div>
				<div class="dup-box-panel" id="dup-pack-build-try3" style="display:none">
					<b class="opt-title"><?php _e('OPTION 3:', 'duplicator'); ?></b><br/>
					<?php _e('This option is available on some hosts that allow for users to adjust server configurations.  With this option you will be directed to an FAQ page that will show '
					. 'various recommendations you can take to improve/unlock constraints set up on this server.', 'duplicator'); ?><br/><br/>

					<div style="text-align: center; margin: 10px; font-size:16px; font-weight: bold">
						<a href="https://snapcreek.com/duplicator/docs/faqs-tech/?utm_source=duplicator_free&utm_medium=wordpress_plugin&utm_content=host_interupt_diagnosebtn&utm_campaign=build_issues#faq-trouble-100-q" target="_blank">
							[<?php _e('Diagnose Server Setup', 'duplicator'); ?>]
						</a>
					</div>

					<b><?php _e('RUNTIME DETAILS', 'duplicator'); ?>:</b><br/>
					<div class="dup-msg-error-area">
					<div id="dup-msg-error-response-time">
							<span class="label"><?php _e("Allowed Runtime:", 'duplicator'); ?></span>
							<span class="data"></span>
						</div>
						<div id="dup-msg-error-response-php">
							<span class="label"><?php _e("PHP Max Execution", 'duplicator'); ?></span><br/>
							<span class="data sub-data">
								<span class="label"><?php _e("Time", 'duplicator'); ?>:</span>
								<?php
									$try_value = @ini_get('max_execution_time');
									$try_update = set_time_limit(0);
									echo "$try_value <a href='http://www.php.net/manual/en/info.configuration.php#ini.max-execution-time' target='_blank'> (default)</a>";
								?>
								<i class="fa fa-question-circle data-size-help"
									data-tooltip-title="<?php _e("PHP Max Execution Time", 'duplicator'); ?>"
									data-tooltip="<?php _e('This value is represented in seconds. A value of 0 means no timeout limit is set for PHP.', 'duplicator'); ?>"></i>
							</span><br/>

							<span class="data sub-data">
								<span class="label"><?php _e("Mode", 'duplicator'); ?>:</span>
								<?php
									$try_update = $try_update ? 'is dynamic' : 'value is fixed';
									echo "{$try_update}";
								?>
								<i class="fa fa-question-circle data-size-help"
									data-tooltip-title="<?php _e("PHP Max Execution Mode", 'duplicator'); ?>"
									data-tooltip="<?php _e('If the value is [dynamic] then its possible for PHP to run longer than the default.  '
										. 'If the value is [fixed] then PHP will not be allowed to run longer than the default. <br/><br/> If this value is larger than the [Allowed Runtime] above then '
										. 'the web server has been enabled with a timeout cap and is overriding the PHP max time setting.', 'duplicator'); ?>"></i>
							</span>
						</div>

						<div id="dup-msg-error-response-status">
							<span class="label"><?php _e("Server Status:", 'duplicator'); ?></span>
							<span class="data"></span>
						</div>
						<div id="dup-msg-error-response-text">
							<span class="label"><?php _e("Error Message:", 'duplicator'); ?></span><br/>
							<span class="data"></span>
						</div>
					</div>

					<!-- LOGS -->
					<div id="dup-logs">
						<br/>
						<i class="fa fa-list-alt"></i>
						<a href='javascript:void(0)' style="color:#000" onclick='Duplicator.OpenLogWindow(true)'><?php _e('Read Package Log File', 'duplicator');?></a>
						<br/><br/>
					</div>
				</div>
			</div>
			<br/><br/><br/>
		</div>

	</div>
</div>

</form>

<script>
jQuery(document).ready(function($) {
	/*	----------------------------------------
	*	METHOD: Performs Ajax post to create a new package
	*	Timeout (10000000 = 166 minutes)  */
	Duplicator.Pack.Create = function() {

		var startTime;
		var endTime;

		var data = {action : 'duplicator_package_build', nonce: '<?php echo $ajax_nonce; ?>'}

		$.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			timeout: 10000000,
			data: data,
			beforeSend: function() {startTime = new Date().getTime();},
			complete:   function() {
				endTime = new Date().getTime();
				var millis = (endTime - startTime);
				var minutes = Math.floor(millis / 60000);
				var seconds = ((millis % 60000) / 1000).toFixed(0);
				var status = minutes + ":" + (seconds < 10 ? '0' : '') + seconds;
				$('#dup-msg-error-response-time span.data').html(status);
				$('#dup-create-area-nolink').hide();
				$('#dup-create-area-link').show();
			},
			success:    function(data) { 
				$('#dup-progress-bar-area').hide(); 
				$('#dup-progress-area, #dup-msg-success').show(300);
				
				var Pack = data.Package;
				var InstallURL = Pack.StoreURL + Pack.Installer.File + "?get=1&file=" + Pack.Installer.File;
				var ArchiveURL = Pack.StoreURL + Pack.Archive.File   + "?get=1";
				
				$('#dup-btn-archive-size').append('&nbsp; (' + data.ZipSize + ')')
				$('#data-name-hash').text(Pack.NameHash || 'error read');
				$('#data-time').text(data.Runtime || 'unable to read time');
				
				//Wire Up Downloads
				$('#dup-btn-installer').on("click", {name: InstallURL }, Duplicator.Pack.DownloadFile  );
				$('#dup-btn-archive').on("click",   {name: ArchiveURL }, Duplicator.Pack.DownloadFile  );

				$('#dup-link-download-both').on("click",   function() {
					 window.open(InstallURL);
					 window.open(ArchiveURL);

				});

				
			},
			error: function(data) { 
				$('#dup-progress-bar-area').hide(); 
				$('#dup-progress-area, #dup-msg-error').show(200);
				var status = data.status + ' -' + data.statusText;
				var response = (data.responseText != undefined && data.responseText.trim().length > 1) ? data.responseText.trim() : 'No client side error - see package log file';
				$('#dup-msg-error-response-status span.data').html(status)
				$('#dup-msg-error-response-text span.data').html(response);
				console.log(data);
			}
		});
		return false;
	}

	Duplicator.Pack.ToggleTwoPart = function() {
		var $btn = $('#dup-two-part-btn');
		if ($('#dup-two-part-check').is(':checked')) {
			$btn.removeAttr("disabled");
		} else {
			$btn.attr("disabled", true);
		}
	};

	//Page Init:
	Duplicator.UI.AnimateProgressBar('dup-progress-bar');
	Duplicator.Pack.Create();

});
</script>