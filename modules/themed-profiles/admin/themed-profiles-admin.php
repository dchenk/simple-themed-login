<?php
/**
 * Holds Themed Login Themed Profiles Admin class
 *
 * @package ThemedLogin
 */

if (!class_exists('ThemedLogin_Themed_Profiles_Admin')) {

	/**
	 * Themed Login Themed Profiles Admin class
	 */
	class ThemedLogin_Themed_Profiles_Admin extends ThemedLogin_Abstract {
		/**
		 * Holds options key
		 *
		 * @access protected
		 * @var string
		 */
		protected $options_key = 'theme_my_login_themed_profiles';

		/**
		 * Returns default options
		 *
		 * @access public
		 */
		public static function default_options(): array {
			return ThemedLogin_Themed_Profiles::default_options();
		}

		/**
		 * Activates the module
		 *
		 * Callback for "tml_activate_themed-profiles/themed-profiles.php" hook in method ThemedLogin_Modules_Admin::activate_module()
		 *
		 * @see ThemedLogin_Modules_Admin::activate_module()
		 * @access public
		 */
		public function activate() {
			if (!$page_id = ThemedLogin::get_page_id('profile')) {
				$page_id = wp_insert_post([
					'post_title' => __('Your Profile', 'themed-login'),
					'post_status' => 'publish',
					'post_type' => 'page',
					'post_content' => '[theme-my-login]',
					'comment_status' => 'closed',
					'ping_status' => 'closed',
				]);
				update_post_meta($page_id, '_tml_action', 'profile');
			}
		}

		/**
		 * Adds "Themed Profiles" tab to Themed Login menu
		 *
		 * Callback for "admin_menu" hook
		 *
		 * @access public
		 */
		public function admin_menu() {
			add_submenu_page(
				'themed_login',
				__('Themed Profiles Settings', 'themed-login'),
				__('Themed Profiles', 'themed-login'),
				'manage_options',
				$this->options_key,
				[$this, 'settings_page']
			);

			add_settings_section('general', null, '__return_false', $this->options_key);

			add_settings_field('themed_profiles', __('Themed Profiles', 'themed-login'), [$this, 'settings_field_themed_profiles'], $this->options_key, 'general');
			add_settings_field('restrict_admin', __('Restrict Admin Access', 'themed-login'), [$this, 'settings_field_restrict_admin_access'], $this->options_key, 'general');
		}

		/**
		 * Registers options group
		 *
		 * Callback for "admin_init" hook
		 *
		 * @access public
		 */
		public function admin_init() {
			register_setting($this->options_key, $this->options_key, [$this, 'save_settings']);
		}

		/**
		 * Renders settings page
		 *
		 * Callback for add_submenu_page()
		 *
		 * @access public
		 */
		public function settings_page() {
			ThemedLogin_Admin::settings_page([
				'title' => __('Themed Profiles Settings', 'themed-login'),
				'options_key' => $this->options_key,
			]);
		}

		/**
		 * Renders Themed Profiles settings field
		 *
		 * @access public
		 */
		public function settings_field_themed_profiles() {
			global $wp_roles;

			foreach ($wp_roles->get_names() as $role => $role_name) {
				if ('pending' == $role) {
					continue;
				} ?>
				<input name="<?php echo $this->options_key; ?>[<?php echo $role; ?>][theme_profile]" type="checkbox"
					id="<?php echo $this->options_key; ?>_<?php echo $role; ?>_theme_profile"
					value="1"<?php checked($this->get_option([$role, 'theme_profile'])); ?>>
				<label
					for="<?php echo $this->options_key; ?>_<?php echo $role; ?>_theme_profile"><?php echo $role_name; ?></label>
				<br>
				<?php
			}
		}

		/**
		 * Renders Restrict Admin Access settings field
		 *
		 * @access public
		 */
		public function settings_field_restrict_admin_access() {
			global $wp_roles;

			foreach ($wp_roles->get_names() as $role => $role_name) {
				if ('pending' != $role) {
					?>
					<input name="<?php echo $this->options_key; ?>[<?php echo $role; ?>][restrict_admin]"
					type="checkbox" id="<?php echo $this->options_key; ?>_<?php echo $role; ?>_restrict_admin"
					value="1"<?php
					checked($this->get_option([$role, 'restrict_admin']));
					if ('administrator' == $role) {
						echo ' disabled="disabled"';
					} ?>><?php
				} ?>
				<label for="<?php echo $this->options_key; ?>_<?php echo $role; ?>_restrict_admin"><?php echo $role_name; ?></label>
				<br>
				<?php
			}
		}

		/**
		 * Sanitizes settings
		 *
		 * Callback for register_setting()
		 *
		 * @access public
		 *
		 * @param array $settings Settings passed in from filter
		 * @return array Sanitized settings
		 */
		public function save_settings($settings) {
			global $wp_roles;

			foreach ($wp_roles->get_names() as $role => $role_name) {
				if ('pending' != $role) {
					$settings[$role] = [
						'theme_profile' => !empty($settings[$role]['theme_profile']),
						'restrict_admin' => !empty($settings[$role]['restrict_admin']),
					];
				}
			}
			return $settings;
		}

		/**
		 * Uninstalls the module
		 *
		 * Callback for "tml_uninstall_themed-profiles/themed-profiles.php" hook in method ThemedLogin_Admin::uninstall()
		 *
		 * @see ThemedLogin_Admin::uninstall()
		 * @access public
		 */
		public function uninstall() {
			delete_option($this->options_key);
		}

		/**
		 * Loads the module
		 *
		 * @access protected
		 */
		protected function load() {
			add_action('tml_activate_themed-profiles/themed-profiles.php', [$this, 'activate']);
			add_action('tml_uninstall_themed-profiles/themed-profiles.php', [$this, 'uninstall']);

			add_action('admin_menu', [$this, 'admin_menu']);
			add_action('admin_init', [$this, 'admin_init']);
		}
	}

	new ThemedLogin_Themed_Profiles_Admin();

}
