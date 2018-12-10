<?php
/**
 * Holds the Themed Login widget class
 *
 * @package ThemedLogin
 */

if (!class_exists('ThemedLogin_Widget')) {

	/**
	 * Themed Login widget class
	 */
	class ThemedLogin_Widget extends WP_Widget {
		public function __construct() {
			$widget_options = [
				'classname'   => 'widget_theme_my_login',
				'description' => __('A login form for your blog.', 'themed-login'),
			];
			parent::__construct('simple-themed-login', __('Simple Themed Login', 'themed-login'), $widget_options);
		}

		/**
		 * Displays the widget
		 *
		 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
		 * @param array $instance The settings for the particular instance of the widget
		 */
		public function widget($args, $instance) {
			global $themedLoginInstance;

			$instance = wp_parse_args($instance, [
				'default_action'      => 'login',
				'logged_in_widget'    => true,
				'logged_out_widget'   => true,
				'show_title'          => true,
				'show_log_link'       => true,
				'show_reg_link'       => true,
				'show_pass_link'      => true,
				'show_gravatar'       => true,
				'gravatar_size'       => 50,
			]);

			// Show if logged in
			if (is_user_logged_in() && !$instance['logged_in_widget']) {
				return;
			}
			// Show if logged out
			if (!is_user_logged_in() && !$instance['logged_out_widget']) {
				return;
			}
			$args = array_merge($args, $instance);

			echo $themedLoginInstance->shortcode($args);
		}

		/**
		 * Updates the widget
		 *
		 * @param array $new_instance New settings for this instance as input by the user.
		 * @param array $old_instance Old settings for this instance.
		 * @return array Settings to save or bool false to cancel saving.
		 */
		public function update($new_instance, $old_instance) {
			$instance = $old_instance;
			$instance['default_action']      = in_array($new_instance['default_action'], ['login', 'register', 'lostpassword'], true) ?
				$new_instance['default_action'] :
				'login';
			$instance['logged_in_widget']    = !empty($new_instance['logged_in_widget']);
			$instance['logged_out_widget']   = !empty($new_instance['logged_out_widget']);
			$instance['show_title']          = !empty($new_instance['show_title']);
			$instance['show_log_link']       = !empty($new_instance['show_log_link']);
			$instance['show_reg_link']       = !empty($new_instance['show_reg_link']);
			$instance['show_pass_link']      = !empty($new_instance['show_pass_link']);
			$instance['show_gravatar']       = !empty($new_instance['show_gravatar']);
			$instance['gravatar_size']       = absint($new_instance['gravatar_size']);
			return $instance;
		}

		/**
		 * Displays the widget admin form
		 *
		 * @param array $instance Current settings.
		 */
		public function form($instance) {
			$defaults = [
				'default_action'      => 'login',
				'logged_in_widget'    => 1,
				'logged_out_widget'   => 1,
				'show_title'          => 1,
				'show_log_link'       => 1,
				'show_reg_link'       => 1,
				'show_pass_link'      => 1,
				'show_gravatar'       => 1,
				'gravatar_size'       => 50,
				'register_widget'     => 1,
				'lostpassword_widget' => 1,
			];
			$instance = wp_parse_args($instance, $defaults);

			$actions = [
				'login'        => __('Login', 'themed-login'),
				'register'     => __('Register', 'themed-login'),
				'lostpassword' => __('Lost Password', 'themed-login'),
			];

			echo '<p>' . __('Default Action', 'themed-login') . '<br><select name="' . $this->get_field_name('default_action') . '" id="' . $this->get_field_id('default_action') . '">';
			foreach ($actions as $action => $title) {
				?>
				<option value="<?php echo $action; ?>"<?php selected($instance['default_action'], $action); ?>><?php echo $title; ?></option><?php
			}
			echo '</select></p>';

			$is_checked = (empty($instance['logged_in_widget'])) ? '' : 'checked="checked" ';
			echo '<p><input name="' . $this->get_field_name('logged_in_widget') . '" type="checkbox" id="' . $this->get_field_id('logged_in_widget') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('logged_in_widget') . '">' . __('Show When Logged In', 'themed-login') . '</label></p>';

			$is_checked = (empty($instance['logged_out_widget'])) ? '' : 'checked="checked" ';
			echo '<p><input name="' . $this->get_field_name('logged_out_widget') . '" type="checkbox" id="' . $this->get_field_id('logged_out_widget') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('logged_out_widget') . '">' . __('Show When Logged Out', 'themed-login') . '</label></p>';

			$is_checked = (empty($instance['show_title'])) ? '' : 'checked="checked" ';
			echo '<p><input name="' . $this->get_field_name('show_title') . '" type="checkbox" id="' . $this->get_field_id('show_title') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('show_title') . '">' . __('Show Title', 'themed-login') . '</label></p>';

			$is_checked = (empty($instance['show_log_link'])) ? '' : 'checked="checked" ';
			echo '<p><input name="' . $this->get_field_name('show_log_link') . '" type="checkbox" id="' . $this->get_field_id('show_log_link') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('show_log_link') . '">' . __('Show Login Link', 'themed-login') . '</label></p>';

			$is_checked = (empty($instance['show_reg_link'])) ? '' : 'checked="checked" ';
			echo '<p><input name="' . $this->get_field_name('show_reg_link') . '" type="checkbox" id="' . $this->get_field_id('show_reg_link') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('show_reg_link') . '">' . __('Show Register Link', 'themed-login') . '</label></p>';

			$is_checked = (empty($instance['show_pass_link'])) ? '' : 'checked="checked" ';
			echo '<p><input name="' . $this->get_field_name('show_pass_link') . '" type="checkbox" id="' . $this->get_field_id('show_pass_link') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('show_pass_link') . '">' . __('Show Lost Password Link', 'themed-login') . '</label></p>';

			$is_checked = (empty($instance['show_gravatar'])) ? '' : 'checked="checked" ';
			echo '<p><input name="' . $this->get_field_name('show_gravatar') . '" type="checkbox" id="' . $this->get_field_id('show_gravatar') . '" value="1" ' . $is_checked . '/> <label for="' . $this->get_field_id('show_gravatar') . '">' . __('Show Gravatar', 'themed-login') . '</label></p>';

			echo '<p>' . __('Gravatar Size', 'themed-login') . ': <input name="' . $this->get_field_name('gravatar_size') . '" type="text" id="' . $this->get_field_id('gravatar_size') . '" value="' . $instance['gravatar_size'] . '" size="3"> <label for="' . $this->get_field_id('gravatar_size') . '"></label></p>';
		}
	}

}
