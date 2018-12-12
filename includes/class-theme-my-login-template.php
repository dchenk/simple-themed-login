<?php
/**
 * Holds the Themed Login template class
 *
 * @package ThemedLogin
 */

if (!class_exists('ThemedLogin_Template')) {

	/*
	 * Themed Login template class
	 *
	 * This class contains properties and methods common to displaying output.
	 */
	class ThemedLogin_Template extends ThemedLogin_Abstract {
		/**
		 * Holds the ID of the instance, which just marks its position on the page.
		 * The first instance has an ID of 0.
		 *
		 * @var int
		 */
		private $id = 0;

		/**
		 * Holds active instance flag
		 *
		 * @access private
		 * @var bool
		 */
		private $is_active = false;

		/**
		 * Constructor
		 *
		 * @access public
		 *
		 * @param array|string $options Instance options
		 */
		public function __construct($options = '') {
			parent::__construct();

			$this->options = wp_parse_args($options, $this->options);
		}

		/**
		 * Retrieves default options
		 *
		 * @access public
		 *
		 * @return array Default options
		 */
		public static function default_options(): array {
			return [
				'default_action'        => 'login',
				'login_template'        => '',
				'register_template'     => '',
				'lostpassword_template' => '',
				'resetpass_template'    => '',
				'user_template'         => '',
				'show_title'            => true,
				'show_log_link'         => true,
				'show_reg_link'         => true,
				'show_pass_link'        => true,
				'logged_in_widget'      => true,
				'logged_out_widget'     => true,
				'show_gravatar'         => true,
				'gravatar_size'         => 50,
				'before_widget'         => '',
				'after_widget'          => '',
				'before_title'          => '',
				'after_title'           => '',
			];
		}

		/**
		 * Displays output according to current action
		 *
		 * @access public
		 *
		 * @return string HTML output
		 */
		public function display($action = '') {
			if (empty($action)) {
				$action = $this->get_option('default_action');
			}

			ob_start();

			echo $this->get_option('before_widget');
			if ($this->get_option('show_title')) {
				echo $this->get_option('before_title') . $this->get_title($action) . $this->get_option('after_title') . "\n";
			}

			// Is there a specified template?
			if (has_action('tml_display_' . $action)) {
				do_action_ref_array('tml_display_' . $action, [&$this]);
			} else {
				$template = [];
				if (is_user_logged_in() && 'login' == $action) {
					if ($this->get_option('user_template')) {
						$template[] = $this->get_option('user_template');
					}
					$template[] = 'user-panel.php';
				} else {
					switch ($action) {
					case 'lostpassword':
					case 'retrievepassword':
						if ($this->get_option('lostpassword_template')) {
							$template[] = $this->get_option('lostpassword_template');
						}
						$template[] = 'lostpassword-form.php';
						break;
					case 'resetpass':
					case 'rp':
						if ($this->get_option('resetpass_template')) {
							$template[] = $this->get_option('resetpass_template');
						}
						$template[] = 'resetpass-form.php';
						break;
					case 'register':
						if ($this->get_option('register_template')) {
							$template[] = $this->get_option('register_template');
						}
						$template[] = 'register-form.php';
						break;
					case 'confirmaction':
						echo '<div class="tml">' . _wp_privacy_account_request_confirmed_message($_GET['request_id']) . '</div>';
						break;
					case 'login':
					default:
						if ($this->get_option('login_template')) {
							$template[] = $this->get_option('login_template');
						}
						$template[] = 'login-form.php';
					}
				}
				$this->get_template($template);
			}
			echo $this->get_option('after_widget') . "\n";

			$output = ob_get_contents();
			ob_end_clean();

			return apply_filters_ref_array('tml_display', [$output, $action, $this]);
		}

		/**
		 * Returns the title to be used for a particular action.
		 * The returned title depends on whether the user is logged in.
		 *
		 * @access public
		 *
		 * @param string $action The action to retrieve. Defaults to current action.
		 * @return string Title of $action
		 */
		public function get_title($action = ''): string {
			if (is_admin()) {
				return '';
			}

			if (empty($action)) {
				$action = $this->get_option('default_action');
			}

			if (is_user_logged_in() && $action === 'login' && $this->get_option('default_action') === 'login') {
				$title = sprintf(__('Welcome, %s', 'themed-login'), wp_get_current_user()->display_name);
			} else {
				$page_id = ThemedLogin::get_page_id($action);
				if ($page_id) {
					$title = get_post_field('post_title', $page_id);
				} else {
					$title = self::default_title($action);
				}
			}

			return apply_filters('themed_login_title', $title, $action);
		}

		/**
		 * Returns the default title to be used for a particular action.
		 *
		 * @param string $action
		 * @return string
		 */
		public static function default_title(string $action): string {
			switch ($action) {
			case 'register':
				return __('Register', 'themed-login');
			case 'lostpassword':
			case 'retrievepassword':
			case 'resetpass':
			case 'rp':
				return __('Lost Password', 'themed-login');
			case 'confirmaction':
				return __('Your Data Request', 'themed-login');
			case 'login':
			default:
				return __('Log In', 'themed-login');
			}
		}

		/**
		 * Outputs action title
		 *
		 * @access public
		 *
		 * @param string $action The action to retrieve. Defaults to current action.
		 */
		public function the_title($action = '') {
			echo $this->get_title($action);
		}

		/**
		 * Returns plugin errors
		 *
		 * @access public
		 */
		public function get_errors() {
			global $error, $themedLoginInstance;

			$wp_error = $themedLoginInstance->errors;

			if (empty($wp_error)) {
				$wp_error = new WP_Error();
			}

			// In case a plugin uses $error rather than the $errors object
			if (!empty($error)) {
				$wp_error->add('error', $error);
				unset($error);
			}

			$output = '';
			if ($this->is_active() && $wp_error->get_error_code()) {
				$errors = '';
				$messages = '';
				foreach ($wp_error->get_error_codes() as $code) {
					$severity = $wp_error->get_error_data($code);
					foreach ($wp_error->get_error_messages($code) as $error) {
						if ('message' == $severity) {
							$messages .= '    ' . $error . "<br>\n";
						} else {
							$errors .= '    ' . $error . "<br>\n";
						}
					}
				}
				if (!empty($errors)) {
					$output .= '<p class="error">' . apply_filters('login_errors', $errors) . "</p>\n";
				}
				if (!empty($messages)) {
					$output .= '<p class="message">' . apply_filters('login_messages', $messages) . "</p>\n";
				}
			}
			return $output;
		}

		/**
		 * Prints plugin errors
		 *
		 * @access public
		 */
		public function the_errors() {
			echo $this->get_errors();
		}

		/**
		 * Returns requested action URL
		 *
		 * @access public
		 *
		 * @param string $action Action to retrieve
		 * @param string $scheme Scheme to give the URL context
		 * @return string The requested action URL
		 */
		public function get_action_url($action = '', $scheme = 'login') {
			if ($action == $this->get_option('default_action')) {
				$args = [];
				if ($this->id) {
					$args['instance'] = $this->id;
				}
				$url = ThemedLogin_Common::get_current_url($args);
			} else {
				$url = ThemedLogin::get_page_link($action);
			}

			$url = remove_query_arg(ThemedLogin_Common::filtered_query_args(), $url);

			$url = set_url_scheme($url, $scheme);

			return apply_filters('themed_login_action_url', $url, $action, $scheme, $this->id);
		}

		/**
		 * Outputs requested action URL
		 *
		 * @access public
		 *
		 * @param string $action Action to retrieve
		 * @param string $scheme Scheme to give the URL context
		 */
		public function the_action_url($action = 'login', $scheme = 'login') {
			echo esc_url($this->get_action_url($action, $scheme));
		}

		/**
		 * Returns the action links
		 *
		 * @param array|string $args Optionally specify which actions to include/exclude. By default, all are included.
		 * @return array
		 */
		public function get_action_links($args = []): array {
			$args = wp_parse_args($args, [
				'login'        => true,
				'register'     => true,
				'lostpassword' => true,
			]);

			$action_links = [];
			if ($args['login'] && $this->get_option('show_log_link')) {
				$action_links[] = [
					'title' => $this->get_title('login'),
					'url'   => $this->get_action_url('login'),
				];
			}
			if ($args['register'] && $this->get_option('show_reg_link') && get_option('users_can_register')) {
				$action_links[] = [
					'title' => $this->get_title('register'),
					'url'   => $this->get_action_url('register'),
				];
			}
			if ($args['lostpassword'] && $this->get_option('show_pass_link')) {
				$action_links[] = [
					'title' => $this->get_title('lostpassword'),
					'url'   => $this->get_action_url('lostpassword'),
				];
			}
			return apply_filters('themed_login_action_links', $action_links, $args);
		}

		/**
		 * Outputs the action links
		 *
		 * @access public
		 *
		 * @param array|string $args Optionally specify which actions to include/exclude. By default, all are included.
		 */
		public function the_action_links($args = []) {
			$links = $this->get_action_links($args);
			if ($links) {
				echo '<ul class="tml-action-links">';
				foreach ((array) $links as $link) {
					echo '<li><a href="' . esc_url($link['url']) . '" rel="nofollow">' . esc_html($link['title']) . '</a></li>';
				}
				echo '</ul>';
			}
		}

		/**
		 * Returns logged-in user links
		 *
		 * @return array Logged-in user links
		 */
		public static function get_user_links() {
			$links = [
				[
					'title' => __('Dashboard', 'themed-login'),
					'url'   => admin_url(),
				],
				[
					'title' => __('Profile', 'themed-login'),
					'url'   => admin_url('profile.php'),
				],
			];
			return apply_filters('tml_user_links', $links);
		}

		/**
		 * Outputs logged-in user links
		 */
		public function the_user_links() {
			echo '<ul class="tml-user-links">';
			foreach ((array) self::get_user_links() as $link) {
				echo '<li><a href="' . esc_url($link['url']) . '">' . esc_html($link['title']) . '</a></li>';
			}
			echo '<li><a href="' . wp_logout_url() . '">' . self::get_title('logout') . '</a></li>';
			echo '</ul>';
		}

		/**
		 * Displays user avatar
		 *
		 * @param int $size
		 */
		public function the_user_avatar($size = 0) {
			if ($size === 0) {
				$size = $this->get_option('gravatar_size', 50);
			}

			$current_user = wp_get_current_user();

			echo get_avatar($current_user->ID, $size);
		}

		/**
		 * Returns template message for requested action
		 *
		 * @param string $action Action to retrieve
		 * @return string The requested template message
		 */
		public static function get_action_template_message($action = '') {
			switch ($action) {
			case 'register':
				$message = __('Register for this site', 'themed-login');
				break;
			case 'lostpassword':
				$message = __('Please enter your username or email address. You will receive a link via email to create a new password.', 'themed-login');
				break;
			case 'resetpass':
				$message = __('Enter your new password below.', 'themed-login');
				break;
			default:
				$message = '';
			}
			$message = apply_filters('login_message', $message);

			return apply_filters("themed_login_action_template_message_${action}", $message);
		}

		/**
		 * Outputs template message for requested action
		 *
		 * @param string $action Action to retrieve
		 * @param string $before_message Text/HTML to add before the message
		 * @param string $after_message Text/HTML to add after the message
		 */
		public function the_action_template_message($action = 'login', $before_message = '<p class="message">', $after_message = '</p>') {
			$message = self::get_action_template_message($action);
			if ($message) {
				echo $before_message . $message . $after_message;
			}
		}

		/**
		 * Locates specified template.
		 * This function applies the filter "themed_login_template_dirs" to the array listing
		 * the directories in which templates could be found. Further, this function applies
		 * the "themed_login_template" filter to the string giving the template path.
		 *
		 * @param array|string $template_names The template(s) to locate
		 * @param bool $load If true, the template will be included if found
		 * @param array $args Array of extra variables to make available to template
		 * @return bool|string Template path if found, false if not
		 */
		public function get_template($template_names, $load = true, $args = []) {
			// Easy access to current user
			$current_user = wp_get_current_user();

			extract(apply_filters_ref_array('themed_login_template_args', [$args, $this]));

			// $template_dirs lists the directories in which templates could be found.
			$template_dirs = apply_filters('themed_login_template_dirs', [
				get_template_directory() . '/themed-login',
				THEMED_LOGIN_DIR . '/templates',
			]);

			$located = '';
			foreach ((array) $template_names as $template_name) {
				if ($template_name) {
					if (preg_match('/\/|\\\\/', $template_name)) {
						continue;
					}
					foreach ($template_dirs as $dir) {
						if (file_exists($dir . '/' . $template_name)) {
							$located = $dir . '/' . $template_name;
							break 2;
						}
					}
				}
			}

			$located = apply_filters_ref_array('themed_login_template', [$located, $template_names, $this]);

			if ($load && file_exists($located)) {
				include($located);
			}

			return $located;
		}

		/**
		 * Returns the proper redirect URL according to action
		 *
		 * @param string $action The action
		 * @return string The redirect URL
		 */
		public function get_redirect_url($action = '') {
			if (empty($action)) {
				$action = $this->get_option('default_action');
			}

			$redirect_to = $_REQUEST['redirect_to'] ?? '';

			switch ($action) {
			case 'lostpassword':
			case 'retrievepassword':
				$url = apply_filters('lostpassword_redirect', !empty($redirect_to) ? $redirect_to : ThemedLogin::get_page_link('login', 'checkemail=confirm'));
				break;
			case 'register':
				$url = apply_filters('registration_redirect', !empty($redirect_to) ? $redirect_to : ThemedLogin::get_page_link('login', 'checkemail=registered'));
				break;
			case 'login':
			default:
				$url = apply_filters('login_redirect', !empty($redirect_to) ? $redirect_to : admin_url(), $redirect_to, null);
			}
			return apply_filters('themed_login_redirect_url', $url, $action);
		}

		/**
		 * Outputs redirect URL
		 *
		 * @param string $action The action
		 */
		public function the_redirect_url($action = '') {
			echo esc_attr($this->get_redirect_url($action));
		}

		/**
		 * Outputs the current template instance ID
		 */
		public function instance_id() {
			echo $this->id > 0 ? $this->id : '';
		}

		/**
		 * Sets the instance ID.
		 *
		 * @param int $id
		 */
		public function set_id(int $id) {
			$this->id = $id;
		}

		/**
		 * Returns requested $value
		 *
		 * @param string $value The value to retrieve
		 * @return bool|string The value if it exists, false if not
		 */
		public function get_posted_value($value) {
			if ($this->is_active() && isset($_REQUEST[$value])) {
				return stripslashes($_REQUEST[$value]);
			}
			return false;
		}

		/**
		 * Outputs requested value
		 *
		 * @param string $value The value to retrieve
		 */
		public function the_posted_value($value) {
			echo esc_attr($this->get_posted_value($value));
		}

		/**
		 * Returns active status
		 *
		 * @return bool True if instance is active, false if not
		 */
		public function is_active() {
			return $this->is_active;
		}

		/**
		 * Sets the template instance as active
		 */
		public function set_active() {
			$this->is_active = true;
		}
	}

}
