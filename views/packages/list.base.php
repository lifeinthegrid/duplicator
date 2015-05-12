<?php
	$qryResult = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}duplicator_packages` ORDER BY id DESC", ARRAY_A);
	$qryStatus = $wpdb->get_results("SELECT status FROM `{$wpdb->prefix}duplicator_packages` WHERE status >= 100", ARRAY_A);
	$totalElements = count($qryResult);
	$statusCount   = count($qryStatus);
	$package_debug = DUP_Settings::Get('package_debug');
?>

<style>
	div#dup-list-alert-nodata {padding:50px 20px;text-align:center; font-size:20px; line-height:26px}
	div.dup-notice-msg {border:1px solid silver; padding: 10px; border-radius: 5px; width: 550px; 
		margin:40px auto 0px auto; font-size:12px; text-align: left; word-break:normal;
		background: #fefcea; 
		background: -moz-linear-gradient(top,  #fefcea 0%, #efe5a2 100%);
		background: -ms-linear-gradient(top,  #fefcea 0%,#efe5a2 100%);
		background: linear-gradient(to bottom,  #fefcea 0%,#efe5a2 100%);
	}
	input#dup-bulk-action-all {margin:0px;padding:0px 0px 0px 5px;}
	button.dup-button-selected {border:1px solid #000 !important; background-color:#dfdfdf !important;}
	div.dup-details-area-error {padding:10px; background-color:#FEF6F3; width:98%; border:1px solid silver; border-radius: 3px }
	
	/* Table package details */
	table.dup-pack-table {word-break:break-all;}
	table.dup-pack-table th {white-space:nowrap !important;}
	table.dup-pack-table td.pack-name {text-overflow:ellipsis; white-space:nowrap}
	table.dup-pack-table input[name="delete_confirm"] {margin-left:15px}
	table.dup-pack-table td.fail {border-left: 4px solid #d54e21;}
	table.dup-pack-table td.pass {border-left: 4px solid #2ea2cc;}
	tr.dup-pack-info td {white-space:nowrap; padding:12px 30px 0px 7px;}
	tr.dup-pack-info td.get-btns {text-align:right; padding:3px 5px 6px 0px !important;}
	td.dup-pack-details {display:none; padding:5px 0px 15px 15px; line-height:22px;}
	textarea.dup-pack-debug {width:98%; height:300px; font-size:11px; display:none}
</style>

<form id="form-duplicator" method="post">

<?php if($statusCount >= 3)  :	?>
	<div style="font-size:13px; position: absolute; top:10px; right:20px">
		<a href="admin.php?page=duplicator-about"  style="color:maroon"><i><i class="fa fa-check-circle"></i> <?php _e("Help Support Duplicator", 'wpduplicator') ?></i> </a>
	</div>
<?php endif; ?>	

<!-- ====================
TOOL-BAR -->
<table id="dup-toolbar">
	<tr valign="top">
		<td style="white-space: nowrap">
			<div class="alignleft actions">
				<select id="dup-pack-bulk-actions">
					<option value="-1" selected="selected"><?php _e("Bulk Actions", 'wpduplicator') ?></option>
					<option value="delete" title="<?php _e("Delete selected package(s)", 'wpduplicator') ?>"><?php _e("Delete", 'wpduplicator') ?></option>
				</select>
				<input type="button" id="dup-pack-bulk-apply" class="button action" value="<?php _e("Apply", 'wpduplicator') ?>" onclick="Duplicator.Pack.Delete()">
			</div>
			<br class="clear">
		</td>
		<td align="center">
			<a href="?page=duplicator-tools" id="btn-logs-dialog" class="button"  title="<?php _e("Package Logs", 'wpduplicator') ?>..."><i class="fa fa-list-alt"></i>
		</td>
		<td class="dup-toolbar-btns">
			<span><i class="fa fa-archive"></i> <?php _e("All Packages", 'wpduplicator'); ?></span> &nbsp;
			<a id="dup-pro-create-new"  href="?page=duplicator&tab=new1" class="add-new-h2"><?php _e("Create New", 'wpduplicator'); ?></a>
		</td>
	</tr>
</table>	


<?php if($totalElements == 0)  :	?>
	
	<!-- ====================
	NO-DATA MESSAGES-->
	<table class="widefat dup-pack-table">
		<thead><tr><th>&nbsp;</th></tr></thead>
		<tbody><tr><td><?php include (DUPLICATOR_PLUGIN_PATH .  "views/packages/list-nodata.php") ?> </td></tr></tbody>
		<tfoot><tr><th>&nbsp;</th></tr></tfoot>
	</table>
	
<?php else : ?>	
	

	
	<!-- ====================
	LIST ALL PACKAGES -->
	<table class="widefat dup-pack-table">
		<thead>
			<tr>
				<th><input type="checkbox" id="dup-bulk-action-all"  title="<?php _e("Select all packages", 'wpduplicator') ?>" style="margin-left:15px" onclick="Duplicator.Pack.SetDeleteAll()" /></th>
				<th><?php _e("Details", 'wpduplicator') ?></th>
				<th><?php _e("Created", 'wpduplicator') ?></th>
				<th><?php _e("Size", 'wpduplicator') ?></th>
				<th style="width:90%;"><?php _e("Name", 'wpduplicator') ?></th>
				<th style="text-align:center;" colspan="2">
					<?php _e("Package",  'wpduplicator')?>
				</th>
			</tr>
		</thead>
		<?php

		$rowCount = 0;
		$totalSize = 0;
		$rows = $qryResult;
		foreach ($rows as $row) {
			$Package = unserialize($row['package']);
			
			if (is_object($Package)) {
				 $pack_name			= $Package->Name;
				 $pack_archive_size = $Package->Archive->Size;
				 $pack_version      = $Package->Version;
				 $pack_notes		= $Package->Notes;
				 $pack_storeurl		= $Package->StoreURL;
				 $pack_namehash	    = $Package->NameHash;		
			} else {
				 $pack_archive_size = 0;
				 $pack_version	    = 'unknown';
				 $pack_notes		= 'unknown';
				 $pack_storeurl		= 'unknown';
				 $pack_name			= 'unknown';
				 $pack_namehash	    = 'unknown';	
			}
			
			$detail_id		= "duplicator-detail-row-{$rowCount}";
			$plugin_version = empty($pack_version) ? 'unknown' : $pack_version;
			$plugin_compat  = version_compare($plugin_version, '0.5.0');
			$notes          = empty($pack_notes) ? __("(No Notes Taken)", 'wpduplicator') : $pack_notes;

			//Links
			$uniqueid  			= "{$row['name']}_{$row['hash']}";
			$sqlfilelink		= $pack_storeurl . "{$uniqueid}_database.sql";
			$packagepath 		= $pack_storeurl . "{$uniqueid}_archive.zip";
			$installerpath		= $pack_storeurl . "{$uniqueid}_installer.php";
			$logfilelink		= $pack_storeurl . "{$uniqueid}.log";
			$reportfilelink		= $pack_storeurl . "{$uniqueid}_scan.json";
			$installfilelink	= "{$installerpath}?get=1&file={$uniqueid}_installer.php";
			$logfilename	    = "{$uniqueid}.log";
			$css_alt		    = ($rowCount % 2 != 0) ? '' : 'alternate';
			?>

			<!-- COMPLETE -->
			<?php if ($row['status'] >= 100) : ?>
				<tr class="dup-pack-info <?php echo $css_alt ?>">
					<td class="pass"><input name="delete_confirm" type="checkbox" id="<?php echo $row['id'] ;?>" /></td>
					<td><a href="javascript:void(0);" onclick="return Duplicator.Pack.ToggleDetail('<?php echo $detail_id ;?>');">[<?php echo __("View", 'wpduplicator') . ' ' . $row['id'];?>]</a></td>
					<td><?php echo date( "m-d-y G:i", strtotime($row['created']));?></td>
					<td><?php echo DUP_Util::ByteSize($pack_archive_size); ?></td>
					<td class='pack-name'><?php	echo  $pack_name ;?></td>
					<td class="get-btns">
						<button id="<?php echo "{$uniqueid}_installer.php" ?>" class="button no-select" onclick="Duplicator.Pack.DownloadFile('<?php echo $installfilelink; ?>', this); return false;"><i class="fa fa-bolt"></i> <?php _e("Installer", 'wpduplicator') ?></button> &nbsp;
					</td>
					<td class="get-btns">	
						<button id="<?php echo "{$uniqueid}_archive.zip" ?>" class="button no-select" onclick="Duplicator.Pack.DownloadFile('<?php echo $packagepath; ?>', this); return false;"><i class="fa fa-file-archive-o"></i> <?php _e("Archive", 'wpduplicator') ?></button>
					</td>
				</tr>
				<tr>
					<td colspan="8" id="<?php echo $detail_id; ?>" class="dup-pack-details  <?php echo $css_alt ?>">
						<b><?php _e("Version", 'wpduplicator') ?>:</b> <?php echo $plugin_version ?> &nbsp; | &nbsp; 
						<b><?php _e("User", 'wpduplicator') ?>:</b> <?php echo $row['owner']; ?> &nbsp; | &nbsp;  
						<b><?php _e("Hash", 'wpduplicator')?>:</b> <?php echo $pack_namehash ;?> <br/>
						<b><?php _e("Notes", 'wpduplicator')?>:</b> <?php echo $notes ?> 
						<div style="height:7px">&nbsp;</div>
						<button class="button" onclick="Duplicator.Pack.ShowLinksDialog(<?php echo "'{$sqlfilelink}', '{$packagepath}', '{$installfilelink}', '{$logfilelink}', '{$reportfilelink}' " ;?>); return false;" class="thickbox"><i class="fa fa-lock"></i> &nbsp; <?php _e("Links", 'wpduplicator')?></button> &nbsp; 
						<button class="button" onclick="window.open(<?php echo "'{$sqlfilelink}', '_blank'" ;?>); return false;"><i class="fa fa-table"></i> &nbsp; <?php _e("SQL", 'wpduplicator')?></button> &nbsp; 
						<button class="button" onclick="Duplicator.OpenLogWindow(<?php echo "'{$logfilename}'" ;?>); return false;"><i class="fa fa-list-alt"></i> &nbsp; <?php _e("Log", 'wpduplicator')?></button>
						<?php if ($package_debug) : ?>
							<div style="margin-top:7px">
								<a href="javascript:void(0)" onclick="window.open(<?php echo "'{$reportfilelink}', '_blank'" ;?>); return false;">[<?php _e("Open Scan Report", 'wpduplicator')?>]</a> &nbsp;
								<a href="javascript:void(0)" onclick="jQuery(this).parent().find('.dup-pack-debug').toggle()">[<?php _e("View Package Object", 'wpduplicator')?>]</a><br/>
								<textarea class="dup-pack-debug"><?php @print_r($Package); ?> </textarea>
							</div>
						<?php endif;  ?>	
					</td>
				</tr>	
				
			<!-- NOT COMPLETE -->				
			<?php else : ?>	
			
				<?php
					$size = 0;
					$tmpSearch = glob(DUPLICATOR_SSDIR_PATH_TMP . "/{$pack_namehash}_*");
					if (is_array($tmpSearch)) {
						$result = array_map('filesize', $tmpSearch);
						$size = array_sum($result);
					}
					$pack_archive_size = $size;
				?>
				<tr class="dup-pack-info  <?php echo $css_alt ?>">
					<td class="fail"><input name="delete_confirm" type="checkbox" id="<?php echo $row['id'] ;?>" /></td>
					<td><a href="javascript:void(0);" onclick="return Duplicator.Pack.ToggleDetail('<?php echo $detail_id ;?>');">[<?php echo __("View", 'wpduplicator') . ' ' . $row['id'];?>]</a></td>
					<td><?php echo date( "m-d-y G:i", strtotime($row['created']));?></td>
					<td><?php echo DUP_Util::ByteSize($size); ?></td>
					<td class='pack-name'><?php echo $pack_name ;?></td>
					<td class="get-btns" colspan="2">		
						<span style='display:inline-block; padding:7px 40px 0px 0px'>
							<a href="javascript:void(0);" onclick="return Duplicator.Pack.ToggleDetail('<?php echo $detail_id ;?>');"><?php _e("View Error Details", 'wpduplicator') ?>...</a>
						</span>									
					</td>
				</tr>
				<tr>
					<td colspan="8" id="<?php echo $detail_id; ?>" class="dup-pack-details  <?php echo $css_alt ?>">
						<div class="dup-details-area-error">
							<b><?php _e("Version", 'wpduplicator') ?>:</b> <?php echo $plugin_version ?> &nbsp; | &nbsp; 
							<b><?php _e("User", 'wpduplicator') ?>:</b> <?php echo $row['owner']; ?> &nbsp; | &nbsp;  
							<b><?php _e("Hash", 'wpduplicator')?>:</b> <?php echo $pack_name ;?> <br/>
							
							<?php if ($pack_name == 'unknown') : ?>
								<b><?php _e("Unrecoverable Error! Please remove this package.", 'wpduplicator')?></b><br/>
							<?php else : ?>	
								<b><?php _e("Notes", 'wpduplicator')?>:</b> <?php echo $notes ?> <br/>
								<?php
									printf("%s <u><a href='http://lifeinthegrid.com/duplicator-docs' target='_blank'>%s</a></u>",
									__("This package has encountered errors.  Click 'View Log' for more details.  For additional support see the ", 'wpduplicator'),
									__("online knowledgebase", 'wpduplicator')); 
								?><div style="height:7px">&nbsp;</div>
								<button class='button' onclick="Duplicator.OpenLogWindow(<?php echo "'{$logfilename}'" ;?>); return false;"><?php _e("View Log", 'wpduplicator')?></button>
								<?php if ($package_debug) : ?>
									<div style="margin-top:7px">
										<a href="javascript:void(0)" onclick="jQuery(this).parent().find('.dup-pack-debug').toggle()">[View Package Object]</a><br/>
										<textarea class="dup-pack-debug"><?php print_r($Package);?> </textarea>
									</div>
								<?php endif;  ?>	
							<?php endif;  ?>	
							
							
						</div>
					</td>
				</tr>	
			<?php endif; ?>
			<?php
			$totalSize = $totalSize + $pack_archive_size;
			$rowCount++;
		}
	?>
	<tfoot>
		<tr>
			<th colspan="8" style='text-align:right; font-size:12px'>						
				<?php echo _e("Packages", 'wpduplicator') . ': ' . $totalElements; ?> |
				<?php echo _e("Total Size", 'wpduplicator') . ': ' . DUP_Util::ByteSize($totalSize); ?> 
			</th>
		</tr>
	</tfoot>
	</table>
<?php endif; ?>	
</form>


<!--form id="dup-paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" style="display:none"> 
	<input name="cmd" type="hidden" value="_s-xclick" /> 
	<input name="hosted_button_id" type="hidden" value="EYJ7AV43RTZJL" /> 
	<img src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/en_US/i/scr/pixel.gif" border="0" alt="" width="1" height="1" /> 
</form-->


<!-- ==========================================
DIALOG: QUICK PATH -->
<?php add_thickbox(); ?>
<div id="dup-dlg-quick-path" title="<?php _e('Download Links', 'wpduplicator'); ?>" style="display:none">
	<p>
		<i class="fa fa-lock"></i>
		<?php _e("The following links contain sensitive data.  Please share with caution!", 'wpduplicator');	?>
	</p>
	
	<div style="padding: 0px 15px 15px 15px;">
		<a href="javascript:void(0)" style="display:inline-block; text-align:right" onclick="Duplicator.Pack.GetLinksText()">[Select All]</a> <br/>
		<textarea id="dup-dlg-quick-path-data" style='border:1px solid silver; border-radius:3px; width:99%; height:225px; font-size:11px'></textarea><br/>
		<i style='font-size:11px'><?php _e("The database SQL script is a quick link to your database backup script.  An exact copy is also stored in the package.", 'wpduplicator'); ?></i>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
		
	/*	----------------------------------------
	 *	METHOD: Shows hides the package detail items
	 *	@param id	The id to toggle */
	Duplicator.Pack.ToggleDetail = function(id) {
		$('#' + id).toggle();
		return false;
	}

	/*	----------------------------------------
	 *	METHOD: Removes all selected package sets 
	 *	@param event	To prevent bubbling */
	Duplicator.Pack.Delete = function (event) {
		var arr = new Array;
		var count = 0;
		
		if ($("#dup-pack-bulk-actions").val() != "delete") {
			alert("<?php _e('Please select an action from the bulk action drop down menu to perform a specific action.', 'wpduplicator') ?>");
			return;
		}
		$("input[name=delete_confirm]").each(function() {
			 if (this.checked) { arr[count++] = this.id; }
		});
		var list = arr.join(',');
		if (list.length == 0) {
			alert("<?php _e('Please select at least one package to delete.', 'wpduplicator') ?>");
			return;
		}
		
		if (confirm("<?php _e('Are you sure, you want to delete the selected package(s)?', 'wpduplicator') ?>")){
			
			$.ajax({
				type: "POST",
				url: ajaxurl,
				dataType: "json",
				data: {action : 'duplicator_package_delete', duplicator_delid : list },
				success: function(data) { 
					//console.log(data); //Debug return
					Duplicator.ReloadWindow(data); 
				}
			});
		} 
		if (event)
			event.preventDefault(); 
	};
	
	/*  ----------------------------------------
	 *  METHOD: Toogles the Bulk Action Check boxes */
	Duplicator.Pack.SetDeleteAll = function() {
		var state = $('input#dup-bulk-action-all').is(':checked') ? 1 : 0;
		$("input[name=delete_confirm]").each(function() {
			 this.checked = (state) ? true : false;
		});
	}
	
	/*	----------------------------------------
	 *	METHOD: Shows the 'Download Links' dialog
	 *	@param db		The path to the sql file
	 *	@param install	The path to the install file 
	 *	@param pack		The path to the package file */
	Duplicator.Pack.ShowLinksDialog = function(db, install, pack, log, report) {
		
		var url = '#TB_inline?width=650&height=350&inlineId=dup-dlg-quick-path';
		tb_show("<?php _e('Package File Links', 'wpduplicator') ?>", url);
		
		var msg = <?php printf('"%s:\n" + db + "\n\n%s:\n" + install + "\n\n%s:\n" + pack + "\n\n%s:\n" + log + "\n\n%s:\n" + report;', 
			__("DATABASE",  'wpduplicator'), 
			__("PACKAGE", 'wpduplicator'), 
			__("INSTALLER",   'wpduplicator'),
			__("LOG", 'wpduplicator'),
			__("REPORT", 'wpduplicator')); 
		?>
		$("#dup-dlg-quick-path-data").val(msg);
		return false;
	}
	
	//LOAD: 'Download Links' Dialog and other misc setup
	Duplicator.Pack.GetLinksText = function() {$('#dup-dlg-quick-path-data').select();};	
	
});
</script>

