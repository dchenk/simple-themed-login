<?php
/**
 * Holds Themed Login Security Admin class
 *
 * @package ThemedLogin
 */

if (!class_exists('ThemedLogin_Security_Admin')) {

	/**
	 * Themed Login Security Admin class
	 */
	class ThemedLogin_Security_Admin extends ThemedLogin_Abstract {
		/**
		 * Holds options key
		 *
		 * @var string
		 */
		protected $options_key = 'theme_my_login_security';

		/**
		 * Returns default options
		 *
		 * @return array
		 */
		public static function default_options(): array {
			return ThemedLogin_Security::default_options();
		}

		/**
		 * Uninstalls the module
		 *
		 * Callback for "tml_uninstall_security/security.php" hook in method ThemedLogin_Admin::uninstall()
		 *
		 * @see ThemedLogin_Admin::uninstall()
		 */
		public function uninstall() {
			delete_option($this->options_key);
		}

		/**
		 * Adds "Security" tab to Themed Login menu
		 *
		 * Callback for "admin_menu" hook
		 */
		public function admin_menu() {
//			error_log('SECURITY ADMIN admin_menu');

			add_submenu_page(
				'theme_my_login',
				__('Login Security Settings', 'themed-login'),
				__('Security', 'themed-login'),
				'manage_options',
				$this->options_key,
				[$this, 'settings_page']
			);

			add_settings_section('general', null, '__return_false', $this->options_key);

//			error_log("Options key (security-admin): {$this->options_key}");

			add_settings_field('private_site', __('Private Site', 'themed-login'), [$this, 'settings_field_private_site'], $this->options_key, 'general');
			add_settings_field('private_login', __('Private Login', 'themed-login'), [$this, 'settings_field_private_login'], $this->options_key, 'general');
			add_settings_field('login_attempts', __('Login Attempts', 'themed-login'), [$this, 'settings_field_login_attempts'], $this->options_key, 'general');
		}

		/**
		 * Registers options group
		 */
		public function admin_init() {
			register_setting($this->options_key, $this->options_key, [$this, 'save_settings']);
		}

		/**
		 * Renders settings page
		 */
		public function settings_page() {
			error_log("Options key, settings_page (security-admin): {$this->options_key}");
			ThemedLogin_Admin::settings_page([
				'title' => __('Login Security Settings', 'themed-login'),
				'options_key' => $this->options_key,
			]);
		}

		/**
		 * Renders Private Site settings field
		 */
		public function settings_field_private_site() {
			?>
			<input name="<?php echo $this->options_key; ?>[private_site]" type="checkbox"
				id="<?php echo $this->options_key; ?>_private_site"
				value="1"<?php checked($this->get_option('private_site')); ?>>
			<label for="<?php echo $this->options_key; ?>_private_site"><?php _e('Require users to be logged in to view site', 'themed-login'); ?></label>
			<?php
		}

		/**
		 * Renders Private Login settings field
		 */
		public function settings_field_private_login() {
			?>
			<input name="<?php echo $this->options_key; ?>[private_login]" type="checkbox"
				id="<?php echo $this->options_key; ?>_private_login"
				value="1"<?php checked($this->get_option('private_login')); ?>>
			<label for="<?php echo $this->options_key; ?>_private_login"><?php _e('Disable <code>wp-login.php</code>', 'themed-login'); ?></label>
			<?php
		}

		/**
		 * Renders Login Attempts settings field
		 */
		public function settings_field_login_attempts() {
			$units = [
				'minute' => __('minutes', 'themed-login'),
				'hour' => __('hours', 'themed-login'),
				'day' => __('days', 'themed-login'),
			];

			// Threshold
			$threshold = '<input type="number" name="' . $this->options_key . '[failed_login][threshold]" id="' . $this->options_key . '_failed_login_threshold" value="' . $this->get_option(['failed_login', 'threshold']) . '">';

			// Threshold duration
			$threshold_duration = '<input type="number" name="' . $this->options_key . '[failed_login][threshold_duration]" id="' . $this->options_key . '_failed_login_threshold_duration" value="' . $this->get_option(['failed_login', 'threshold_duration']) . '">';

			// Threshold duration unit
			$threshold_duration_unit = '<select name="' . $this->options_key . '[failed_login][threshold_duration_unit]" id="' . $this->options_key . '_failed_login_threshold_duration_unit">';
			foreach ($units as $unit => $label) {
				$threshold_duration_unit .= '<option value="' . $unit . '"' . selected($this->get_option(['failed_login', 'threshold_duration_unit']), $unit, false) . '>' . $label . '</option>';
			}
			$threshold_duration_unit .= '</select>';

			// Lockout duration
			$lockout_duration = '<input type="number" name="' . $this->options_key . '[failed_login][lockout_duration]" id="' . $this->options_key . '_failed_login_lockout_duration" value="' . $this->get_option(['failed_login', 'lockout_duration']) . '">';

			// Lockout duration unit
			$lockout_duration_unit = '<select name="' . $this->options_key . '[failed_login][lockout_duration_unit]" id="' . $this->options_key . '_failed_login_lockout_duration_unit">';
			foreach ($units as $unit => $label) {
				$lockout_duration_unit .= '<option value="' . $unit . '"' . selected($this->get_option(['failed_login', 'lockout_duration_unit']), $unit, false) . '>' . $label . '</option>';
			}
			$lockout_duration_unit .= '</select>';

			printf(__('After %1$s failed login attempts within %2$s %3$s, lock the account out for %4$s %5$s.', 'themed-login'), $threshold, $threshold_duration, $threshold_duration_unit, $lockout_duration, $lockout_duration_unit);
		}

		/**
		 * Sanitizes settings
		 *
		 * Callback for "tml_save_settings" hook in method ThemedLogin_Admin::save_settings()
		 *
		 * @see ThemedLogin_Admin::save_settings()
		 *
		 * @param array|string $settings Settings passed in from filter
		 * @return array|string Sanitized settings
		 */
		public function save_settings($settings) {
			return [
				'private_site' => !empty($settings['private_site']),
				'private_login' => !empty($settings['private_login']),
				'failed_login' => [
					'threshold' => absint($settings['failed_login']['threshold']),
					'threshold_duration' => absint($settings['failed_login']['threshold_duration']),
					'threshold_duration_unit' => $settings['failed_login']['threshold_duration_unit'],
					'lockout_duration' => absint($settings['failed_login']['lockout_duration']),
					'lockout_duration_unit' => $settings['failed_login']['lockout_duration_unit'],
				],
			];
		}

		/**
		 * Attaches actions/filters explicitly to "users.php"
		 *
		 * Callback for "load-users.php" hook
		 */
		public function load_users_page() {
			wp_enqueue_script('tml-security-admin', plugins_url('security-admin.js', __FILE__), ['jquery']);

			add_action('admin_notices', [$this, 'admin_notices']);

			if (isset($_GET['action']) && in_array($_GET['action'], ['lock', 'unlock'], true)) {
				$redirect_to = isset($_REQUEST['wp_http_referer']) ? remove_query_arg(['wp_http_referer', 'updated', 'delete_count'], stripslashes($_REQUEST['wp_http_referer'])) : 'users.php';
				$user = $_GET['user'] ?? '';

				if (!$user || !current_user_can('edit_user', $user)) {
					wp_die(__('You can&#8217;t edit that user.', 'themed-login'));
				}

				if (!$user = get_userdata($user)) {
					wp_die(__('You can&#8217;t edit that user.', 'themed-login'));
				}

				if ('lock' == $_GET['action']) {
					check_admin_referer('lock-user_' . $user->ID);
					ThemedLogin_Security::lock_user($user);
					$redirect_to = add_query_arg('update', 'lock', $redirect_to);
				} else {
					if ('unlock' == $_GET['action']) {
						check_admin_referer('unlock-user_' . $user->ID);
						ThemedLogin_Security::unlock_user($user);
						$redirect_to = add_query_arg('update', 'unlock', $redirect_to);
					}
				}

				wp_redirect($redirect_to);
				exit;
			}
		}

		/**
		 * Adds update messages to the admin screen
		 *
		 * Callback for "admin_notices" hook in file admin-header.php
		 */
		public function admin_notices() {
			if (isset($_GET['update'])) {
				echo '<div id="message" class="updated fade"><p>';
				switch ($_GET['update']) {
					case 'lock':
						echo __('User locked.', 'themed-login');
						break;
					case 'unlock':
						echo __('User unlocked.', 'themed-login');
				}
				echo '</p></div>';
			}
		}

		/**
		 * Adds "Lock" and "Unlock" links for each pending user on users.php
		 *
		 * Callback for "user_row_actions" hook in {@internal unknown}
		 *
		 * @param array $actions The user actions
		 * @param WP_User $user_object The current user object
		 * @return array The filtered user actions
		 */
		public function user_row_actions($actions, $user_object) {
			$current_user = wp_get_current_user();

			$security_meta = isset($user_object->theme_my_login_security) ? (array)$user_object->theme_my_login_security : [];

			if ($current_user->ID != $user_object->ID) {
				if (isset($security_meta['is_locked']) && $security_meta['is_locked']) {
					$new_actions['unlock-user'] = '<a href="' .
						add_query_arg(
							'wp_http_referer',
							urlencode(esc_url(stripslashes($_SERVER['REQUEST_URI']))),
							wp_nonce_url("users.php?action=unlock&amp;user={$user_object->ID}", "unlock-user_{$user_object->ID}")
						) . '">' .
						__('Unlock', 'themed-login') .
						'</a>';
				} else {
					$new_actions['lock-user'] = '<a href="' .
						add_query_arg(
							'wp_http_referer',
							urlencode(esc_url(stripslashes($_SERVER['REQUEST_URI']))),
							wp_nonce_url("users.php?action=lock&amp;user={$user_object->ID}", "lock-user_{$user_object->ID}")
						) . '">' .
						__('Lock', 'themed-login') .
						'</a>';
				}
				$actions = array_merge($new_actions, $actions);
			}
			return $actions;
		}

		/**
		 * Loads the module
		 */
		protected function load() {
//			error_log('loading SECURITY_ADMIN  --  ' . $_SERVER['REQUEST_URI']);

			add_action('tml_uninstall_security/security.php', [$this, 'uninstall']);

			add_action('admin_menu', [$this, 'admin_menu']);
			add_action('admin_init', [$this, 'admin_init']);

			add_action('load-users.php', [$this, 'load_users_page']);
			add_filter('user_row_actions', [$this, 'user_row_actions'], 10, 2);
		}
	}

//	ThemedLogin_Security_Admin::get_object();
	new ThemedLogin_Security_Admin();

}
