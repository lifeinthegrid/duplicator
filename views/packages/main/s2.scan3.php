<?php
	/*IDE Helper*/
	/* @var $Package DUP_Package */
	function _duplicatorGetRootPath() {
		$txt   = __('Root Path', 'duplicator');
		$root  = rtrim(DUPLICATOR_WPROOTPATH, '//');
		$sroot = strlen($root) > 50 ? substr($root, 0, 50) . '...' : $root;
		echo "<div title='{$root}' class='divider'><i class='fa fa-folder-open'></i> {$sroot}</div>";
	}
?>

<!-- ================================================================
ARCHIVE -->
<div class="details-title">
	<i class="fa fa-file-archive-o"></i>&nbsp;<?php _e('Archive', 'duplicator');?>
	<div class="dup-more-details" onclick="Duplicator.Pack.showDetailsDlg()" title="<?php _e('Show Scan Details', 'duplicator');?>"><i class="fa fa-window-maximize"></i></div>
</div>

<div class="scan-header scan-item-first">
	<i class="fa fa-files-o"></i>
	<?php _e("Files", 'duplicator'); ?>
	
	<div class="scan-header-details">
		<div class="dup-scan-filter-status">
			<?php
				if ($Package->Archive->ExportOnlyDB) {
					echo '<i class="fa fa-filter"></i> '; _e('Database Only', 'duplicator');
				} elseif ($Package->Archive->FilterOn) {
					echo '<i class="fa fa-filter"></i> '; _e('Enabled', 'duplicator');
				}
			?>
		</div>
		<div id="data-arc-size1"></div>
		<i class="fa fa-question-circle data-size-help"
			data-tooltip-title="<?php _e('Archive Size', 'duplicator'); ?>"
			data-tooltip="<?php _e('This size includes only files BEFORE compression is applied. It does not include the size of the '
						. 'database script or any applied filters.  Once complete the package size will be smaller than this number.', 'duplicator'); ?>"></i>

		<div class="dup-data-size-uncompressed"><?php _e("uncompressed"); ?></div>
	</div>
</div>

