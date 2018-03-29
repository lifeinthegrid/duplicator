<?php
defined("ABSPATH") or die("");
/** IDE HELPERS */
/* @var $GLOBALS['DUPX_AC'] DUPX_ArchiveConfig */

require_once($GLOBALS['DUPX_INIT'] . '/classes/class.crypt.php');

$_POST['secure-pass'] = isset($_POST['secure-pass']) ? $_POST['secure-pass'] : '' ;
$_POST['secure-try']  = isset($_POST['secure-try'])  ? 1 : 0 ;
$page_url = DUPX_HTTP::get_request_uri();
$page_err = 0;

//FORWARD: password not enabled
if (! $GLOBALS['DUPX_AC']->secure_on && ! $_GET['debug']) {
	DUPX_HTTP::post_with_html($page_url, array('view' => 'step1'));
	exit;
}

//POSTBACK: valid password
if ($_POST['secure-pass'] == DUPX_Crypt::unscramble($GLOBALS['DUPX_AC']->secure_pass)) {
	DUPX_HTTP::post_with_html($page_url, array('view' => 'step1'));
	exit;
}

//ERROR: invalid password
if ($_POST['secure-try'] && $_POST['secure-pass'] != DUPX_Crypt::unscramble($GLOBALS['DUPX_AC']->secure_pass)) {
	$page_err = 1;
}
?>


<!-- =========================================
VIEW: STEP 0 - PASSWORD -->
<form method="post" id="i1-pass-form" class="content-form"  data-parsley-validate="">
	<input type="hidden" name="view" value="secure" />
	<input type="hidden" name="secure-try" value="1" />

	<div class="hdr-main">
		Installer Password
	</div>

	<?php if ($page_err) : ?>
		<div class="error-pane">
			<p>Invalid Password! Please try again...</p>
		</div>
	<?php endif; ?>

	<div style="text-align: center">
		This file has been protected by a password.  Please provide the password that was used when the package was created.  If you do not remember the password
		check the details of the package on the site where it was created.
	</div>

	<div class="i1-pass-area">
		<div class="i1-pass-data">
			<label for="secure-pass">&nbsp; Enter Password</label>
			<div id="i1-pass-input">
				<input type="password" name="secure-pass" id="secure-pass" required="required" />
				<button type="button" class="pass-toggle" id="secure-lock" onclick="DUPX.togglePassword()" title="Show/Hide the password"><i class="fa fa-lock"></i></button>
			</div>
            <button type="button" name="secure-btn" id="secure-btn" onclick="DUPX.checkPassword()">Submit</button>
		</div>
	</div>

</form>

<script>
	/**
	 * Submits the password for validation
	 */
	DUPX.checkPassword = function()
	{
		var $form = $('#i1-pass-form');
		$form.parsley().validate();
		if (! $form.parsley().isValid()) {
			return;
		}
		$form.submit();
	}

	/**
	 * Submits the password for validation
	 */
	DUPX.togglePassword = function()
	{
		var $input = $('#secure-pass');
		var $lock  = $('#secure-lock');
		if (($input).attr('type') == 'text') {
			$lock.html('<i class="fa fa-lock"></i>');
			$input.attr('type', 'password');
		} else {
			$lock.html('<i class="fa fa-unlock"></i>');
			$input.attr('type', 'text');
		}
	}
</script>
<!-- END OF VIEW INIT 1 -->