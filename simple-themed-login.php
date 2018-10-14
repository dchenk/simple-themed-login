<?php
/*
Plugin Name: Simple Themed Login
Plugin URI: https://github.com/dchenk/theme-my-login
Description: Themes the WordPress login, registration, and forgot password pages according to your theme.
Version: 1.1.0
Author: Simple Themed Login
Author URI: https://github.com/dchenk/simple-themed-login
Text Domain: simple-themed-login
Domain Path: /languages
*/

if ( !defined('SIMPLE_THEMED_LOGIN_PATH') ) {
	define( 'SIMPLE_THEMED_LOGIN_PATH', dirname( __FILE__ ) );
}

// Require a few needed files
require_once( SIMPLE_THEMED_LOGIN_PATH . '/includes/class-theme-my-login-common.php' );
require_once( SIMPLE_THEMED_LOGIN_PATH . '/includes/class-theme-my-login-abstract.php' );
require_once( SIMPLE_THEMED_LOGIN_PATH . '/includes/class-theme-my-login.php' );
require_once( SIMPLE_THEMED_LOGIN_PATH . '/includes/class-theme-my-login-template.php' );
require_once( SIMPLE_THEMED_LOGIN_PATH . '/includes/class-theme-my-login-widget.php' );

// Instantiate Theme_My_Login singleton
Theme_My_Login::get_object();

if ( is_admin() ) {
	require_once( SIMPLE_THEMED_LOGIN_PATH . '/admin/class-theme-my-login-admin.php' );

	// Instantiate Theme_My_Login_Admin singleton
	Theme_My_Login_Admin::get_object();
}

if ( is_multisite() ) {
	require_once( SIMPLE_THEMED_LOGIN_PATH . '/includes/class-theme-my-login-ms-signup.php' );

	// Instantiate Theme_My_Login_MS_Signup singleton
	Theme_My_Login_MS_Signup::get_object();
}

if ( !function_exists('theme_my_login') ) :
	/**
	 * Displays a TML instance
	 *
	 * @see Theme_My_Login::shortcode() for $args parameters
	 *
	 * @param string|array $args Template tag arguments
	 */
	function theme_my_login( $args = '' ) {
		echo Theme_My_Login::get_object()->shortcode( wp_parse_args( $args ) );
	}
endif;
