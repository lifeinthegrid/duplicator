
<form id="dup-form-opts" method="post" action="?page=duplicator&tab=new2" data-validate="parsley">
<input type="hidden" id="dup-form-opts-action" name="action" value="">
<input type="hidden" id="dup-form-opts-hash" name="package-hash" value="<?php echo $package_hash; ?>">
<div>
	<label for="package-name"><b><?php _e('Name', 'wpduplicator') ?>:</b> </label>
		<div class="dup-notes-add">
		<button class="button button-small" type="button" onclick="jQuery('#dup-notes-area').toggle()"><i class="fa fa-pencil-square-o"></i> <?php _e('Notes', 'wpduplicator') ?></button>
	</div>
	<a href="javascript:void(0)" onclick="Duplicator.Pack.ResetName()" title="<?php _e('Create a new default name', 'wpduplicator') ?>"><i class="fa fa-undo"></i></a> <br/>
	<input id="package-name"  name="package-name" type="text" value="<?php echo $Package->Name ?>" maxlength="40"  data-required="true" data-regexp="^[0-9A-Za-z|_]+$" /> <br/>
	<div id="dup-notes-area">
		<label><b><?php _e('Notes', 'wpduplicator') ?>:</b></label> <br/>
		<textarea id="package-notes" name="package-notes" maxlength="300" /><?php echo $Package->Notes ?></textarea>
	</div>
</div>
<br/>

<!-- ===================
META-BOX: STORAGE -->
<div class="dup-box">
	<div class="dup-box-title">
		<i class="fa fa-database"></i>&nbsp;<?php  _e("Storage", 'wpduplicator'); ?> 
		<div class="dup-box-arrow"></div>
	</div>			

	<div class="dup-box-panel" id="dup-pack-storage-panel" style="<?php echo $ui_css_storage ?>">
		<table class="widefat package-tbl">
			<thead>
				<tr>
					<th style='width:275px'><?php _e("Name", 'wpduplicator'); ?></th>
					<th style='width:100px'><?php _e("Type", 'wpduplicator'); ?></th>
					<th style="white-space: nowrap"><?php _e("Location", 'wpduplicator'); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr class="package-row">
					<td><i class="fa fa-server"></i>&nbsp;<?php  _e('Default', 'wpduplicator');?></td>
					<td><?php _e("Local", 'wpduplicator'); ?></td>
					<td><?php echo DUPLICATOR_SSDIR_PATH; ?></td>				
				</tr>
				<tr>
					<td colspan="4">
						<p class="description">
							<?php _e("All packages including the archive, installer and SQL script are stored in the location above. ", 'wpduplicator'); ?>
							<br/>
						<small>
							<?php _e("Dropbox, FTP and other multiple storage options available in ", 'wpduplicator'); ?>
							<a href="http://duplicatorpro.com/?from_free_storage" target="_blank">
								<?php _e("Duplicator Pro", 'wpduplicator'); ?>
							</a>
						</small>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div><br/>
<!-- end meta-box storage  -->

