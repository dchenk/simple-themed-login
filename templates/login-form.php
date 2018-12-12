<?php
// If you would like to edit this file, copy it to your current theme's directory and edit it there.
// This plugin will always look in your theme's directory first before using this default template.

// The reCAPTCHA key must be in a file named "recaptcha-key.txt" in the root of the WP installation
// and contain just the key string.
$recapKey = '{not found}';
$recapKeyPath = ABSPATH . 'recaptcha-key.txt';
$stlRecaptcha = defined('THEMED_LOGIN_RECAPTCHA') && THEMED_LOGIN_RECAPTCHA;
if ($stlRecaptcha) {
	if (file_exists($recapKeyPath)) {
		$recapKey = file_get_contents($recapKeyPath);
	} ?>
	<script src="https://www.google.com/recaptcha/api.js" async defer></script>
	<script>
		var uBoxChecked = false;
		function onSubmitCheck() {
			if (uBoxChecked) {return true;}
			alert("Please check the box to verify that you're a human.");
			return false;
		}
		function boxChecked() { uBoxChecked = true; }
		function boxRedo() { uBoxChecked = false; alert("Please check the box again to verify that you're a human."); }
	</script>
	<?php
}

global $themedLoginInstance;
$template = $themedLoginInstance->current_instance;

?>
<div class="tml tml-login" id="themed-login<?php $template->instance_id(); ?>">
	<?php
	$template->the_action_template_message('login');
	$template->the_errors(); ?>
	<form id="loginform<?php $template->instance_id(); ?>" action="<?php $template->the_action_url('login', 'login_post'); ?>" method="post">
		<p class="tml-user-login-wrap">
			<label for="user_login<?php $template->instance_id(); ?>"><?php
				switch ($themedLoginInstance->get_option('login_type')) {
				case 'username':
					_e('Username', 'themed-login');
					break;
				case 'email':
					_e('Email', 'themed-login');
					break;
				default:
					_e('Username or Email', 'themed-login');
				}
			?></label>
			<input type="text" name="log" id="user_login<?php $template->instance_id(); ?>" value="<?php $template->the_posted_value('log'); ?>" size="20">
		</p>
		<p class="tml-user-pass-wrap">
			<label for="user_pass<?php $template->instance_id(); ?>"><?php _e('Password', 'themed-login'); ?></label>
			<input type="password" name="pwd" id="user_pass<?php $template->instance_id(); ?>" value="" size="20" autocomplete="off">
		</p>
		<?php
		if ($stlRecaptcha) {
			echo '<div class="g-recaptcha" data-callback="boxChecked" data-expired-callback="boxRedo" data-error-callback="boxRedo" data-sitekey="' . $recapKey . '"></div>';
		} ?>
		<?php do_action('login_form'); ?>
		<div class="tml-rememberme-submit-wrap">
			<p class="tml-rememberme-wrap">
				<input name="rememberme" type="checkbox" id="rememberme<?php $template->instance_id(); ?>" value="forever">
				<label for="rememberme<?php $template->instance_id(); ?>"><?php esc_attr_e('Remember Me', 'themed-login'); ?></label>
			</p>
			<p class="tml-submit-wrap">
				<input type="submit" name="wp-submit" id="wp-submit<?php $template->instance_id(); ?>" value="<?php esc_attr_e('Log In', 'themed-login'); ?>">
				<input type="hidden" name="redirect_to" value="<?php $template->the_redirect_url('login'); ?>">
				<input type="hidden" name="instance" value="<?php $template->instance_id(); ?>">
				<input type="hidden" name="action" value="login">
			</p>
		</div>
	</form>
	<?php $template->the_action_links(['login' => false]); ?>
</div>
