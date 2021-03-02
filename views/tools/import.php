<?php
defined('ABSPATH') || defined('DUPXABSPATH') || exit;
?>
<style>
	div.panel {padding: 20px 5px 10px 10px;}
	div.area {font-size:16px; text-align: center; line-height: 30px}
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
	</div>
	<p style="text-align:center">
		<a href="https://snapcreek.com/duplicator/?utm_source=duplicator_free&utm_medium=wordpress_plugin&utm_content=free_tools_imports&utm_campaign=duplicator_pro" target="_blank" class="button button-primary button-large dup-check-it-btn" >
			<?php esc_html_e('Check Out Pro', 'duplicator') ?>
		</a>
	</p>
</div>