<!-- META-BOX: ARCHIVE -->
<div class="dup-box">
    <div class="dup-box-title">
        <i class="fa fa-file-archive-o"></i> <?php _e('Archive', 'wpduplicator') ?> &nbsp;
        <span style="font-size:13px">
            <span id="dup-archive-filter-file" title="<?php _e('File filter enabled', 'wpduplicator') ?>"><i class="fa fa-files-o"></i> <i class="fa fa-filter"></i> &nbsp;&nbsp;</span> 
            <span id="dup-archive-filter-db" title="<?php _e('Database filter enabled', 'wpduplicator') ?>"><i class="fa fa-table"></i> <i class="fa fa-filter"></i></span>	
        </span>
        <div class="dup-box-arrow"></div>
    </div>		
    <div class="dup-box-panel" id="dup-pack-archive-panel" style="<?php echo $ui_css_archive ?>">
        <input type="hidden" name="archive-format" value="ZIP" />
        <!-- NESTED TABS -->
        <div class="categorydiv" id="dup-pack-opts-tabs">
            <ul class="category-tabs">
                <li class="tabs"><a href="javascript:void(0)" onclick="Duplicator.Pack.ToggleOptTabs(1, this)"><?php _e('Files', 'wpduplicator') ?></a></li>
                <li><a href="javascript:void(0)"onclick="Duplicator.Pack.ToggleOptTabs(2, this)"><?php _e('Database', 'wpduplicator') ?></a></li>
            </ul>

            <!-- TAB1: PACKAGE -->
            <div class="tabs-panel" id="dup-pack-opts-tabs-panel-1">
                <!-- FILTERS -->
                <?php
                $uploads = wp_upload_dir();
                $upload_dir = DUP_Util::SafePath($uploads['basedir']);
                ?>
                <div class="dup-enable-filters">
                    <input type="checkbox" id="filter-on" name="filter-on" onclick="Duplicator.Pack.ToggleFileFilters()" <?php echo ($Package->Archive->FilterOn) ? "checked='checked'" : ""; ?> />	
                    <label for="filter-on"><?php _e("Enable File Filters", 'wpduplicator') ?></label>
                </div>

                <div id="dup-file-filter-items">
                    <label for="filter-dirs" title="<?php _e("Separate all filters by semicolon", 'wpduplicator'); ?>"><?php _e("Directories", 'wpduplicator') ?>: </label>
                    <div class='dup-quick-links'>
                        <a href="javascript:void(0)" onclick="Duplicator.Pack.AddExcludePath('<?php echo rtrim(DUPLICATOR_WPROOTPATH, '/'); ?>')">[<?php _e("root path", 'wpduplicator') ?>]</a>
                        <a href="javascript:void(0)" onclick="Duplicator.Pack.AddExcludePath('<?php echo rtrim($upload_dir, '/'); ?>')">[<?php _e("wp-uploads", 'wpduplicator') ?>]</a>
                        <a href="javascript:void(0)" onclick="Duplicator.Pack.AddExcludePath('<?php echo DUP_Util::SafePath(WP_CONTENT_DIR); ?>/cache')">[<?php _e("cache", 'wpduplicator') ?>]</a>
                        <a href="javascript:void(0)" onclick="jQuery('#filter-dirs').val('')"><?php _e("(clear)", 'wpduplicator') ?></a>
                    </div>
                    <textarea name="filter-dirs" id="filter-dirs" placeholder="/full_path/exclude_path1;/full_path/exclude_path2;"><?php echo str_replace(";", ";\n", esc_textarea($Package->Archive->FilterDirs)) ?></textarea><br/>
                    <label class="no-select" title="<?php _e("Separate all filters by semicolon", 'wpduplicator'); ?>"><?php _e("File extensions", 'wpduplicator') ?>:</label>
                    <div class='dup-quick-links'>
                        <a href="javascript:void(0)" onclick="Duplicator.Pack.AddExcludeExts('avi;mov;mp4;mpeg;mpg;swf;wmv;aac;m3u;mp3;mpa;wav;wma')">[<?php _e("media", 'wpduplicator') ?>]</a>
                        <a href="javascript:void(0)" onclick="Duplicator.Pack.AddExcludeExts('zip;rar;tar;gz;bz2;7z')">[<?php _e("archive", 'wpduplicator') ?>]</a>
                        <a href="javascript:void(0)" onclick="jQuery('#filter-exts').val('')"><?php _e("(clear)", 'wpduplicator') ?></a>
                    </div>
                    <textarea name="filter-exts" id="filter-exts" placeholder="ext1;ext2;ext3;"><?php echo esc_textarea($Package->Archive->FilterExts); ?></textarea>

                    <div class="dup-tabs-opts-help">
                        <?php _e("The directory paths and extensions above will be be excluded from the archive file if enabled is checked.", 'wpduplicator'); ?> <br/>
                        <?php _e("Use the full path for directories and semicolons to separate all items.", 'wpduplicator'); ?>
                    </div>
                </div>
            </div>

            <!-- TAB2: DATABASE -->
            <div class="tabs-panel" id="dup-pack-opts-tabs-panel-2" style="display: none;">
                <div class="dup-enable-filters">						
                    <table>
                        <tr>
                            <td><input type="checkbox" id="dbfilter-on" name="dbfilter-on" onclick="Duplicator.Pack.ToggleDBFilters()" <?php echo ($Package->Database->FilterOn) ? "checked='checked'" : ""; ?> /></td>
                            <td><label for="dbfilter-on"><?php _e("Enable Table Filters", 'wpduplicator') ?> &nbsp;</label> </td>
                            <td><div class="dup-tabs-opts-help" style="margin:5px 0px 0px 0px"><?php _e("checked tables are excluded", 'wpduplicator') ?></div></td>
                        </tr>
                    </table>
                </div>
                <div id="dup-db-filter-items">
                    <a href="javascript:void(0)" id="dball" onclick="jQuery('#dup-dbtables .checkbox').prop('checked', true).trigger('click');">[ <?php _e('Include All', 'wpduplicator'); ?> ]</a> &nbsp; 
                    <a href="javascript:void(0)" id="dbnone" onclick="jQuery('#dup-dbtables .checkbox').prop('checked', false).trigger('click');">[ <?php _e('Exclude All', 'wpduplicator'); ?> ]</a>
                    <div style="font-stretch:ultra-condensed; font-family: Calibri; white-space: nowrap">
                        <?php
                        $tables = $wpdb->get_results("SHOW FULL TABLES FROM `" . DB_NAME . "` WHERE Table_Type = 'BASE TABLE' ", ARRAY_N);
                        $num_rows = count($tables);
                        echo '<table id="dup-dbtables"><tr><td valign="top">';
                        $next_row = round($num_rows / 3, 0);
                        $counter = 0;
                        $tableList = explode(',', $Package->Database->FilterTables);
                        foreach ($tables as $table)
                        {
                            if (in_array($table[0], $tableList))
                            {
                                $checked = 'checked="checked"';
                                $css = 'text-decoration:line-through';
                            }
                            else
                            {
                                $checked = '';
                                $css = '';
                            }
                            echo "<label for='dbtables-{$table[0]}' style='{$css}'><input class='checkbox dbtable' $checked type='checkbox' name='dbtables[]' id='dbtables-{$table[0]}' value='{$table[0]}' onclick='Duplicator.Pack.ExcludeTable(this)' />&nbsp;{$table[0]}</label><br />";
                            $counter++;
                            if ($next_row <= $counter)
                            {
                                echo '</td><td valign="top">';
                                $counter = 0;
                            }
                        }
                        echo '</td></tr></table>';
                        ?>
                    </div>
                    <div class="dup-tabs-opts-help">
						<?php _e("Checked tables will not be added to the database script.  Excluding certain tables can possibly cause your site or plugins to not work correctly after install!", 'wpduplicator'); ?>
                    </div>	
                </div>
            </div>
        </div>		
    </div>
