<?php
/**
 * Plugin Name: Themed Profiles
 * Description: Enable themed profile pages. Configured in the "Themed Profiles" tab.
 *
 * Holds Themed Login Themed Profiles class
 *
 * @package ThemedLogin
 */

if (!class_exists('ThemedLogin_Themed_Profiles')) {

	/**
	 * Themed Login Themed Profiles class
	 *
	 * Allows users to edit profile on the front-end.
	 */
	class ThemedLogin_Themed_Profiles extends ThemedLogin_Abstract {
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
		 *
		 * @return array Default options
		 */
		public static function default_options(): array {
			global $wp_roles;

			if (empty($wp_roles)) {
				$wp_roles = new WP_Roles;
			}

			$options = [];
			foreach ($wp_roles->get_names() as $role => $label) {
				if ('pending' != $role) {
					$options[$role] = [
						'theme_profile'  => true,
						'restrict_admin' => false,
					];
				}
			}
			return $options;
		}

		/**
		 * Add the profile page to the default pages.
		 *
		 * @return array The default pages with the profile page added.
		 */
		public function tml_default_pages($pages) {
			$pages['profile'] = __('Profile', 'themed-login');
			return $pages;
		}

		/**
		 * Adds filters to site_url() and admin_url()
		 *
		 * Callback for "tml_modules_loaded" in file "theme-my-login.php"
		 *
		 * @access public
		 */
		public function modules_loaded() {
			add_filter('site_url', [$this, 'site_url'], 10, 3);
			add_filter('admin_url', [$this, 'site_url'], 10, 2);
		}

		/**
		 * Redirects "profile.php" to themed profile page
		 *
		 * Callback for "init" hook
		 *
		 * @access public
		 */
		public function init() {
			global $current_user, $pagenow;

			if (is_user_logged_in() && is_admin()) {
				$redirect_to = ThemedLogin::get_page_link('profile');

				$user_role = reset($current_user->roles);
				if (is_multisite() && empty($user_role)) {
					$user_role = 'subscriber';
				}

				if ('profile.php' == $pagenow && ! isset($_REQUEST['page'])) {
					if ($this->get_option([$user_role, 'theme_profile'])) {
						if (! empty($_GET)) {
							$redirect_to = add_query_arg((array) $_GET, $redirect_to);
						}
						wp_redirect($redirect_to);
						exit;
					}
				} else {
					if ($this->get_option([$user_role, 'restrict_admin'])) {
						if (! defined('DOING_AJAX')) {
							wp_redirect($redirect_to);
							exit;
						}
					}
				}
			}
		}

		/**
		 * Redirects login page to profile if user is logged in
		 *
		 * Callback for "template_redirect" hook
		 *
		 * @access public
		 */
		public function template_redirect() {
			global $themedLoginInstance;
			if (ThemedLogin::is_tml_page()) {
				switch ($themedLoginInstance->request_action) {
				case 'profile':
					// Redirect to login page if not logged in
					if (! is_user_logged_in()) {
						$redirect_to = ThemedLogin::get_page_link('login', 'reauth=1');
						wp_redirect($redirect_to);
						exit;
					}
					break;
				case 'logout':
					// Allow logout action
					break;
				case 'register':
					// Allow register action if multisite
					if (is_multisite()) {
						break;
					}
					// no break
				default:
					// Redirect to profile for any other action if logged in
					if (is_user_logged_in()) {
						$redirect_to = ThemedLogin::get_page_link('profile');
						wp_redirect($redirect_to);
						exit;
					}
				}
			}
		}

		/**
		 * Hides admin bar is admin is restricted
		 *
		 * Callback for "show_admin_bar" hook
		 *
		 * @access public
		 */
		public function show_admin_bar($show_admin_bar) {
			global $current_user;

			$user_role = reset($current_user->roles);
			if (is_multisite() && empty($user_role)) {
				$user_role = 'subscriber';
			}

			if ($this->get_option([$user_role, 'restrict_admin'])) {
				return false;
			}
			return $show_admin_bar;
		}

		/**
		 * Enqueue scripts
		 *
		 * @access public
		 */
		public function wp_enqueue_scripts() {
			wp_enqueue_script('tml-themed-profiles', plugins_url('themed-profiles.js', __FILE__), ['jquery']);
		}

		/**
		 * Add a 'no-js' class to the body
		 *
		 * @access public
		 *
		 * @param array $classes Body classes
		 * @return array Body classes
		 */
		public function body_class($classes) {
			if (!ThemedLogin::is_tml_page('profile')) {
				return $classes;
			}
			if (!in_array('no-js', $classes, true)) {
				$classes[] = 'no-js';
			}
			return $classes;
		}

		/**
		 * Handles profile action
		 *
		 * Callback for "tml_request_profile" in method ThemedLogin::the_request()
		 *
		 * @see ThemedLogin::the_request()
		 * @access public
		 */
		public function tml_request_profile() {
			require_once(ABSPATH . 'wp-admin/includes/user.php');
			require_once(ABSPATH . 'wp-admin/includes/misc.php');

			global $themedLoginInstance;

			define('IS_PROFILE_PAGE', true);

			load_textdomain('default', WP_LANG_DIR . '/admin-' . get_locale() . '.mo');

			register_admin_color_schemes();

			wp_enqueue_style('password-strength', plugins_url('themed-profiles.css', __FILE__));

			wp_enqueue_script('user-profile');

			$current_user = wp_get_current_user();

			if ('POST' == $_SERVER['REQUEST_METHOD']) {
				check_admin_referer('update-user_' . $current_user->ID);

				if (! current_user_can('edit_user', $current_user->ID)) {
					wp_die(__('You do not have permission to edit this user.', 'themed-login'));
				}

				do_action('personal_options_update', $current_user->ID);

				$errors = edit_user($current_user->ID);

				if (! is_wp_error($errors)) {
					$args = ['updated' => 'true'];
					if (!empty($_REQUEST['instance'])) {
						$args['instance'] = $_REQUEST['instance'];
					}
					$redirect = add_query_arg($args);
					wp_redirect($redirect);
					exit;
				}
				$themedLoginInstance->errors = $errors;
			}
		}

		/**
		 * Outputs profile form HTML
		 *
		 * Callback for "tml_display_profile" hook in method ThemedLogin_Template::display()
		 *
		 * @see ThemedLogin_Template::display()
		 * @access public
		 *
		 * @param ThemedLogin_Template $template Reference to $theme_my_login_template object
		 */
		public function display_profile(&$template) {
			global $current_user, $profileuser, $_wp_admin_css_colors, $wp_version, $themedLoginInstance;

			require_once(ABSPATH . 'wp-admin/includes/user.php');
			require_once(ABSPATH . 'wp-admin/includes/misc.php');

			if (isset($_GET['updated']) && 'true' == $_GET['updated']) {
				$themedLoginInstance->errors->add('profile_updated', __('Profile updated.', 'themed-login'), 'message');
			}

			$current_user = wp_get_current_user();
			$profileuser  = get_user_to_edit($current_user->ID);

			$user_role = reset($profileuser->roles);
			if (is_multisite() && empty($user_role)) {
				$user_role = 'subscriber';
			}

			$_template = [];

			// Allow template override via shortcode or template tag args
			if (!empty($template->options['profile_template'])) {
				$_template[] = $template->options['profile_template'];
			}

			// Allow role template override via shortcode or template tag args
			if (!empty($template->options["profile_template_{$user_role}"])) {
				$_template[] = $template->options["profile_template_{$user_role}"];
			}

			// Role template
			$_template[] = "profile-form-{$user_role}.php";

			// Default template
			$_template[] = 'profile-form.php';

			// Load template
			$template->get_template($_template, true, compact('current_user', 'profileuser', 'user_role', '_wp_admin_css_colors', 'wp_version'));
		}

		/**
		 * Changes links from "profile.php" to themed profile page
		 *
		 * Callback for "site_url" hook
		 *
		 * @see site_url()
		 * @access public
		 *
		 * @param string $url The generated link
		 * @param string $path The specified path
		 * @param string $orig_scheme The original connection scheme
		 * @return string The filtered link
		 */
		public function site_url($url, $path, $orig_scheme = '') {
			global $current_user, $pagenow;

			if ('profile.php' != $pagenow && strpos($url, 'profile.php') !== false) {
				$user_role = reset($current_user->roles);
				if (is_multisite() && empty($user_role)) {
					$user_role = 'subscriber';
				}

				if ($user_role && ! $this->get_option([$user_role, 'theme_profile'])) {
					return $url;
				}
				$parsed_url = parse_url($url);

				$url = ThemedLogin::get_page_link('profile');

				if (isset($parsed_url['query'])) {
					$url = add_query_arg(array_map('rawurlencode', wp_parse_args($parsed_url['query'])), $url);
				}
			}
			return $url;
		}

		/**
		 * Hide Profile link if user is not logged in
		 *
		 * Callback for "wp_setup_nav_menu_item" hook in wp_setup_nav_menu_item()
		 *
		 * @see wp_setup_nav_menu_item()
		 * @access public
		 *
		 * @param object $menu_item The menu item
		 * @return object The (possibly) modified menu item
		 */
		public function wp_setup_nav_menu_item($menu_item) {
			if (is_admin()) {
				return $menu_item;
			}
			if ('page' != $menu_item->object) {
				return $menu_item;
			}
			// If user is not logged in, hide profile.
			if (!is_user_logged_in() && ThemedLogin::is_tml_page('profile', $menu_item->object_id)) {
				$menu_item->_invalid = true;
			}

			return $menu_item;
		}

		/**
		 * Loads the module
		 *
		 * @access protected
		 */
		protected function load() {
			add_filter('tml_default_pages', [$this, 'tml_default_pages']);
			add_action('tml_modules_loaded', [$this, 'modules_loaded']);

			add_action('init', [$this, 'init']);
			add_action('template_redirect', [$this, 'template_redirect']);
			add_filter('show_admin_bar', [$this, 'show_admin_bar']);
			add_action('wp_enqueue_scripts', [$this, 'wp_enqueue_scripts']);
			add_filter('body_class', [$this, 'body_class']);

			add_action('tml_request_profile', [$this, 'tml_request_profile']);
			add_action('tml_display_profile', [$this, 'display_profile']);

			add_filter('wp_setup_nav_menu_item', [$this, 'wp_setup_nav_menu_item'], 12);
		}
	}

	new ThemedLogin_Themed_Profiles();

}

if (is_admin()) {
	include_once(__DIR__ . '/admin/themed-profiles-admin.php');
}
