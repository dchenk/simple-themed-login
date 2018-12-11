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
			$options = [
				'classname'   => 'widget_themed_login',
				'description' => __('A login form for your site.', 'themed-login'),
			];
			parent::__construct('themed-login', __('Themed Login', 'themed-login'), $options);
		}

		/**
		 * Displays the widget
		 *
		 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
		 * @param array $instance The settings for the particular instance of the widget
		 */
		public function widget($args, $instance) {
			global $themedLoginInstance;
//			error_log('args: ' . print_r($instance, true));

			$instance = wp_parse_args($instance, [
				'default_action'    => 'login',
				'logged_in_widget'  => true,
				'logged_out_widget' => true,
				'show_title'        => true,
				'show_log_link'     => true,
				'show_reg_link'     => true,
				'show_pass_link'    => true,
				'show_gravatar'     => true,
				'gravatar_size'     => 50,
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
			]; ?>

			<p>
				<label for="<?php echo $this->get_field_id('default_action'); ?>"><?php _e('Action', 'themed-login'); ?></label>
				<select name="<?php echo $this->get_field_name('default_action'); ?>" id="<?php echo $this->get_field_id('default_action'); ?>"><?php
				foreach ($actions as $action => $title) {
					?>
					<option value="<?php echo $action; ?>"<?php selected($instance['default_action'], $action); ?>><?php echo $title; ?></option><?php
				} ?>
				</select>
			</p>
			<p>
				<input type="checkbox" name="<?php echo $this->get_field_name('logged_in_widget'); ?>"
					id="<?php echo $this->get_field_id('logged_in_widget'); ?>" value="1"<?php checked(!empty($instance['logged_in_widget'])); ?>>
				<label for="<?php echo $this->get_field_id('logged_in_widget'); ?>"><?php _e('Show When Logged In', 'themed-login'); ?></label>
			</p>
			<p>
				<input type="checkbox" name="<?php echo $this->get_field_name('logged_out_widget'); ?>"
					id="<?php echo $this->get_field_id('logged_out_widget'); ?>" value="1"<?php checked(!empty($instance['logged_out_widget'])); ?>>
				<label for="<?php echo $this->get_field_id('logged_out_widget'); ?>"><?php _e('Show When Logged Out', 'themed-login'); ?></label>
			</p>
			<p>
				<input type="checkbox" name="<?php echo $this->get_field_name('show_title'); ?>"
					id="<?php echo $this->get_field_id('show_title'); ?>" value="1"<?php checked(!empty($instance['show_title'])); ?>>
				<label for="<?php echo $this->get_field_id('show_title'); ?>"><?php _e('Show Title', 'themed-login'); ?></label>
			</p>
			<p>
				<input type="checkbox" name="<?php echo $this->get_field_name('show_log_link'); ?>"
					id="<?php echo $this->get_field_id('show_log_link'); ?>" value="1"<?php checked(!empty($instance['show_log_link'])); ?>>
				<label for="<?php echo $this->get_field_id('show_log_link'); ?>"><?php _e('Show Login Link', 'themed-login'); ?></label>
			</p>
			<p>
				<input type="checkbox" name="<?php echo $this->get_field_name('show_reg_link'); ?>"
					id="<?php echo $this->get_field_id('show_reg_link'); ?>" value="1"<?php checked(!empty($instance['show_reg_link'])); ?>>
				<label for="<?php echo $this->get_field_id('show_reg_link'); ?>"><?php _e('Show Register Link', 'themed-login'); ?></label>
			</p>
			<p>
				<input type="checkbox" name="<?php echo $this->get_field_name('show_pass_link'); ?>"
					id="<?php echo $this->get_field_id('show_pass_link'); ?>" value="1"<?php checked(!empty($instance['show_pass_link'])); ?>>
				<label for="<?php echo $this->get_field_id('show_pass_link'); ?>"><?php _e('Show Lost Password Link', 'themed-login'); ?></label>
			</p>
			<p>
				<input type="checkbox" name="<?php echo $this->get_field_name('show_gravatar'); ?>"
					id="<?php echo $this->get_field_id('show_gravatar'); ?>" value="1"<?php checked(!empty($instance['show_gravatar'])); ?>>
				<label for="<?php echo $this->get_field_id('show_gravatar'); ?>"><?php _e('Show Gravatar', 'themed-login'); ?></label>
			</p>
			<p>
				<?php _e('Gravatar Size', 'themed-login'); ?>:
				<input type="number" name="<?php echo $this->get_field_name('gravatar_size'); ?>"
					id="<?php echo $this->get_field_id('gravatar_size'); ?>" value="<?php echo $instance['gravatar_size']; ?>" size="3">
				<label for="<?php echo $this->get_field_id('gravatar_size'); ?>"></label>
			</p>
			<?php
		}
	}

}
