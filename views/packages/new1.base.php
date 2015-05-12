<?php
require_once (DUPLICATOR_PLUGIN_PATH . 'classes/package.php');

global $wpdb;

//POST BACK
$action_updated = null;
if (isset($_POST['action']))
{
    $action_result = DUP_Settings::DeleteWPOption($_POST['action']);
    switch ($_POST['action'])
    {
        case 'duplicator_package_active' : $action_response = __('Package settings have been reset.', 'wpduplicator');
            break;
    }
}

DUP_Util::InitSnapshotDirectory();

$Package = DUP_Package::GetActive();
$package_hash = $Package->MakeHash();

$dup_tests = array();
$dup_tests = DUP_Server::GetRequirements();
$default_name = DUP_Package::GetDefaultName();

$view_state = DUP_UI::GetViewStateArray();
$ui_css_storage = (isset($view_state['dup-pack-storage-panel']) && $view_state['dup-pack-storage-panel']) ? 'display:block' : 'display:none';
$ui_css_archive = (isset($view_state['dup-pack-archive-panel']) && $view_state['dup-pack-archive-panel']) ? 'display:block' : 'display:none';
$ui_css_installer = (isset($view_state['dup-pack-installer-panel']) && $view_state['dup-pack-installer-panel']) ? 'display:block' : 'display:none';
?>

