<?php
	require_once (DUPLICATOR_PLUGIN_PATH . 'classes/package.php');
	global $wp_version;
	$Package = new DUP_Package();
	$Package->SaveActive($_POST);
	$Package = DUP_Package::GetActive();
	
	$package_mysqldump	= DUP_Settings::Get('package_mysqldump');
	$mysqlDumpPath = DUP_Database::GetMySqlDumpPath();
	$build_mode = ($mysqlDumpPath && $package_mysqldump) ? 'mysqldump (fast)' : 'PHP (slow)';
?>

<style>
	/* ============----------
	PROGRESS ARES-CHECKS */
	div#dup-progress-area {text-align:center; max-width:650px; min-height:200px; margin:0px auto 0px auto; padding:0px;}
	div#dup-msg-success {color:#18592A; padding:5px; text-align: left}	
	div#dup-msg-success-subtitle {font-style: italic; margin:7px 0px}	
	div#dup-msg-error {color:#A62426; padding:5px; max-width: 790px;}
	div#dup-msg-error-response-text { max-height:350px; overflow-y:scroll; border:1px solid silver; border-radius: 3px; padding:8px;background:#fff}

	div.dup-panel {margin-bottom: 25px}
	div.dup-scan-filter-status {display:inline; float: right; font-size:11px; margin-right:10px; color:#AF0000; font-style: italic}
	/* 	SERVER-CHECKS */
	div.dup-scan-title {display:inline-block;  padding:1px; font-weight: bold;}
	div.dup-scan-title a {display:inline-block; min-width:200px; padding:3px; }
	div.dup-scan-title a:focus {outline: 1px solid #fff; box-shadow: none}
	div.dup-scan-title div {display:inline-block;  }
	div.dup-scan-info {display:none;}
	div.dup-scan-good {display:inline-block; color:green;font-weight: bold;}
	div.dup-scan-warn {display:inline-block; color:#AF0000;font-weight: bold;}
	span.dup-toggle {float:left; margin:0 2px 2px 0; }
	/*DATABASE*/
	table#dup-scan-db-details {line-height: 14px; margin:15px 0px 0px 5px;  width:98%}
	table#dup-scan-db-details td {padding:0px;}
	table#dup-scan-db-details td:first-child {font-weight: bold;  white-space: nowrap; width:90px}
	div#dup-scan-db-info {margin:0px 0px 0px 10px}
	div#data-db-tablelist {max-height: 300px; overflow-y: scroll}
	div#data-db-tablelist div{padding:0px 0px 0px 15px;}
	div#data-db-tablelist span{display:inline-block; min-width: 75px}
	div#data-db-size1 {display: inline-block; float:right; font-size:11px; margin-right: 15px; font-style: italic}
	/*FILES */
	div#data-arc-size1 {display: inline-block; float:right; font-size:11px; margin-right: 15px; font-style: italic}
	div#data-arc-names-data, div#data-arc-big-data
		{word-wrap: break-word;font-size:10px; border:1px dashed silver; padding:5px; display: none}
	
	/*Footer*/
	div.dup-button-footer {text-align:center; margin:0}
	button.button {font-size:15px !important; height:30px !important; font-weight:bold; padding:3px 5px 5px 5px !important;}
</style>

<!-- =========================================
TOOL BAR: STEPS -->
<table id="dup-toolbar">
	<tr valign="top">
		<td style="white-space: nowrap">
			<div id="dup-wiz">
				<div id="dup-wiz-steps">
					<div class="completed-step"><a><span>1</span> <?php DUP_Util::_e('Setup'); ?></a></div>
					<div class="active-step"><a><span>2</span> <?php DUP_Util::_e('Scan'); ?> </a></div>
					<div><a><span>3</span> <?php DUP_Util::_e('Build'); ?> </a></div>
				</div>
				<div id="dup-wiz-title">
					<?php DUP_Util::_e('Step 2: System Scan'); ?>
				</div> 
			</div>	
		</td>
		<td class="dup-toolbar-btns">
			<a id="dup-pro-create-new"  href="?page=duplicator" class="add-new-h2"><i class="fa fa-archive"></i> <?php DUP_Util::_e("All Packages"); ?></a> &nbsp;
			<span> <?php DUP_Util::_e("Create New"); ?></span>
		</td>
	</tr>
</table>		
<hr style="margin-bottom:10px">


<form id="form-duplicator" method="post" action="?page=duplicator&tab=new3">
<div id="dup-progress-area">
	<!--  PROGRESS BAR -->
	<div id="dup-progress-bar-area">
		<h2><i class="fa fa-spinner fa-spin"></i> <?php DUP_Util::_e('Scanning Site'); ?></h2>
		<div id="dup-progress-bar"></div>
		<b><?php DUP_Util::_e('Please Wait...'); ?></b>
	</div>

	<!--  SUCCESS MESSAGE -->
	<div id="dup-msg-success" style="display:none">
		<div style="text-align:center">
			<div class="dup-hdr-success"><i class="fa fa-check-square-o fa-lg"></i> <?php DUP_Util::_e('Scan Complete'); ?></div>
			<div id="dup-msg-success-subtitle">
				<?php DUP_Util::_e("Scan checks are not required to pass, however they could cause issues on some systems."); ?><br/>
				<?php DUP_Util::_e("Process Time:"); ?> <span id="data-rpt-scantime"></span>
			</div>
		</div><br/>
		
		<!-- ================================================================
		META-BOX: SERVER
		================================================================ -->
		<div class="dup-panel">
		<div class="dup-panel-title">
			<i class="fa fa-hdd-o"></i> <?php 	DUP_Util::_e("Server");	?>
			<div style="float:right; margin:-1px 10px 0px 0px">
				<small><a href="?page=duplicator-tools&tab=diagnostics" target="_blank"><?php DUP_Util::_e('Diagnostics');?></a></small>
			</div>
		
		</div>
		<div class="dup-panel-panel">
			<!-- ============
			WEB SERVER -->
			<div>
				<div class='dup-scan-title'>
					<a><?php DUP_Util::_e('Web Server');?></a> <div id="data-srv-web-all"></div>
				</div>
				<div class='dup-scan-info dup-info-box'>
					<?php
					$web_servers = implode(', ', $GLOBALS['DUPLICATOR_SERVER_LIST']);
					echo '<span id="data-srv-web-model"></span>&nbsp;<b>' . DUP_Util::__('Web Server') . ":</b>&nbsp; '{$_SERVER['SERVER_SOFTWARE']}'";	
					echo '<small>';
					DUP_Util::_e("Supported web servers:");
					echo "{$web_servers}";
					echo '</small>';
					?>
				</div>
			</div>				
			<!-- ============
			PHP SETTINGS -->
			<div>
				<div class='dup-scan-title'>
					<a><?php DUP_Util::_e('PHP Setup');?></a> <div id="data-srv-php-all"></div>
				</div>
				<div class='dup-scan-info dup-info-box'>
					<?php 
					//OPEN_BASEDIR
					$test = ini_get("open_basedir");
					echo '<span id="data-srv-php-openbase"></span>&nbsp;<b>' . DUP_Util::__('Open Base Dir') . ":</b>&nbsp; '{$test}' <br/>";
					echo '<small>';
					DUP_Util::_e('Issues might occur when [open_basedir] is enabled. Work with your server admin to disable this value in the php.ini file if you’re having issues building a package.');
					echo "&nbsp;<i><a href='http://www.php.net/manual/en/ini.core.php#ini.open-basedir' target='_blank'>[" . DUP_Util::__('details') . "]</a></i><br/>";
					echo '</small>';

					//MAX_EXECUTION_TIME
					$test = (set_time_limit(0)) ? 0 : ini_get("max_execution_time");
					echo '<hr size="1" /><span id="data-srv-php-maxtime"></span>&nbsp;<b>' . DUP_Util::__('Max Execution Time') . ":</b>&nbsp; '{$test}' <br/>";
					echo '<small>';
					printf(DUP_Util::__('Issues might occur for larger packages when the [max_execution_time] value in the php.ini is too low.  The minimum recommended timeout is "%1$s" seconds or higher. An attempt is made to override this value if the server allows it.  A value of 0 (recommended) indicates that PHP has no time limits.'), DUPLICATOR_SCAN_TIMEOUT);
					echo '<br/><br/>';
					DUP_Util::_e('Note: Timeouts can also be set at the web server layer, so if the PHP max timeout passes and you still see a build interrupt messages, then your web server could be killing the process.   If you are limited on processing time, consider using the database or file filters to shrink the size of your overall package.   However use caution as excluding the wrong resources can cause your install to not work properly.');
					echo "&nbsp;<i><a href='http://www.php.net/manual/en/info.configuration.php#ini.max-execution-time' target='_blank'>[" .DUP_Util::__('details')  . "]</a></i>";
					echo '</small>';
					
					//MYSQLI
					echo '<hr size="1" /><span id="data-srv-php-mysqli"></span>&nbsp;<b>' . DUP_Util::__('MySQLi') . "</b> <br/>";
					echo '<small>';
					DUP_Util::_e('Creating the package does not require the mysqli module.  However the installer.php file requires that the PHP module mysqli be installed on the server it is deployed on.');
					echo "&nbsp;<i><a href='http://php.net/manual/en/mysqli.installation.php' target='_blank'>[" . DUP_Util::__('details') . "]</a></i>";
					echo '</small>';
					?>
				</div>
			</div>
			<!-- ============
			WORDPRESS SETTINGS -->
			<div>
				<div class='dup-scan-title'>
					<a><?php DUP_Util::_e('WordPress');?></a> <div id="data-srv-wp-all"></div>
				</div>
				<div class='dup-scan-info dup-info-box'>
					<?php 
					//VERSION CHECK
					echo '<span id="data-srv-wp-version"></span>&nbsp;<b>' . DUP_Util::__('WordPress Version') . ":</b>&nbsp; '{$wp_version}' <br/>";
					echo '<small>';
					printf(DUP_Util::__('It is recommended to have a version of WordPress that is greater than %1$s'), DUPLICATOR_SCAN_MIN_WP);
					echo '</small>';
				
					//CORE FILES
					echo '<hr size="1" /><span id="data-srv-wp-core"></span>&nbsp;<b>' . DUP_Util::__('Core Files') . "</b> <br/>";
					echo '<small>';
					DUP_Util::_e("If the scanner is unable to locate the wp-config.php file in the root directory, then you will need to manually copy it to its new location.");
					echo '</small>';

					//CACHE DIR
					$cache_path = $cache_path = DUP_Util::SafePath(WP_CONTENT_DIR) . '/cache';
					$cache_size = DUP_Util::ByteSize(DUP_Util::GetDirectorySize($cache_path));
					echo '<hr size="1" /><span id="data-srv-wp-cache"></span>&nbsp;<b>' . DUP_Util::__('Cache Path') . ":</b>&nbsp; '{$cache_path}' ({$cache_size}) <br/>";
					echo '<small>';
					DUP_Util::_e("Cached data will lead to issues at install time and increases your archive size. It is recommended to empty your cache directory at build time. Use caution when removing data from the cache directory. If you have a cache plugin review the documentation for how to empty it; simply removing files might cause errors on your site. The cache size minimum threshold is currently set at ");
					echo DUP_Util::ByteSize(DUPLICATOR_SCAN_CACHESIZE) . '.';
					echo '</small>';
						
					?>
				</div>
			</div>
		</div><!-- end .dup-panel -->
		</div><!-- end .dup-panel-panel -->
	
		<h2 style="font-size:18px; font-weight:bold; margin:-15px 0 0 10px"><i class="fa fa-file-archive-o"></i>&nbsp;<?php DUP_Util::_e('Archive');?> </h2>
		
		<!-- ================================================================
		FILES
		================================================================ -->
		<div class="dup-panel">
			<div class="dup-panel-title">
				<i class="fa fa-files-o"></i>
				<?php DUP_Util::_e("Files"); ?> 
				<div id="data-arc-size1"></div>
				<div class="dup-scan-filter-status">
					<?php 
						if ($Package->Archive->FilterOn) {
							echo '<i class="fa fa-filter"></i> '; DUP_Util::_e('Enabled');
						} 
					?> 
				</div>
			</div>
			<div class="dup-panel-panel">

				<!-- ============
				TOTAL SIZE -->
				<div>
					<div class='dup-scan-title'>
						<a><?php DUP_Util::_e('Total Size');?></a> <div id="data-arc-status-size"></div>
					</div>
					<div class='dup-scan-info  dup-info-box'>
						<b><?php DUP_Util::_e('Size');?>:</b> <span id="data-arc-size2"></span>  &nbsp; | &nbsp;
						<b><?php DUP_Util::_e('File Count');?>:</b> <span id="data-arc-files"></span>  &nbsp; | &nbsp;
						<b><?php DUP_Util::_e('Directory Count');?>:</b> <span id="data-arc-dirs"></span> 
						<small>
						<?php 
							printf(DUP_Util::__('Total size represents all files minus any filters that have been setup.  The current thresholds that trigger warnings are %1$s for the entire site and %2$s for large files.'), 
									DUP_Util::ByteSize(DUPLICATOR_SCAN_SITE), 
									DUP_Util::ByteSize(DUPLICATOR_SCAN_WARNFILESIZE));
						?>
						</small>
					</div>
				</div>		

				<!-- ============
				FILE NAME LENGTHS -->
				<div>
					<div class='dup-scan-title'>
						<a><?php DUP_Util::_e('Invalid Names');?></a> <div id="data-arc-status-names"></div>
					</div>
					<div class='dup-scan-info dup-info-box'>
						<small>
						<?php 
							DUP_Util::_e('Invalid file or folder names can cause issues when extracting an archive across different environments.  Invalid file names consist of lengths over 250 characters and illegal characters that may not work on all operating systems such as * ? > < : / \ |  .  It is recommended to remove or filter these files before building the archive or else you might have issues at install time.');
						?>
						</small><br/>
						<a href="javascript:void(0)" onclick="jQuery('#data-arc-names-data').toggle()">[<?php DUP_Util::_e('Show Paths');?>]</a>							
						<div id="data-arc-names-data"></div>
					</div>
				</div>		

				<!-- ============
				LARGE FILES -->
				<div>
					<div class='dup-scan-title'>
						<a><?php DUP_Util::_e('Large Files');?></a> <div id="data-arc-status-big"></div>
					</div>
					<div class='dup-scan-info  dup-info-box'>
						<small>
						<?php 
							printf(DUP_Util::__('Large files such as movies or other backuped data can cause issues with timeouts.  The current check for large files is %1$s per file.  If your having issues creating a package consider excluding these files with the files filter and manually moving them to your new location.'), 
									DUP_Util::ByteSize(DUPLICATOR_SCAN_WARNFILESIZE));
						?>
						</small><br/>
						<a href="javascript:void(0)" onclick="jQuery('#data-arc-big-data').toggle()">[<?php DUP_Util::_e('Show Paths');?>]</a>
						<div id="data-arc-big-data"></div>
					</div>
				</div>	
				
				<!-- ============
				VIEW FILTERS -->
				<?php if ($Package->Archive->FilterOn) : ?>
					<div>
						<div class='dup-scan-title'>
							<a><?php DUP_Util::_e('View Filters');?></a> 
						</div>
						<div class='dup-scan-info  dup-info-box'>							
							<b>[<?php DUP_Util::_e('Directories');?>]</b><br/>
							<?php
								if (strlen( $Package->Archive->FilterDirs)) {
									echo str_replace(";", "<br/>", $Package->Archive->FilterDirs); 
								} else {
									DUP_Util::_e('No directory filters have been set.');
								}
							?>
							<br/>
							
							<b>[<?php DUP_Util::_e('File Extensions');?>]</b><br/>
							<?php
								if (strlen( $Package->Archive->FilterExts)) {
									echo $Package->Archive->FilterExts; 
								} else {
									DUP_Util::_e('No file extension filters have been set.');
								}
							?>	
													<small>
							<?php DUP_Util::_e('The lists above are the directories and file extension that will be excluded from the archive.'); ?>
						</small><br/>
						</div>

					</div>	
				<?php endif;  ?>	

			</div><!-- end .dup-panel -->
			<br/>

			<!-- ================================================================
			DATABASE
			================================================================ -->
			<div class="dup-panel-title">
				<i class="fa fa-table"></i>
				<?php DUP_Util::_e("Database");	?>
				<div id="data-db-size1"></div>
				<div class="dup-scan-filter-status">
					<?php 
						if ($Package->Database->FilterOn) {
							echo '<i class="fa fa-filter"></i> '; DUP_Util::_e('Enabled');
						} 
					?> 
				</div>
			</div>
			<div class="dup-panel-panel" id="dup-scan-db">

				<!-- ============
				TOTAL SIZE -->
				<div>
					<div class='dup-scan-title'>
						<a><?php DUP_Util::_e('Total Size');?></a>
						<div id="data-db-status-size1"></div>
					</div>
					<div class='dup-scan-info  dup-info-box'>
						<b><?php DUP_Util::_e('Tables');?>:</b> <span id="data-db-tablecount"></span> &nbsp; | &nbsp;
						<b><?php DUP_Util::_e('Records');?>:</b> <span id="data-db-rows"></span> &nbsp; | &nbsp;
						<b><?php DUP_Util::_e('Size');?>:</b> <span id="data-db-size2"></span> <br/><br/>
						<?php 
							$lnk = '<a href="maint/repair.php" target="_blank">' . DUP_Util::__('repair and optimization') . '</a>';
							printf(DUP_Util::__('Total size and row count for all database tables are approximate values.  The thresholds that trigger warnings are %1$s and %2$s records.  Large databases take time to process and can cause issues with server timeout and memory settings.  Running a %3$s on your database can also help improve the overall size and performance.  If your server supports shell_exec and mysqldump you can try to enable this option from the settings menu.'), 
									DUP_Util::ByteSize(DUPLICATOR_SCAN_DBSIZE), 
									number_format(DUPLICATOR_SCAN_DBROWS),
									$lnk);
						?>
					</div>
				</div>

				<!-- ============
				TABLE DETAILS -->
				<div>
					<div class='dup-scan-title'>
						<a><?php DUP_Util::_e('Table Details');?></a>
						<div id="data-db-status-size2"></div>
					</div>
					<div class='dup-scan-info dup-info-box'>
						<div id="dup-scan-db-info">
							<div id="data-db-tablelist"></div>
						</div>
					</div>
				</div>

				<table id="dup-scan-db-details">
					<tr><td><b><?php DUP_Util::_e('Name:');?></b></td><td><?php echo DB_NAME ;?> </td></tr>
					<tr><td><b><?php DUP_Util::_e('Host:');?></b></td><td><?php echo DB_HOST ;?> </td></tr>
					<tr><td><b><?php DUP_Util::_e('Build Mode:');?></b></td><td><a href="?page=duplicator-settings" target="_blank"><?php echo $build_mode ;?></a> </td></tr>
				</table>	

			</div><!-- end .dup-panel -->
		</div><!-- end .dup-panel-panel -->
		
		
	</div>

	<!--  ERROR MESSAGE -->
	<div id="dup-msg-error" style="display:none">
		<div class="dup-hdr-error"><i class="fa fa-exclamation-circle"></i> <?php DUP_Util::_e('Scan Error'); ?></div>
		<i><?php DUP_Util::_e('Please try again!'); ?></i><br/>
		<div style="text-align:left">
			<b><?php DUP_Util::_e("Server Status:"); ?></b> &nbsp;
			<div id="dup-msg-error-response-status" style="display:inline-block"></div><br/>

			<b><?php DUP_Util::_e("Error Message:"); ?></b>
			<div id="dup-msg-error-response-text"></div>
		</div>
	</div>			
</div> <!-- end #dup-progress-area -->

<div class="dup-button-footer" style="display:none">
	<input type="button" value="&#9668; <?php DUP_Util::_e("Back") ?>" onclick="window.location.assign('?page=duplicator&tab=new1')" class="button button-large" />
	<input type="button" value="<?php DUP_Util::_e("Rescan") ?>" onclick="Duplicator.Pack.Rescan()" class="button button-large" />
	<input type="submit" value="<?php DUP_Util::_e("Build") ?> &#9658" class="button button-primary button-large" id="dup-build-button" />
	<!-- Used for iMacros testing do not remove -->
	<div id="dup-automation-imacros"></div>
</div>
</form>

<script type="text/javascript">
jQuery(document).ready(function($) {
		
	/*	========================--
	*	METHOD: Performs Ajax post to create check system  */
	Duplicator.Pack.Scan = function() {
		var data = {action : 'duplicator_package_scan'}

		$.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			timeout: 10000000,
			data: data,
			complete: function() {$('.dup-button-footer').show()},
			success:    function(data) { 
				Duplicator.Pack.LoadScanData(data)
				//Imacros testing required
				$('#dup-automation-imacros').html('<input type="hidden" id="dup-finished" value="done" />');
			},
			error: function(data) { 
				$('#dup-progress-bar-area').hide(); 
				var status = data.status + ' -' + data.statusText;
				$('#dup-msg-error-response-status').html(status)
				$('#dup-msg-error-response-text').html(data.responseText);
				$('#dup-msg-error').show(200);
				console.log(data);
			}
		});
	}
	
	Duplicator.Pack.Rescan = function() {
		$('#dup-msg-success,#dup-msg-error,.dup-button-footer').hide();
		$('#dup-progress-bar-area').show(); 
		Duplicator.Pack.Scan();
	}
	
	Duplicator.Pack.LoadScanStatus = function(status) {
		var result;
		switch (status) {
			case false :    result = '<div class="dup-scan-warn"><i class="fa fa-exclamation-triangle"></i></div>';      break;
			case 'Warn' :   result = '<div class="dup-scan-warn"><i class="fa fa-exclamation-triangle"></i> Warn</div>'; break;
			case true :     result = '<div class="dup-scan-good"><i class="fa fa-check"></i></div>';	                 break;
			case 'Good' :   result = '<div class="dup-scan-good"><i class="fa fa-check"></i> Good</div>';                break;
			default :
				result = 'unable to read';
		}
		return result;
	}
	
	/*	========================--
	*	METHOD:    */
	Duplicator.Pack.LoadScanData = function(data) {
		
		var errMsg = "unable to read";
		$('#dup-progress-bar-area').hide(); 
		$('#dup-msg-success').show();
		
		//****************
		//REPORT
		var base = $('#data-rpt-scanfile').attr('href');
		$('#data-rpt-scanfile').attr('href',  base + '&scanfile=' + data.RPT.ScanFile);
		$('#data-rpt-scantime').text(data.RPT.ScanTime || 0);
		

		//****************
		//SERVER
		$('#data-srv-web-model').html(Duplicator.Pack.LoadScanStatus(data.SRV.WEB.model));
		$('#data-srv-web-all').html(Duplicator.Pack.LoadScanStatus(data.SRV.WEB.ALL));

		$('#data-srv-php-openbase').html(Duplicator.Pack.LoadScanStatus(data.SRV.PHP.openbase));
		$('#data-srv-php-maxtime').html(Duplicator.Pack.LoadScanStatus(data.SRV.PHP.maxtime));
		$('#data-srv-php-mysqli').html(Duplicator.Pack.LoadScanStatus(data.SRV.PHP.mysqli));
		$('#data-srv-php-openssl').html(Duplicator.Pack.LoadScanStatus(data.SRV.PHP.openssl));
		$('#data-srv-php-all').html(Duplicator.Pack.LoadScanStatus(data.SRV.PHP.ALL));

		$('#data-srv-wp-version').html(Duplicator.Pack.LoadScanStatus(data.SRV.WP.version));
		$('#data-srv-wp-core').html(Duplicator.Pack.LoadScanStatus(data.SRV.WP.core));
		$('#data-srv-wp-cache').html(Duplicator.Pack.LoadScanStatus(data.SRV.WP.cache));
		$('#data-srv-wp-all').html(Duplicator.Pack.LoadScanStatus(data.SRV.WP.ALL));
		
		//****************
		//DATABASE
		var html = "";
		if (data.DB.Status.Success) {
			$('#data-db-status-size1').html(Duplicator.Pack.LoadScanStatus(data.DB.Status.Size));
			$('#data-db-status-size2').html(Duplicator.Pack.LoadScanStatus(data.DB.Status.Size));
			$('#data-db-size1').text(data.DB.Size || errMsg);
			$('#data-db-size2').text(data.DB.Size || errMsg);
			$('#data-db-rows').text(data.DB.Rows || errMsg);
			$('#data-db-tablecount').text(data.DB.TableCount || errMsg);
			//Table Details
			if (data.DB.TableList == undefined || data.DB.TableList.length == 0) {
				html = '<?php DUP_Util::_e("Unable to report on any tables") ?>';
			} else {
				$.each(data.DB.TableList, function(i) {
					html += '<b>' + i  + '</b><br/>';
					$.each(data.DB.TableList[i], function(key,val) {html += '<div><span>' + key  + ':</span>' + val + '</div>'; })
				});					
			}
			$('#data-db-tablelist').append(html);
		} else {
			html = '<?php DUP_Util::_e("Unable to report on database stats") ?>';
			$('#dup-scan-db').html(html);
		}
		
		//****************
		//ARCHIVE
		$('#data-arc-status-size').html(Duplicator.Pack.LoadScanStatus(data.ARC.Status.Size));
		$('#data-arc-status-names').html(Duplicator.Pack.LoadScanStatus(data.ARC.Status.Names));
		$('#data-arc-status-big').html(Duplicator.Pack.LoadScanStatus(data.ARC.Status.Big));
		$('#data-arc-size1').text(data.ARC.Size || errMsg);
		$('#data-arc-size2').text(data.ARC.Size || errMsg);
		$('#data-arc-files').text(data.ARC.FileCount || errMsg);
		$('#data-arc-dirs').text(data.ARC.DirCount || errMsg);
	
		//Invalid Names
		html = '<?php DUP_Util::_e("No name length issues.") ?>';
		if (data.ARC.WarnFileName != undefined && data.ARC.WarnFileName.length > 0) {
			html = '';
			$.each(data.ARC.WarnFileName, function(key, val) {html += '<?php DUP_Util::_e("FILE") ?> ' + key + ':<br/>[' + val  + ']<br/>';});
		}
		$('#data-arc-names-data').html(html);
		
		//Large Files
		html = '<?php DUP_Util::_e("No large files found.") ?>';
		if (data.ARC.WarnFileSize != undefined && data.ARC.WarnFileSize.length > 0) {
			html = '';
			$.each(data.ARC.WarnFileSize, function(key, val) {html += '<?php DUP_Util::_e("FILE") ?> ' + key + ':<br/>' + val  + '<br/>' ;});	
		}
		$('#data-arc-big-data').html(html);
		
	}
	
	//Page Init:
	Duplicator.UI.AnimateProgressBar('dup-progress-bar');
	Duplicator.Pack.Scan();
	
	//Init: Toogle for system requirment detial links
	$('.dup-scan-title a').each(function() {
		$(this).attr('href', 'javascript:void(0)');
		$(this).click({selector : '.dup-scan-info'}, Duplicator.Pack.ToggleSystemDetails);
		$(this).prepend("<span class='ui-icon ui-icon-triangle-1-e dup-toggle' />");
	});
	
});
</script>