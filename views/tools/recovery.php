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
			echo '<h2><i class="fas fa-undo-alt"></i> ' .  esc_html__('Recovery Points are available in Duplicator Pro.', 'duplicator')  . '</h2>';			           
            esc_html_e('Recovery Points allow you to quickly revert your website to a specific point in time.', 'duplicator');                       
            echo '<br/>';
            esc_html_e('Upgrade plugins or make risky site changes with confidence!', 'duplicator');
		?>
	</div>
	<p style="text-align:center">
		<a href="https://snapcreek.com/duplicator/?utm_source=duplicator_free&utm_medium=wordpress_plugin&utm_content=free_tools_recovery&utm_campaign=duplicator_pro" target="_blank" class="button button-primary button-large dup-check-it-btn" >
			<?php esc_html_e('Check Out Pro', 'duplicator') ?>
		</a>
	</p>
</div>