<!-- ============
TOTAL SIZE -->
<div class="scan-item">
	<div class="title" onclick="Duplicator.Pack.toggleScanItem(this);">
		<div class="text"><i class="fa fa-caret-right"></i> <?php _e('Size Checks', 'duplicator');?></div>
		<div id="data-arc-status-size"></div>
	</div>
	<div class="info" id="scan-itme-file-size">
		<b><?php _e('Size', 'duplicator');?>:</b> <span id="data-arc-size2"></span>  &nbsp; | &nbsp;
		<b><?php _e('File Count', 'duplicator');?>:</b> <span id="data-arc-files"></span>  &nbsp; | &nbsp;
		<b><?php _e('Directory Count', 'duplicator');?>:</b> <span id="data-arc-dirs"></span> <br/>
		<?php
			_e('Compressing larger sites on <i>some budget hosts</i> may cause timeouts.  ' , 'duplicator');
			echo "<i>&nbsp; <a href='javascipt:void(0)' onclick='jQuery(\"#size-more-details\").toggle(100)'>[" . __('more details...', 'duplicator') . "]</a></i>";
		?>
		<div id="size-more-details">
			<?php
				echo "<b>" . __('Overview', 'duplicator') . ":</b><br/>";

				printf(__('This notice is triggered at <b>%s</b> and can be ignored on most hosts.  If during the build process you see a "Host Build Interrupt" message then this '
					. 'host has strict processing limits.  Below are some options you can take to overcome constraints set up on this host.', 'duplicator'),
					DUP_Util::byteSize(DUPLICATOR_SCAN_SIZE_DEFAULT));

				echo '<br/><br/>';

				echo "<b>" . __('Timeout Options', 'duplicator') . ":</b><br/>";
				echo '<ul>';
				echo '<li>' . __('Apply the "Quick Filters" below or click the back button to apply on previous page.', 'duplicator') . '</li>';
				echo '<li>' . __('See the FAQ link to adjust this hosts timeout limits: ', 'duplicator') . "&nbsp;<a href='https://snapcreek.com/duplicator/docs/faqs-tech/?utm_source=duplicator_free&utm_medium=wordpress_plugin&utm_campaign=problem_resolution&utm_content=pkg_s2scan3_tolimits#faq-trouble-100-q' target='_blank'>" . __('What can I try for Timeout Issues?', 'duplicator') . '</a></li>';
				echo '<li>' . __('Consider trying multi-threaded support in ', 'duplicator');
				echo "<a href='https://snapcreek.com/duplicator/?utm_source=duplicator_free&utm_medium=wordpress_plugin&utm_content=multithreaded_pro&utm_campaign=duplicator_pro' target='_blank'>" . __('Duplicator Pro.', 'duplicator') . "</a>";
				echo '</li>';
				echo '</ul>';

				$hlptxt = sprintf(__('Files over %1$s are listed below. Larger files such as movies or zipped content can cause timeout issues on some budget hosts.  If you are having '
				. 'issues creating a package try excluding the directory paths below or go back to Step 1 and add them.', 'duplicator'),
				DUP_Util::byteSize(DUPLICATOR_SCAN_WARNFILESIZE));
			?>
		</div>
		<script id="hb-files-large" type="text/x-handlebars-template">
			<div class="container">
				<div class="hdrs">
					<span style="font-weight:bold">
						<?php _e('Quick Filters', 'duplicator'); ?>
						<sup><i class="fa fa-question-circle" data-tooltip-title="<?php _e("Large Files", 'duplicator'); ?>" data-tooltip="<?php echo $hlptxt; ?>"></i></sup>
					</span>
					<div class='hdrs-up-down'>
						<i class="fa fa-caret-up fa-lg dup-nav-toggle" onclick="Duplicator.Pack.toggleAllDirPath(this, 'hide')" title="<?php _e("Hide All", 'duplicator'); ?>"></i>
						<i class="fa fa-caret-down fa-lg dup-nav-toggle" onclick="Duplicator.Pack.toggleAllDirPath(this, 'show')" title="<?php _e("Show All", 'duplicator'); ?>"></i>
					</div>
				</div>
				<div class="data">
					<?php _duplicatorGetRootPath();	?>
					{{#if ARC.FilterInfo.Files.Size}}
						{{#each ARC.FilterInfo.TreeSize as |directory|}}
							<div class="directory">
								<i class="fa fa-caret-right fa-lg dup-nav" onclick="Duplicator.Pack.toggleDirPath(this)"></i> &nbsp;
								{{#if directory.iscore}}
									<i class="fa fa-window-close-o chk-off" title="<?php _e('Core WordPress directories should not be filtered. Use caution when excluding files.', 'duplicator'); ?>"></i>
								{{else}}
									<input type="checkbox" name="dir_paths[]" value="{{directory.dir}}" id="lf_dir_{{@index}}" onclick="Duplicator.Pack.filesOff(this)" />
								{{/if}}
								<label for="lf_dir_{{@index}}" title="{{directory.dir}}">
									<i class="size">[{{directory.size}}]</i> /{{directory.sdir}}/
								</label> <br/>
								<div class="files">
									{{#each directory.files as |file|}}	
										<input type="checkbox" name="file_paths[]" value="{{file.path}}" id="lf_file_{{directory.dir}}-{{@index}}" />
										<label for="lf_file_{{directory.dir}}-{{@index}}" title="{{file.path}}">
											<i class="size">[{{file.bytes}}]</i>	{{file.name}}
										</label> <br/>
									{{/each}}
								</div>
							</div>
						{{/each}}
					{{else}}
						 <?php 
							if (! isset($_GET['retry'])) {
								_e('No large files found during this scan.', 'duplicator');
							} else {
								echo "<div style='color:maroon'>";
									_e('No large files found during this scan.  If you\'re having issues building a package click the back button and try '
									. 'adding a file filter to non-essential files paths like wp-content/uploads.   These excluded files can then '
									. 'be manually moved to the new location after you have ran the migration installer.', 'duplicator');
								echo "</div>";
							}
						?>
					{{/if}}
				</div>
			</div>


			<div class="apply-btn" style="margin-bottom:5px;float:right">
				<div class="apply-warn">
					 <?php _e('*Checking a directory will exclude all items recursively from that path down.<br/>Please use caution when filtering directories.', 'duplicator'); ?>
				</div>
				<button type="button" class="button-small" onclick="Duplicator.Pack.applyFilters(this, 'large')">
					<i class="fa fa-filter"></i> <?php _e('Add Filters &amp; Rescan', 'duplicator');?>
				</button>
				<button type="button" class="button-small" onclick="Duplicator.Pack.showPathsDlg('large')" title="<?php _e('Copy Paths to Clipboard', 'duplicator');?>">
					<i class="fa fa-clipboard" aria-hidden="true"></i>
				</button>
			</div>
			<div style="clear:both"></div>


		</script>
		<div id="hb-files-large-result" class="hb-files-style"></div>
	</div>
</div>

<!-- ======================
ADDON SITES -->
<div id="addonsites-block"  class="scan-item">
	<div class='title' onclick="Duplicator.Pack.toggleScanItem(this);">
		<div class="text"><i class="fa fa-caret-right"></i> <?php _e('Addon Sites', 'duplicator');?></div>
		<div id="data-arc-status-addonsites"></div>
	</div>
    <div class="info">
        <div style="margin-bottom:10px;">
            <small>
            <?php
                printf(__('An "Addon Site" is a separate WordPress site(s) residing in subdirectories within this site. If you confirm these to be separate sites, '
					. 'then it is recommended that you exclude them by checking the corresponding boxes below and clicking the \'Add Filters & Rescan\' button.  To backup the other sites '
					. 'install the plugin on the sites needing to be backed-up.'));
            ?>
            </small>
        </div>
        <script id="hb-addon-sites" type="text/x-handlebars-template">
            <div class="container">
                <div class="hdrs">
                    <span style="font-weight:bold">
                        <?php _e('Quick Filters', 'duplicator'); ?>
                    </span>
                </div>
                <div class="data">
                    {{#if ARC.FilterInfo.Dirs.AddonSites.length}}
                        {{#each ARC.FilterInfo.Dirs.AddonSites as |path|}}
                        <div class="directory">
                            <input type="checkbox" name="dir_paths[]" value="{{path}}" id="as_dir_{{@index}}"/>
                            <label for="as_dir_{{@index}}" title="{{path}}">
                                {{path}}
                            </label>
                        </div>
                        {{/each}}
                    {{else}}
                    <?php _e('No add on sites found.'); ?>
                    {{/if}}
                </div>
            </div>
            <div class="apply-btn">
                <div class="apply-warn">
                    <?php _e('*Checking a directory will exclude all items in that path recursively.'); ?>
                </div>
                <button type="button" class="button-small" onclick="Duplicator.Pack.applyFilters(this, 'addon')">
                    <i class="fa fa-filter"></i> <?php _e('Add Filters &amp; Rescan');?>
                </button>
            </div>
        </script>
        <div id="hb-addon-sites-result" class="hb-files-style"></div>
    </div>
</div>


<!-- ============
FILE NAME CHECKS -->
<div class="scan-item scan-item-last">
	<div class="title" onclick="Duplicator.Pack.toggleScanItem(this);">
		<div class="text"><i class="fa fa-caret-right"></i> <?php _e('Name Checks', 'duplicator');?></div>
		<div id="data-arc-status-names"></div>
	</div>
	<div class="info">
		<?php
			_e('Unicode and special characters such as "*?><:/\|", can be problematic on some hosts.', 'duplicator');
            _e('<b>');
            _e('  Only consider using this filter if the package build is failing. Select files that are not important to your site or you can migrate manually.', 'duplicator');
            _e('</b>');
			$txt = __('If this environment/system and the system where it will be installed are set up to support Unicode and long paths then these filters can be ignored.  '
				. 'If you run into issues with creating or installing a package, then is recommended to filter these paths.', 'duplicator');
		?>
		<script id="hb-files-utf8" type="text/x-handlebars-template">
			<div class="container">
				<div class="hdrs">
					<span style="font-weight:bold"><?php _e('Quick Filters', 'duplicator');?></span>
						<sup><i class="fa fa-question-circle" data-tooltip-title="<?php _e("Name Checks", 'duplicator'); ?>" data-tooltip="<?php echo $txt; ?>"></i></sup>
					<div class='hdrs-up-down'>
						<i class="fa fa-caret-up fa-lg dup-nav-toggle" onclick="Duplicator.Pack.toggleAllDirPath(this, 'hide')" title="<?php _e("Hide All", 'duplicator'); ?>"></i>
						<i class="fa fa-caret-down fa-lg dup-nav-toggle" onclick="Duplicator.Pack.toggleAllDirPath(this, 'show')" title="<?php _e("Show All", 'duplicator'); ?>"></i>
					</div>
				</div>
				<div class="data">
					<?php _duplicatorGetRootPath();	?>
					{{#if  ARC.FilterInfo.TreeWarning}}
						{{#each ARC.FilterInfo.TreeWarning as |directory|}}
							<div class="directory">
								{{#if directory.count}}
									<i class="fa fa-caret-right fa-lg dup-nav" onclick="Duplicator.Pack.toggleDirPath(this)"></i> &nbsp;
								{{else}}
									<i class="empty"></i>
								{{/if}}
										
								{{#if directory.iscore}}
									<i class="fa fa-window-close-o chk-off" title="<?php _e('Core WordPress directories should not be filtered. Use caution when excluding files.', 'duplicator'); ?>"></i>
								{{else}}		
									<input type="checkbox" name="dir_paths[]" value="{{directory.dir}}" id="nc1_dir_{{@index}}" onclick="Duplicator.Pack.filesOff(this)" />
								{{/if}}
								
								<label for="nc1_dir_{{@index}}" title="{{directory.dir}}">
									<i class="count">({{directory.count}})</i>
									/{{directory.sdir}}/
								</label> <br/>
								<div class="files">
									{{#each directory.files}}
										<input type="checkbox" name="file_paths[]" value="{{path}}" id="warn_file_{{directory.dir}}-{{@index}}" />
										<label for="warn_file_{{directory.dir}}-{{@index}}" title="{{path}}">
											{{name}}
										</label> <br/>
									{{/each}}
								</div>
							</div>
						{{/each}}
					{{else}}
						<?php _e('No file/directory name warnings found.', 'duplicator');?>
					{{/if}}
				</div>
			</div>
			<div class="apply-btn">
				<div class="apply-warn">
					 <?php _e('*Checking a directory will exclude all items recursively from that path down.<br/>Please use caution when filtering directories.', 'duplicator'); ?>
				</div>
				<button type="button" class="button-small" onclick="Duplicator.Pack.applyFilters(this, 'utf8')">
					<i class="fa fa-filter"></i> <?php _e('Add Filters &amp; Rescan', 'duplicator');?>
				</button>
				<button type="button" class="button-small" onclick="Duplicator.Pack.showPathsDlg('utf8')" title="<?php _e('Copy Paths to Clipboard', 'duplicator');?>">
					<i class="fa fa-clipboard" aria-hidden="true"></i>
				</button>
			</div>
		</script>
		<div id="hb-files-utf8-result" class="hb-files-style"></div>
	</div>
</div>


<!-- ============
DATABASE -->
<div id="dup-scan-db">
	<div class="scan-header">
		<i class="fa fa-table"></i>
		<?php _e("Database", 'duplicator');	?>
		<div class="scan-header-details">
			<div class="dup-scan-filter-status">
				<?php
					if ($Package->Database->FilterOn) {
						echo '<i class="fa fa-filter"></i> '; _e('Enabled', 'duplicator');
					}
				?>
			</div>
			<div id="data-db-size1"></div>
			<i class="fa fa-question-circle data-size-help"
				data-tooltip-title="<?php _e("Database Size:", 'duplicator'); ?>"
				data-tooltip="<?php _e('The database size represents only the included tables. The process for gathering the size uses the query SHOW TABLE STATUS.  '
					. 'The overall size of the database file can impact the final size of the package.', 'duplicator'); ?>"></i>

			<div class="dup-data-size-uncompressed"><?php _e("uncompressed"); ?></div>

		</div>
	</div>

	<div class="scan-item scan-item-last">
		<div class="title" onclick="Duplicator.Pack.toggleScanItem(this);">
			<div class="text"><i class="fa fa-caret-right"></i> <?php _e('Overview', 'duplicator');?></div>
			<div id="data-db-status-size"></div>
		</div>
		<div class="info">
			<?php echo '<b>' . __('TOTAL SIZE', 'duplicator') . ' &nbsp; &#8667; &nbsp; </b>'; ?>
			<b><?php _e('Size', 'duplicator');?>:</b> <span id="data-db-size2"></span> &nbsp; | &nbsp;
			<b><?php _e('Tables', 'duplicator');?>:</b> <span id="data-db-tablecount"></span> &nbsp; | &nbsp;
			<b><?php _e('Records', 'duplicator');?>:</b> <span id="data-db-rows"></span><br/>
			<?php
				printf(__('Total size and row counts are approximate values.  The thresholds that trigger notices are <i>%1$s OR %2$s</i> records total for the entire database.  '
					. 'Larger databases take more time to process.  On some budget hosts that have cpu/memory/timeout limits this may cause issues.', 'duplicator'),
						DUP_Util::byteSize(DUPLICATOR_SCAN_DB_ALL_SIZE),
						number_format(DUPLICATOR_SCAN_DB_ALL_ROWS));

				echo '<br/><br/><hr size="1" />';

				//TABLE DETAILS
				echo '<b>' . __('TABLE DETAILS:', 'duplicator') . '</b><br/>';
				printf(__('The notices for tables are <i>%1$s, %2$s records or names with upper-case characters</i>.  Individual tables will not trigger '
					. 'a notice message, but can help narrow down issues if they occur later on.', 'duplicator'),
						DUP_Util::byteSize(DUPLICATOR_SCAN_DB_TBL_SIZE),
						number_format(DUPLICATOR_SCAN_DB_TBL_ROWS));
				
				echo '<div id="dup-scan-db-info"><div id="data-db-tablelist"></div></div>';

				//RECOMMENDATIONS
				echo '<br/><hr size="1" />';
				echo '<b>' . __('RECOMMENDATIONS:', 'duplicator') . '</b><br/>';
				
				echo '<div style="padding:5px">';
				$lnk = '<a href="maint/repair.php" target="_blank">' . __('repair and optimization', 'duplicator') . '</a>';
				printf(__('1. Run a %1$s on the table to improve the overall size and performance.', 'duplicator'), $lnk);
				echo '<br/><br/>';
				_e('2. Remove post revisions and stale data from tables.  Tables such as logs, statistical or other non-critical data should be cleared.', 'duplicator');
				echo '<br/><br/>';
				$lnk = '<a href="?page=duplicator-settings&tab=package" target="_blank">' . __('Enable mysqldump', 'duplicator') . '</a>';
				printf(__('3. %1$s if this host supports the option.', 'duplicator'), $lnk);
				echo '<br/><br/>';
				$lnk = '<a href="http://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_lower_case_table_names" target="_blank">' . __('lower_case_table_names', 'duplicator') . '</a>';
				printf(__('4. For table name case sensitivity issues either rename the table with lower case characters or be prepared to work with the %1$s system variable setting.', 'duplicator'), $lnk);
				echo '</div>';

			?>
		</div>
	</div>
    
<!-- ============
TOTAL SIZE -->
    <div class="data-ll-section scan-header" style="display:none">
		<i class="fa fa-table"></i>
		<?php _e("Total Size", 'duplicator');	?>
		<div class="scan-header-details">

			<div id="data-ll-totalsize"></div>
			<i class="fa fa-question-circle data-size-help"
				data-tooltip-title="<?php _e("Total Size:", 'duplicator'); ?>"
				data-tooltip="<?php _e('The total size of the site (files plus  database).', 'duplicator'); ?>"></i>

			<div class="dup-data-size-uncompressed"><?php _e("uncompressed"); ?></div>

		</div>
	</div>

	<div class="data-ll-section scan-item scan-item-last" style="display:none">
		<div class="title" onclick="Duplicator.Pack.toggleScanItem(this);">
			<div class="text"><i class="fa fa-caret-right"></i> <?php _e('Overview', 'duplicator');?></div>
			<div id="data-ll-status-totalsize"></div>
		</div>
		<div class="info">
            <?php echo '<b>' . __('TOTAL SIZE', 'duplicator') . ":{$totalSiteSize}"; ?>

			<?php
				printf(__('The total size of this site exceeds the maximum size the DupArchive engine will process on Duplicator Lite (1 GB).', 'duplicator'));

				echo '<br/><br/><hr size="1" />';

				
				echo '<b>' . __('RECOMMENDATIONS:', 'duplicator') . '</b><br/>';

				echo '<div style="padding:5px">';
				$lnk = '<a href="maint/repair.php" target="_blank">' . __('repair and optimization', 'duplicator') . '</a>';
				printf(__('1. Filter unnecessary directories or files, if possible.', 'duplicator'), $lnk);
				echo '<br/><br/>';
                $lnk = '<a href="#" target="_blank">' . __('Enable mysqldump', 'duplicator') . '</a>';
				_e("2. If #1 isn't an option, perform a two part install as described here.", 'duplicator');
				echo '<br/><br/>';
				$lnk = '<a href="#" target="_blank">' . $a.  __('Duplicator Pro', 'duplicator') . '</a>';
				printf(__("3. If #1 and #2 aren't viable consider upgrading to %1$s.", 'duplicator'), $lnk);
				echo '</div>';

			?>
		</div>
	</div>

	<?php
        echo '<div class="dup-pro-support">&nbsp;';
        _e('Migrate large, multi-gig sites with', 'duplicator');
        echo '&nbsp;<i><a href="https://snapcreek.com/duplicator/?utm_source=duplicator_free&amp;utm_medium=wordpress_plugin&amp;utm_content=free_size_warn_multigig&amp;utm_campaign=duplicator_pro" target="_blank">' . __('Duplicator Pro', 'duplicator') . '!</a></i>';
        echo '</div>';
	?>
</div>
<br/><br/>


<!-- ==========================================
DIALOGS:
========================================== -->
<?php
	$alert1 = new DUP_UI_Dialog();
	$alert1->height     = 600;
	$alert1->width      = 600;
	$alert1->title		= __('Scan Details', 'duplicator');
	$alert1->message	= "<div id='arc-details-dlg'></div>";
	$alert1->initAlert();
	
	$alert2 = new DUP_UI_Dialog();
	$alert2->height     = 425;
	$alert2->width      = 650;
	$alert2->title		= __('Copy Quick Filter Paths', 'duplicator');
	$alert2->message	= "<div id='arc-paths-dlg'></div>";
	$alert2->initAlert();
?>

<!-- =======================
DIALOG: Scan Results -->
<div id="dup-archive-details" style="display:none">
	
	<!-- PACKAGE -->
	<h2><i class="fa fa-archive"></i> <?php _e('Package', 'duplicator');?></h2>
	<b><?php _e('Name', 'duplicator');?>:</b> <?php echo $Package->Name; ?><br/>
	<b><?php _e('Notes', 'duplicator');?>:</b> <?php echo $Package->Notes; ; ?>
	<br/><br/>

	<!-- DATABASE -->
	<h2><i class="fa fa-table"></i> <?php _e('Database', 'duplicator');?></h2>
	<table id="db-area">
		<tr><td><b><?php _e('Name:', 'duplicator');?></b></td><td><?php echo DB_NAME; ?> </td></tr>
		<tr><td><b><?php _e('Host:', 'duplicator');?></b></td><td><?php echo DB_HOST; ?> </td></tr>
		<tr>
			<td style="vertical-align: top"><b><?php _e('Build Mode:', 'duplicator');?></b></td>
			<td style="line-height:18px">
				<a href="?page=duplicator-settings" target="_blank"><?php echo $dbbuild_mode ;?></a>
				<?php if ($mysqlcompat_on) :?>
					<br/>
					<small style="font-style:italic; color:maroon">
						<i class="fa fa-exclamation-circle"></i> <?php _e('MySQL Compatibility Mode Enabled', 'duplicator'); ?>
						<a href="https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_compatible" target="_blank">[<?php _e('details', 'duplicator'); ?>]</a>
					</small>
				<?php endif;?>
			</td>
		</tr>
	</table><br/>

	<!-- FILE FILTERS -->
	<h2 style="border: none">
		<i class="fa fa-filter"></i> <?php _e('File Filters', 'duplicator');?>:
		<small><?php echo ($Package->Archive->FilterOn) ? __('Enabled', 'duplicator') : __('Disabled', 'duplicator') ;?></small>
	</h2>
	<div class="filter-area">
		<b><i class="fa fa-folder-open"></i> <?php echo rtrim(DUPLICATOR_WPROOTPATH, "//");?></b>

		<script id="hb-filter-file-list" type="text/x-handlebars-template">
			<div class="file-info">
				<b>[<?php _e('Directories', 'duplicator');	?>]</b>
				<div class="file-info">
					{{#if ARC.FilterInfo.Dirs.Instance}}
						{{#each ARC.FilterInfo.Dirs.Instance as |dir|}}
							{{stripWPRoot dir}}/<br/>
						{{/each}}
					{{else}}
						 <?php	_e('No custom directory filters set.', 'duplicator');?>
					{{/if}}
				</div>

				<b>[<?php _e('Extensions', 'duplicator');?>]</b><br/>
				<div class="file-info">
					<?php
						if (strlen( $Package->Archive->FilterExts)) {
							echo $Package->Archive->FilterExts;
						} else {
							_e('No file extension filters have been set.', 'duplicator');
						}
					?>
				</div>

				<b>[<?php _e('Files', 'duplicator');	?>]</b>
				<div class="file-info">
					{{#if ARC.FilterInfo.Files.Instance}}
						{{#each ARC.FilterInfo.Files.Instance as |file|}}
							{{stripWPRoot file}}<br/>
						{{/each}}
					{{else}}
						 <?php	_e('No custom file filters set.', 'duplicator');?>
					{{/if}}
				</div>

				<b>[<?php _e('Auto Filters', 'duplicator');	?>]</b>
				<div class="file-info">
					{{#each ARC.FilterInfo.Dirs.Core as |dir|}}
						{{stripWPRoot dir}}/<br/>
					{{/each}}
				</div>

			</div>
		</script>
		<div class="hb-filter-file-list-result"></div>


	</div>

	<small>
		<?php _e('Path filters will be skipped during the archive process when enabled.', 'duplicator');	?>
		<a href="<?php echo DUPLICATOR_SITE_URL ?>/wp-admin/admin-ajax.php?action=duplicator_package_scan" target="dup_report"><?php _e('[view json result report]', 'duplicator');?></a>
		<br/>
		<?php _e('Auto filters are applied to prevent archiving other backup sets.', 'duplicator');	?>
	</small><br/>
</div>

<!-- =======================
DIALOG: PATHS COPY & PASTE -->
<div id="dup-archive-paths" style="display:none">
	
	<b><i class="fa fa-folder"></i> <?php _e('Directories', 'duplicator');?></b>
	<div class="copy-button">
		<button type="button" class="button-small" onclick="Duplicator.Pack.copyText(this, '#arc-paths-dlg textarea.path-dirs')">
			<i class="fa fa-clipboard"></i> <?php _e('Click to Copy', 'duplicator');?>
		</button>
	</div>
	<textarea class="path-dirs"></textarea>
	<br/><br/>

	<b><i class="fa fa-files-o"></i> <?php _e('Files', 'duplicator');?></b>
	<div class="copy-button">
		<button type="button" class="button-small" onclick="Duplicator.Pack.copyText(this, '#arc-paths-dlg textarea.path-files')">
			<i class="fa fa-clipboard"></i> <?php _e('Click to Copy', 'duplicator');?>
		</button>
	</div>
	<textarea class="path-files"></textarea>
	<br/>
	<small><?php _e('Copy the paths above and apply them as needed on Step 1 &gt; Archive &gt; Files section.', 'duplicator');?></small>
</div>



<script>
jQuery(document).ready(function($)
{

	Handlebars.registerHelper('stripWPRoot', function(path) {
		return  path.replace('<?php echo rtrim(DUPLICATOR_WPROOTPATH, "//") ?>', '');
	});


	//Uncheck file names if directory is checked
	Duplicator.Pack.filesOff = function (dir)
	{
		var $checks = $(dir).parent('div.directory').find('div.files input[type="checkbox"]');
		$(dir).is(':checked')
			? $.each($checks, function() {$(this).attr({disabled : true, checked : false, title : '<?php _e('Directory applied filter set.', 'duplicator');?>'});})
			: $.each($checks, function() {$(this).removeAttr('disabled checked title');});
		$('div.apply-warn').show(300);
	}

	//Opens a dialog to show scan details
	Duplicator.Pack.showDetailsDlg = function ()
	{
		$('#arc-details-dlg').html($('#dup-archive-details').html());
		<?php $alert1->showAlert(); ?>
		Duplicator.UI.loadQtip();
		return;
	}
	
	//Opens a dialog to show scan details
	Duplicator.Pack.showPathsDlg = function (type)
	{
		var id = (type == 'large') ? '#hb-files-large-result' : '#hb-files-utf8-result'
		var dirFilters  = [];
		var fileFilters = [];
		$(id + " input[name='dir_paths[]']:checked").each(function()  {dirFilters.push($(this).val());});
		$(id + " input[name='file_paths[]']:checked").each(function() {fileFilters.push($(this).val());});

		var $dirs  = $('#dup-archive-paths textarea.path-dirs');
		var $files = $('#dup-archive-paths textarea.path-files');
		(dirFilters.length > 0)
		   ? $dirs.text(dirFilters.join(";\n"))
		   : $dirs.text("<?php _e('No directories have been selected!', 'duplicator');?>");

	    (fileFilters.length > 0)
		   ? $files.text(fileFilters.join(";\n"))
		   : $files.text("<?php _e('No files have been selected!', 'duplicator');?>");

		$('#arc-paths-dlg').html($('#dup-archive-paths').html());
		<?php $alert2->showAlert(); ?>
		
		return;
	}

	//Toggles a directory path to show files
	Duplicator.Pack.toggleDirPath = function(item)
	{
		var $dir   = $(item).parents('div.directory');
		var $files = $dir.find('div.files');
		var $arrow = $dir.find('i.dup-nav');
		if ($files.is(":hidden")) {
			$arrow.addClass('fa-caret-down').removeClass('fa-caret-right');
			$files.show();
		} else {
			$arrow.addClass('fa-caret-right').removeClass('fa-caret-down');
			$files.hide(250);
		}
	}

	//Toggles a directory path to show files
	Duplicator.Pack.toggleAllDirPath = function(item, toggle)
	{
		var $dirs  = $(item).parents('div.container').find('div.data div.directory');
		 (toggle == 'hide')
			? $.each($dirs, function() {$(this).find('div.files').show(); $(this).find('i.dup-nav').trigger('click');})
			: $.each($dirs, function() {$(this).find('div.files').hide(); $(this).find('i.dup-nav').trigger('click');});
	}

	Duplicator.Pack.copyText = function(btn, query)
	{
		$(query).select();
		 try {
		   document.execCommand('copy');
		   $(btn).css({color: '#fff', backgroundColor: 'green'});
		   $(btn).text("<?php _e('Copied to Clipboard!', 'duplicator');?>");
		 } catch(err) {
		   alert("<?php _e('Manual copy of selected text required on this browser.', 'duplicator');?>")
		 }
	}

	Duplicator.Pack.applyFilters = function(btn, type)
	{
		var $btn = $(btn);
		$btn.html('<i class="fa fa-circle-o-notch fa-spin"></i> <?php _e('Initializing Please Wait...', 'duplicator');?>');
		$btn.attr('disabled', 'true');

		//var id = (type == 'large') ? '#hb-files-large-result' : '#hb-files-utf8-result'
		var id = '';
        switch(type){
            case 'large':
                id = '#hb-files-large-result';
                break;
            case 'utf8':
                id = '#hb-files-utf8-result';
                break;
            case 'addon':
                id = '#hb-addon-sites-result';
                break;
        }
		var dirFilters  = [];
		var fileFilters = [];
		$(id + " input[name='dir_paths[]']:checked").each(function()  {dirFilters.push($(this).val());});
		$(id + " input[name='file_paths[]']:checked").each(function() {fileFilters.push($(this).val());});

		var data = {
			action: 'DUP_CTRL_Package_addQuickFilters',
			nonce: '<?php echo wp_create_nonce('DUP_CTRL_Package_addQuickFilters'); ?>',
			dir_paths : dirFilters.join(";"),
			file_paths : fileFilters.join(";"),
		};

		$.ajax({
			type: "POST",
			cache: false,
			url: ajaxurl,
			dataType: "json",
			timeout: 100000,
			data: data,
			complete: function() { },
			success:  function() {Duplicator.Pack.rescan();},
			error: function(data) {
				console.log(data);
				alert("<?php _e('Error applying filters.  Please go back to Step 1 to add filter manually!', 'duplicator');?>");
			}
		});
	}

	Duplicator.Pack.initArchiveFilesData = function(data)
	{
		//TOTAL SIZE
		//var sizeChecks = data.ARC.Status.Size == 'Warn' || data.ARC.Status.Big == 'Warn' ? 'Warn' : 'Good';
		$('#data-arc-status-size').html(Duplicator.Pack.setScanStatus(data.ARC.Status.Size));
		$('#data-arc-status-names').html(Duplicator.Pack.setScanStatus(data.ARC.Status.Names));
		$('#data-arc-size1').text(data.ARC.Size || errMsg);
		$('#data-arc-size2').text(data.ARC.Size || errMsg);
		$('#data-arc-files').text(data.ARC.FileCount || errMsg);
		$('#data-arc-dirs').text(data.ARC.DirCount || errMsg);

		//LARGE FILES
		var template = $('#hb-files-large').html();
		var templateScript = Handlebars.compile(template);
		var html = templateScript(data);
		$('#hb-files-large-result').html(html);

		//ADDON SITES
        var template = $('#hb-addon-sites').html();
        var templateScript = Handlebars.compile(template);
        var html = templateScript(data);
        $('#hb-addon-sites-result').html(html);

		//NAME CHECKS
		var template = $('#hb-files-utf8').html();
		var templateScript = Handlebars.compile(template);
		var html = templateScript(data);
		$('#hb-files-utf8-result').html(html);

		//SCANNER DETAILS: Dirs
		var template = $('#hb-filter-file-list').html();
		var templateScript = Handlebars.compile(template);
		var html = templateScript(data);
		$('div.hb-filter-file-list-result').html(html);

		Duplicator.UI.loadQtip();
	}

	Duplicator.Pack.initArchiveDBData = function(data)
	{
		var errMsg = "unable to read";
		var color;
		var html = "";
		var DB_TotalSize = 'Good';
		var DB_TableRowMax  = <?php echo DUPLICATOR_SCAN_DB_TBL_ROWS; ?>;
		var DB_TableSizeMax = <?php echo DUPLICATOR_SCAN_DB_TBL_SIZE; ?>;
		if (data.DB.Status.Success)
		{
			DB_TotalSize = data.DB.Status.DB_Rows == 'Warn' || data.DB.Status.DB_Size == 'Warn' ? 'Warn' : 'Good';
			$('#data-db-status-size').html(Duplicator.Pack.setScanStatus(DB_TotalSize));
			$('#data-db-size1').text(data.DB.Size || errMsg);
			$('#data-db-size2').text(data.DB.Size || errMsg);
			$('#data-db-rows').text(data.DB.Rows || errMsg);
			$('#data-db-tablecount').text(data.DB.TableCount || errMsg);
			//Table Details
			if (data.DB.TableList == undefined || data.DB.TableList.length == 0) {
				html = '<?php _e("Unable to report on any tables", 'duplicator') ?>';
			} else {
				$.each(data.DB.TableList, function(i) {
					html += '<b>' + i  + '</b><br/>';
					html += '<table><tr>';
					$.each(data.DB.TableList[i], function(key,val) {
						switch(key) {
							case 'Case':
								color = (val == 1) ? 'red' : 'black';
								html += '<td style="color:' + color + '">Uppercase: ' + val + '</td>';
								break;
							case 'Rows':
								color = (val > DB_TableRowMax) ? 'red' : 'black';
								html += '<td style="color:' + color + '">Rows: ' + val + '</td>';
								break;
							case 'USize':
								color = (parseInt(val) > DB_TableSizeMax) ? 'red' : 'black';
								html += '<td style="color:' + color + '">Size: ' + data.DB.TableList[i]['Size'] + '</td>';
								break;
						}	
					});
					html += '</tr></table>';
				});
			}
			$('#data-db-tablelist').append(html);
		} else {
			html = '<?php _e("Unable to report on database stats", 'duplicator') ?>';
			$('#dup-scan-db').html(html);
		}
	}

    Duplicator.Pack.initLiteLimitData = function(data)
	{
        if(data.LL.Status.TotalSize == 'Fail') {
            $('.data-ll-section').css('display', 'block');
            $('#data-ll-status-totalsize').html(Duplicator.Pack.setScanStatus(data.LL.Status.TotalSize));
            $('#data-ll-totalsize').text(data.LL.TotalSize || errMsg);
        }
	}

	<?php
		if (isset($_GET['retry']) && $_GET['retry'] == '1' ) {
			echo "$('#scan-itme-file-size').show(300)";
		}
	?>
	
});
</script>
