<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName -- Main plugin file; name dictated by WordPress plugin conventions.
/**
 * Plugin Name: Complianz - Terms and Conditions
 * Plugin URI: https://wordpress.org/plugins/complianz-terms-conditions
 * Description: Plugin from Complianz to generate Terms & Conditions for your website.
 * Version: 1.4.0
 * Requires at least: 5.7
 * Requires PHP: 7.4
 * Text Domain: complianz-terms-conditions
 * Domain Path: /languages
 * Author: Complianz
 * Author URI: https://complianz.io
 *
 * @package Complianz_Terms_Conditions
 */

/**
 * Main plugin bootstrap file for Complianz Terms & Conditions.
 *
 * Declares the plugin header, defines the core COMPLIANZ_TC singleton class,
 * registers activation hooks, and requires the global functions file. All
 * plugin components (admin, wizard, document, REST API, Gutenberg block) are
 * loaded and instantiated from here in response to the `plugins_loaded` hook.
 *
 * @package    Complianz_Terms_Conditions
 * @author     Complianz
 * @copyright  2023 Complianz.io
 * @license    GPL-2.0-or-later
 * @link       https://complianz.io
 *
 * @since      1.0.0
 */

/*
	Copyright 2023  Complianz.io  (email : support@complianz.io)
*/

// Prevent direct file access outside of the WordPress bootstrap.
defined( 'ABSPATH' ) || die( 'you do not have access to this page!' );

// Flag this as the free edition of the plugin (used by premium add-ons to detect edition).
define( 'cmplz_tc_free', true ); // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ConstantNotUpperCase -- Established constant name used across the plugin ecosystem; renaming would break add-ons.

