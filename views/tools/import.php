<?php
defined('ABSPATH') || defined('DUPXABSPATH') || exit;
?>
<style>
	div.panel {padding: 20px 5px 10px 10px;}
	div.area {font-size:16px; text-align: center; line-height: 30px;   margin:auto; width: 100%;}
    div.sc-note {color:maroon; font-style: italic; line-height:17px; font-size: 12px; margin:30px auto 40px auto; max-width: 650px; }
</style>

<div class="panel">

	<br/>
	<div class="area">
		<img src="<?php echo DUPLICATOR_PLUGIN_URL ?>assets/img/logo-dpro-300x50.png"  />
		<?php
			echo '<h2><i class="fas fa-arrow-alt-circle-down"></i> ' .  esc_html__('Drag and Drop imports are available in Duplicator Pro.', 'duplicator')  . '</h2>';
			esc_html_e('The Import feature lets you skip the FTP and database creation steps when installing a site.', 'duplicator');
            echo '<br/>';
			esc_html_e('Just drag and drop a Duplicator Pro archive to quickly replace an existing WordPress installation!', 'duplicator');
		?>
        <div class="sc-note">
            <?php esc_html_e(' Drag and Drop import functionality works with packages created by Duplicator Pro.  In the near future, the Duplicator Pro importer '
                . 'will be enhanced to allow the importing of Duplicator Lite packages.  For instructions on how to perform a classic install of Duplicator Lite '
                . 'packages visit the ', 'duplicator'); ?>
            <a href="https://snapcreek.com/duplicator/docs/quick-start/?utm_source=duplicator_free&utm_medium=wordpress_plugin&utm_content=package_built_install_help&utm_campaign=duplicator_free#quick-040-q" target="_blank"><?php esc_html_e('Quick Start Guide', 'duplicator'); ?></a>.
        </div>
	</div>
	<p style="text-align:center">
		<a href="https://snapcreek.com/duplicator/?utm_source=duplicator_free&utm_medium=wordpress_plugin&utm_content=free_tools_imports&utm_campaign=duplicator_pro" target="_blank" class="button button-primary button-large dup-check-it-btn" >
			<?php esc_html_e('Check Out Pro', 'duplicator') ?>
		</a>
	</p>
</div>


