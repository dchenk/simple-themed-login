<?php
/**
 * Holds the Theme My Login Admin class
 *
 * @package Theme_My_Login
 * @since 6.0
 */

if ( ! class_exists( 'Theme_My_Login_Admin' ) ) :
/**
 * Theme My Login Admin class
 *
 * @since 6.0
 */
class Theme_My_Login_Admin extends Theme_My_Login_Abstract {
	/**
	 * Holds options key
	 *
	 * @var string
	 */
	protected $options_key = 'theme_my_login';

	/**
	 * Returns singleton instance
	 *
	 * @return Theme_My_Login
	 */
	public static function get_object( $class = null ) {
		return parent::get_object( __CLASS__ );
	}

	/**
	 * Returns default options
	 *
	 */
	public static function default_options() {
		return Theme_My_Login::default_options();
	}

	/**
	 * Loads object
	 *
	 */
	protected function load() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 8 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 11 );

		add_action( 'admin_notices',              array( $this, 'admin_notices'       ) );
		add_action( 'wp_ajax_tml-dismiss-notice', array( $this, 'ajax_dismiss_notice' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes'       ) );
		add_action( 'save_post',      array( $this, 'save_action_meta_box' ) );

		if ( ! $this->get_option( 'allow_update' ) ) {
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'pre_set_site_transient_update_plugins' ) );
		}
		add_action( 'upgrader_pre_install', array( $this, 'upgrader_pre_install' ), 0, 2 );

		register_uninstall_hook( SIMPLE_THEMED_LOGIN_PATH . '/theme-my-login.php', array( 'Theme_My_Login_Admin', 'uninstall' ) );
	}

	/**
	 * Builds plugin admin menu and pages
	 */
	public function admin_menu() {
		add_menu_page(
			__( 'Simple Themed Login Settings', 'simple-themed-login' ),
			'STL',
			'manage_options',
			'theme_my_login',
			['Theme_My_Login_Admin', 'settings_page']
		);

		add_submenu_page(
			'theme_my_login',
			__( 'General', 'simple-themed-login' ),
			__( 'General', 'simple-themed-login' ),
			'manage_options',
			'theme_my_login',
			['Theme_My_Login_Admin', 'settings_page']
		);
	}

	/**
	 * Registers TML settings
	 * This is used because register_setting() isn't available until the "admin_init" hook.
	 */
	public function admin_init() {

		// Register setting
		register_setting( 'theme_my_login', 'theme_my_login',  array( $this, 'save_settings' ) );

		// Install/Upgrade
		if ( version_compare( $this->get_option( 'version', 0 ), Theme_My_Login::VERSION, '<' ) )
			$this->install();

		// Add sections
		add_settings_section( 'general', __( 'General', 'simple-themed-login' ), '__return_false',                          $this->options_key );
		add_settings_section( 'modules', __( 'Modules', 'simple-themed-login' ), '__return_false',                          $this->options_key );

		// Add fields
		add_settings_field( 'enable_css', __( 'Stylesheet', 'simple-themed-login' ), array( $this, 'settings_field_enable_css' ), $this->options_key, 'general' );
		add_settings_field( 'login_type', __( 'Login Type', 'simple-themed-login' ), array( $this, 'settings_field_login_type' ), $this->options_key, 'general' );
		add_settings_field( 'modules',    __( 'Modules',    'simple-themed-login' ), array( $this, 'settings_field_modules'    ), $this->options_key, 'modules' );
		add_settings_field( 'update',     __( 'Update',     'simple-themed-login' ), array( $this, 'settings_field_update'     ), $this->options_key, 'update'  );
	}

	/**
	 * Enqueues TML scripts
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'theme-my-login-admin', plugins_url( 'theme-my-login-admin.js', __FILE__ ), array( 'jquery' ), Theme_My_Login::VERSION, true );
		wp_localize_script( 'theme-my-login-admin', 'tmlAdmin', array(
			'interim_login_url' => site_url( 'wp-login.php?interim-login=1', 'login' )
		) );
	}

	/**
	 * Print admin notices.
	 */
	public function admin_notices() {
		if ( !current_user_can('manage_options') ) {
			return;
		}
        // Potentially useful function stub here.
	}

	/**
	 * Handle saving of notice dismissals.
	 */
	public function ajax_dismiss_notice() {
		if ( empty($_POST['notice']) ) {
			return;
		}

		$dismissed_notices = $this->get_option( 'dismissed_notices', array() );
		$dismissed_notices[] = sanitize_key( $_POST['notice'] );

		$this->set_option( 'dismissed_notices', $dismissed_notices );
		$this->save_options();

		wp_send_json_success();
	}

	/**
	 * Adds the TML Action meta box.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'tml_action',
			__( 'Theme My Login Action', 'simple-themed-login' ),
			array( $this, 'action_meta_box' ),
			'page',
			'side'
		);
	}

	/**
	 * Renders the TML Action meta box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function action_meta_box( $post ) {
		$page_action = get_post_meta( $post->ID, '_tml_action', true );
		?>

		<select name="tml_action" id="tml_action">
			<option value=""></option>
			<?php foreach ( Theme_My_Login::default_pages() as $action => $label ) : ?>
				<option value="<?php echo esc_attr( $action ); ?>"<?php selected( $action, $page_action ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>

		<?php
	}

	/**
	 * Saves the TML Action meta box.
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_action_meta_box( $post_id ) {
		if ( 'page' != get_post_type( $post_id ) ) {
			return;
		}

		if ( isset( $_POST['tml_action'] ) ) {
			$tml_action = sanitize_key( $_POST['tml_action'] );
			if ( ! empty( $_POST['tml_action'] ) ) {
				update_post_meta( $post_id, '_tml_action', $tml_action );
			} else {
				if ( false !== get_post_meta( $post_id, '_tml_action', true ) ) {
					delete_post_meta( $post_id, '_tml_action' );
				}
			}
		}
	}

	/**
	 * Renders the settings page
	 */
	public static function settings_page( $args = '' ) {
		extract( wp_parse_args( $args, array(
			'title'       => 'Simple Themed Login Settings',
			'options_key' => 'theme_my_login'
		) ) );
		?>
		<div id="<?php echo $options_key; ?>" class="wrap">
			<h2><?php echo esc_html( $title ); ?></h2>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( $options_key );
					do_settings_sections( $options_key );
					submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders Stylesheet settings field
	 */
	public function settings_field_enable_css() {
		?>
		<input name="theme_my_login[enable_css]" type="checkbox" id="theme_my_login_enable_css" value="1"<?php checked( 1, $this->get_option( 'enable_css' ) ); ?>>
		<label for="theme_my_login_enable_css"><?php _e( 'Enable "theme-my-login.css"', 'simple-themed-login' ); ?></label>
		<p class="description"><?php _e( 'In order to keep changes between upgrades, you can store your customized "theme-my-login.css" in your current theme directory.', 'simple-themed-login' ); ?></p>
		<?php
	}

	/**
	 * Renders Login Type settings field
	 */
	public function settings_field_login_type() {
		?>

		<ul>

			<li><input name="theme_my_login[login_type]" type="radio" id="theme_my_login_login_type_default" value="default"<?php checked( 'default', $this->get_option( 'login_type' ) ); ?>>
			<label for="theme_my_login_login_type_default"><?php _e( 'Username or E-mail', 'simple-themed-login' ); ?></label></li>

			<li><input name="theme_my_login[login_type]" type="radio" id="theme_my_login_login_type_username" value="username"<?php checked( 'username', $this->get_option( 'login_type' ) ); ?>>
			<label for="theme_my_login_login_type_username"><?php _e( 'Username only', 'simple-themed-login' ); ?></label></li>

			<li><input name="theme_my_login[login_type]" type="radio" id="theme_my_login_login_type_email" value="email"<?php checked( 'email', $this->get_option( 'login_type' ) ); ?>>
			<label for="theme_my_login_login_type_email"><?php _e( 'E-mail only', 'simple-themed-login' ); ?></label></li>

		</ul>

		<p class="description"><?php _e( 'Allow users to login using their username and/or e-mail address.', 'simple-themed-login' ); ?></p>

		<?php
	}

	/**
	 * Renders Modules settings field
	 *
	 */
	public function settings_field_modules() {
		foreach ( get_plugins( sprintf( '/%s/modules', plugin_basename( SIMPLE_THEMED_LOGIN_PATH ) ) ) as $path => $data ) {
			$id = sanitize_key( $data['Name'] );
		?>
		<input name="theme_my_login[active_modules][]" type="checkbox" id="theme_my_login_active_modules_<?php echo $id; ?>" value="<?php echo $path; ?>"<?php checked( in_array( $path, (array) $this->get_option( 'active_modules' ) ) ); ?>>
		<label for="theme_my_login_active_modules_<?php echo $id; ?>"><?php printf( __( 'Enable %s', 'simple-themed-login' ), $data['Name'] ); ?></label><br>
		<?php if ( $data['Description'] ) : ?>
		<p class="description"><?php echo $data['Description']; ?></p>
		<?php endif;
		}
	}

	/**
	 * Renders Update settings field.
	 *
	 */
	public function settings_field_update() {
		?>
		<p>
			<input name="theme_my_login[allow_update]" type="radio" id="theme_my_login_allow_update_on" value="1"<?php checked( (bool) $this->get_option('allow_update') ); ?>>
			<label for="theme_my_login_allow_update_on"><?php _e( 'I understand the possible consequences, but I want the latest features and wish to allow the update', 'simple-themed-login' ); ?></label>
		</p>
		<p>
			<input name="theme_my_login[allow_update]" type="radio" id="theme_my_login_allow_update_off" value="0"<?php checked( !$this->get_option('allow_update') ); ?>>
			<label for="theme_my_login_allow_update_off"><?php _e( 'I understand that I will no longer receive any new features but I would like to stay on the 6.4 branch anyway', 'simple-themed-login' ); ?></label>
		</p>
		<?php
	}

	/**
	 * Sanitizes TML settings
	 *
	 * This is the callback for register_setting()
	 *
	 * @param string|array $settings Settings passed in from filter
	 * @return string|array Sanitized settings
	 */
	public function save_settings( $settings ) {
		$settings['enable_css']     = ! empty( $settings['enable_css'] );
		$settings['login_type']     = in_array( $settings['login_type'], array( 'default', 'username', 'email' ) ) ? $settings['login_type'] : 'default';
		$settings['active_modules'] = isset( $settings['active_modules'] ) ? (array) $settings['active_modules'] : array();
		$settings['allow_update']   = ! empty( $settings['allow_update'] );

		// If we have modules to activate
		if ( $activate = array_diff( $settings['active_modules'], $this->get_option( 'active_modules', array() ) ) ) {
			foreach ( $activate as $module ) {
				if ( file_exists( SIMPLE_THEMED_LOGIN_PATH . '/modules/' . $module ) )
					include_once( SIMPLE_THEMED_LOGIN_PATH . '/modules/' . $module );
				do_action( 'tml_activate_' . $module );
			}
		}

		// If we have modules to deactivate
		if ( $deactivate = array_diff( $this->get_option( 'active_modules', array() ), $settings['active_modules'] ) ) {
			foreach ( $deactivate as $module ) {
				do_action( 'tml_deactivate_' . $module );
			}
		}

		$settings = wp_parse_args( $settings, $this->get_options() );

		return $settings;
	}

	/**
	 * Give those who opt to stay on the 6.4 branch updates.
	 *
	 *
	 * @param object $transient The transient data.
	 * @return object The transient data.
	 */
	public function pre_set_site_transient_update_plugins( $transient = '' ) {
		$basename = 'theme-my-login/theme-my-login.php';

		if ( ! is_object( $transient ) ) {
			$transient = new stdClass;
		}

		if ( ! isset( $transient->response ) || ! isset( $transient->no_update ) ) {
			return $transient;
		}

		if ( is_array( $transient->response ) && isset( $transient->response[ $basename ] ) ) {
			$plugin_data = $transient->response[ $basename ];
			unset( $transient->response[ $basename ] );
		} elseif ( is_array( $transient->no_update ) && isset( $transient->no_update[ $basename ] ) ) {
			$plugin_data = $transient->no_update[ $basename ];
			unset( $transient->no_update[ $basename ] );
		} else {
			return $transient;
		}

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$plugin_info = plugins_api( 'plugin_information', array( 'slug' => 'simple-themed-login' ) );
		if ( is_wp_error( $plugin_info ) ) {
			return $transient;
		}

		uksort( $plugin_info->versions, 'version_compare' );

		// Find the latest 6.4 version
		foreach ( array_reverse( $plugin_info->versions ) as $version => $file ) {
			if ( strpos( $version, '6.4' ) === 0 ) {
				$plugin_data->new_version = $version;
				$plugin_data->package = $file;
				break;
			}
		}

		// This is an update
		if ( version_compare( Theme_My_Login::VERSION, $plugin_data->new_version, '<' ) ) {
			$transient->response[ $basename ] = $plugin_data;

		// This is just fetching the plugin information
		} else {
			$transient->no_update[ $basename ] = $plugin_data;
		}

		$transient->last_checked = time();

		return $transient;
	}

	/**
	 * Disable upgrading to 7.0+ unless explicitly allowed.
	 *
	 *
	 * @param bool|WP_Error $response Whether to allow the install or not.
	 * @param array         $args     Extra arguments passed to the hook.
	 * @return bool|WP_Error
	 */
	public function upgrader_pre_install( $response, $args ) {
		// Bail if we're not upgrading a plugin
		if ( empty( $args['plugin'] ) ) {
			return $response;
		}

		$basename = plugin_basename( SIMPLE_THEMED_LOGIN_PATH . '/theme-my-login.php' );

		// Bal if we're not upgrading TML
		if ( $basename != $args['plugin'] ) {
			return $response;
		}

		$plugins = get_site_transient( 'update_plugins' );

		// Bail if we're not upgrading to 7.0+
		if ( version_compare( $plugins->response[ $basename ]->new_version, '7.0', '<' ) ) {
			return $response;
		}

		// Bail if the update has been allowed
		if ( $this->get_option( 'allow_update' ) ) {
			return $response;
		}

		return new WP_Error( 'update_denied', sprintf(
			__( 'Theme My Login has not been updated because you have not allowed the update on the <a href="%s" target="_top">settings page</a>.', 'simple-themed-login' ),
			admin_url( 'admin.php?page=theme_my_login' )
		) );
	}

	public function install() {
		global $wpdb;

		// Current version
		$version = $this->get_option( 'version', Theme_My_Login::VERSION );

		// Check if legacy page exists
		if ( $page_id = $this->get_option( 'page_id' ) ) {
			$page = get_post( $page_id );
		} else {
			$page = get_page_by_title( 'Login' );
		}

		// 6.3 upgrade
		if ( version_compare( $version, '6.3.3', '<' ) ) {
			// Delete obsolete options
			$this->delete_option( 'page_id'     );
			$this->delete_option( 'show_page'   );
			$this->delete_option( 'initial_nag' );
			$this->delete_option( 'permalinks'  );
			$this->delete_option( 'flush_rules' );

			// Move options to their own rows
			foreach ( $this->get_options() as $key => $value ) {
				if ( in_array( $key, array( 'active_modules' ) ) )
					continue;

				if ( ! is_array( $value ) )
					continue;

				update_option( "theme_my_login_{$key}", $value );

				$this->delete_option( $key );
			}

			// Maybe create login page?
			if ( $page ) {
				// Make sure the page is not in the trash
				if ( 'trash' == $page->post_status )
					wp_untrash_post( $page->ID );

				update_post_meta( $page->ID, '_tml_action', 'login' );
			}
		}

		// 6.3.7 upgrade
		if ( version_compare( $version, '6.3.7', '<' ) ) {
			// Convert TML pages to regular pages
			$wpdb->update( $wpdb->posts, array( 'post_type' => 'page' ), array( 'post_type' => 'tml_page' ) );

			// Get rid of stale rewrite rules
			flush_rewrite_rules( false );
		}

		// 6.4 upgrade
		if ( version_compare( $version, '6.4', '<' ) ) {
			// Convert e-mail login option
			if ( $this->get_option( 'email_login' ) )
				$this->set_option( 'login_type', 'both' );
			$this->delete_option( 'email_login' );
		}

		// 6.4.5 upgrade
		if ( version_compare( $version, '6.4.5', '<' ) ) {
			// Convert login type option
			$login_type = $this->get_option( 'login_type' );
			if ( 'both' == $login_type ) {
				$this->set_option( 'login_type', 'default' );
			} elseif ( 'default' == $login_type ) {
				$this->set_option( 'login_type', 'username' );
			}
		}

		// Setup default pages
		foreach ( Theme_My_Login::default_pages() as $action => $title ) {
			if ( ! $page_id = Theme_My_Login::get_page_id( $action ) ) {
				$page_id = wp_insert_post( array(
					'post_title'     => $title,
					'post_name'      => $action,
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'post_content'   => '[theme-my-login]',
					'comment_status' => 'closed',
					'ping_status'    => 'closed'
				) );
				update_post_meta( $page_id, '_tml_action', $action );
			}
		}

		// Activate modules
		foreach ( $this->get_option( 'active_modules', array() ) as $module ) {
			if ( file_exists( SIMPLE_THEMED_LOGIN_PATH . '/modules/' . $module ) )
				include_once( SIMPLE_THEMED_LOGIN_PATH . '/modules/' . $module );
			do_action( 'tml_activate_' . $module );
		}

		$this->set_option( 'version', Theme_My_Login::VERSION );
		$this->save_options();
	}

	/**
	 * Wrapper for multisite uninstallation
	 */
	public static function uninstall() {
		global $wpdb;

		if ( is_multisite() ) {
			if ( isset( $_GET['networkwide'] ) && ( $_GET['networkwide'] == 1 ) ) {
				$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::_uninstall();
				}
				restore_current_blog();
				return;
			}
		}
		self::_uninstall();
	}

	protected static function _uninstall() {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		// Run module uninstall hooks
		$modules = get_plugins( sprintf( '/%s/modules', plugin_basename( SIMPLE_THEMED_LOGIN_PATH ) ) );
		foreach ( array_keys( $modules ) as $module ) {
			$module = plugin_basename( trim( $module ) );

			if ( file_exists( SIMPLE_THEMED_LOGIN_PATH . '/modules/' . $module ) )
				@include ( SIMPLE_THEMED_LOGIN_PATH . '/modules/' . $module );

			do_action( 'tml_uninstall_' . $module );
		}

		// Get pages
		$pages = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'meta_key'       => '_tml_action',
			'posts_per_page' => -1
		) );

		// Delete pages
		foreach ( $pages as $page ) {
			wp_delete_post( $page->ID, true );
		}

		// Delete options
		delete_option( 'theme_my_login' );
		delete_option( 'widget_theme-my-login' );
	}
}
endif; // Class exists