if ( ! function_exists( 'cmplz_tc_activation_check' ) ) {
	/**
	 * Validates minimum environment requirements before the plugin is activated.
	 *
	 * Deactivates the plugin and shows a wp_die() notice when the server does not
	 * meet the minimum PHP or WordPress version requirements. This prevents fatal
	 * errors from being thrown on sites running older software stacks.
	 *
	 * @since 2.1.5
	 *
	 * @see   deactivate_plugins()
	 * @see   register_activation_hook()
	 *
	 * @return void
	 */
	function cmplz_tc_activation_check() {
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'Complianz - Terms & Conditions cannot be activated. The plugin requires PHP 7.4 or higher', 'complianz-terms-conditions' ) );
		}

		global $wp_version;
		if ( version_compare( $wp_version, '4.9', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'Complianz - Terms & Conditions cannot be activated. The plugin requires WordPress 4.9 or higher', 'complianz-terms-conditions' ) );
		}
	}
	// Run the environment check immediately on plugin activation.
	register_activation_hook( __FILE__, 'cmplz_tc_activation_check' );
}

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed -- Main plugin bootstrap file intentionally combines the plugin class with activation functions.
if ( ! class_exists( 'COMPLIANZ_TC' ) ) {
	/**
	 * Core plugin class — singleton container for all Complianz T&C components.
	 *
	 * Manages plugin initialisation by setting up constants, requiring component
	 * files, registering WordPress hooks, and instantiating the individual
	 * sub-system objects (config, admin, wizard, document, etc.). Only one
	 * instance is ever created; use COMPLIANZ_TC::get_instance() to access it.
	 *
	 * Admin-only components (review prompt, admin UI, field handling, wizard,
	 * callback notices) are loaded and instantiated exclusively inside the
	 * is_admin() branch to avoid unnecessary overhead on the frontend.
	 *
	 * @package    Complianz_Terms_Conditions
	 *
	 * @since      1.0.0
	 */
	class COMPLIANZ_TC {

		/**
		 * Holds the single instance of this class.
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    COMPLIANZ_TC|null
		 */
		public static $instance;

		/**
		 * Plugin configuration object (document types, field definitions, regions).
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    cmplz_tc_config
		 */
		public static $config;

		/**
		 * Review prompt handler (shows the admin "please review us" notice).
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    cmplz_tc_review
		 */
		public static $review;

		/**
		 * Admin UI controller (settings pages, menus, notices).
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    cmplz_tc_admin
		 */
		public static $admin;

		/**
		 * Field renderer and option persistence handler.
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    cmplz_tc_field
		 */
		public static $field;

		/**
		 * Step-by-step setup wizard controller.
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    cmplz_tc_wizard
		 */
		public static $wizard;

		/**
		 * Placeholder for a future guided tour component (currently unused).
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    mixed
		 */
		public static $tour;

		/**
		 * Document generator: builds, stores, and exports T&C documents.
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    cmplz_tc_document
		 */
		public static $document;

		/**
		 * Holds the withdrawal submission handler instance.
		 *
		 * @since  1.4.0
		 * @access public
		 * @var    cmplz_tc_withdrawal
		 */
		public static $withdrawal;

		/**
		 * Initialises the plugin by setting up constants, loading files, and instantiating components.
		 *
		 * Called once via get_instance(). Admin-only components are conditionally
		 * loaded to avoid overhead on frontend requests.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function __construct() {
			self::setup_constants();
			self::includes();
			self::hooks();

			self::$config = new cmplz_tc_config();

			// Only instantiate heavy admin components when in the WordPress admin area.
			if ( is_admin() ) {
				self::$review = new cmplz_tc_review();
				self::$admin  = new cmplz_tc_admin();
				self::$field  = new cmplz_tc_field();
				self::$wizard = new cmplz_tc_wizard();
			}

			// The document object is needed both on the frontend (shortcode) and in admin.
			self::$document = new cmplz_tc_document();

			// The withdrawal handler registers a public admin-post endpoint on all requests.
			self::$withdrawal = new cmplz_tc_withdrawal();
		}

		/**
		 * Returns the single instance of COMPLIANZ_TC, creating it on first call.
		 *
		 * Implements the singleton pattern so that only one instance of the plugin
		 * class is ever active per request. All callers — including REST-API routes,
		 * shortcodes, and the PDF download endpoint — access plugin components
		 * through this method (e.g. COMPLIANZ_TC::$document).
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return COMPLIANZ_TC The single plugin instance.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof COMPLIANZ ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Defines all plugin-wide constants used throughout the codebase.
		 *
		 * Centralises path, URL, and tuning constants so they are set once and
		 * available globally. The version constant appends a cache-busting timestamp
		 * when SCRIPT_DEBUG is enabled, forcing browsers to reload assets during
		 * active development.
		 *
		 * @since  1.0.0
		 * @access private
		 *
		 * @return void
		 */
		private function setup_constants() {
			// Average minutes required to complete a single wizard question (used for time-estimate display).
			define( 'CMPLZ_TC_MINUTES_PER_QUESTION', 0.18 );
			// Reduced estimate used when the user opts for the quick-setup flow.
			define( 'CMPLZ_TC_MINUTES_PER_QUESTION_QUICK', 0.1 );
			// Admin menu position for the plugin's top-level menu item.
			define( 'CMPLZ_TC_MAIN_MENU_POSITION', 40 );
			// Trailing-slash URL to the plugin root directory.
			define( 'cmplz_tc_url', plugin_dir_url( __FILE__ ) ); // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ConstantNotUpperCase -- Lowercase constant name; established across codebase and add-ons.
			// Trailing-slash filesystem path to the plugin root directory.
			define( 'cmplz_tc_path', plugin_dir_path( __FILE__ ) ); // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ConstantNotUpperCase -- Lowercase constant name; established across codebase and add-ons.
			// Plugin basename (e.g. complianz-terms-conditions/complianz-terms-conditions.php).
			define( 'cmplz_tc_plugin', plugin_basename( __FILE__ ) ); // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ConstantNotUpperCase -- Lowercase constant name; established across codebase and add-ons.
			// Absolute filesystem path to this main plugin file.
			define( 'cmplz_tc_plugin_file', __FILE__ ); // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ConstantNotUpperCase -- Lowercase constant name; established across codebase and add-ons.
			// Append a timestamp in SCRIPT_DEBUG mode to bust browser/CDN asset caches.
			$debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? time() : '';
			define( 'cmplz_tc_version', '1.4.0' . $debug ); // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ConstantNotUpperCase -- Lowercase constant name; established across codebase and add-ons.
		}

		/**
		 * Requires all plugin component files in dependency order.
		 *
		 * Files are loaded conditionally where appropriate: the Gutenberg block is
		 * only included when the block editor is active; admin-only files are
		 * deferred to the is_admin() branch to avoid loading unnecessary code on
		 * public-facing requests.
		 *
		 * @since  1.0.0
		 * @access private
		 *
		 * @return void
		 */
		private function includes() {
			// Document class is required on all requests (shortcode + PDF endpoint).
			require_once cmplz_tc_path . 'class-document.php';

			// Withdrawal handler is required on all requests: it serves the public
			// admin-post endpoint and its state is read during front-end rendering.
			require_once cmplz_tc_path . 'class-withdrawal.php';

			// Only load the Gutenberg block integration when the block editor is in use.
			if ( cmplz_tc_uses_gutenberg() ) {
				require_once plugin_dir_path( __FILE__ ) . 'gutenberg/block.php';
			}

			// REST API routes are registered on every request so they are always reachable.
			require_once plugin_dir_path( __FILE__ ) . 'rest-api/rest-api.php';

			// Admin-only files: defer loading to reduce frontend memory footprint.
			if ( is_admin() ) {
				require_once cmplz_tc_path . '/assets/icons.php';
				require_once cmplz_tc_path . 'class-admin.php';
				require_once cmplz_tc_path . 'class-review.php';
				require_once cmplz_tc_path . 'class-field.php';
				require_once cmplz_tc_path . 'class-wizard.php';
				require_once cmplz_tc_path . 'callback-notices.php';
			}

			// Config is required on all requests: it holds document definitions and field maps.
			require_once cmplz_tc_path . 'config/class-config.php';
		}

		/**
		 * Registers WordPress action and filter hooks for the plugin.
		 *
		 * Defers translation loading to the `init` hook to comply with WordPress 6.7+
		 * requirements (loading translations earlier triggers a _doing_it_wrong() notice
		 * in WP 6.7 and above).
		 *
		 * @since  1.0.0
		 * @access private
		 *
		 * @return void
		 */
		private function hooks() {
			add_action(
				'init',
				function () {
					// Load the plugin's text domain for i18n; deferred to init for WP 6.7 compatibility.
					load_plugin_textdomain( 'complianz-terms-conditions' );
				}
			);

			// Community translations sometimes damage printf placeholders, which makes
			// sprintf() fatal on PHP 8; repair them before any caller formats them.
			add_filter( 'gettext', 'cmplz_tc_repair_translation', 10, 3 );
			add_filter( 'gettext_with_context', 'cmplz_tc_repair_translation', 10, 4 );
			add_filter( 'ngettext', 'cmplz_tc_repair_translation', 10, 5 );
			add_filter( 'ngettext_with_context', 'cmplz_tc_repair_translation', 10, 6 );
		}
	}

	/**
	 * Boots the plugin on `plugins_loaded` at priority 9.
	 *
	 * Priority 9 (rather than the default 10) ensures the plugin instance is
	 * available before other plugins that hook at the default priority and may
	 * depend on COMPLIANZ_TC::$document or other static properties.
	 */
	add_action(
		'plugins_loaded',
		function () {
			COMPLIANZ_TC::get_instance();
		},
		9
	);
}

/**
 * Runs first-time setup tasks when the plugin is activated.
 *
 * Sets a short-lived transient that triggers a redirect to the plugin settings
 * page immediately after activation, giving users a smooth onboarding
 * experience.
 *
 * @since  1.0.0
 *
 * @see    register_activation_hook()
 *
 * @return void
 */
function cmplz_tc_activation() {
	// Set a transient consumed by the admin redirect handler to forward the user to settings.
	set_transient( 'cmplz_tc_redirect_to_settings', true, DAY_IN_SECONDS );
}
register_activation_hook( __FILE__, 'cmplz_tc_activation' );


// Load global helper functions used throughout the plugin.
require_once plugin_dir_path( __FILE__ ) . 'functions.php';