</div><br/>
<!-- end meta-box options  -->


<!-- META-BOX: INSTALLER -->
<div class="dup-box">
    <div class="dup-box-title">
        <i class="fa fa-bolt"></i> <?php _e('Installer', 'wpduplicator') ?>
        <div class="dup-box-arrow"></div>
    </div>			

    <div class="dup-box-panel" id="dup-pack-installer-panel" style="<?php echo $ui_css_installer ?>">
        <div class="dup-installer-header-1"><?php echo _e('STEP 1 - INPUTS'); ?></div><br/>
        <table class="dup-installer-tbl">
            <tr>
                <td colspan="2"><div class="dup-installer-header-2"><?php _e("MySQL Server", 'wpduplicator') ?></div></td>
            </tr>
            <tr>
                <td style="width:130px"><?php _e("Host", 'wpduplicator') ?></td>
                <td><input type="text" name="dbhost" id="dbhost" value="<?php echo $Package->Installer->OptsDBHost ?>"  maxlength="200" placeholder="localhost"/></td>
            </tr>
            <tr>
                <td><?php _e("Database", 'wpduplicator') ?></td>
                <td><input type="text" name="dbname" id="dbname" value="<?php echo $Package->Installer->OptsDBName ?>" maxlength="100" placeholder="mydatabaseName" /></td>
            </tr>							
            <tr>
                <td><?php _e("User", 'wpduplicator') ?></td>
                <td><input type="text" name="dbuser" id="dbuser" value="<?php echo $Package->Installer->OptsDBUser ?>"  maxlength="100" placeholder="databaseUserName" /></td>
            </tr>
            <tr>
                <td colspan="2"><div class="dup-installer-header-2"><?php _e("Advanced Options", 'wpduplicator') ?></div></td>
            </tr>						
            <tr>
                <td colspan="2">
                    <table>
                        <tr>
                            <td style="width:130px"><?php _e("SSL", 'wpduplicator') ?></td>
                            <td style="padding-right: 20px; white-space: nowrap">
                                <input type="checkbox" name="ssl-admin" id="ssl-admin" <?php echo ($Package->Installer->OptsSSLAdmin) ? "checked='checked'" : ""; ?>  />
                                <label class="chk-labels" for="ssl-admin"><?php _e("Enforce on Admin", 'wpduplicator') ?></label>
                            </td>
                            <td>
                                <input type="checkbox" name="ssl-login" id="ssl-login" <?php echo ($Package->Installer->OptsSSLLogin) ? "checked='checked'" : ""; ?>  />
                                <label class="chk-labels" for="ssl-login"><?php _e("Enforce on Logins", 'wpduplicator') ?></label>
                            </td>
                        </tr>
                        <tr>
                            <td><?php _e("Cache", 'wpduplicator') ?></td>									
                            <td style="padding-right: 20px; white-space: nowrap">
                                <input type="checkbox" name="cache-wp" id="cache-wp" <?php echo ($Package->Installer->OptsCacheWP) ? "checked='checked'" : ""; ?>  />
                                <label class="chk-labels" for="cache-wp"><?php _e("Keep Enabled", 'wpduplicator') ?></label>	
                            </td>
                            <td>
                                <input type="checkbox" name="cache-path" id="cache-path" <?php echo ($Package->Installer->OptsCachePath) ? "checked='checked'" : ""; ?>  />
                                <label class="chk-labels" for="cache-path"><?php _e("Keep Home Path", 'wpduplicator') ?></label>			
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table><br />

        <div class="dup-installer-header-1"><?php echo _e('STEP 2 - INPUTS'); ?></div>

        <table class="dup-installer-tbl">
            <tr>
                <td style="width:130px"><?php _e("New URL", 'wpduplicator') ?></td>
                <td><input type="text" name="url-new" id="url-new" value="<?php echo $Package->Installer->OptsURLNew ?>" placeholder="http://mynewsite.com" /></td>
            </tr>
        </table>
		
        <div class="dup-tabs-opts-help">
			<?php _e("The installer can have these fields pre-filled at install time.", 'wpduplicator'); ?> <b><?php _e('All values are optional.', 'wpduplicator'); ?></b>
        </div>		

    </div>		
