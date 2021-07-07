<?php
defined('ABSPATH') || defined('DUPXABSPATH') || exit;
DUP_Util::hasCapability('export');

require_once(DUPLICATOR_PLUGIN_PATH . '/assets/js/javascript.php');
require_once(DUPLICATOR_PLUGIN_PATH . '/views/inc.header.php');
?>
<style>
    /*================================================
    PAGE-SUPPORT:*/
	div.dup-pro-area {
		padding:10px 70px; max-width:750px; width:90%; margin:auto; text-align:center;
		background:#fff; border-radius:20px;
		box-shadow:inset 0px 0px 67px 20px rgba(241,241,241,1);
	}
	i.dup-gopro-help {color:#777 !important; margin-left:5px; font-size:14px; }
	td.group-header {background-color:#D5D5D5; color: #000; font-size: 20px; padding:7px !important; font-weight: bold; text-align: left}
    div.dup-compare-area {width:400px;  float:left; border:1px solid #dfdfdf; border-radius:4px; margin:10px; line-height:18px;box-shadow:0 8px 6px -6px #ccc;}
	div.feature {background:#fff; padding:15px; margin:2px; text-align:center; min-height:20px}
	div.feature a {font-size:18px; font-weight:bold;}
	div.dup-compare-area div.feature div.info {display:none; padding:7px 7px 5px 7px; font-style:italic; color:#555; font-size:14px}
	div.dup-gopro-header {text-align:center; margin:5px 0 15px 0; font-size:18px; line-height:30px}
	div.dup-gopro-header b {font-size:35px}
	button.dup-check-it-btn {box-shadow:5px 5px 5px 0px #999 !important; font-size:20px !important; height:45px !important;   padding:7px 30px 7px 30px !important;   color:white!important;  background-color: #3e8f3e!important; font-weight: bold!important;
    color: white;
    font-weight: bold;}

	#comparison-table { margin-top:25px; border-spacing:0px;  width:100%}
	#comparison-table th { color:#E21906;}
	#comparison-table td, #comparison-table th { font-size:1.2rem; padding:11px; }
	#comparison-table .feature-column { text-align:left; width:46%}
	#comparison-table .check-column { text-align:center; width:27% }
	#comparison-table tr:nth-child(2n+2) { background-color:#f6f6f6; }
	.button.button-large.dup-check-it-btn { line-height: 28px; }

</style>

<div class="dup-pro-area">
	<img src="<?php echo esc_url(DUPLICATOR_PLUGIN_URL."assets/img/logo-dpro-300x50.png"); ?>"  />
	<div style="font-size:18px; font-style:italic; color:gray; border-bottom: 1px solid silver; padding-bottom:10px; margin-bottom: -30px">
		<?php esc_html_e('The simplicity of Duplicator', 'duplicator') ?>
		<?php esc_html_e('with power for everyone.', 'duplicator') ?>
	</div>

	<table id="comparison-table">
		<tr>
			<th class="feature-column"></th>
			<th class="check-column"><?php esc_html_e('Free', 'duplicator') ?></th>
			<th class="check-column"><?php esc_html_e('Professional', 'duplicator') ?></th>
		</tr>
        
		<!-- =====================
		CORE FEATURES-->
<!--        <tr>
            <td colspan="3" class="group-header"><?php esc_html_e('Core Features', 'duplicator') ?></td>
        </tr>-->
		<tr>
			<td class="feature-column"><?php esc_html_e('Backup Files & Database', 'duplicator') ?></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
		<tr>
			<td class="feature-column"><?php esc_html_e('File &amp; Database Table Filters', 'duplicator') ?></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
		<tr>
			<td class="feature-column"><?php esc_html_e('Migration Wizard', 'duplicator') ?></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
		<tr>
			<td class="feature-column"><?php esc_html_e('Overwrite Live Site', 'duplicator') ?></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
        <tr>
            <td class="feature-column"><?php esc_html_e('Drag and Drop Installs', 'duplicator') ?>
             <sup>
                <i class="fa fa-question-circle dup-gopro-help"
                    data-tooltip-title="<?php esc_attr_e("Drag and Drop Site Overwrites", 'duplicator'); ?>"
                    data-tooltip="<?php esc_attr_e('Overwrite a live site just by dragging an archive to the destination site. No FTP or database creation required!', 'duplicator'); ?>"/></i>
             </sup>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
                <tr>
			<td class="feature-column"><?php esc_html_e('Scheduled Backups', 'duplicator') ?></td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
		<tr>
            <td class="feature-column">
                <?php esc_html_e('Recovery Points', 'duplicator') ?>
                <sup>
                <i class="fa fa-question-circle dup-gopro-help"
                    data-tooltip-title="<?php esc_attr_e("Recovery Points", 'duplicator'); ?>"
                    data-tooltip="<?php esc_attr_e('Recovery Points provide great protection against mistakes and bad updates. Simply mark a package as the "Recovery Point", and if anything goes wrong just browse to the Recovery URL for fast site restoration.', 'duplicator'); ?>"/></i></sup>
            <td class="check-column"></td>
            <td class="check-column"><i class="fa fa-check"></i></td>
		</tr>

		<!-- =====================
		CLOUD STORAGE-->
<!--        <tr>
            <td colspan="3" class="group-header"><?php esc_html_e('Cloud Storage', 'duplicator') ?></td>
        </tr>        -->
		<tr>
			<td class="feature-column">
				<img src="<?php echo esc_url(DUPLICATOR_PLUGIN_URL."assets/img/amazon-64.png") ?>" style='height:16px; width:16px'  />
				<?php esc_html_e('Amazon S3 Storage', 'duplicator') ?>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
		<tr>
			<td class="feature-column">
				<img src="<?php echo esc_url(DUPLICATOR_PLUGIN_URL."assets/img/dropbox-64.png"); ?>" style='height:16px; width:16px'  />
				<?php esc_html_e('Dropbox Storage ', 'duplicator') ?>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
		<tr>
			<td class="feature-column">
				<img src="<?php echo esc_url(DUPLICATOR_PLUGIN_URL."assets/img/google_drive_64px.png"); ?>" style='height:16px; width:16px'  />
				<?php esc_html_e('Google Drive Storage', 'duplicator') ?>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
		<tr>
			<td class="feature-column">
				<img src="<?php echo DUPLICATOR_PLUGIN_URL ?>assets/img/onedrive-48px.png" style='height:16px; width:16px'  />
				<?php esc_html_e('Microsoft OneDrive Storage', 'duplicator') ?>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
		<tr>
			<td class="feature-column">
				<img src="<?php echo DUPLICATOR_PLUGIN_URL ?>assets/img/logo_wasbi.png" style='height:16px; width:16px'  />
				<?php esc_html_e('Wasabi Storage', 'duplicator') ?>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
        <tr>
			<td class="feature-column">
				<img src="<?php echo DUPLICATOR_PLUGIN_URL ?>assets/img/ftp-64.png" style='height:16px; width:16px'  />
				<?php esc_html_e('Remote FTP/SFTP Storage', 'duplicator') ?>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>

		<!-- =====================
		ENHANCED OPTIONS -->
<!--        <tr>
            <td colspan="3" class="group-header"><?php esc_html_e('Enhanced Options', 'duplicator') ?></td>
        </tr>-->
		<tr>
			<td class="feature-column">
				<img src="<?php echo DUPLICATOR_PLUGIN_URL ?>assets/img/cpanel-48.png" style="width:16px; height:12px" />
				<?php esc_html_e('cPanel Database API', 'duplicator') ?>
				<sup>
					<i  class="fa fa-question-circle dup-gopro-help"
						data-tooltip-title="<?php esc_attr_e("cPanel", 'duplicator'); ?>"
                        data-tooltip="<?php esc_attr_e('Create the database and database user directly in the installer.  No need to browse to your host\'s cPanel application.', 'duplicator'); ?>"/></i>
                </sup>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
		<tr>
            <td class="feature-column">
                <?php esc_html_e('Large Site Support', 'duplicator') ?>
                <sup>
					<i class="fa fa-question-circle dup-gopro-help"
						data-tooltip-title="<?php esc_attr_e("Large Site Support", 'duplicator'); ?>"
                        data-tooltip="<?php esc_attr_e('Advanced archive engine processes with server background throttling on multi-gig sites - even on stubborn budget hosts!', 'duplicator'); ?>"/></i>
                </sup>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
        <tr>
			<td class="feature-column">
                <?php esc_html_e('Managed Hosting Support', 'duplicator') ?>
                <sup>
                    <i class="fa fa-question-circle dup-gopro-help"
						data-tooltip-title="<?php esc_attr_e("Managed Hosting Support", 'duplicator'); ?>"
                        data-tooltip="<?php esc_attr_e('In addition to the many standard hosts we\'ve always supported, Duplicator Pro now supports WordPress.com, WPEngine, GoDaddy Managed, Liquid Web Managed and more!', 'duplicator'); ?>"/></i>
                </sup>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
        <tr>
            <td class="feature-column">
                <?php esc_html_e('Streamlined Installer', 'duplicator') ?>
                <sup>
					<i class="fa fa-question-circle dup-gopro-help"
						data-tooltip-title="<?php esc_attr_e("Streamlined Installer", 'duplicator'); ?>"
                        data-tooltip="<?php esc_attr_e('Installer now has two modes: Basic and Advanced. Advanced is like an enhanced Duplicator Lite installer, while Basic is streamlined and only two steps!', 'duplicator'); ?>"/></i>
                </sup>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
		<tr>
			<td class="feature-column"><?php esc_html_e('Email Alerts', 'duplicator') ?></td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
   		<tr>
			<td class="feature-column"><?php esc_html_e('Custom Search & Replace', 'duplicator') ?></td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
   		<tr>
			<td class="feature-column">
                 <?php esc_html_e('Manual/Quick Transfer', 'duplicator') ?>
                <sup>
					<i class="fa fa-question-circle dup-gopro-help"
						data-tooltip-title="<?php esc_attr_e("Manual/Quick Transfer", 'duplicator'); ?>"
                        data-tooltip="<?php esc_attr_e('Manually transfer a package from the default localhost storage to another directory or cloud service at anytime.', 'duplicator'); ?>"/></i>
                </sup>
            </td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>

        <tr>
			<td class="feature-column">
                <?php esc_html_e('WP-Config Extra Control', 'duplicator') ?>
                <sup>
					<i  class="fa fa-question-circle dup-gopro-help"
						data-tooltip-title="<?php esc_attr_e("WP-Config Control Plus", 'duplicator'); ?>"
                        data-tooltip="<?php esc_attr_e('Control many wp-config.php settings right from the installer!', 'duplicator'); ?>"/></i>
                </sup>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>

		<!-- =====================
		POWER TOOLS  -->
<!--        <tr>
            <td colspan="3" class="group-header">
                <?php esc_html_e('Power Tools', 'duplicator');  ?>
                <span style="font-weight:normal; font-size:11px"><?php esc_html_e('Freelancer+', 'duplicator');  ?></span>
            </td>
        </tr>-->
   		<tr>
			<td class="feature-column"><?php esc_html_e('Hourly Schedules', 'duplicator') ?></td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
        <tr>
			<td class="feature-column">
                <?php esc_html_e('Installer Branding', 'duplicator') ?>
                <sup>
					<i  class="fa fa-question-circle dup-gopro-help"
						data-tooltip-title="<?php esc_attr_e("Installer Branding", 'duplicator'); ?>"
                        data-tooltip="<?php esc_attr_e('Give the installer a custom header with your brand and logo.', 'duplicator'); ?>"/></i>
                </sup>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
        <tr>
			<td class="feature-column">
                <?php esc_html_e('Migrate Duplicator Settings', 'duplicator') ?>
                <sup>
					<i  class="fa fa-question-circle dup-gopro-help"
						data-tooltip-title="<?php esc_attr_e("Migrate Duplicator Settings", 'duplicator'); ?>"
                        data-tooltip="<?php esc_attr_e('Exports all schedules, storage locations, templates and settings from this Duplicator Pro instance into a downloadable export file.', 'duplicator'); ?>"/></i>
                </sup>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>

        <tr>
			<td class="feature-column">
                <?php esc_html_e('Regenerate Salts', 'duplicator') ?>
                <sup>
					<i  class="fa fa-question-circle dup-gopro-help"
						data-tooltip-title="<?php esc_attr_e("Regenerate Salts", 'duplicator'); ?>"
                        data-tooltip="<?php esc_attr_e('Installer contains option to regenerate salts in the wp-config.php file.  This feature is only available with Freelancer, Business or Gold licenses.', 'duplicator'); ?>"/></i>
                </sup>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
		<tr>
			<td class="feature-column">
				<?php esc_html_e('Priority Customer Support', 'duplicator') ?>
				<sup><i  class="fa fa-question-circle dup-gopro-help"
						data-tooltip-title="<?php esc_attr_e("Support", 'duplicator'); ?>"
                        data-tooltip="<?php esc_attr_e('Pro users get top priority for any requests to our support desk.  In most cases responses will be answered in under 24 hours.', 'duplicator'); ?>"/></i>
                </sup>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>


		<!-- =====================
		MULTI-SITE -->
<!--        <tr>
            <td colspan="3" class="group-header">
                <?php esc_html_e('MultiSite', 'duplicator');  ?>
            </td>
        </tr>-->
		<tr>
			<td class="feature-column"><?php esc_html_e('Multisite Network Migration', 'duplicator') ?></td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
        <tr>
			<td class="feature-column"><?php esc_html_e('Multisite Subsite &gt; Standalone', 'duplicator') ?><sup>
					<i  class="fa fa-question-circle dup-gopro-help"
						data-tooltip-title="<?php esc_attr_e("Multisite", 'duplicator'); ?>"
                        data-tooltip="<?php esc_attr_e('Install an individual subsite from a Multisite as a standalone site.  This feature is only available with Business or Gold licenses.', 'duplicator'); ?>"/></i></sup>
			</td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
		<tr>
			<td class="feature-column"><?php esc_html_e('Plus Many Other Features...', 'duplicator') ?></td>
			<td class="check-column"></td>
			<td class="check-column"><i class="fa fa-check"></i></td>
		</tr>
	</table>

	<br style="clear:both" />
	<p style="text-align:center">
		<button onclick="window.open('https://snapcreek.com/duplicator/?utm_source=duplicator_free&utm_medium=wordpress_plugin&utm_content=free_go_pro&utm_campaign=duplicator_pro');" class="button button-large dup-check-it-btn" >
			<?php esc_html_e('Check It Out!', 'duplicator') ?>
		</button>
	</p>
	<br/><br/>
</div>
<br/><br/>
