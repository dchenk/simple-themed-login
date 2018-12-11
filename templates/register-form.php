<?php
// If you would like to edit this file, copy it to your current theme's directory and edit it there.
// This plugin will always look in your theme's directory first, before using this default template.

global $themedLoginInstance;
$template = $themedLoginInstance->current_instance;

?>
<div class="tml tml-register" id="themed-login<?php $template->the_instance(); ?>">
	<?php
	$template->the_action_template_message('register');
	$template->the_errors(); ?>
	<form id="registerform<?php $template->the_instance(); ?>" action="<?php $template->the_action_url('register', 'login_post'); ?>" method="post">
		<?php
		if ($themedLoginInstance->get_option('login_type') != 'email') {
			?>
			<p class="tml-user-login-wrap">
				<label for="user_login<?php $template->the_instance(); ?>"><?php _e('Username', 'themed-login'); ?></label>
				<input type="text" name="user_login" id="user_login<?php $template->the_instance(); ?>" class="input" value="<?php $template->the_posted_value('user_login'); ?>" size="20">
			</p><?php
		} ?>

		<p class="tml-user-email-wrap">
			<label for="user_email<?php $template->the_instance(); ?>">Email</label>
			<input type="text" name="user_email" id="user_email<?php $template->the_instance(); ?>" class="input" value="<?php $template->the_posted_value('user_email'); ?>" size="20">
		</p>

		<?php do_action('register_form'); ?>

		<p class="tml-registration-confirmation"><?php
			echo apply_filters('themed_login_register_passmail_template_message', __('Registration confirmation will be emailed to you.', 'themed-login')); ?>
		</p>

		<p class="tml-submit-wrap">
			<input type="submit" name="wp-submit" id="wp-submit<?php $template->the_instance(); ?>" value="<?php esc_attr_e('Register', 'themed-login'); ?>">
			<input type="hidden" name="redirect_to" value="<?php $template->the_redirect_url('register'); ?>">
			<input type="hidden" name="instance" value="<?php $template->the_instance(); ?>">
			<input type="hidden" name="action" value="register">
		</p>
	</form>
	<?php $template->the_action_links(['register' => false]); ?>
</div>
