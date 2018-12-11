<?php
/**
 * Plugin Name: Custom Passwords
 * Description: This module lets users create their own password when registering. There are no other settings for this module.
 *
 *
 * @package ThemedLogin
 * @subpackage ThemedLogin_Custom_Passwords
 */

if (!class_exists('ThemedLogin_Custom_Passwords')) {

	/**
	 * Themed Login Custom Passwords module class
	 */
	class ThemedLogin_Custom_Passwords extends ThemedLogin_Abstract {

		/**
		 * Outputs password fields to registration form
		 *
		 * Callback for "password_fields" hook in file "register-form.php", included by ThemedLogin_Template::display()
		 *
		 * @see ThemedLogin::display()
		 * @access public
		 */
		public function password_fields() {
			global $themedLoginInstance;
			$template = $themedLoginInstance->current_instance; ?>
			<p class="tml-user-pass1-wrap">
				<label for="pass1<?php $template->the_instance(); ?>"><?php _e('Password', 'themed-login'); ?></label>
				<input autocomplete="off" name="pass1" id="pass1<?php $template->the_instance(); ?>" class="input" size="20" type="password">
			</p>
			<p class="tml-user-pass2-wrap">
				<label for="pass2<?php $template->the_instance(); ?>"><?php _e('Confirm Password', 'themed-login'); ?></label>
				<input autocomplete="off" name="pass2" id="pass2<?php $template->the_instance(); ?>" class="input" size="20" type="password">
			</p>
			<?php
		}

		/**
		 * Handles password errors for registration form
		 *
		 * Callback for "registration_errors" hook in ThemedLogin::register_new_user()
		 *
		 * @see ThemedLogin::register_new_user()
		 * @access public
		 *
		 * @param bool|WP_Error $errors WP_Error object
		 * @return WP_Error object
		 */
		public function password_errors($errors) {
			// Make sure $errors is a WP_Error object
			if (!$errors) {
				$errors = new WP_Error();
			}

			// Make sure passwords aren't empty
			if (empty($_POST['pass1']) || empty($_POST['pass2'])) {
				$errors->add('empty_password', __('<strong>ERROR</strong>: Please enter your password twice.', 'themed-login'));

			// Make sure there's no "\" in the password
			} elseif (false !== strpos(stripslashes($_POST['pass1']), "\\")) {
				$errors->add('password_backslash', __('<strong>ERROR</strong>: Passwords may not contain the character "\\".', 'themed-login'));

			// Make sure passwords match
			} elseif ($_POST['pass1'] != $_POST['pass2']) {
				$errors->add('password_mismatch', __('<strong>ERROR</strong>: Please enter the same password in the two password fields.', 'themed-login'));

			// Make sure password is long enough
			} elseif (strlen($_POST['pass1']) < apply_filters('tml_minimum_password_length', 6)) {
				$errors->add('password_length', sprintf(__('<strong>ERROR</strong>: Your password must be at least %d characters in length.', 'themed-login'), apply_filters('tml_minimum_password_length', 6)));

			// All is good, assign password to a friendlier key
			} else {
				$_POST['user_pass'] = $_POST['pass1'];
			}

			return $errors;
		}

		/**
		 * Handles password errors for multisite signup form
		 *
		 * Callback for "registration_errors" hook in ThemedLogin::register_new_user()
		 *
		 * @see ThemedLogin::register_new_user()
		 * @access public
		 *
		 * @param array $result
		 *
		 * @return array object
		 */
		public function ms_password_errors($result) {
			if (isset($_POST['stage']) && 'validate-user-signup' == $_POST['stage']) {
				$errors = $this->password_errors($result['errors'] ?? false);
				foreach ($errors->get_error_codes() as $code) {
					foreach ($errors->get_error_messages($code) as $error) {
						$result['errors']->add($code, preg_replace('/<strong>([^<]+)<\/strong>: /', '', $error));
					}
				}
			}
			return $result;
		}

		/**
		 * Adds password to signup meta array
		 *
		 * Callback for "add_signup_meta" hook
		 *
		 * @access public
		 *
		 * @param array $meta Signup meta
		 * @return array $meta Signup meta
		 */
		public function ms_save_password($meta) {
			if (isset($_POST['user_pass'])) {
				$meta['user_pass'] = $_POST['user_pass'];
			}
			return $meta;
		}

		/**
		 * Sets the user password
		 *
		 * Callback for "random_password" hook in wp_generate_password()
		 *
		 * @see wp_generate_password()
		 * @access public
		 *
		 * @param string $password Auto-generated password passed in from filter
		 * @return string Password chosen by user
		 */
		public function set_password($password) {
			global $wpdb;

			// Remove filter as not to filter User Moderation activation key
			remove_filter('random_password', [$this, 'set_password']);

			if (is_multisite() && isset($_REQUEST['key'])) {
				$meta = $wpdb->get_var($wpdb->prepare("SELECT meta FROM {$wpdb->signups} WHERE activation_key = %s", $_REQUEST['key']));
				if ($meta) {
					$meta = unserialize($meta);
					if (isset($meta['user_pass'])) {
						$password = $meta['user_pass'];
						unset($meta['user_pass']);
						$wpdb->update($wpdb->signups, ['meta' => serialize($meta)], ['activation_key' => $_REQUEST['key']]);
					}
				}
			} else {
				// Make sure password isn't empty
				if (!empty($_POST['user_pass'])) {
					$password = $_POST['user_pass'];
				}
			}
			return $password;
		}

		/**
		 * Removes the default password nag
		 *
		 * Callback for "register_new_user" hook in register_new_user()
		 *
		 * @see register_new_user()
		 * @access public
		 *
		 * @param int $user_id The user's ID
		 */
		public function remove_default_password_nag($user_id) {
			update_user_meta($user_id, 'default_password_nag', false);
		}

		/**
		 * Changes the register template message
		 *
		 * Callback for "themed_login_register_passmail_template_message" hook
		 *
		 * @access public
		 *
		 * @return string The new register message
		 */
		public function register_passmail_template_message() {
			// Removes "A password will be emailed to you." from register form
			return '';
		}

		/**
		 * Handles display of various action/status messages
		 *
		 * Callback for "tml_request" hook in ThemedLogin::the_request()
		 *
		 * @access public
		 *
		 * @param object $themedLogin Reference to global ThemedLogin object
		 */
		public function action_messages(&$themedLogin) {
			// Change "Registration complete. Please check your e-mail." to reflect the fact that they already set a password
			if (isset($_GET['registration']) && 'complete' == $_GET['registration']) {
				$themedLogin->errors->add('registration_complete', __('Registration complete. You may now log in.', 'themed-login'), 'message');
			}
		}

		/**
		 * Changes where the user is redirected upon successful registration
		 *
		 * Callback for "registration_redirect" hook in ThemedLogin_Template::get_redirect_url()
		 *
		 * @see ThemedLogin_Template::get_redirect_url()
		 *
		 * @return string $redirect_to Default redirect
		 * @return string URL to redirect to
		 */
		public function registration_redirect($redirect_to) {
			// Redirect to login page with "registration=complete" added to the query
			$redirect_to = site_url('wp-login.php?registration=complete');
			// Add instance to the query if specified
			if (!empty($_REQUEST['instance'])) {
				$redirect_to = add_query_arg('instance', $_REQUEST['instance'], $redirect_to);
			}
			return $redirect_to;
		}

		/**
		 * Loads the module
		 *
		 * @access protected
		 */
		protected function load() {
			add_action('register_form', [$this, 'password_fields']);
			add_filter('registration_errors', [$this, 'password_errors']);
			add_filter('random_password', [$this, 'set_password']);

			add_filter('wpmu_validate_user_signup', [$this, 'ms_password_errors']);
			add_filter('add_signup_meta', [$this, 'ms_save_password']);

			add_action('register_new_user', [$this, 'remove_default_password_nag']);
			add_action('approve_user', [$this, 'remove_default_password_nag']);

			add_filter('themed_login_register_passmail_template_message', [$this, 'register_passmail_template_message']);
			add_action('themed_login_request', [$this, 'action_messages']);

			add_filter('registration_redirect', [$this, 'registration_redirect']);
		}

	}

	new ThemedLogin_Custom_Passwords();

}
