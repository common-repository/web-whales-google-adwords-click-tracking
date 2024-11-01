<?php
	/**
	 * Plugin Name: Google AdWords Click Tracking by Web Whales
	 * Description: This plugin adds Google AdWords tracking code to HTML elements, making clicks on those elements trackable.
	 * Version: 1.0
	 * Author: Web Whales
	 * Author URI: https://webwhales.nl
	 * Contributors: ronald_edelschaap
	 * License: GPLv3
	 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
	 * Text Domain: ww-gaw-click-tracking
	 * Domain Path: /languages
	 *
	 * Requires at least: 4.3
	 * Tested up to: 4.6.1
	 *
	 * @author  Web Whales
	 * @version 1.0
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}

	/**
	 * Class WW_Google_AdWords_Click_Tracking
	 */
	final class WW_Google_AdWords_Click_Tracking {

		const PLUGIN_PREFIX = 'ww_gaw_click_tracking_', PLUGIN_SLUG = 'ww-gaw-click-tracking', PLUGIN_VERSION = '1.0', TEXT_DOMAIN = 'ww-gaw-click-tracking';

		private static $instance, $notices = array( 'error' => array(), 'update' => array() );

		/**
		 * Constructor for the gateway.
		 */
		private function __construct() {
			$this->init();
		}

		/**
		 * Enqueue the plugin script and style sheet when needed
		 */
		public function enqueue_scripts() {
			$js_file = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'ww-gaw-click-tracking.js';

			if ( is_file( $js_file ) ) {
				wp_enqueue_script( self::PLUGIN_SLUG, plugin_dir_url( __FILE__ ) . 'assets/ww-gaw-click-tracking.js', array( 'jquery' ), filemtime( $js_file ), true );
				wp_localize_script( self::PLUGIN_SLUG, 'ww_gaw_click_tracking_defaults', self::getClickTrackingDefaults() );
			}

			if ( (bool) self::get_option( 'load_adwords_conversion_async_script', true ) ) {
				wp_enqueue_script( 'google-adwords-conversion-async', '//www.googleadservices.com/pagead/conversion_async.js', array(), false, true );
			}
		}

		/**
		 * Retrieve a plugin option
		 *
		 * @param string $option
		 * @param mixed  $default
		 *
		 * @return string Returns the value or an empty string when the setting does not exist
		 */
		public function option( $option, $default = '' ) {
			return self::get_option( $option, $default );
		}

		/**
		 * Handles the admin settings page
		 */
		public function page_settings() {
			if ( ! empty( $_POST['action'] ) && $_POST['action'] == 'save_plugin_settings' && check_admin_referer( 'save-plugin-settings' ) ) {
				$post_fields = array(
					'default_conversion_currency',
					'default_conversion_id',
					'default_conversion_label',
					'default_conversion_value',
					'default_remarketing_only',
					'load_adwords_conversion_async_script',
				);
				$post_data   = $this->array_trim( array_intersect_key( $_POST, array_flip( $post_fields ) ) );
				$updated     = false;

				foreach ( $post_data as $key => $data ) {
					switch ( $key ) {
						case 'default_conversion_currency':
							$data = strtoupper( substr( $data, 0, 3 ) );

							if ( $data != self::get_option( $key ) ) {
								self::update_option( $key, $data );
								$updated = true;
							}
							break;

						case 'default_conversion_id':
							$data = (int) $data;

							if ( $data != (int) self::get_option( $key, 0 ) ) {
								self::update_option( $key, $data );
								$updated = true;
							}
							break;

						case 'default_conversion_label':
							if ( $data != self::get_option( $key ) ) {
								self::update_option( $key, $data );
								$updated = true;
							}
							break;

						case 'default_conversion_value':
							$data = (float) str_replace( ',', '.', $data );

							if ( $data != (float) self::get_option( $key, 0 ) ) {
								self::update_option( $key, $data );
								$updated = true;
							}
							break;

						case 'default_remarketing_only':
						case 'load_adwords_conversion_async_script':
							$data = (bool) $data;

							if ( $data != (bool) self::get_option( $key, false ) ) {
								self::update_option( $key, $data );
								$updated = true;
							}
							break;
					}
				}

				if ( $updated ) {
					self::$notices['update'][] = '<p>' . __( 'The settings have been updated.', self::TEXT_DOMAIN ) . '</p>';
				}
			}

			$this->include_template( 'settings', array( '_this' => $this ) );
		}

		/**
		 * Get a plugin URL
		 *
		 * @param string $path
		 *
		 * @return string
		 */
		public function plugins_url( $path = '' ) {
			return plugins_url( $path, __FILE__ );
		}

		/**
		 * Print error messages on the admin settings page
		 */
		public function print_admin_error_notices() {
			if ( ! empty( self::$notices['error'] ) ) {
				foreach ( self::$notices['error'] as $error_notice ) {
					print '<div class="error">' . $error_notice . '</div>';
				}
			}
		}

		/**
		 * Print update messages on the admin settings page
		 */
		public function print_admin_update_notices() {
			if ( ! empty( self::$notices['update'] ) ) {
				foreach ( self::$notices['update'] as $update_notice ) {
					print '<div class="updated">' . $update_notice . '</div>';
				}
			}
		}

		/**
		 * Get the plugin text domain
		 *
		 * @return string
		 */
		public function text_domain() {
			return self::TEXT_DOMAIN;
		}

		/**
		 * Add the admin settings page to the admin settings menu
		 */
		public function wp_admin_menu() {
			add_options_page(
				__( 'Google AdWords Click Tracking', self::TEXT_DOMAIN ),
				__( 'Google AdWords Click Tracking', self::TEXT_DOMAIN ),
				'manage_options',
				self::PLUGIN_SLUG,
				array( & $this, 'page_settings' )
			);
		}

		/**
		 * Add an settings link to the plugin action links
		 *
		 * @param array $actions
		 *
		 * @return array
		 */
		public function wp_admin_plugin_action_links( $actions ) {
			if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
				$settings_action = '<a href="' . menu_page_url( self::PLUGIN_SLUG, false ) . '">' . esc_html( __( 'Settings' ) ) . '</a>';

				array_unshift( $actions, $settings_action );
			}

			return $actions;
		}

		/**
		 * Trim the values of an array recursively
		 *
		 * @param array  $array    The array
		 * @param string $charlist Optionally, the stripped characters can also be specified using the charlist parameter. Simply list all characters that you want to be stripped. With .. you can specify a range of characters.
		 *
		 * @see trim()
		 *
		 * @return array Returns the array with trimmed values
		 */
		private function array_trim( $array, $charlist = null ) {
			foreach ( $array as &$arr ) {
				$arr = is_null( $charlist )
					? ( is_array( $arr ) ? $this->array_trim( $arr ) : trim( $arr ) )
					: ( is_array( $arr ) ? $this->array_trim( $arr, $charlist ) : trim( $arr, $charlist ) );
				unset( $arr );
			}

			return $array;
		}

		/**
		 * Load some general stuff
		 *
		 * @return void
		 */
		private function init() {
			//Load text domain
			load_plugin_textdomain( self::TEXT_DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages/' );

			//Add an item to the admin settings menu and a settings link to the plugin page
			add_action( 'admin_menu', array( $this, 'wp_admin_menu' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'wp_admin_plugin_action_links' ) );

			//Enqueue the plugin script and style sheet when needed
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 11 );
		}

		/**
		 * Add a new option
		 *
		 * @param string $option
		 * @param mixed  $value
		 * @param string $autoload
		 *
		 * @return bool
		 *
		 * @see add_option()
		 */
		public static function add_option( $option, $value = '', $autoload = 'yes' ) {
			return add_option( self::PLUGIN_PREFIX . $option, $value, '', $autoload );
		}

		/**
		 * Removes option by name. Prevents removal of protected WordPress options.
		 *
		 * @param string $option
		 *
		 * @return bool
		 *
		 * @see delete_option()
		 */
		public static function delete_option( $option ) {
			return delete_option( self::PLUGIN_PREFIX . $option );
		}

		/**
		 * Gets a class instance. Used to prevent this plugin from loading multiple times
		 *
		 * @return self
		 */
		public static function get_instance() {
			if ( empty( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Retrieve an option value based on the name of the option.
		 *
		 * @param string $option
		 * @param mixed  $default
		 *
		 * @return mixed
		 *
		 * @see get_option()
		 */
		public static function get_option( $option, $default = '' ) {
			return get_option( self::PLUGIN_PREFIX . $option, $default );
		}

		/**
		 * Include a template file
		 *
		 * @param string $template The template's file name
		 * @param array  $args     Optional arguments, will be converted to PHP variables that can be used in the template file
		 *
		 * @return bool Returns TRUE when the template was successfully loaded or FALSE on failure
		 */
		public static function include_template( $template, $args = array() ) {
			if ( ! empty( $args ) && is_array( $args ) ) {
				extract( $args );
			}

			if ( strpos( $template, '.' ) === false ) {
				$template .= '.phtml';
			}

			$template = plugin_dir_path( __FILE__ ) . 'templates' . DIRECTORY_SEPARATOR . str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $template );

			if ( is_file( $template ) ) {
				include $template;

				return true;
			}

			return false;
		}

		public static function plugin_activate() {
			$current_version  = self::get_option( 'plugin_version', '0' );
			$current_settings = array(
				'default_conversion_currency'          => self::get_option( 'default_conversion_currency' ),
				'default_conversion_value'             => self::get_option( 'default_conversion_value', 0 ),
				'load_adwords_conversion_async_script' => self::get_option( 'load_adwords_conversion_async_script', null ),
			);

			switch ( $current_version ) {
				default:
					if ( empty( $current_settings['default_conversion_currency'] ) ) {
						self::add_option( 'default_conversion_currency', 'EUR' );
					}

					if ( empty( $current_settings['default_conversion_value'] ) ) {
						self::add_option( 'default_conversion_value', 1 );
					}

					if ( $current_settings['load_adwords_conversion_async_script'] === null ) {
						self::add_option( 'load_adwords_conversion_async_script', true );
					}
					break;
			}

			self::update_option( 'plugin_version', self::PLUGIN_VERSION );
		}

		/**
		 * Update the value of an option that was already added.
		 *
		 * @param string $option
		 * @param mixed  $value
		 *
		 * @return bool
		 *
		 * @see update_option()
		 */
		public static function update_option( $option, $value ) {
			return update_option( self::PLUGIN_PREFIX . $option, wp_unslash( $value ) );
		}

		/**
		 * Retrieve default click tracking settings
		 *
		 * @return array
		 */
		private static function getClickTrackingDefaults() {
			return array(
				'default_conversion_currency' => self::get_option( 'default_conversion_currency', 'EUR' ),
				'default_conversion_id'       => (int) self::get_option( 'default_conversion_id', 0 ),
				'default_conversion_label'    => self::get_option( 'default_conversion_label' ),
				'default_conversion_value'    => (float) self::get_option( 'default_conversion_value', 0 ),
				'default_remarketing_only'    => (bool) self::get_option( 'default_remarketing_only', false ),
			);
		}
	}


	/**
	 * Load this plugin through the static instance
	 */
	add_action( 'plugins_loaded', array( 'WW_Google_AdWords_Click_Tracking', 'get_instance' ), 99 );


	/**
	 * Register the activation hook
	 */
	register_activation_hook( __FILE__, array( 'WW_Google_AdWords_Click_Tracking', 'plugin_activate' ) );