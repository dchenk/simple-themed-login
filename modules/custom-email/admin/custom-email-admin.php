<?php
/**
 * Holds Theme My Login Custom E-mail Admin class
 *
 * @package Theme_My_Login
 */

if (!class_exists('Theme_My_Login_Custom_Email_Admin')) {
	/**
	 * Theme My Login Custom E-mail Admin class
	 */
	class Theme_My_Login_Custom_Email_Admin extends Theme_My_Login_Abstract {
		/**
		 * Holds options key
		 *
		 * @var string
		 */
		protected $options_key = 'theme_my_login_email';

		/**
		 * Returns default options
		 */
		public static function default_options() {
			return Theme_My_Login_Custom_Email::default_options();
		}

		/**
		 * Uninstalls the module
		 *
		 * Callback for "tml_uninstall_custom-email/custom-email.php" hook in method Theme_My_Login_Admin::uninstall()
		 *
		 * @see Theme_My_Login_Admin::uninstall()
		 */
		public function uninstall() {
			delete_option($this->options_key);
		}

		/**
		 * Adds "E-mail" to the Theme My Login menu
		 *
		 * Callback for "admin_menu" hook
		 */
		public function admin_menu() {
			add_submenu_page(
				'theme_my_login',
				__('STL Custom E-mail Settings', 'themed-login'),
				__('E-mail', 'themed-login'),
				'manage_options',
				$this->options_key,
				[$this, 'settings_page']
			);

			add_settings_section('general', null, '__return_false', $this->options_key);

			add_settings_field('new_user', __('New User', 'themed-login'), [$this, 'new_user_meta_box'], $this->options_key, 'general');
			add_settings_field('new_user_admin', __('New User Admin', 'themed-login'), [$this, 'new_user_admin_meta_box'], $this->options_key, 'general');
			add_settings_field('retrieve_pass', __('Retrieve Password', 'themed-login'), [$this, 'retrieve_pass_meta_box'], $this->options_key, 'general');
			add_settings_field('reset_pass', __('Reset Password', 'themed-login'), [$this, 'reset_pass_meta_box'], $this->options_key, 'general');
		}

		/**
		 * Registers options group
		 *
		 * Callback for "admin_init" hook
		 */
		public function admin_init() {
			register_setting($this->options_key, $this->options_key, [$this, 'save_settings']);
		}

		/**
		 * Loads admin styles and scripts
		 *
		 * Callback for "load-settings_page_theme-my-login" hook in file "wp-admin/admin.php"
		 */
		public function load_settings_page() {
			wp_enqueue_script('tml-custom-email-admin', plugins_url('custom-email-admin.js', __FILE__), ['postbox']);
		}

		/**
		 * Renders settings page
		 *
		 * Callback for add_submenu_page()
		 */
		public function settings_page() {
			Theme_My_Login_Admin::settings_page([
				'title' => __('Login Email Settings', 'themed-login'),
				'options_key' => $this->options_key,
			]);
		}

		/**
		 * Renders New User Notification settings section
		 *
		 * This is the callback for add_settings_field()
		 *
		 * @access public
		 */
		public function new_user_meta_box() {
			?>
		<p class="description">
			<?php _e('This e-mail will be sent to a new user upon registration.', 'themed-login'); ?>
			<?php _e('Please be sure to include the variable %reseturl% or else the user will not be able to recover their password!', 'themed-login'); ?>
			<?php _e('If any field is left empty, the default will be used instead.', 'themed-login'); ?>
		</p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_new_user_mail_from_name"><?php _e('From Name', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[new_user][mail_from_name]" type="text" id="<?php echo $this->options_key; ?>_new_user_mail_from_name" value="<?php echo $this->get_option(['new_user', 'mail_from_name']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_new_user_mail_from"><?php _e('From E-mail', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[new_user][mail_from]" type="text" id="<?php echo $this->options_key; ?>_new_user_mail_from" value="<?php echo $this->get_option(['new_user', 'mail_from']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_new_user_mail_content_type"><?php _e('E-mail Format', 'themed-login'); ?></label></th>
				<td>
					<select name="<?php echo $this->options_key; ?>[new_user][mail_content_type]" id="<?php echo $this->options_key; ?>_new_user_mail_content_type">
						<option value="plain"<?php selected($this->get_option(['new_user', 'mail_content_type']), 'plain'); ?>><?php _e('Plain Text', 'themed-login'); ?></option>
						<option value="html"<?php selected($this->get_option(['new_user', 'mail_content_type']), 'html'); ?>><?php _e('HTML', 'themed-login'); ?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_new_user_title"><?php _e('Subject', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[new_user][title]" type="text" id="<?php echo $this->options_key; ?>_new_user_title" value="<?php echo $this->get_option(['new_user', 'title']); ?>" class="large-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_new_user_message"><?php _e('Message', 'themed-login'); ?></label></th>
				<td>
					<p class="description"><?php _e('Available Variables', 'themed-login'); ?>: %blogname%, %siteurl%, %reseturl%, %user_login%, %user_email%, %user_ip%</p>
					<textarea name="<?php echo $this->options_key; ?>[new_user][message]" id="<?php echo $this->options_key; ?>_new_user_message" class="large-text" rows="10"><?php echo $this->get_option(['new_user', 'message']); ?></textarea></p>
				</td>
			</tr>
		</table>
		<?php
		}

		/**
		 * Renders New User Admin Notification settings section
		 *
		 * This is the callback for add_settings_field()
		 *
		 * @access public
		 */
		public function new_user_admin_meta_box() {
			?>
		<p class="description">
			<?php _e('This e-mail will be sent to the e-mail address or addresses (multiple addresses may be separated by commas) specified below, upon new user registration.', 'themed-login'); ?>
			<?php _e('If any field is left empty, the default will be used instead.', 'themed-login'); ?>
		</p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_new_user_admin_mail_to"><?php _e('To', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[new_user][admin_mail_to]" type="text" id="<?php echo $this->options_key; ?>_new_user_admin_mail_to" value="<?php echo $this->get_option(['new_user', 'admin_mail_to']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_new_user_admin_mail_from_name"><?php _e('From Name', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[new_user][admin_mail_from_name]" type="text" id="<?php echo $this->options_key; ?>_new_user_admin_mail_from_name" value="<?php echo $this->get_option(['new_user', 'admin_mail_from_name']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_new_user_admin_mail_from"><?php _e('From E-mail', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[new_user][admin_mail_from]" type="text" id="<?php echo $this->options_key; ?>_new_user_admin_mail_from" value="<?php echo $this->get_option(['new_user', 'admin_mail_from']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_new_user_admin_mail_content_type"><?php _e('E-mail Format', 'themed-login'); ?></label></th>
				<td>
					<select name="<?php echo $this->options_key; ?>[new_user][admin_mail_content_type]" id="<?php echo $this->options_key; ?>_new_user_admin_mail_content_type">
						<option value="plain"<?php selected($this->get_option(['new_user', 'admin_mail_content_type']), 'plain'); ?>><?php _e('Plain Text', 'themed-login'); ?></option>
						<option value="html"<?php selected($this->get_option(['new_user', 'admin_mail_content_type']), 'html'); ?>><?php _e('HTML', 'themed-login'); ?></option>
					</select>
				</td>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_new_user_admin_title"><?php _e('Subject', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[new_user][admin_title]" type="text" id="<?php echo $this->options_key; ?>_new_user_admin_title" value="<?php echo $this->get_option(['new_user', 'admin_title']); ?>" class="large-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_new_user_admin_message"><?php _e('Message', 'themed-login'); ?></label></th>
				<td>
					<p class="description"><?php _e('Available Variables', 'themed-login'); ?>: %blogname%, %siteurl%, %user_login%, %user_email%, %user_ip%</p>
					<textarea name="<?php echo $this->options_key; ?>[new_user][admin_message]" id="<?php echo $this->options_key; ?>_new_user_admin_message" class="large-text" rows="10"><?php echo $this->get_option(['new_user', 'admin_message']); ?></textarea>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">&nbsp;</th>
				<td>
					<input name="<?php echo $this->options_key; ?>[new_user][admin_disable]" type="checkbox" id="<?php echo $this->options_key; ?>_new_user_admin_disable" value="1"<?php checked(1, $this->get_option(['new_user', 'admin_disable'])); ?>>
					<label for="<?php echo $this->options_key; ?>_new_user_admin_disable"><?php _e('Disable Admin Notification', 'themed-login'); ?></label>
				</td>
			</tr>
		</table>
		<?php
		}

		/**
		 * Renders Retrieve Password settings section
		 *
		 * This is the callback for add_settings_field()
		 *
		 * @access public
		 */
		public function retrieve_pass_meta_box() {
			?>
		<p class="description">
			<?php _e('This e-mail will be sent to a user when they attempt to recover their password.', 'themed-login'); ?>
			<?php _e('Please be sure to include the variable %reseturl% or else the user will not be able to recover their password!', 'themed-login'); ?>
			<?php _e('If any field is left empty, the default will be used instead.', 'themed-login'); ?>
		</p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_retrieve_pass_mail_from_name"><?php _e('From Name', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[retrieve_pass][mail_from_name]" type="text" id="<?php echo $this->options_key; ?>_retrieve_pass_mail_from_name" value="<?php echo $this->get_option(['retrieve_pass', 'mail_from_name']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_retrieve_pass_mail_from"><?php _e('From E-mail', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[retrieve_pass][mail_from]" type="text" id="<?php echo $this->options_key; ?>_retrieve_pass_mail_from" value="<?php echo $this->get_option(['retrieve_pass', 'mail_from']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_retrieve_pass_mail_content_type"><?php _e('E-mail Format', 'themed-login'); ?></label></th>
				<td>
					<select name="<?php echo $this->options_key; ?>[retrieve_pass][mail_content_type]" id="<?php echo $this->options_key; ?>_retrieve_pass_mail_content_type">
						<option value="plain"<?php selected($this->get_option(['retrieve_pass', 'mail_content_type']), 'plain'); ?>><?php _e('Plain Text', 'themed-login'); ?></option>
						<option value="html"<?php selected($this->get_option(['retrieve_pass', 'mail_content_type']), 'html'); ?>><?php _e('HTML', 'themed-login'); ?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_retrieve_pass_title"><?php _e('Subject', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[retrieve_pass][title]" type="text" id="<?php echo $this->options_key; ?>_retrieve_pass_title" value="<?php echo $this->get_option(['retrieve_pass', 'title']); ?>" class="large-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_retrieve_pass_message"><?php _e('Message', 'themed-login'); ?></label></th>
				<td>
					<p class="description"><?php _e('Available Variables', 'themed-login'); ?>: %blogname%, %siteurl%, %reseturl%, %user_login%, %user_email%, %user_ip%</p>
					<textarea name="<?php echo $this->options_key; ?>[retrieve_pass][message]" id="<?php echo $this->options_key; ?>_retrieve_pass_message" class="large-text" rows="10"><?php echo $this->get_option(['retrieve_pass', 'message']); ?></textarea>
				</td>
			</tr>
		</table>
		<?php
		}

		/**
		 * Renders Reset Password settings section
		 *
		 * This is the callback for add_settings_field()
		 *
		 * @access public
		 */
		public function reset_pass_meta_box() {
			?>
		<p class="description">
			<?php _e('This e-mail will be sent to the e-mail address or addresses (multiple addresses may be separated by commas) specified below, upon user password change.', 'themed-login'); ?>
			<?php _e('If any field is left empty, the default will be used instead.', 'themed-login'); ?>
		</p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_reset_pass_admin_mail_to"><?php _e('To', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[reset_pass][admin_mail_to]" type="text" id="<?php echo $this->options_key; ?>_reset_pass_admin_mail_to" value="<?php echo $this->get_option(['reset_pass', 'admin_mail_to']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_reset_pass_admin_mail_from_name"><?php _e('From Name', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[reset_pass][admin_mail_from_name]" type="text" id="<?php echo $this->options_key; ?>_reset_pass_admin_mail_from_name" value="<?php echo $this->get_option(['reset_pass', 'admin_mail_from_name']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_reset_pass_admin_mail_from"><?php _e('From E-mail', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[reset_pass][admin_mail_from]" type="text" id="<?php echo $this->options_key; ?>_reset_pass_admin_mail_from" value="<?php echo $this->get_option(['reset_pass', 'admin_mail_from']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_reset_pass_admin_mail_content_type"><?php _e('E-mail Format', 'themed-login'); ?></label></th>
				<td>
					<select name="<?php echo $this->options_key; ?>[reset_pass][admin_mail_content_type]" id="<?php echo $this->options_key; ?>_reset_pass_admin_mail_content_type">
						<option value="plain"<?php selected($this->get_option(['reset_pass', 'admin_mail_content_type']), 'plain'); ?>><?php _e('Plain Text', 'themed-login'); ?></option>
						<option value="html"<?php selected($this->get_option(['reset_pass', 'admin_mail_content_type']), 'html'); ?>><?php _e('HTML', 'themed-login'); ?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_reset_pass_admin_title"><?php _e('Subject', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[reset_pass][admin_title]" type="text" id="<?php echo $this->options_key; ?>_reset_pass_admin_title" value="<?php echo $this->get_option(['reset_pass', 'admin_title']); ?>" class="large-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_reset_pass_admin_message"><?php _e('Message', 'themed-login'); ?></label></th>
				<td>
					<p class="description"><?php _e('Available Variables', 'themed-login'); ?>: %blogname%, %siteurl%, %user_login%, %user_email%, %user_ip%</p>
					<textarea name="<?php echo $this->options_key; ?>[reset_pass][admin_message]" id="<?php echo $this->options_key; ?>_reset_pass_admin_message" class="large-text" rows="10"><?php echo $this->get_option(['reset_pass', 'admin_message']); ?></textarea>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">&nbsp;</th>
				<td>
					<input name="<?php echo $this->options_key; ?>[reset_pass][admin_disable]" type="checkbox" id="<?php echo $this->options_key; ?>_reset_pass_admin_disable" value="1"<?php checked(1, $this->get_option(['reset_pass', 'admin_disable'])); ?>>
					<label for="<?php echo $this->options_key; ?>_reset_pass_admin_disable"><?php _e('Disable Admin Notification', 'themed-login'); ?></label>
				</td>
			</tr>
		</table>
		<?php
		}

		/**
		 * Renders User Activation settings section
		 *
		 * This is the callback for add_settings_field()
		 *
		 * @access public
		 */
		public function user_activation_meta_box() {
			?>
		<p class="description">
			<?php _e('This e-mail will be sent to a new user upon registration when "E-mail Confirmation" is checked for "User Moderation".', 'themed-login'); ?>
			<?php _e('Please be sure to include the variable %activateurl% or else the user will not be able to activate their account!', 'themed-login'); ?>
			<?php _e('If any field is left empty, the default will be used instead.', 'themed-login'); ?>
		</p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_activation_mail_from_name"><?php _e('From Name', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[user_activation][mail_from_name]" type="text" id="<?php echo $this->options_key; ?>_user_activation_mail_from_name" value="<?php echo $this->get_option(['user_activation', 'mail_from_name']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_activation_mail_from"><?php _e('From E-mail', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[user_activation][mail_from]" type="text" id="<?php echo $this->options_key; ?>_user_activation_mail_from" value="<?php echo $this->get_option(['user_activation', 'mail_from']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_activation_mail_content_type"><?php _e('E-mail Format', 'themed-login'); ?></label></th>
				<td>
					<select name="<?php echo $this->options_key; ?>[user_activation][mail_content_type]" id="<?php echo $this->options_key; ?>_user_activation_mail_content_type">
						<option value="plain"<?php selected($this->get_option(['user_activation', 'mail_content_type']), 'plain'); ?>><?php _e('Plain Text', 'themed-login'); ?></option>
						<option value="html"<?php selected($this->get_option(['user_activation', 'mail_content_type']), 'html'); ?>><?php _e('HTML', 'themed-login'); ?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_activation_title"><?php _e('Subject', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[user_activation][title]" type="text" id="<?php echo $this->options_key; ?>_user_activation_title" value="<?php echo $this->get_option(['user_activation', 'title']); ?>" class="large-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_activation_message"><?php _e('Message', 'themed-login'); ?></label></th>
				<td>
					<p class="description"><?php _e('Available Variables', 'themed-login'); ?>: %blogname%, %siteurl%, %activateurl%, %user_login%, %user_email%, %user_ip%</p>
					<textarea name="<?php echo $this->options_key; ?>[user_activation][message]" id="<?php echo $this->options_key; ?>_user_activation_message" class="large-text" rows="10"><?php echo $this->get_option(['user_activation', 'message']); ?></textarea>
				</td>
			</tr>
		</table>
		<?php
		}

		/**
		 * Renders User Approval settings section
		 *
		 * This is the callback for add_settings_field()
		 *
		 * @access public
		 */
		public function user_approval_meta_box() {
			?>
		<p class="description">
			<?php _e('This e-mail will be sent to a new user upon admin approval when "Admin Approval" is checked for "User Moderation".', 'themed-login'); ?>
			<?php _e('Please be sure to include the variable %reseturl% or else the user will not be able to recover their password!', 'themed-login'); ?>
			<?php _e('If any field is left empty, the default will be used instead.', 'themed-login'); ?>
		</p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_approval_mail_from_name"><?php _e('From Name', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[user_approval][mail_from_name]" type="text" id="<?php echo $this->options_key; ?>_user_approval_mail_from_name" value="<?php echo $this->get_option(['user_approval', 'mail_from_name']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_approval_mail_from"><?php _e('From E-mail', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[user_approval][mail_from]" type="text" id="<?php echo $this->options_key; ?>_user_approval_mail_from" value="<?php echo $this->get_option(['user_approval', 'mail_from']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_approval_mail_content_type"><?php _e('E-mail Format', 'themed-login'); ?></label></th>
				<td>
					<select name="<?php echo $this->options_key; ?>[user_approval][mail_content_type]" id="<?php echo $this->options_key; ?>_user_approval_mail_content_type">
						<option value="plain"<?php selected($this->get_option(['user_approval', 'mail_content_type']), 'plain'); ?>><?php _e('Plain Text', 'themed-login'); ?></option>
						<option value="html"<?php selected($this->get_option(['user_approval', 'mail_content_type']), 'html'); ?>><?php _e('HTML', 'themed-login'); ?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_approval_title"><?php _e('Subject', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[user_approval][title]" type="text" id="<?php echo $this->options_key; ?>_user_approval_title" value="<?php echo $this->get_option(['user_approval', 'title']); ?>" class="large-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_approval_message"><?php _e('Message', 'themed-login'); ?></label></th>
				<td>
					<p class="description"><?php _e('Available Variables', 'themed-login'); ?>: %blogname%, %siteurl%, %reseturl%, %loginurl%, %user_login%, %user_email%</p>
					<textarea name="<?php echo $this->options_key; ?>[user_approval][message]" id="<?php echo $this->options_key; ?>_user_approval_message" class="large-text" rows="10"><?php echo $this->get_option(['user_approval', 'message']); ?></textarea></td>
				</td>
			</tr>
		</table>
		<?php
		}

		/**
		 * Renders User Approval Admin settings section
		 *
		 * This is the callback for add_settings_field()
		 *
		 * @access public
		 */
		public function user_approval_admin_meta_box() {
			?>
		<p class="description">
			<?php _e('This e-mail will be sent to the e-mail address or addresses (multiple addresses may be separated by commas) specified below upon user registration when "Admin Approval" is checked for "User Moderation".', 'themed-login'); ?>
			<?php _e('If any field is left empty, the default will be used instead.', 'themed-login'); ?>
		</p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_approval_admin_mail_to"><?php _e('To', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[user_approval][admin_mail_to]" type="text" id="<?php echo $this->options_key; ?>_user_approval_admin_mail_to" value="<?php echo $this->get_option(['user_approval', 'admin_mail_to']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_approval_admin_mail_from_name"><?php _e('From Name', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[user_approval][admin_mail_from_name]" type="text" id="<?php echo $this->options_key; ?>_user_approval_admin_mail_from_name" value="<?php echo $this->get_option(['user_approval', 'admin_mail_from_name']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_approval_admin_mail_from"><?php _e('From E-mail', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[user_approval][admin_mail_from]" type="text" id="<?php echo $this->options_key; ?>_user_approval_admin_mail_from" value="<?php echo $this->get_option(['user_approval', 'admin_mail_from']); ?>" class="regular-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_approval_admin_mail_content_type"><?php _e('E-mail Format', 'themed-login'); ?></label></th>
				<td>
					<select name="<?php echo $this->options_key; ?>[user_approval][admin_mail_content_type]" id="<?php echo $this->options_key; ?>_user_approval_admin_mail_content_type">
						<option value="plain"<?php selected($this->get_option(['user_approval', 'admin_mail_content_type']), 'plain'); ?>><?php _e('Plain Text', 'themed-login'); ?></option>
						<option value="html"<?php selected($this->get_option(['user_approval', 'admin_mail_content_type']), 'html'); ?>><?php _e('HTML', 'themed-login'); ?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_approval_admin_title"><?php _e('Subject', 'themed-login'); ?></label></th>
				<td><input name="<?php echo $this->options_key; ?>[user_approval][admin_title]" type="text" id="<?php echo $this->options_key; ?>_user_approval_admin_title" value="<?php echo $this->get_option(['user_approval', 'admin_title']); ?>" class="large-text"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="<?php echo $this->options_key; ?>_user_approval_admin_message"><?php _e('Message', 'themed-login'); ?></label></th>
				<td>
					<p class="description"><?php _e('Available Variables', 'themed-login'); ?>: %blogname%, %siteurl%, %pendingurl%, %user_login%, %user_email%, %user_ip%</p>
					<textarea name="<?php echo $this->options_key; ?>[user_approval][admin_message]" id="<?php echo $this->options_key; ?>_user_approval_admin_message" class="large-text" rows="10"><?php echo $this->get_option(['user_approval', 'admin_message']); ?></textarea></td>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">&nbsp;</th>
				<td>
					<input name="<?php echo $this->options_key; ?>[user_approval][admin_disable]" type="checkbox" id="<?php echo $this->options_key; ?>_user_approval_admin_disable" value="1"<?php checked(1, $this->get_option(['user_approval', 'admin_disable'])); ?>>
					<label for="<?php echo $this->options_key; ?>_user_approval_admin_disable"><?php _e('Disable Admin Notification', 'themed-login'); ?></label>
				</td>
			</tr>
		</table>
		<?php
		}

		/**
		 * Renders User Denial settings section
		 *
		 * This is the callback for add_settings_field()
		 *
		 * @access public
		 */
		public function user_denial_meta_box() {
			?>
			<p class="description">
				<?php _e('This e-mail will be sent to a user who is deleted/denied when "Admin Approval" is checked for "User Moderation" and the user\'s role is "Pending".', 'themed-login'); ?>
				<?php _e('If any field is left empty, the default will be used instead.', 'themed-login'); ?>
			</p>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->options_key; ?>_user_denial_mail_from_name"><?php _e('From Name', 'themed-login'); ?></label></th>
					<td><input name="<?php echo $this->options_key; ?>[user_denial][mail_from_name]" type="text" id="<?php echo $this->options_key; ?>_user_denial_mail_from_name" value="<?php echo $this->get_option(['user_denial', 'mail_from_name']); ?>" class="regular-text"></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->options_key; ?>_user_denial_mail_from"><?php _e('From E-mail', 'themed-login'); ?></label></th>
					<td><input name="<?php echo $this->options_key; ?>[user_denial][mail_from]" type="text" id="<?php echo $this->options_key; ?>_user_denial_mail_from" value="<?php echo $this->get_option(['user_denial', 'mail_from']); ?>" class="regular-text"></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->options_key; ?>_user_denial_mail_content_type"><?php _e('E-mail Format', 'themed-login'); ?></label></th>
					<td>
						<select name="<?php echo $this->options_key; ?>[user_denial][mail_content_type]" id="<?php echo $this->options_key; ?>_user_denial_mail_content_type">
							<option value="plain"<?php selected($this->get_option(['user_denial', 'mail_content_type']), 'plain'); ?>><?php _e('Plain Text', 'themed-login'); ?></option>
							<option value="html"<?php selected($this->get_option(['user_denial', 'mail_content_type']), 'html'); ?>><?php _e('HTML', 'themed-login'); ?></option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->options_key; ?>_user_denial_title"><?php _e('Subject', 'themed-login'); ?></label></th>
					<td><input name="<?php echo $this->options_key; ?>[user_denial][title]" type="text" id="<?php echo $this->options_key; ?>_user_denial_title" value="<?php echo $this->get_option(['user_denial', 'title']); ?>" class="large-text"></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->options_key; ?>_user_denial_message"><?php _e('Message', 'themed-login'); ?></label></th>
					<td>
						<p class="description"><?php _e('Available Variables', 'themed-login'); ?>: %blogname%, %siteurl%, %user_login%, %user_email%</p>
						<textarea name="<?php echo $this->options_key; ?>[user_denial][message]" id="<?php echo $this->options_key; ?>_user_denial_message" class="large-text" rows="10"><?php echo $this->get_option(['user_denial', 'message']); ?></textarea>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">&nbsp;</th>
					<td>
						<input name="<?php echo $this->options_key; ?>[user_denial][disable]" type="checkbox" id="<?php echo $this->options_key; ?>_user_denial_disable" value="1"<?php checked(1, $this->get_option(['user_denial', 'disable'])); ?>>
						<label for="<?php echo $this->options_key; ?>_user_denial_disable"><?php _e('Disable Notification', 'themed-login'); ?></label>
					</td>
				</tr>
			</table>
		<?php
		}

		/**
		 * Sanitizes settings
		 *
		 * Callback for register_setting()
		 *
		 * @access public
		 *
		 * @param array|string $settings Settings passed in from filter
		 * @return array|string Sanitized settings
		 */
		public function save_settings($settings) {
			$settings['new_user']['admin_disable'] = isset($settings['new_user']['admin_disable']) ? (bool) $settings['new_user']['admin_disable'] : false;
			$settings['reset_pass']['admin_disable'] = isset($settings['reset_pass']['admin_disable']) ? (bool) $settings['reset_pass']['admin_disable'] : false;

			if (class_exists('Theme_My_Login_User_Moderation')) {
				$settings['user_approval']['admin_disable'] = isset($settings['user_approval']['admin_disable']) ? (bool) $settings['user_approval']['admin_disable'] : false;
				$settings['user_denial']['disable'] = isset($settings['user_denial']['disable']) ? (bool) $settings['user_denial']['disable'] : false;
			}

			return Theme_My_Login_Common::array_merge_recursive($this->get_options(), $settings);
		}

		/**
		 * Loads the module
		 *
		 * Called by Theme_My_Login_Abstract::__construct()
		 *
		 * @see Theme_My_Login_Abstract::__construct()
		 */
		protected function load() {
			add_action('tml_uninstall_custom-email/custom-email.php', [$this, 'uninstall']);

			add_action('admin_menu', [$this, 'admin_menu']);
			add_action('admin_init', [$this, 'admin_init']);

			add_action('load-tml_page_theme_my_login_email', [$this, 'load_settings_page']);
		}
	}

	new Theme_My_Login_Custom_Email_Admin();

}
