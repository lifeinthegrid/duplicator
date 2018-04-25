<form id="dup-settings-form" action="<?php echo admin_url( 'admin.php?page=duplicator-tools&tab=diagnostics&section=info' ); ?>" method="post">
	<?php wp_nonce_field( 'duplicator_settings_page', '_wpnonce', false ); ?>
	<input type="hidden" id="dup-settings-form-action" name="action" value="">

	<?php if (! empty($action_response))  :	?>
		<div id="message" class="notice notice-success is-dismissible dup-wpnotice-box"><p><?php echo $action_response; ?></p></div>
	<?php endif; ?>

<style>
	<?php echo isset($css_hide_msg) ? $css_hide_msg : ''; ?>
	div.success {color:#4A8254}
	div.failed {color:red}
	table.dup-reset-opts td:first-child {font-weight: bold}
	table.dup-reset-opts td {padding:10px}
	button.dup-fixed-btn {min-width: 150px; text-align: center}
	div#dup-tools-delete-moreinfo {display: none; padding: 5px 0 0 20px; border:1px solid silver; background-color: #fff; border-radius: 5px; padding:10px; margin:5px; width:750px }
</style>

	<?php
		include_once 'inc.data.php';
		include_once 'inc.settings.php';
		include_once 'inc.validator.php';
		include_once 'inc.phpinfo.php';
	?>
</form>
