<?php

if (!class_exists('Theme_My_Login_Abstract')) {

	/**
	 * Theme My Login Abstract class
	 * This class is the base class to be extended.
	 */
	abstract class Theme_My_Login_Abstract {
		/**
		 * Holds options key
		 *
		 * @var string
		 */
		protected $options_key;

		/**
		 * Holds options array
		 *
		 * Extending classes should explicitly define options here or create a method
		 * named default_options() which returns an array of options.
		 *
		 * @var array
		 */
		protected $options = [];

		public function __construct() {
			$this->load_options();
			$this->load();
		}

		/**
		 * Returns singleton instance
		 *
		 * @param string $class Class to instantiate
		 * @return object Instance of $class
		 */
		public static function get_object($class = null) {
			if (!class_exists($class)) {
				return null;
			}
			return new $class;
		}

		/**
		 * Loads options from DB
		 *
		 * @param array|string
		 */
		public function load_options() {
			if (method_exists($this, 'default_options')) {
				$this->options = (array) $this->default_options();
			}

			if (!$this->options_key) {
				return;
			}

			$options = get_option($this->options_key, []);
			$this->options = wp_parse_args($options, $this->options);
		}

		/**
		 * Saves options to DB
		 */
		public function save_options() {
			if ($this->options_key) {
				update_option($this->options_key, $this->options);
			}
		}

		/**
		 * Retrieves an option
		 *
		 * @param array|string $option Name of option to retrieve or an array of hierarchy for multidimensional options
		 * @param mixed $default Default value to return if $option is not set
		 * @return mixed Value of requested option or $default if option is not set
		 */
		public function get_option($option, $default = false) {
			if (!is_array($option)) {
				$option = [$option];
			}
			return self::_get_option($option, $default, $this->options);
		}

		/**
		 * Retrieves all options
		 *
		 * @return array
		 */
		public function get_options() {
			return $this->options;
		}

		/**
		 * Sets an option
		 *
		 * @param string $option Name of option to set or an array of hierarchy for multidimensional options
		 * @param mixed $value Value of new option
		 */
		public function set_option($option, $value = '') {
			if (!is_array($option)) {
				$option = [$option];
			}

			self::_set_option($option, $value, $this->options);
		}

		/**
		 * Sets all options
		 *
		 * @param array $options Options array
		 */
		public function set_options($options) {
			$this->options = (array) $options;
		}

		/**
		 * Deletes an option
		 *
		 * @param string $option Name of option to delete
		 */
		public function delete_option($option) {
			if (!is_array($option)) {
				$option = [$option];
			}

			self::_delete_option($option, $this->options);
		}

		/**
		 * Called when object is constructed
		 */
		protected function load() {
			// This should be overridden by a child class
		}

		/**
		 * Recursively retrieves a multidimensional option
		 *
		 * @param array $option Array of hierarchy
		 * @param mixed $default Default value to return
		 * @param array Options to search
		 * @return mixed Value of requested option or $default if option is not set
		 */
		private function _get_option($option, $default, &$options) {
			$key = array_shift($option);
			if (!isset($options[$key])) {
				return $default;
			}
			if (!empty($option)) {
				return self::_get_option($option, $default, $options[$key]);
			}
			return $options[$key];
		}

		/**
		 * Recursively sets a multidimensional option
		 *
		 * @param array $option Array of hierarchy
		 * @param mixed $value Value of new option
		 * @param array $options Options to update
		 */
		private function _set_option($option, $value, &$options) {
			$key = array_shift($option);
			if (!empty($option)) {
				if (!isset($options[$key])) {
					$options[$key] = [];
				}
				self::_set_option($option, $value, $options[$key]);
				return;
			}
			$options[$key] = $value;
		}

		/**
		 * Recursively finds and deletes a multidimensional option
		 *
		 * @param array $option Array of hierarchy
		 * @param array $options Options to update
		 */
		private function _delete_option($option, &$options) {
			$key = array_shift($option);
			if (!empty($option)) {
				self::_delete_option($option, $options[$key]);
				return;
			}
			unset($options[$key]);
		}
	}

}
