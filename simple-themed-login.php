<?php
/*
Plugin Name: Simple Themed Login
Plugin URI: https://github.com/dchenk/simple-themed-login
Description: Themes the WordPress login, registration, and forgot password pages according to your theme.
Version: 1.2.0
Author: Simple Themed Login
Author URI: https://github.com/dchenk/simple-themed-login
Text Domain: simple-themed-login
Domain Path: /languages
*/

if (!defined('SIMPLE_THEMED_LOGIN_PATH')) {
	define('SIMPLE_THEMED_LOGIN_PATH', __DIR__);
}

require_once(SIMPLE_THEMED_LOGIN_PATH . '/includes/class-theme-my-login-common.php');
require_once(SIMPLE_THEMED_LOGIN_PATH . '/includes/class-theme-my-login-abstract.php');
require_once(SIMPLE_THEMED_LOGIN_PATH . '/includes/class-theme-my-login.php');
require_once(SIMPLE_THEMED_LOGIN_PATH . '/includes/class-theme-my-login-template.php');
require_once(SIMPLE_THEMED_LOGIN_PATH . '/includes/class-theme-my-login-widget.php');

// Instantiate a Theme_My_Login singleton.
$themeMyLoginInstance = new Theme_My_Login();

if (is_admin()) {
	require_once(SIMPLE_THEMED_LOGIN_PATH . '/admin/class-theme-my-login-admin.php');

	// Instantiate Theme_My_Login_Admin singleton
	new Theme_My_Login_Admin();
}

if (!function_exists('theme_my_login')) {
	/**
	 * Displays a TML instance
	 *
	 * @see Theme_My_Login::shortcode() for $args parameters
	 *
	 * @param array|string $args Template tag arguments
	 */
	function theme_my_login($args = '') {
		global $themeMyLoginInstance;
		echo $themeMyLoginInstance->shortcode($args);
	}
}
