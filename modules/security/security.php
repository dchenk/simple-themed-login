<?php
/**
 * Plugin Name: Security
 * Description: Enabling this module will initialize security. You will then have to configure the settings via the "Security" tab.
 *
 * Holds Themed Login Security class
 *
 * @package ThemedLogin
 */

if (!class_exists('ThemedLogin_Security')) {

	/**
	 * Themed Login Security module class
	 *
	 * Adds options to help protect your site.
	 */
	class ThemedLogin_Security extends ThemedLogin_Abstract {
		/**
		 * Holds options key
		 *
		 * @var string
		 */
		protected $options_key = 'theme_my_login_security';

		/**
		 * Returns default options
		 *
		 * @return array Default options
		 */
		public static function default_options(): array {
			return [
				'private_site'  => 0,
				'private_login' => 1,
				'failed_login'  => [
					'threshold'               => 5,
					'threshold_duration'      => 1,
					'threshold_duration_unit' => 'hour',
					'lockout_duration'        => 30,
					'lockout_duration_unit'   => 'hour',
				],
			];
		}

		/**
		 * Redirects to a /login page if wp-login.php is disabled
		 */
		public function init() {
			global $pagenow;
			if ($pagenow == 'wp-login.php' && $this->get_option('private_login')) {
				parse_str($_SERVER['QUERY_STRING'], $q);
				if (empty($q['interim-login']) && empty($_REQUEST['interim-login'])) {
					wp_redirect('/login');
					exit;
				}
			}
		}

		/**
		 * Blocks entire site if user is not logged in and private site is enabled
		 *
		 * Callback for "template_redirect" hook in the file wp-settings.php
		 */
		public function template_redirect() {
			$private_site = apply_filters('themed_login_enforce_private_site', $this->get_option('private_site'));
			if ($private_site && !is_user_logged_in() && !ThemedLogin::is_tml_page()) {
				$redirect_to = apply_filters('tml_security_private_site_redirect', wp_login_url($_SERVER['REQUEST_URI'], true));
				wp_safe_redirect($redirect_to);
				exit;
			}
		}

		/**
		 * Handles "unlock" action for login page
		 *
		 * Callback for "tml_request_unlock" hook in method ThemedLogin::template_redirect()
		 *
		 * @see ThemedLogin::template_redirect()
		 */
		public function request_unlock() {
			$user = self::check_user_unlock_key($_GET['key'], $_GET['login']);

			$redirect_to = ThemedLogin_Common::get_current_url();

			if (is_wp_error($user)) {
				$redirect_to = add_query_arg('unlock', 'invalidkey', $redirect_to);
				wp_redirect($redirect_to);
				exit;
			}

			self::unlock_user($user->ID);

			$redirect_to = add_query_arg('unlock', 'complete', $redirect_to);
			wp_redirect($redirect_to);
			exit;
		}

		/**
		 * Handles display of various action/status messages
		 *
		 * Callback for "tml_request" hook in ThemedLogin::the_request()
		 *
		 * @param ThemedLogin $tml object Reference to the global object
		 */
		public function action_messages(&$tml) {
			if (isset($_GET['unlock']) && 'complete' == $_GET['unlock']) {
				$tml->errors->add('unlock_complete', __('Your account has been unlocked. You may now log in.', 'themed-login'), 'message');
			}
		}

		/**
		 * Validates a user unlock key
		 *
		 * @param string $key Unlock key
		 * @param string $login User login
		 * @return WP_Error|WP_User WP_User object on success, WP_Error object on failure
		 */
		public static function check_user_unlock_key($key, $login) {
			$key = preg_replace('/[^a-z0-9]/i', '', $key);

			if (empty($key) || ! is_string($key)) {
				return new WP_Error('invalid_key', __('Invalid key', 'themed-login'));
			}
			if (empty($login) || ! is_string($login)) {
				return new WP_Error('invalid_key', __('Invalid key', 'themed-login'));
			}
			if (! $user = get_user_by('login', $login)) {
				return new WP_Error('invalid_key', __('Invalid key', 'themed-login'));
			}
			if ($key != self::get_user_unlock_key($user->ID)) {
				return new WP_Error('invalid_key', __('Invalid key', 'themed-login'));
			}
			return $user;
		}

		/**
		 * Blocks locked users from logging in
		 *
		 * Callback for "authenticate" hook in function wp_authenticate()
		 *
		 * @see wp_authenticate()
		 *
		 * @param WP_Error|WP_User $user WP_User or WP_Error object
		 * @param string $username Username posted
		 * @param string $password Password posted
		 * @return WP_Error|WP_User WP_User if the user can login, WP_Error otherwise
		 */
		public function authenticate($user, $username, $password) {
			// Make sure user exists
			$field = is_email($username) ? 'email' : 'login';
			$userdata = get_user_by($field, $username);
			if (!$userdata) {
				return $user;
			}

			$time = time();

			if (self::is_user_locked($userdata->ID)) {
				$expiration = self::get_user_lock_expiration($userdata->ID);
				if ($expiration) {
					if ($time > $expiration) {
						self::unlock_user($userdata->ID);
					} else {
						return new WP_Error(
							'locked_account',
							sprintf(__('<strong>ERROR</strong>: This account has been locked because of too many failed login attempts. You may try again in %s.', 'themed-login'), human_time_diff($time, $expiration))
						);
					}
				} else {
					return new WP_Error('locked_account', __('<strong>ERROR</strong>: This account has been locked.', 'themed-login'));
				}
			} elseif (is_wp_error($user) && 'incorrect_password' == $user->get_error_code()) {
				// Get the attempts
				$attempts = self::get_failed_login_attempts($userdata->ID);

				// Get the first valid attempt
				$first_attempt = reset($attempts);

				// Get the relative duration
				$duration = $first_attempt['time'] + self::get_seconds_from_unit($this->get_option(['failed_login', 'threshold_duration']), $this->get_option(['failed_login', 'threshold_duration_unit']));

				// If current time is less than relative duration time, we're still within the defensive zone
				if ($time < $duration) {
					// Log this attempt
					self::add_failed_login_attempt($userdata->ID, $time);
					// If failed attempts reach threshold, lock the account
					if (self::get_failed_login_attempt_count($userdata->ID) >= $this->get_option(['failed_login', 'threshold'])) {
						// Create new expiration
						$expiration = $time + self::get_seconds_from_unit($this->get_option(['failed_login', 'lockout_duration']), $this->get_option(['failed_login', 'lockout_duration_unit']));
						self::lock_user($userdata->ID, $expiration);
						return new WP_Error('locked_account', sprintf(__('<strong>ERROR</strong>: This account has been locked because of too many failed login attempts. You may try again in %s.', 'themed-login'), human_time_diff($time, $expiration)));
					}
				} else {
					// Clear the attempts
					self::reset_failed_login_attempts($userdata->ID);
					// Log this attempt
					self::add_failed_login_attempt($userdata->ID, $time);
				}
			}
			return $user;
		}

		/**
		 * Blocks locked users from resetting their password, if locked by admin
		 *
		 * Callback for "allow_password_reset" in method ThemedLogin::retrieve_password()
		 *
		 * @see ThemedLogin::retrieve_password()
		 *
		 * @param bool $allow Default setting
		 * @param int $user_id User ID
		 * @return bool Whether to allow password reset or not
		 */
		public function allow_password_reset($allow, $user_id) {
			if (self::is_user_locked($user_id) && !self::get_user_lock_expiration($user_id)) {
				$allow = false;
			}
			return $allow;
		}

		/**
		 * Displays failed login attempts on users profile for administrators
		 *
		 * @param object $profileuser User object
		 */
		public function show_user_profile($profileuser) {
			if (!current_user_can('manage_users')) {
				return;
			}
			$failed_attempts = self::get_failed_login_attempts($profileuser->ID);
			if ($failed_attempts) {
				?>
				<h3><?php _e('Failed Login Attempts', 'themed-login'); ?></h3>

				<table class="form-table">
				<tr>
					<th scope="col"><?php _e('IP Address', 'themed-login'); ?></th>
					<th scope="col"><?php _e('Date', 'themed-login'); ?></th>
				</tr>
				<?php foreach ($failed_attempts as $attempt) {
					$t_time = date_i18n(__('Y/m/d g:i:s A', 'themed-login'), $attempt['time']);

					$time_diff = time() - $attempt['time'];

					if ($time_diff > 0 && $time_diff < 24*60*60) {
						$h_time = sprintf(__('%s ago', 'themed-login'), human_time_diff($attempt['time']));
					} else {
						$h_time = date_i18n(__('Y/m/d', 'themed-login'), $attempt['time']);
					} ?>
					<tr>
						<td><?php echo $attempt['ip']; ?></td>
						<td><abbr title="<?php echo $t_time; ?>"><?php echo $h_time; ?></abbr></td>
					</tr>
					<?php
				} ?>
				</table>
			<?php
			}
		}

		/**
		 * Shows admin bar for wp-login.php when it is disabled
		 *
		 * @param bool $show True to show admin bar, false to hide
		 * @return bool True to show admin bar, false to hide
		 */
		public function show_admin_bar($show): bool {
			global $pagenow;
			if (is_user_logged_in() && 'wp-login.php' == $pagenow && $this->get_option('private_login')) {
				return true;
			}
			return $show;
		}

		/**
		 * Locks a user
		 *
		 * @param int|WP_User $user User ID ir WP_User object
		 * @param int $expires When the lock expires, in seconds from current time
		 */
		public static function lock_user($user, $expires = 0) {
			if (is_object($user)) {
				$user = $user->ID;
			}

			$user = (int) $user;

			do_action('tml_lock_user', $user);

			$security = self::get_security_meta($user);

			$security['is_locked']       = true;
			$security['lock_expiration'] = absint($expires);
			$security['unlock_key']      = wp_generate_password(20, false);

			update_user_meta($user, 'theme_my_login_security', $security);

			if ($expires) {
				self::user_lock_notification($user);
			}
		}

		/**
		 * Unlocks a user
		 *
		 * @param int|WP_User $user User ID or WP_User object
		 * @return bool|int
		 */
		public static function unlock_user($user) {
			if (is_object($user)) {
				$user = $user->ID;
			}

			$user = (int) $user;

			do_action('tml_unlock_user', $user);

			$security = self::get_security_meta($user);

			$security['is_locked']             = false;
			$security['lock_expiration']       = 0;
			$security['unlock_key']            = '';
			$security['failed_login_attempts'] = [];

			return update_user_meta($user, 'theme_my_login_security', $security);
		}

		/**
		 * Determine if a user is locked or not
		 *
		 * @param int|WP_User $user User ID or WP_User object
		 * @return bool True if user is locked, false if not
		 */
		public static function is_user_locked($user) {
			if (is_object($user)) {
				$user = $user->ID;
			}

			$user = (int) $user;

			$security = self::get_security_meta($user);

			// If "is_locked" is not set, there is no lock
			if (!$security['is_locked']) {
				return false;
			}

			// If "lock_expires" is not set, there is a lock but no expiry
			if (!$expires = self::get_user_lock_expiration($user)) {
				return true;
			}

			// We have a lock with an expiry
			if (time() > $expires) {
				self::unlock_user($user);
				return false;
			}

			return true;
		}

		/**
		 * Get a user's failed login attempts
		 *
		 * @param int $user_id User ID
		 * @return array User's failed login attempts
		 */
		public static function get_failed_login_attempts($user_id) {
			$security_meta = self::get_security_meta($user_id);
			return $security_meta['failed_login_attempts'];
		}

		/**
		 * Reset a user's failed login attempts
		 *
		 * @param int $user_id User ID
		 * @return bool|int
		 */
		public static function reset_failed_login_attempts($user_id) {
			$security_meta = self::get_security_meta($user_id);
			$security_meta['failed_login_attempts'] = [];
			return update_user_meta($user_id, 'theme_my_login_security', $security_meta);
		}

		/**
		 * Get a user's failed login attempt count
		 *
		 * @param int $user_id User ID
		 * @return int Number of user's failed login attempts
		 */
		public static function get_failed_login_attempt_count($user_id) {
			return count(self::get_failed_login_attempts($user_id));
		}

		/**
		 * Add a failed login attempt to a user
		 *
		 * @param int $user_id User ID
		 * @param int $time Time of attempt, in seconds
		 * @param string $ip IP address of attempt
		 * @return bool|int
		 */
		public static function add_failed_login_attempt($user_id, $time = '', $ip = '') {
			$security_meta = self::get_security_meta($user_id);
			if (! is_array($security_meta['failed_login_attempts'])) {
				$security_meta['failed_login_attempts'] = [];
			}

			$time = absint($time);

			if (empty($time)) {
				$time = time();
			}

			if (empty($ip)) {
				$ip = $_SERVER['REMOTE_ADDR'];
			}

			$security_meta['failed_login_attempts'][] = ['time' => $time, 'ip' => $ip];

			return update_user_meta($user_id, 'theme_my_login_security', $security_meta);
		}

		/**
		 * Get user's lock expiration time
		 *
		 * @param int $user_id User ID
		 * @return int User's lock expiration time
		 */
		public static function get_user_lock_expiration($user_id) {
			$security_meta = self::get_security_meta($user_id);
			return apply_filters('tml_user_lock_expiration', absint($security_meta['lock_expiration']), $user_id);
		}

		/**
		 * Get a user's unlock key
		 *
		 * @param int $user_id User ID
		 * @return string User's unlock key
		 */
		public static function get_user_unlock_key($user_id) {
			$security_meta = self::get_security_meta($user_id);
			return apply_filters('tml_user_unlock_key', $security_meta['unlock_key'], $user_id);
		}

		/**
		 * Get number of seconds from days, hours and minutes
		 *
		 * @param int $value Number of $unit
		 * @param string $unit Can be either "day", "hour" or "minute"
		 * @return int Number of seconds
		 */
		public static function get_seconds_from_unit($value, $unit = 'minute') {
			switch ($unit) {
			case 'day':
				return $value * 24 * 60 * 60;
			case 'hour':
				return $value * 60 * 60;
			case 'minute':
				return $value * 60;
			default:
				return 120;
			}
		}

		/**
		 * Sends a user a notification that their account has been locked
		 *
		 * @param int $user_id User ID
		 */
		public static function user_lock_notification($user_id) {
			global $current_site;

			if (apply_filters('send_user_lock_notification', true)) {
				$user = new WP_User($user_id);

				$user_login = stripslashes($user->user_login);
				$user_email = stripslashes($user->user_email);

				if (is_multisite()) {
					$blogname = $current_site->site_name;
				} else {
					// The blogname option is escaped with esc_html on the way into the database in sanitize_option
					// we want to reverse this for the plain text arena of emails.
					$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
				}

				$unlock_url = add_query_arg(['action' => 'unlock', 'key' => self::get_user_unlock_key($user->ID), 'login' => rawurlencode($user_login)], wp_login_url());

				$title    = sprintf(__('[%s] Account Locked', 'themed-login'), $blogname);
				$message  = sprintf(__('For your security, your account has been locked because of too many failed login attempts. To unlock your account please click the following link: ', 'themed-login'), $blogname) . "\r\n\r\n";
				$message .=  $unlock_url . "\r\n";

				if ($user->has_cap('administrator')) {
					$message .= "\r\n";
					$message .= __('The following attempts resulted in the lock:', 'themed-login') . "\r\n\r\n";
					foreach (self::get_failed_login_attempts($user->ID) as $attempt) {
						$time = date_i18n(__('Y/m/d g:i:s A', 'themed-login'), $attempt['time']);
						$message .= $attempt['ip'] . "\t" . $time . "\r\n\r\n";
					}
				}

				$title   = apply_filters('user_lock_notification_title', $title, $user_id);
				$message = apply_filters('user_lock_notification_message', $message, $unlock_url, $user_id);

				wp_mail($user_email, $title, $message);
			}
		}

		/**
		 * Loads the module
		 */
		protected function load() {
//			error_log('loading SECURITY  --  ' . $_SERVER['REQUEST_URI']);

			add_action('init', [$this, 'init']);
			add_action('template_redirect', [$this, 'template_redirect']);
			add_action('tml_request_unlock', [$this, 'request_unlock']);
			add_action('themed_login_request', [$this, 'action_messages']);

			add_action('authenticate', [$this, 'authenticate'], 100, 3);
			add_filter('allow_password_reset', [$this, 'allow_password_reset'], 10, 2);

			add_action('show_user_profile', [$this, 'show_user_profile']);
			add_action('edit_user_profile', [$this, 'show_user_profile']);

			add_filter('show_admin_bar', [$this, 'show_admin_bar']);
		}

		/**
		 * Get a user's security meta
		 *
		 * @param int $user_id User ID
		 * @return array User's security meta
		 */
		protected static function get_security_meta($user_id) {
			$defaults = [
				'is_locked'             => false,
				'lock_expiration'       => 0,
				'unlock_key'            => '',
				'failed_login_attempts' => [],
			];
			$meta = get_user_meta($user_id, 'theme_my_login_security', true);
			if (!is_array($meta)) {
				$meta = [];
			}

			return array_merge($defaults, $meta);
		}
	}

	new ThemedLogin_Security();

}

if (is_admin()) {
	include_once(__DIR__ . '/admin/security-admin.php');
}
