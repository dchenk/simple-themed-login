<?php
/*
Plugin Name: Themed Login
Plugin URI: https://github.com/dchenk/themed-login
Description: Themes the WordPress login, registration, and forgot password pages according to your theme.
Version: 2.1.1
Author: widerwebs
Author URI: https://github.com/dchenk/themed-login
Text Domain: themed-login
Domain Path: /languages
*/

if (!defined('THEMED_LOGIN_DIR')) {
	define('THEMED_LOGIN_DIR', __DIR__);
}

require_once(THEMED_LOGIN_DIR . '/includes/class-themed-login-common.php');
require_once(THEMED_LOGIN_DIR . '/includes/class-themed-login-abstract.php');
require_once(THEMED_LOGIN_DIR . '/includes/class-themed-login.php');
require_once(THEMED_LOGIN_DIR . '/includes/class-themed-login-template.php');
require_once(THEMED_LOGIN_DIR . '/includes/class-themed-login-widget.php');

// Instantiate a ThemedLogin singleton.
$themedLoginInstance = new ThemedLogin();

if (is_admin()) {
	require_once(THEMED_LOGIN_DIR . '/admin/class-theme-my-login-admin.php');

	// Instantiate ThemedLogin_Admin singleton
	new ThemedLogin_Admin();
}
