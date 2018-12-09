<?php
/*
Plugin Name: Themed Login
Plugin URI: https://github.com/dchenk/themed-login
Description: Themes the WordPress login, registration, and forgot password pages according to your theme.
Version: 1.2.0
Author: widerwebs
Author URI: https://github.com/dchenk/themed-login
Text Domain: themed-login
Domain Path: /languages
*/

if (!defined('THEMED_LOGIN_DIR')) {
	define('THEMED_LOGIN_DIR', __DIR__);
}

require_once(THEMED_LOGIN_DIR . '/includes/class-theme-my-login-common.php');
require_once(THEMED_LOGIN_DIR . '/includes/class-theme-my-login-abstract.php');
require_once(THEMED_LOGIN_DIR . '/includes/class-theme-my-login.php');
require_once(THEMED_LOGIN_DIR . '/includes/class-theme-my-login-template.php');
require_once(THEMED_LOGIN_DIR . '/includes/class-theme-my-login-widget.php');

// Instantiate a ThemedLogin singleton.
$themeMyLoginInstance = new ThemedLogin();

if (is_admin()) {
	require_once(THEMED_LOGIN_DIR . '/admin/class-theme-my-login-admin.php');

	// Instantiate ThemedLogin_Admin singleton
	new ThemedLogin_Admin();
}

if (!function_exists('theme_my_login')) {
	/**
	 * Displays a shortcode-defined template
	 *
	 * @see ThemedLogin::shortcode() for $args parameters
	 *
	 * @param array|string $args Template tag arguments
	 */
	function theme_my_login($args = '') {
		global $themeMyLoginInstance;
		echo $themeMyLoginInstance->shortcode($args);
	}
}
