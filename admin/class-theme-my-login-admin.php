<?php
/**
 * Holds the Theme My Login Admin class
 *
 * @package Theme_My_Login
 */

if (!class_exists('Theme_My_Login_Admin')) {

	/**
	 * Theme My Login Admin class
	 */
	class Theme_My_Login_Admin extends Theme_My_Login_Abstract {
		/**
		 * Holds options key
		 *
		 * @var string
		 */
		protected $options_key = 'theme_my_login';

		/**
		 * Returns default options
		 */
		public static function default_options() {
			return Theme_My_Login::default_options();
		}

		/**
		 * Builds plugin admin menu and pages
		 */
		public function admin_menu() {
			add_menu_page(
				__('Simple Themed Login Settings', 'simple-themed-login'),
				'STL',
				'manage_options',
				'theme_my_login',
				[$this, 'settings_page']
			);
		}

		/**
		 * Registers settings
		 * This is used because register_setting() isn't available until the "admin_init" hook.
		 */
		public function admin_init() {
			register_setting('theme_my_login', 'theme_my_login', [$this, 'save_settings']);

			// Install with default settings
			if (version_compare($this->get_option('version', '0'), Theme_My_Login::VERSION, '<')) {
				$this->install();
			}

			// Add sections
			add_settings_section('general', __('General', 'simple-themed-login'), '__return_false', $this->options_key);
			add_settings_section('modules', __('Modules', 'simple-themed-login'), '__return_false', $this->options_key);

			// Add fields
			add_settings_field('enable_css', __('Stylesheet', 'simple-themed-login'), [$this, 'settings_field_enable_css'], $this->options_key, 'general');
			add_settings_field('login_type', __('Login Type', 'simple-themed-login'), [$this, 'settings_field_login_type'], $this->options_key, 'general');
			add_settings_field('modules', __('Modules', 'simple-themed-login'), [$this, 'settings_field_modules'], $this->options_key, 'modules');
		}

		/**
		 * Enqueues scripts
		 */
		public function admin_enqueue_scripts() {
			wp_enqueue_script('theme-my-login-admin', plugins_url('theme-my-login-admin.js', __FILE__), ['jquery'], Theme_My_Login::VERSION, true);
			wp_localize_script('theme-my-login-admin', 'tmlAdmin', [
				'interim_login_url' => site_url('wp-login.php?interim-login=1', 'login'),
			]);
		}

		/**
		 * Print admin notices.
		 */
		public function admin_notices() {
			if (!current_user_can('manage_options')) {
				return;
			}
			// Potentially useful function stub here.
		}

		/**
		 * Handle saving of notice dismissals.
		 */
		public function ajax_dismiss_notice() {
			if (empty($_POST['notice'])) {
				return;
			}

			$dismissed_notices = $this->get_option('dismissed_notices', []);
			$dismissed_notices[] = sanitize_key($_POST['notice']);

			$this->set_option('dismissed_notices', $dismissed_notices);
			$this->save_options();

			wp_send_json_success();
		}

		/**
		 * Adds the Action meta box.
		 */
		public function add_meta_boxes() {
			add_meta_box('tml_action', __('Login Action', 'simple-themed-login'), [$this, 'action_meta_box'], 'page', 'side');
		}

		/**
		 * Renders the Action meta box.
		 *
		 * @param WP_Post $post object
		 */
		public function action_meta_box($post) {
			$page_action = get_post_meta($post->ID, '_tml_action', true); ?>
			<select name="tml_action" id="tml_action">
				<option value=""></option>
				<?php
				foreach (Theme_My_Login::default_pages() as $action => $label) {
					?>
					<option value="<?php echo esc_attr($action); ?>"<?php selected($action, $page_action); ?>><?php echo esc_html($label); ?></option>
					<?php
				} ?>
			</select>
			<?php
		}

		/**
		 * Saves the Action meta box.
		 *
		 * @param int $post_id The post ID.
		 */
		public function save_action_meta_box($post_id) {
			if ('page' != get_post_type($post_id)) {
				return;
			}

			if (isset($_POST['tml_action'])) {
				$tml_action = sanitize_key($_POST['tml_action']);
				if (!empty($_POST['tml_action'])) {
					update_post_meta($post_id, '_tml_action', $tml_action);
				} else {
					if (false !== get_post_meta($post_id, '_tml_action', true)) {
						delete_post_meta($post_id, '_tml_action');
					}
				}
			}
		}

		/**
		 * Renders the settings page
		 */
		public static function settings_page($args = '') {
			$args = wp_parse_args($args, [
				'title' => 'Simple Themed Login Settings',
				'options_key' => 'theme_my_login',
			]); ?>
			<div id="<?php echo 'theme_my_login'; ?>" class="wrap">
				<h2><?php echo esc_html($args['title']); ?></h2>
				<?php settings_errors(); ?>
				<form method="post" action="options.php">
					<?php
			settings_fields($args['options_key']);
			do_settings_sections($args['options_key']);
			submit_button(); ?>
				</form>
			</div>
			<?php
		}

		/**
		 * Renders Stylesheet settings field
		 */
		public function settings_field_enable_css() {
			?>
			<input name="theme_my_login[enable_css]" type="checkbox" id="theme_my_login_enable_css"
				value="1"<?php checked(1, $this->get_option('enable_css')); ?>>
			<label
				for="theme_my_login_enable_css"><?php _e('Enable "theme-my-login.css"', 'simple-themed-login'); ?></label>
			<p class="description"><?php _e('In order to keep changes between upgrades, you can store your customized "theme-my-login.css" in your current theme directory.', 'simple-themed-login'); ?></p>
			<?php
		}

		/**
		 * Renders Login Type settings field
		 */
		public function settings_field_login_type() {
			?>
			<ul>
				<li>
					<input name="theme_my_login[login_type]" type="radio" id="theme_my_login_login_type_default"
						value="default"<?php checked('default', $this->get_option('login_type')); ?>>
					<label for="theme_my_login_login_type_default"><?php _e('Username or E-mail', 'simple-themed-login'); ?></label>
				</li>
				<li>
					<input name="theme_my_login[login_type]" type="radio" id="theme_my_login_login_type_username"
						value="username"<?php checked('username', $this->get_option('login_type')); ?>>
					<label for="theme_my_login_login_type_username"><?php _e('Username only', 'simple-themed-login'); ?></label>
				</li>
				<li>
					<input name="theme_my_login[login_type]" type="radio" id="theme_my_login_login_type_email"
						value="email"<?php checked('email', $this->get_option('login_type')); ?>>
					<label for="theme_my_login_login_type_email"><?php _e('E-mail only', 'simple-themed-login'); ?></label>
				</li>
			</ul>

			<p class="description"><?php _e('Allow users to login using their username and/or e-mail address.', 'simple-themed-login'); ?></p>
			<?php
		}

		/**
		 * Renders Modules settings field
		 */
		public function settings_field_modules() {
			$modules = get_plugins(sprintf('/%s/modules', plugin_basename(THEMED_LOGIN_DIR)));
			foreach ($modules as $path => $data) {
				$id = sanitize_key($data['Name']); ?>
				<input name="theme_my_login[active_modules][]" type="checkbox"
					id="theme_my_login_active_modules_<?php echo $id; ?>"
					value="<?php echo $path; ?>"<?php checked(in_array($path, (array) $this->get_option('active_modules'), true)); ?>>
				<label
					for="theme_my_login_active_modules_<?php echo $id; ?>"><?php printf(__('Enable %s', 'simple-themed-login'), $data['Name']); ?></label>
				<br>
				<?php if ($data['Description']) {
					?>
					<p class="description"><?php echo $data['Description']; ?></p>
					<?php
				}
			}
		}

		/**
		 * Sanitizes settings
		 *
		 * This is the callback for register_setting()
		 *
		 * @param array|string $settings Settings passed in from filter
		 * @return array|string Sanitized settings
		 */
		public function save_settings($settings) {
			$settings['enable_css'] = !empty($settings['enable_css']);
			$settings['login_type'] = in_array($settings['login_type'], ['default', 'username', 'email'], true) ? $settings['login_type'] : 'default';
			$settings['active_modules'] = isset($settings['active_modules']) ? (array) $settings['active_modules'] : [];

			// If we have modules to activate
			if ($activate = array_diff($settings['active_modules'], $this->get_option('active_modules', []))) {
				foreach ($activate as $module) {
					$fp = THEMED_LOGIN_DIR . '/modules/' . $module;
					if (file_exists($fp)) {
						include_once($fp);
					}
					do_action('tml_activate_' . $module);
				}
			}

			// If we have modules to deactivate
			if ($deactivate = array_diff($this->get_option('active_modules', []), $settings['active_modules'])) {
				foreach ($deactivate as $module) {
					do_action('tml_deactivate_' . $module);
				}
			}

			$settings = wp_parse_args($settings, $this->get_options());

			return $settings;
		}

		public function install() {
			// Setup default pages.
			foreach (Theme_My_Login::default_pages() as $action => $title) {
				if (!Theme_My_Login::get_page_id($action)) {
					$page_id = wp_insert_post([
						'post_title' => $title,
						'post_name' => $action,
						'post_status' => 'publish',
						'post_type' => 'page',
						'post_content' => '[theme-my-login]',
						'comment_status' => 'closed',
						'ping_status' => 'closed',
					]);
					update_post_meta($page_id, '_tml_action', $action);
				}
			}

			// Activate modules
			foreach ($this->get_option('active_modules', []) as $module) {
				$fp = THEMED_LOGIN_DIR . '/modules/' . $module;
				if (file_exists($fp)) {
					include_once($fp);
				}
				do_action('tml_activate_' . $module);
			}

			$this->set_option('version', Theme_My_Login::VERSION);
			$this->save_options();
		}

		/**
		 * Wrapper for multisite uninstallation
		 */
		public static function uninstall() {
			global $wpdb;
			if (is_multisite()) {
				if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)) {
					$blogids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
					foreach ($blogids as $blog_id) {
						switch_to_blog($blog_id);
						self::_uninstall();
					}
					restore_current_blog();
					return;
				}
			}
			self::_uninstall();
		}

		/**
		 * Loads object
		 */
		protected function load() {
			add_action('admin_init', [$this, 'admin_init']);
			add_action('admin_menu', [$this, 'admin_menu'], 8);
			add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'], 11);

			add_action('admin_notices', [$this, 'admin_notices']);
			add_action('wp_ajax_tml-dismiss-notice', [$this, 'ajax_dismiss_notice']);

			add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
			add_action('save_post', [$this, 'save_action_meta_box']);

			register_uninstall_hook(THEMED_LOGIN_DIR . '/theme-my-login.php', [$this, 'uninstall']);
		}

		protected static function _uninstall() {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');

			// Run module uninstall hooks
			$modules = get_plugins(sprintf('/%s/modules', plugin_basename(THEMED_LOGIN_DIR)));
			foreach (array_keys($modules) as $module) {
				$module = plugin_basename(trim($module));

				$filePath = THEMED_LOGIN_DIR . '/modules/' . $module;
				if (file_exists($filePath)) {
					@include_once($filePath);
				}

				do_action('tml_uninstall_' . $module);
			}

			// Get pages
			$pages = get_posts([
				'post_type' => 'page',
				'post_status' => 'any',
				'meta_key' => '_tml_action',
				'posts_per_page' => -1,
			]);

			// Delete pages
			foreach ($pages as $page) {
				wp_delete_post($page->ID);
			}

			// Delete options
			delete_option('theme_my_login');
			delete_option('widget_theme-my-login');
		}
	}

}
