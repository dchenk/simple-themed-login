<?php
// If you would like to edit this file, copy it to your current theme's directory and edit it there.
// This plugin will always look in your theme's directory first before using this default template.

// The reCAPTCHA key must be in a file named "recaptcha-key.txt" in the root of the WP installation
// and contain just the key string.
$recapKey = '{not found}';
$recapKeyPath = ABSPATH . 'recaptcha-key.txt';
$stlRecaptcha = defined('STL_RECAPTCHA') && STL_RECAPTCHA;
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
?>
<div class="tml tml-login" id="theme-my-login<?php $template->the_instance(); ?>">
	<?php
	$template->the_action_template_message('login');
	$template->the_errors(); ?>
	<form name="loginform" id="loginform<?php $template->the_instance(); ?>" action="<?php $template->the_action_url('login', 'login_post'); ?>" method="post">
		<p class="tml-user-login-wrap">
			<label for="user_login<?php $template->the_instance(); ?>"><?php
				if ('username' == $theme_my_login->get_option('login_type')) {
					_e('Username', 'themed-login');
				} elseif ('email' == $theme_my_login->get_option('login_type')) {
					_e('E-mail', 'themed-login');
				} else {
					_e('Username or E-mail', 'themed-login');
				}
			?></label>
			<input type="text" name="log" id="user_login<?php $template->the_instance(); ?>" class="input" value="<?php $template->the_posted_value('log'); ?>" size="20">
		</p>
		<p class="tml-user-pass-wrap">
			<label for="user_pass<?php $template->the_instance(); ?>"><?php _e('Password', 'themed-login'); ?></label>
			<input type="password" name="pwd" id="user_pass<?php $template->the_instance(); ?>" class="input" value="" size="20" autocomplete="off">
		</p>
		<?php
		if ($stlRecaptcha) {
			echo '<div class="g-recaptcha" data-callback="boxChecked" data-expired-callback="boxRedo" data-error-callback="boxRedo" data-sitekey="' . $recapKey . '"></div>';
		} ?>
		<?php do_action('login_form'); ?>
		<div class="tml-rememberme-submit-wrap">
			<p class="tml-rememberme-wrap">
				<input name="rememberme" type="checkbox" id="rememberme<?php $template->the_instance(); ?>" value="forever">
				<label for="rememberme<?php $template->the_instance(); ?>"><?php esc_attr_e('Remember Me', 'themed-login'); ?></label>
			</p>
			<p class="tml-submit-wrap">
				<input type="submit" name="wp-submit" id="wp-submit<?php $template->the_instance(); ?>" value="<?php esc_attr_e('Log In', 'themed-login'); ?>">
				<input type="hidden" name="redirect_to" value="<?php $template->the_redirect_url('login'); ?>">
				<input type="hidden" name="instance" value="<?php $template->the_instance(); ?>">
				<input type="hidden" name="action" value="login">
			</p>
		</div>
	</form>
	<?php $template->the_action_links(['login' => false]); ?>
</div>