<style>
    /* -----------------------------
    REQUIREMENTS*/
    div.dup-sys-section {margin:1px 0px 5px 0px}
    div.dup-sys-title {display:inline-block; width:250px; padding:1px; }
    div.dup-sys-title div {display:inline-block;float:right; }
    div.dup-sys-info {display:none; max-width: 98%; margin:4px 4px 12px 4px}	
    div.dup-sys-pass {display:inline-block; color:green;}
    div.dup-sys-fail {display:inline-block; color:#AF0000;}
    div.dup-sys-contact {padding:5px 0px 0px 10px; font-size:11px; font-style:italic}
    span.dup-toggle {float:left; margin:0 2px 2px 0; }
    table.dup-sys-info-results td:first-child {width:200px}

    /* -----------------------------
    PACKAGE OPTS*/
    form#dup-form-opts label {line-height:22px}
    form#dup-form-opts input[type=checkbox] {margin-top:3px}
    form#dup-form-opts fieldset {border-radius:4px;  border-top:1px solid #dfdfdf;  line-height:20px}
    form#dup-form-opts fieldset{padding:10px 15px 15px 15px; min-height:275px; margin:0 10px 10px 10px}
    form#dup-form-opts textarea, input[type="text"] {width:100%}
    form#dup-form-opts textarea#filter-dirs {height:85px}
    form#dup-form-opts textarea#filter-exts {height:27px}
    textarea#package_notes {height:37px;}
	div.dup-notes-add {float:right; margin:-4px 2px 4px 0;}
    div#dup-notes-area {display:none}

    /*ARCHIVE SECTION*/
    form#dup-form-opts div.tabs-panel{max-height:550px; padding:10px; min-height:280px}
    form#dup-form-opts ul li.tabs{font-weight:bold}
    ul.category-tabs li {padding:4px 15px 4px 15px}
    select#archive-format {min-width:100px; margin:1px 0px 4px 0px}
    span#dup-archive-filter-file {color:#A62426; display:none}
    span#dup-archive-filter-db {color:#A62426; display:none}
    div#dup-file-filter-items, div#dup-db-filter-items {padding:5px 0px 0px 0px}
    label.dup-enable-filters {display:inline-block; margin:-5px 0px 5px 0px}
    div.dup-quick-links {font-size:11px; float:right; display:inline-block; margin-top:2px; font-style:italic}
    div.dup-tabs-opts-help {font-style:italic; font-size:11px; margin:10px 0px 0px 10px; color:#777}
    table#dup-dbtables td {padding:1px 15px 1px 4px}

    /*INSTALLER SECTION*/
    div.dup-installer-header-1 {font-weight:bold; padding-bottom:2px; width:100%}
    div.dup-installer-header-2 {font-weight:bold; border-bottom:1px solid #dfdfdf; padding-bottom:2px; width:100%}
    label.chk-labels {display:inline-block; margin-top:1px}
    table.dup-installer-tbl {width:95%; margin-left:20px}
</style>

<!-- =========================================
TOOL BAR: STEPS -->
<table id="dup-toolbar">
    <tr valign="top">
        <td style="white-space: nowrap">
            <div id="dup-wiz">
                <div id="dup-wiz-steps">
                    <div class="active-step"><a><span>1</span> <?php _e('Setup', 'wpduplicator'); ?></a></div>
                    <div><a><span>2</span> <?php _e('Scan', 'wpduplicator'); ?> </a></div>
                    <div><a><span>3</span> <?php _e('Build', 'wpduplicator'); ?> </a></div>
                </div>
                <div id="dup-wiz-title">
					<?php _e('Step 1: Package Setup', 'wpduplicator'); ?>
                </div> 
            </div>	
        </td>
        <td class="dup-toolbar-btns">
            <a id="dup-pro-create-new"  href="?page=duplicator" class="add-new-h2"><i class="fa fa-archive"></i> <?php _e("All Packages", 'wpduplicator'); ?></a> &nbsp;
            <span> <?php _e("Create New", 'wpduplicator'); ?></span>
        </td>
    </tr>
</table>	
<hr style="margin-bottom:8px">



<?php if (!empty($action_response)) : ?>
    <div id="message" class="updated below-h2"><p><?php echo $action_response; ?></p></div>
<?php endif; ?>	

<!-- =========================================
META-BOX1: SYSTEM REQUIREMENTS -->
<div class="dup-box">
    <div class="dup-box-title dup-box-title-fancy">
        <i class="fa fa-check-square-o"></i>
        <?php
        _e("Requirements:", 'wpduplicator');
        echo ($dup_tests['Success']) ? ' <div class="dup-sys-pass">Pass</div>' : ' <div class="dup-sys-fail">Fail</div>';
        ?>
        <div class="dup-box-arrow"></div>
    </div>

    <div class="dup-box-panel" style="<?php echo ($dup_tests['Success']) ? 'display:none' : ''; ?>">

        <div class="dup-sys-section">
            <i><?php _e("System requirements must pass for the Duplicator to work properly.  Click each link for details.", 'wpduplicator'); ?></i>
        </div>

        <!-- PHP SUPPORT -->
        <div class='dup-sys-req'>
            <div class='dup-sys-title'>
                <a><?php _e('PHP Support', 'wpduplicator'); ?></a>
                <div><?php echo $dup_tests['PHP']['ALL']; ?></div>
            </div>
            <div class="dup-sys-info dup-info-box">
                <table class="dup-sys-info-results">
                    <tr>
                        <td><?php printf("%s [%s]", __("PHP Version", 'wpduplicator'), phpversion()); ?></td>
                        <td><?php echo $dup_tests['PHP']['VERSION'] ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Zip Archive Enabled', 'wpduplicator'); ?></td>
                        <td><?php echo $dup_tests['PHP']['ZIP'] ?></td>
                    </tr>					
                    <tr>
                        <td><?php _e('Safe Mode Off', 'wpduplicator'); ?></td>
                        <td><?php echo $dup_tests['PHP']['SAFE_MODE'] ?></td>
                    </tr>					
                    <tr>
                        <td><?php _e('Function', 'wpduplicator'); ?> <a href="http://php.net/manual/en/function.file-get-contents.php" target="_blank">file_get_contents</a></td>
                        <td><?php echo $dup_tests['PHP']['FUNC_1'] ?></td>
                    </tr>					
                    <tr>
                        <td><?php _e('Function', 'wpduplicator'); ?> <a href="http://php.net/manual/en/function.file-put-contents.php" target="_blank">file_put_contents</a></td>
                        <td><?php echo $dup_tests['PHP']['FUNC_2'] ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Function', 'wpduplicator'); ?> <a href="http://php.net/manual/en/mbstring.installation.php" target="_blank">mb_strlen</a></td>
                        <td><?php echo $dup_tests['PHP']['FUNC_3'] ?></td>
                    </tr>					
                </table>
                <small>
					<?php _e("PHP versions 5.2.17+ or higher is required. Please note that in versioning logic a value such as 5.2.9 is less than 5.2.17. For compression to work the ZipArchive extension for PHP is required. Safe Mode should be set to 'Off' in you php.ini file and is deprecated as of PHP 5.3.0.  For any issues in this section please contact your hosting provider or server administrator.  For additional information see our online documentation.", 'wpduplicator'); ?>
                </small>
            </div>
        </div>		

        <!-- PERMISSIONS -->
        <div class='dup-sys-req'>
            <div class='dup-sys-title'>
                <a><?php _e('Permissions', 'wpduplicator'); ?></a> <div><?php echo $dup_tests['IO']['ALL']; ?></div>
            </div>
            <div class="dup-sys-info dup-info-box">
                <b><?php _e("Required Paths", 'wpduplicator'); ?></b>
                <div style="padding:3px 0px 0px 15px">
                    <?php
                    printf("<b>%s</b> &nbsp; [%s] <br/>", $dup_tests['IO']['WPROOT'], DUPLICATOR_WPROOTPATH);
                    printf("<b>%s</b> &nbsp; [%s] <br/>", $dup_tests['IO']['SSDIR'], DUPLICATOR_SSDIR_PATH);
                    printf("<b>%s</b> &nbsp; [%s] <br/>", $dup_tests['IO']['SSTMP'], DUPLICATOR_SSDIR_PATH_TMP);
                    //printf("<b>%s:</b> [%s] <br/>", __('PHP Script Owner', 'wpduplicator'), DUP_Util::GetCurrentUser());	
                    //printf("<b>%s:</b> [%s] <br/>", __('PHP Process Owner', 'wpduplicator'), DUP_Util::GetProcessOwner());	
                    ?>
                </div>
                <small>
					<?php _e("Permissions can be difficult to resolve on some systems. If the plugin can not read the above paths here are a few things to try. 1) Set the above paths to have permissions of 755 for directories and 644 for files. You can temporarily try 777 however, be sure you don’t leave them this way. 2) Check the owner/group settings for both files and directories. The PHP script owner and the process owner are different. The script owner owns the PHP script but the process owner is the user the script is running as, thus determining its capabilities/privileges in the file system. For more details contact your host or server administrator or visit the 'Help' menu under Duplicator for additional online resources.", 'wpduplicator'); ?>
                </small>					
            </div>
        </div>

        <!-- SERVER SUPPORT -->
        <div class='dup-sys-req'>
            <div class='dup-sys-title'>
                <a><?php _e('Server Support', 'wpduplicator'); ?></a>
                <div><?php echo $dup_tests['SRV']['ALL']; ?></div>
            </div>
            <div class="dup-sys-info dup-info-box">
                <table class="dup-sys-info-results">
                    <tr>
                        <td><?php printf("%s [%s]", __("MySQL Version", 'wpduplicator'), $wpdb->db_version()); ?></td>
                        <td><?php echo $dup_tests['SRV']['MYSQL_VER'] ?></td>
                    </tr>
                    <tr>
                        <td><?php printf("%s", __("MySQLi Support", 'wpduplicator')); ?></td>
                        <td><?php echo $dup_tests['SRV']['MYSQLi'] ?></td>
                    </tr>
                </table>
                <small>
                    <?php
                    _e("MySQL version 5.0+ or better is required and the PHP MySQLi extension (note the trailing 'i') is also required.  Contact your server administrator and request that mysqli extension and MySQL Server 5.0+ be installed. Please note in future versions support for other databases and extensions will be added.", 'wpduplicator');
                    echo "&nbsp;<i><a href='http://php.net/manual/en/mysqli.installation.php' target='_blank'>[" . __('more info', 'wpduplicator') . "]</a></i>";
                    ?>										
                </small>
            </div>
        </div>

        <!-- RESERVED FILES -->
        <div class='dup-sys-req'>
            <div class='dup-sys-title'>
                <a><?php _e('Reserved Files', 'wpduplicator'); ?></a> <div><?php echo $dup_tests['RES']['INSTALL']; ?></div>
            </div>
            <div class="dup-sys-info dup-info-box">
                <?php if ($dup_tests['RES']['INSTALL'] == 'Pass') : ?>
                        <?php _e('None of the reserved files (installer.php, installer-data.sql and installer-log.txt) where found from a previous install.  This means you are clear to create a new package.', 'wpduplicator'); ?>
                    <?php else: ?> 
                    <form method="post" action="admin.php?page=duplicator-tools&tab=cleanup&action=installer">
                    <?php _e('A reserved file(s) was found in the WordPress root directory. Reserved file names are installer.php, installer-data.sql and installer-log.txt.  To archive your data correctly please remove any of these files from your WordPress root directory.  Then try creating your package again.', 'wpduplicator'); ?>
                        <br/><input type='submit' class='button action' value='<?php _e('Remove Files Now', 'wpduplicator') ?>' style='font-size:10px; margin-top:5px;' />
                    </form>
				<?php endif; ?>
            </div>
        </div>

        <!-- ONLINE SUPPORT -->
        <div class="dup-sys-contact">
            <?php
            printf("<i class='fa fa-question-circle'></i> %s <a href='admin.php?page=duplicator-help'>[%s]</a>", __("For additional help please see the ", 'wpduplicator'), __("help page", 'wpduplicator'));
            ?>
        </div>

    </div>
</div><br/>


<!-- =========================================
FORM PACKAGE OPTIONS -->
<div style="padding:5px 5px 2px 5px">
	<?php include('new1.inc.form.php'); ?>
</div>

<script type="text/javascript">
    jQuery(document).ready(function ($) {

        /*	METHOD: Toggle Options tabs*/
        Duplicator.Pack.ToggleOptTabs = function (tab, label) {
            $('.category-tabs li').removeClass('tabs');
            $(label).parent().addClass('tabs');
            if (tab == 1) {
                $('#dup-pack-opts-tabs-panel-1').show();
                $('#dup-pack-opts-tabs-panel-2').hide();
            } else {
                $('#dup-pack-opts-tabs-panel-2').show();
                $('#dup-pack-opts-tabs-panel-1').hide();
            }
        }

        /*	METHOD: Enable/Disable the file filter elements */
        Duplicator.Pack.ToggleFileFilters = function () {
            var $filterItems = $('#dup-file-filter-items');
            if ($("#filter-on").is(':checked')) {
                $filterItems.removeAttr('disabled').css({color: '#000'});
                $('#filter-exts,#filter-dirs').removeAttr('readonly').css({color: '#000'});
                $('#dup-archive-filter-file').show();
            } else {
                $filterItems.attr('disabled', 'disabled').css({color: '#999'});
                $('#filter-dirs, #filter-exts').attr('readonly', 'readonly').css({color: '#999'});
                $('#dup-archive-filter-file').hide();
            }
        };

        /*	METHOD: Appends a path to the directory filter */
        Duplicator.Pack.ToggleDBFilters = function () {
            var $filterItems = $('#dup-db-filter-items');

            if ($("#dbfilter-on").is(':checked')) {
                $filterItems.removeAttr('disabled').css({color: '#000'});
                $('#dup-dbtables input').removeAttr('readonly').css({color: '#000'});
                $('#dup-archive-filter-db').show();
            } else {
                $filterItems.attr('disabled', 'disabled').css({color: '#999'});
                $('#dup-dbtables input').attr('readonly', 'readonly').css({color: '#999'});
                $('#dup-archive-filter-db').hide();
            }
        };


        /*	METHOD: Appends a path to the directory filter  */
        Duplicator.Pack.AddExcludePath = function (path) {
            var text = $("#filter-dirs").val() + path + ';\n';
            $("#filter-dirs").val(text);
        };

        /*	METHOD: Appends a path to the extention filter  */
        Duplicator.Pack.AddExcludeExts = function (path) {
            var text = $("#filter-exts").val() + path + ';';
            $("#filter-exts").val(text);
        };


        //Init: Toogle for system requirment detial links
        $('.dup-sys-title a').each(function () {
            $(this).attr('href', 'javascript:void(0)');
            $(this).click({selector: '.dup-sys-info'}, Duplicator.Pack.ToggleSystemDetails);
            $(this).prepend("<span class='ui-icon ui-icon-triangle-1-e dup-toggle' />");
        });

        //Init: Color code Pass/Fail/Warn items
        $('.dup-sys-title div').each(function () {
            $(this).addClass(($(this).text() == 'Pass') ? 'dup-sys-pass' : 'dup-sys-fail');
        });

        //Init: Toggle OptionTabs
        Duplicator.Pack.ToggleFileFilters();
        Duplicator.Pack.ToggleDBFilters();
    });
</script>