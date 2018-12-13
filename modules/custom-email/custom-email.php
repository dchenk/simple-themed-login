<?php
/**
 * Plugin Name: Custom Emails
 * Description: Customize the emails sent out related to user accounts. Configured in the "Custom Emails" tab.
 *
 * Holds Themed Login Custom E-mail class
 *
 * @package ThemedLogin
 */

if (!class_exists('ThemedLogin_Custom_Email')) {

	/**
	 * Themed Login Custom E-mail class
	 */
	class ThemedLogin_Custom_Email extends ThemedLogin_Abstract {
		/**
		 * Holds options key
		 *
		 * @var string
		 */
		protected $options_key = 'theme_my_login_email';

		/**
		 * Mail from
		 *
		 * @var string
		 */
		protected $mail_from;

		/**
		 * Mail from name
		 *
		 * @var string
		 */
		protected $mail_from_name;

		/**
		 * Mail content type
		 *
		 * @var string
		 */
		protected $mail_content_type;

		/**
		 * Returns default options
		 */
		public static function default_options(): array {
			return [
				'new_user' => [
					'mail_from' => '',
					'mail_from_name' => '',
					'mail_content_type' => '',
					'title' => '',
					'message' => '',
					'admin_mail_to' => '',
					'admin_mail_from' => '',
					'admin_mail_from_name' => '',
					'admin_mail_content_type' => '',
					'admin_title' => '',
					'admin_message' => '',
				],
				'retrieve_pass' => [
					'mail_from' => '',
					'mail_from_name' => '',
					'mail_content_type' => '',
					'title' => '',
					'message' => '',
				],
				'reset_pass' => [
					'admin_mail_to' => '',
					'admin_mail_from' => '',
					'admin_mail_from_name' => '',
					'admin_mail_content_type' => '',
					'admin_title' => '',
					'admin_message' => '',
				],
			];
		}

		/**
		 * Sets variables to be used with mail header filters
		 *
		 * @param string $mail_from E-mail address to send the mail from
		 * @param string $mail_from_name Name to send the mail from
		 * @param string $mail_content_type Content type for the message
		 */
		public function set_mail_headers($mail_from = '', $mail_from_name = '', $mail_content_type = 'text') {
			$this->mail_from         = $mail_from;
			$this->mail_from_name    = $mail_from_name;
			$this->mail_content_type = $mail_content_type;
		}

		/**
		 * Applies all password retrieval mail filters
		 *
		 * Callback for "retrieve_password" hook in ThemedLogin::retrieve_password()
		 *
		 * @see ThemedLogin::retrieve_password()
		 */
		public function apply_retrieve_pass_filters() {
			$this->set_mail_headers(
			$this->get_option(['retrieve_pass', 'mail_from']),
			$this->get_option(['retrieve_pass', 'mail_from_name']),
			$this->get_option(['retrieve_pass', 'mail_content_type'])
		);
			add_filter('retrieve_password_title', [$this, 'retrieve_pass_title_filter'], 10, 3);
			add_filter('retrieve_password_message', [$this, 'retrieve_pass_message_filter'], 10, 4);
		}

		/**
		 * Applies all password reset mail filters
		 *
		 * Callback for "password_reset" hook in ThemedLogin::reset_password()
		 *
		 * @see ThemedLogin::reset_password()
		 */
		public function apply_password_reset_filters() {
			$this->set_mail_headers(
			$this->get_option(['reset_pass', 'admin_mail_from']),
			$this->get_option(['reset_pass', 'admin_mail_from_name']),
			$this->get_option(['reset_pass', 'admin_mail_content_type'])
		);
			add_filter('password_change_notification_mail_to', [$this, 'password_change_notification_mail_to_filter']);
			add_filter('password_change_notification_title', [$this, 'password_change_notification_title_filter'], 10, 2);
			add_filter('password_change_notification_message', [$this, 'password_change_notification_message_filter'], 10, 2);
			add_filter('send_password_change_notification', [$this, 'send_password_change_notification_filter']);
		}

		/**
		 * Applies all new user mail filters
		 *
		 * Callback for "register_post" hook in ThemedLogin::register_new_user()
		 *
		 * @see ThemedLogin::register_new_user()
		 */
		public function apply_new_user_filters() {
			add_filter('new_user_notification_title', [$this, 'new_user_notification_title_filter'], 10, 2);
			add_filter('new_user_notification_message', [$this, 'new_user_notification_message_filter'], 10, 3);
			add_filter('send_new_user_notification', [$this, 'send_new_user_notification_filter']);
			add_filter('new_user_admin_notification_mail_to', [$this, 'new_user_admin_notification_mail_to_filter']);
			add_filter('new_user_admin_notification_title', [$this, 'new_user_admin_notification_title_filter'], 10, 2);
			add_filter('new_user_admin_notification_message', [$this, 'new_user_admin_notification_message_filter'], 10, 2);
			add_filter('send_new_user_admin_notification', [$this, 'send_new_user_admin_notification_filter']);
		}

		/**
		 * Changes the mail from address
		 *
		 * Callback for "wp_mail_from" hook in wp_mail()
		 *
		 * @see wp_mail()
		 *
		 * @param string $from_email Default from email
		 * @return string New from email
		 */
		public function mail_from_filter($from_email) {
			return empty($this->mail_from) ? $from_email : $this->mail_from;
		}

		/**
		 * Changes the mail from name
		 *
		 * Callback for "wp_mail_from_name" hook in wp_mail()
		 *
		 * @see wp_mail()
		 *
		 * @param string $from_name Default from name
		 * @return string New from name
		 */
		public function mail_from_name_filter($from_name) {
			return empty($this->mail_from_name) ? $from_name : $this->mail_from_name;
		}

		/**
		 * Changes the mail content type
		 *
		 * Callback for "wp_mail_content_type" hook in wp_mail()
		 *
		 * @see wp_mail()
		 *
		 * @param string $content_type Default content type
		 * @return string New content type
		 */
		public function mail_content_type_filter($content_type) {
			return empty($this->mail_content_type) ? $content_type : 'text/' . $this->mail_content_type;
		}

		/**
		 * Changes the retrieve password e-mail subject
		 *
		 * Callback for "retrieve_pass_title" hook in ThemedLogin::retrieve_password()
		 *
		 * @see ThemedLogin::retrieve_password()
		 *
		 * @param string $title Default subject
		 * @param string $user_login User login
		 * @param object $user_data User data
		 * @return string New subject
		 */
		public function retrieve_pass_title_filter($title, $user_login, $user_data) {
			$_title = $this->get_option(['retrieve_pass', 'title']);
			return empty($_title) ? $title : ThemedLogin_Common::replace_vars($_title, $user_data->ID);
		}

		/**
		 * Changes the retrieve password e-mail message
		 *
		 * Callback for "retrieve_password_message" hook in ThemedLogin::retrieve_password()
		 *
		 * @see ThemedLogin::retrieve_password()
		 *
		 * @param string $message Default message
		 * @param string $key The user's reset key
		 * @param string $user_login User login
		 * @param object $user_data User data
		 * @return string New message
		 */
		public function retrieve_pass_message_filter($message, $key, $user_login, $user_data) {
			$_message = $this->get_option(['retrieve_pass', 'message']);
			if (! empty($_message)) {
				$message = ThemedLogin_Common::replace_vars($_message, $user_data->ID, [
					'%loginurl%' => site_url('wp-login.php', 'login'),
					'%reseturl%' => site_url("wp-login.php?action=rp&key=${key}&login=" . rawurlencode($user_login), 'login'),
				]);
			}
			return $message;
		}

		/**
		 * Changes who the password change notification e-mail is sent to
		 *
		 * Callback for "password_change_notification_mail_to" hook in $this->password_change_notification()
		 *
		 * @see $this->password_change_notification()
		 *
		 * @param string $to Default admin e-mail address
		 * @return string New e-mail address(es)
		 */
		public function password_change_notification_mail_to_filter($to) {
			$_to = $this->get_option(['reset_pass', 'admin_mail_to']);
			return empty($_to) ? $to : $_to;
		}

		/**
		 * Changes the password change notification e-mail subject
		 *
		 * Callback for "password_change_notification_title" hook in $this->password_change_notification()
		 *
		 * @see $this->password_change_notification()
		 *
		 * @param string $title Default subject
		 * @param int $user_id User ID
		 * @return string New subject
		 */
		public function password_change_notification_title_filter($title, $user_id) {
			$_title = $this->get_option(['reset_pass', 'admin_title']);
			return empty($_title) ? $title : ThemedLogin_Common::replace_vars($_title, $user_id);
		}

		/**
		 * Changes the password change notification e-mail message
		 *
		 * Callback for "password_change_notification_message" hook in $this->password_change_notification()
		 *
		 * @see $this->password_change_notification()
		 *
		 * @param int $user_id User ID
		 * @return string New message
		 */
		public function password_change_notification_message_filter($message, $user_id) {
			$_message = $this->get_option(['reset_pass', 'admin_message']);
			return empty($_message) ? $message : ThemedLogin_Common::replace_vars($_message, $user_id);
		}

		/**
		 * Determines whether or not to send the password change notification e-mail
		 *
		 * Callback for "send_password_change_notification" hook in $this->password_change_notification()
		 *
		 * @see $this->password_change_notification()
		 *
		 * @param bool $enable Default setting
		 * @return bool New setting
		 */
		public function send_password_change_notification_filter($enable) {
			// We'll cheat and set our headers here
			$this->set_mail_headers(
			$this->get_option(['reset_pass', 'admin_mail_from']),
			$this->get_option(['reset_pass', 'admin_mail_from_name']),
			$this->get_option(['reset_pass', 'admin_mail_content_type'])
		);

			if ($this->get_option(['reset_pass', 'admin_disable'])) {
				return false;
			}
			return $enable;
		}

		/**
		 * Changes the new user e-mail subject
		 *
		 * Callback for "new_user_notification_title" hook in $this->new_user_notification()
		 *
		 * @see $this->new_user_notification()
		 *
		 * @param string $title Default title
		 * @param int $user_id User ID
		 * @return string New title
		 */
		public function new_user_notification_title_filter($title, $user_id) {
			$_title = $this->get_option(['new_user', 'title']);
			return empty($_title) ? $title : ThemedLogin_Common::replace_vars($_title, $user_id);
		}

		/**
		 * Changes the new user e-mail message
		 *
		 * Callback for "new_user_notification_message" hook in $this->new_user_notification()
		 *
		 * @see $this->new_user_notification()
		 *
		 * @param string $key The user's password reset key
		 * @param int $user_id User ID
		 * @return string New message
		 */
		public function new_user_notification_message_filter($message, $key, $user_id) {
			$_message = $this->get_option(['new_user', 'message']);
			if (! empty($_message)) {
				$user = get_userdata($user_id);
				$message = ThemedLogin_Common::replace_vars($_message, $user_id, [
					'%reseturl%' => network_site_url("wp-login.php?action=rp&key=${key}&login=" . rawurlencode($user->user_login), 'login'),
					'%loginurl%' => site_url('wp-login.php', 'login'),
				]);
			}
			return $message;
		}

		/**
		 * Determines whether or not to send the new user e-mail
		 *
		 * Callback for "send_new_user_notification" hook in $this->new_user_notification()
		 *
		 * @see $this->new_user_notification()
		 *
		 * @param bool $enable Default setting
		 * @return bool New setting
		 */
		public function send_new_user_notification_filter($enable) {
			// We'll cheat and set out headers here
			$this->set_mail_headers(
			$this->get_option(['new_user', 'mail_from']),
			$this->get_option(['new_user', 'mail_from_name']),
			$this->get_option(['new_user', 'mail_content_type'])
		);
			return $enable;
		}

		/**
		 * Changes who the new user admin notification e-mail is sent to
		 *
		 * Callback for "new_user_admin_notification_mail_to" hook in $this->new_user_notification()
		 *
		 * @see $this->new_user_notification()
		 *
		 * @param string $to Default admin e-mail address
		 * @return string New e-mail address(es)
		 */
		public function new_user_admin_notification_mail_to_filter($to) {
			$_to = $this->get_option(['new_user', 'admin_mail_to']);
			return empty($_to) ? $to : $_to;
		}

		/**
		 * Changes the new user admin notification e-mail subject
		 *
		 * Callback for "new_user_admin_notification_title" hook in $this->new_user_notification()
		 *
		 * @see $this->new_user_notification()
		 *
		 * @param string $title Default subject
		 * @param int $user_id User ID
		 * @return string New subject
		 */
		public function new_user_admin_notification_title_filter($title, $user_id) {
			$_title = $this->get_option(['new_user', 'admin_title']);
			return empty($_title) ? $title : ThemedLogin_Common::replace_vars($_title, $user_id);
		}

		/**
		 * Changes the new user admin notification e-mail message
		 *
		 * Callback for "new_user_admin_notification_message" hook in $this->new_user_notification()
		 *
		 * @see $this->new_user_notification()
		 *
		 * @param int $user_id User ID
		 * @return string New message
		 */
		public function new_user_admin_notification_message_filter($message, $user_id) {
			$_message = $this->get_option(['new_user', 'admin_message']);
			return empty($_message) ? $message : ThemedLogin_Common::replace_vars($_message, $user_id);
		}

		/**
		 * Determines whether or not to send the new user admin notification e-mail
		 *
		 * Callback for "send_new_user_admin_notification" hook in $this->new_user_notification()
		 *
		 * @see $this->new_user_notification()
		 *
		 * @param bool $enable Default setting
		 * @return bool New setting
		 */
		public function send_new_user_admin_notification_filter($enable) {
			// We'll cheat and set out headers here
			$this->set_mail_headers(
			$this->get_option(['new_user', 'admin_mail_from']),
			$this->get_option(['new_user', 'admin_mail_from_name']),
			$this->get_option(['new_user', 'admin_mail_content_type'])
		);

			if ($this->get_option(['new_user', 'admin_disable'])) {
				return false;
			}
			return $enable;
		}

		/**
		 * Applies user moderation mail filters according to moderation type
		 *
		 * Callback for "register_post" hook in ThemedLogin::register_new_user()
		 *
		 * @see ThemedLogin::register_new_user()
		 */
		public function apply_user_moderation_notification_filters() {
			if (!class_exists('ThemedLogin_User_Moderation')) {
				return;
			}
			$moderation_type = ThemedLogin_User_Moderation::get_object()->get_option('type');
			switch ($moderation_type) {
			case 'email':
				$this->set_mail_headers(
					$this->get_option(['user_activation', 'mail_from']),
					$this->get_option(['user_activation', 'mail_from_name']),
					$this->get_option(['user_activation', 'mail_content_type'])
				);
				add_filter('user_activation_notification_title', [$this, 'user_activation_notification_title_filter'], 10, 2);
				add_filter('user_activation_notification_message', [$this, 'user_activation_notification_message_filter'], 10, 3);
				break;
			case 'admin':
				$this->set_mail_headers(
					$this->get_option(['user_approval', 'admin_mail_from']),
					$this->get_option(['user_approval', 'admin_mail_from_name']),
					$this->get_option(['user_approval', 'admin_mail_content_type'])
				);
				add_filter('user_approval_admin_notification_mail_to', [$this, 'user_approval_admin_notification_mail_to_filter']);
				add_filter('user_approval_admin_notification_title', [$this, 'user_approval_admin_notification_title_filter'], 10, 2);
				add_filter('user_approval_admin_notification_message', [$this, 'user_approval_admin_notification_message_filter'], 10, 2);
				add_filter('send_new_user_approval_admin_notification', [$this, 'send_new_user_approval_admin_notification_filter']);
			}
		}

		/**
		 * Applies all user approval mail filters
		 *
		 * Callback for "approve_user" hook in ThemedLogin_User_Moderation::approve_user()
		 *
		 * @see ThemedLogin_User_Moderation::approve_user()
		 */
		public function apply_user_approval_notification_filters() {
			$this->set_mail_headers(
				$this->get_option(['user_approval', 'mail_from']),
				$this->get_option(['user_approval', 'mail_from_name']),
				$this->get_option(['user_approval', 'mail_content_type'])
			);
			add_filter('user_approval_notification_title', [$this, 'user_approval_notification_title_filter'], 10, 2);
			add_filter('user_approval_notification_message', [$this, 'user_approval_notification_message_filter'], 10, 3);
		}

		/**
		 * Applies all user denial mail filters
		 *
		 * Callback for "deny_user" hook in ThemedLogin_User_Moderation_Admin::deny_user()
		 *
		 * @see ThemedLogin_User_Moderation_Admin::deny_user()
		 */
		public function apply_user_denial_notification_filters() {
			$this->set_mail_headers(
			$this->get_option(['user_denial', 'mail_from']),
			$this->get_option(['user_denial', 'mail_from_name']),
			$this->get_option(['user_denial', 'mail_content_type'])
		);
			add_filter('user_denial_notification_title', [$this, 'user_denial_notification_title_filter'], 10, 2);
			add_filter('user_denial_notification_message', [$this, 'user_denial_notification_message_filter'], 10, 2);
			add_filter('send_new_user_denial_notification', [$this, 'send_new_user_denial_notification_filter']);
		}

		/**
		 * Changes the user activation e-mail subject
		 *
		 * Callback for "user_activation_notification_title" hook in ThemedLogin_User_Moderation::new_user_activation_notification()
		 *
		 * @see ThemedLogin_User_Moderation::new_user_activation_notification()
		 *
		 * @param string $title The default subject
		 * @param int $user_id The user's ID
		 * @return string The filtered subject
		 */
		public function user_activation_notification_title_filter($title, $user_id) {
			$_title = $this->get_option(['user_activation', 'title']);
			return empty($_title) ? $title : ThemedLogin_Common::replace_vars($_title, $user_id);
		}

		/**
		 * Changes the user activation e-mail message
		 *
		 * Callback for "user_activation_notification_message" hook in ThemedLogin_User_Moderation::new_user_activation_notification()
		 *
		 * @see ThemedLogin_User_Moderation::new_user_activation_notification()
		 *
		 * @param int $user_id The user's ID
		 * @param string $activation_url The activation URL
		 * @return string The filtered message
		 */
		public function user_activation_notification_message_filter($message, $activation_url, $user_id) {
			$_message = $this->get_option(['user_activation', 'message']);
			if (! empty($_message)) {
				$message = ThemedLogin_Common::replace_vars($_message, $user_id, [
					'%activateurl%' => $activation_url,
				]);
			}
			return $message;
		}

		/**
		 * Changes the user approval e-mail subject
		 *
		 * Callback for "user_approval_notification_title" hook in ThemedLogin_User_Moderation_Admin::approve_user()
		 *
		 * @see ThemedLogin_User_Moderation_Admin::approve_user()
		 *
		 * @param string $title The default subject
		 * @param int $user_id The user's ID
		 * @return string The filtered subject
		 */
		public function user_approval_notification_title_filter($title, $user_id) {
			$_title = $this->get_option(['user_approval', 'title']);
			return empty($_title) ? $title : ThemedLogin_Common::replace_vars($_title, $user_id);
		}

		/**
		 * Changes the user approval e-mail message
		 *
		 * Callback for "user_approval_notification_message" hook in ThemedLogin_User_Moderation_Admin::approve_user()
		 *
		 * @see ThemedLogin_User_Moderation_Admin::approve_user()
		 *
		 * @param string $key The user's reset key
		 * @param int $user_id The user's ID
		 * @return string The filtered message
		 */
		public function user_approval_notification_message_filter($message, $key, $user_id) {
			$_message = $this->get_option(['user_approval', 'message']);
			if (! empty($_message)) {
				$user = get_user_by('id', $user_id);
				$message = ThemedLogin_Common::replace_vars($_message, $user_id, [
					'%loginurl%' => ThemedLogin::get_page_link('login'),
					'%reseturl%' => site_url("wp-login.php?action=rp&key=${key}&login=" . rawurlencode($user->user_login), 'login'),
				]);
			}
			return $message;
		}

		/**
		 * Changes the user approval admin e-mail recipient
		 *
		 * Callback for "user_approval_admin_notification_mail_to" hook in ThemedLogin_User_Moderation::new_user_approval_admin_notification()
		 *
		 * @see ThemedLogin_User_Moderation::new_user_approval_admin_notification()
		 *
		 * @param string $to The default recipient
		 * @return string The filtered recipient
		 */
		public function user_approval_admin_notification_mail_to_filter($to) {
			$_to = $this->get_option(['user_approval', 'admin_mail_to']);
			return empty($_to) ? $to : $_to;
		}

		/**
		 * Changes the user approval admin e-mail subject
		 *
		 * Callback for "user_approval_admin_notification_title" hook in ThemedLogin_User_Moderation::new_user_approval_admin_notification()
		 *
		 * @see ThemedLogin_User_Moderation::new_user_approval_admin_notification()
		 *
		 * @param string $title The default subject
		 * @param int $user_id The user's ID
		 * @return string The filtered subject
		 */
		public function user_approval_admin_notification_title_filter($title, $user_id) {
			$_title = $this->get_option(['user_approval', 'admin_title']);
			return empty($_title) ? $title : ThemedLogin_Common::replace_vars($_title, $user_id);
		}

		/**
		 * Changes the user approval admin e-mail message
		 *
		 * Callback for "user_approval_admin_notification_message" hook in ThemedLogin_User_Moderation::new_user_approval_admin_notification()
		 *
		 * @see ThemedLogin_User_Moderation::new_user_approval_admin_notification()
		 *
		 * @param string $message The default message
		 * @param int $user_id The user's ID
		 * @return string The filtered message
		 */
		public function user_approval_admin_notification_message_filter($message, $user_id) {
			$_message = $this->get_option(['user_approval', 'admin_message']);
			if (! empty($_message)) {
				$message = ThemedLogin_Common::replace_vars($_message, $user_id, [
					'%pendingurl%' => admin_url('users.php?role=pending'),
				]);
			}
			return $message;
		}

		/**
		 * Determines whether or not to send the new user admin approval notification e-mail
		 *
		 * Callback for "send_new_user_approval_admin_notification" hook
		 *
		 * @param bool $enable Default setting
		 * @return bool New setting
		 */
		public function send_new_user_approval_admin_notification_filter($enable) {
			if ($this->get_option(['user_approval', 'admin_disable'])) {
				return false;
			}
			return $enable;
		}

		/**
		 * Changes the user denial e-mail subject
		 *
		 * Callback for "user_denial_notification_title" hook in ThemedLogin_User_Moderation_Admin::deny_user()
		 *
		 * @see ThemedLogin_User_Moderation_Admin::deny_user()
		 *
		 * @param string $title The default subject
		 * @param int $user_id The user's ID
		 * @return string The filtered subject
		 */
		public function user_denial_notification_title_filter($title, $user_id) {
			$_title = $this->get_option(['user_denial', 'title']);
			return empty($_title) ? $title : ThemedLogin_Common::replace_vars($_title, $user_id);
		}

		/**
		 * Changes the user denial e-mail message
		 *
		 * Callback for "user_denial_notification_message" hook in ThemedLogin_User_Moderation_Admin::deny_user()
		 *
		 * @see ThemedLogin_User_Moderation_Admin::deny_user()
		 *
		 * @param string $message The default message
		 * @param int $user_id The user's ID
		 * @return string The filtered message
		 */
		public function user_denial_notification_message_filter($message, $user_id) {
			$_message = $this->get_option(['user_denial', 'message']);
			return empty($_message) ? $message : ThemedLogin_Common::replace_vars($_message, $user_id);
		}

		/**
		 * Determines whether or not to send the new user denial notification e-mail
		 *
		 * @param bool $enable Default setting
		 * @return bool New setting
		 */
		public function send_new_user_denial_notification_filter($enable) {
			if ($this->get_option(['user_denial', 'disable'])) {
				return false;
			}
			return $enable;
		}

		/**
		 * Notify the blog admin of a new user
		 *
		 * @param int $user_id User ID
		 * @param string $notify Type of notification that should happen
		 */
		public function new_user_notification($user_id, $notify = 'both') {
			global $wpdb;

			$user = get_userdata($user_id);

			do_action('tml_new_user_notification', $user_id, $notify);

			// The blogname option is escaped with esc_html on the way into the database in sanitize_option
			// we want to reverse this for the plain text arena of emails.
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

			if (apply_filters('send_new_user_admin_notification', true)) {
				$message  = sprintf(__('New user registration on your site %s:', 'themed-login'), $blogname) . "\r\n\r\n";
				$message .= sprintf(__('Username: %s', 'themed-login'), $user->user_login) . "\r\n\r\n";
				$message .= sprintf(__('E-mail: %s', 'themed-login'), $user->user_email) . "\r\n";

				$title    = sprintf(__('[%s] New User Registration', 'themed-login'), $blogname);

				$title    = apply_filters('new_user_admin_notification_title', $title, $user_id);
				$message  = apply_filters('new_user_admin_notification_message', $message, $user_id);

				$to       = apply_filters('new_user_admin_notification_mail_to', get_option('admin_email'));

				@wp_mail($to, $title, $message);
			}

			if ('admin' == $notify || empty($notify)) {
				return;
			}
			// Generate something random for a password reset key
			$key = wp_generate_password(20, false);

			do_action('retrieve_password_key', $user->user_login, $key);

			// Now insert the key, hashed, into the DB
			require_once ABSPATH . WPINC . '/class-phpass.php';
			$wp_hasher = new PasswordHash(8, true);

			$hashed = time() . ':' . $wp_hasher->HashPassword($key);
			$wpdb->update($wpdb->users, ['user_activation_key' => $hashed], ['user_login' => $user->user_login]);

			if (apply_filters('send_new_user_notification', true)) {
				$message  = sprintf(__('Username: %s', 'themed-login'), $user->user_login) . "\r\n\r\n";
				$message .= __('To set your password, visit the following address:', 'themed-login') . "\r\n\r\n";
				$message .= '<' . network_site_url("wp-login.php?action=rp&key=${key}&login=" . rawurlencode($user->user_login), 'login') . ">\r\n\r\n";

				$message .= wp_login_url() . "\r\n";

				$title = sprintf(__('[%s] Your username and password info', 'themed-login'), $blogname);

				$title   = apply_filters('new_user_notification_title', $title, $user_id);
				$message = apply_filters('new_user_notification_message', $message, $key, $user_id);

				wp_mail($user->user_email, $title, $message);
			}
		}

		/**
		 * Notify the blog admin of a user changing password
		 *
		 * @param object $user User object
		 */
		public function password_change_notification($user) {
			$to = apply_filters('password_change_notification_mail_to', get_option('admin_email'));
			// send a copy of password change notification to the admin
			// but check to see if it's the admin whose password we're changing, and skip this
			if ($user->user_email != $to && apply_filters('send_password_change_notification', true)) {
				// The blogname option is escaped with esc_html on the way into the database in sanitize_option
				// we want to reverse this for the plain text arena of emails.
				$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

				$title   = sprintf(__('[%s] Password Lost/Changed', 'themed-login'), $blogname);
				$message = sprintf(__('Password Lost and Changed for user: %s', 'themed-login'), $user->user_login) . "\r\n";

				$title   = apply_filters('password_change_notification_title', $title, $user->ID);
				$message = apply_filters('password_change_notification_message', $message, $user->ID);

				wp_mail($to, $title, $message);
			}
		}

		/**
		 * Modify PHPMailer settings to supply a plaintext alternate body if sending HTML.
		 *
		 * @param PHPMailer $phpmailer PHPMailer object.
		 */
		public function phpmailer_init($phpmailer) {
			if ($phpmailer->ContentType === 'text/html' && empty($phpmailer->AltBody)) {
				$phpmailer->AltBody = wp_strip_all_tags($phpmailer->Body);
			}
		}

		/**
		 * Loads the module
		 */
		protected function load() {
			add_filter('wp_mail_from', [$this, 'mail_from_filter']);
			add_filter('wp_mail_from_name', [$this, 'mail_from_name_filter']);
			add_filter('wp_mail_content_type', [$this, 'mail_content_type_filter']);

			add_action('retrieve_password', [$this, 'apply_retrieve_pass_filters']);
			add_action('password_reset', [$this, 'apply_password_reset_filters']);
			add_action('tml_new_user_notification', [$this, 'apply_new_user_filters']);

			remove_action('register_new_user', 'wp_send_new_user_notifications');
			remove_action('edit_user_created_user', 'wp_send_new_user_notifications', 10);
			remove_action('after_password_reset', 'wp_password_change_notification');

			add_action('register_new_user', [$this, 'new_user_notification']);
			add_action('edit_user_created_user', [$this, 'new_user_notification'], 10, 2);
			add_action('after_password_reset', [$this, 'password_change_notification']);

			add_action('register_post', [$this, 'apply_user_moderation_notification_filters']);
			add_action('tml_user_activation_resend', [$this, 'apply_user_moderation_notification_filters']);
			add_action('approve_user', [$this, 'apply_user_approval_notification_filters']);
			add_action('deny_user', [$this, 'apply_user_denial_notification_filters']);

			add_action('phpmailer_init', [$this, 'phpmailer_init']);
		}
	}

	new ThemedLogin_Custom_Email();

}

if (is_admin()) {
	include_once(__DIR__ . '/admin/custom-email-admin.php');
}