</div><br/>
<!-- end meta-box: installer  -->


<div class="dup-button-footer">
    <input type="button" value="<?php _e("Reset", 'wpduplicator') ?>" class="button button-large" <?php echo ($dup_tests['Success']) ? '' : 'disabled="disabled"'; ?> onclick="Duplicator.Pack.ResetSettings()" />
    <input type="submit" value="<?php _e("Next", 'wpduplicator') ?> &#9658;" class="button button-primary button-large" <?php echo ($dup_tests['Success']) ? '' : 'disabled="disabled"'; ?> />
</div>

</form>

<script>
    jQuery(document).ready(function ($) {
        var DUP_NAMEDEFAULT = '<?php echo $default_name ?>';
        var DUP_NAMELAST = $('#package-name').val();

        Duplicator.Pack.ResetSettings = function () {
            var key = 'duplicator_package_active';
            var result = confirm('<?php _e("This will reset all of the current package settings.  Would you like to continue?", "wpduplicator"); ?>');
            if (!result)
                return;

            jQuery('#dup-form-opts-action').val(key);
            jQuery('#dup-form-opts').attr('action', '?page=duplicator&tab=new1')
            jQuery('#dup-form-opts').submit();
        }

        Duplicator.Pack.ResetName = function () {
            var current = $('#package-name').val();
            $('#package-name').val((current == DUP_NAMELAST) ? DUP_NAMEDEFAULT : DUP_NAMELAST)
        }

        Duplicator.Pack.ExcludeTable = function (check) {
            var $cb = $(check);
            if ($cb.is(":checked")) {
                $cb.closest("label").css('textDecoration', 'line-through');
            } else {
                $cb.closest("label").css('textDecoration', 'none');
            }
        }

    });
</script>
