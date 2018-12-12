<?php
/**
 * Holds the Themed Login Common class
 *
 * @package ThemedLogin
 */

if (!class_exists('ThemedLogin_Common')) {

	/*
	 * Themed Login Helper class
	 *
	 * This class holds methods common to being common.
	 */
	class ThemedLogin_Common {

		/**
		 * Returns a list of the query arguments to be filtered out.
		 *
		 * @return array
		 */
		public static function filtered_query_args(): array {
			return [
				'instance',
				'action',
				'checkemail',
				'error',
				'loggedout',
				'registered',
				'registration',
				'redirect_to',
				'updated',
				'key',
				'_wpnonce',
				'reauth',
				'login',
			];
		}

		/**
		 * Returns current URL with certain query parameters removed.
		 *
		 * @access public
		 *
		 * @param string $query Optionally append query to the current URL
		 * @return string URL with optional path appended
		 */
		public static function get_current_url($query = '') {
			$url = remove_query_arg(self::filtered_query_args());

			if (!empty($_REQUEST['instance'])) {
				$url = add_query_arg('instance', $_REQUEST['instance']);
			}

			if (!empty($query)) {
				$r = wp_parse_args($query);
				foreach ($r as $k => $v) {
					if (strpos($v, ' ') !== false) {
						$r[$k] = rawurlencode($v);
					}
				}
				$url = add_query_arg($r, $url);
			}
			return $url;
		}

		/**
		 * Merges arrays recursively, replacing duplicate string keys
		 *
		 * @access public
		 */
		public static function array_merge_recursive() {
			$args = func_get_args();

			$result = array_shift($args);

			foreach ($args as $arg) {
				foreach ($arg as $key => $value) {
					// Renumber numeric keys as array_merge() does.
					if (is_numeric($key)) {
						if (! in_array($value, $result, true)) {
							$result[] = $value;
						}
					}
					// Recurse only when both values are arrays.
					elseif (array_key_exists($key, $result) && is_array($result[$key]) && is_array($value)) {
						$result[$key] = self::array_merge_recursive($result[$key], $value);
					}
					// Otherwise, use the latter value.
					else {
						$result[$key] = $value;
					}
				}
			}
			return $result;
		}

		/**
		 * Replaces certain user and blog variables in $input string
		 *
		 * @access public
		 *
		 * @param string $input The input string
		 * @param int $user_id User ID to replace user specific variables
		 * @param array $replacements Misc variables => values replacements
		 * @return string The $input string with variables replaced
		 */
		public static function replace_vars($input, $user_id = 0, $replacements = []) {
			$defaults = [
				'%site_url%' => get_bloginfo('url'),
				'%siteurl%'  => get_bloginfo('url'),
				'%user_ip%'  => $_SERVER['REMOTE_ADDR'],
			];
			$replacements = wp_parse_args($replacements, $defaults);

			// Get user data
			$user = false;
			if ($user_id) {
				$user = get_user_by('id', $user_id);
			}

			// Get all matches ($matches[0] will be '%value%'; $matches[1] will be 'value')
			preg_match_all('/%([a-zA-Z0-9-_]*)%/', $input, $matches);

			// Iterate through matches
			foreach ($matches[0] as $key => $match) {
				if (! isset($replacements[$match])) {
					if ($user && isset($user->{$matches[1][$key]})) { // Replacement from WP_User object
						$replacements[$match] = $user->{$matches[1][$key]};
					} else {
						$replacements[$match] = get_bloginfo($matches[1][$key]);
					} // Replacement from get_bloginfo()
				}
			}

			// Allow replacements to be filtered
			$replacements = apply_filters('tml_replace_vars', $replacements, $user_id);

			if (empty($replacements)) {
				return $input;
			}
			// Get search values
			$search = array_keys($replacements);

			// Get replacement values
			$replace = array_values($replacements);

			return str_replace($search, $replace, $input);
		}
	}

}
