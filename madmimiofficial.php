<?php
/**
 * Plugin Name: Official Mad Mimi Signup Forms
 * Plugin URI: https://wordpress.org/plugins/mad-mimi-sign-up-forms/
 * Description: Add the Mad Mimi webform to your WordPress site! Easy to set up, the Mad Mimi plugin allows your site visitors to subscribe to your email lists.
 * Author: Mad Mimi, LLC
 * Version: 1.7.1
 * Author URI: https://madmimi.com/
 * License: GPL-2.0
 * Text Domain: mad-mimi-sign-up-forms
 * Domain Path: /languages
 *
 * Copyright © 2017 Mad Mimi, LLC. All Rights Reserved.
 */

class MadMimi_Official {

	private static $instance;
	private static $basename;

	public $settings;
	public $debug;

	public static function instance() {

		if ( isset( self::$instance ) ) {

			return self::$instance;

		}

		self::$instance = new self();
		self::$instance->setup_constants();
		self::$instance->requirements();
		self::$instance->setup_actions();

	}

	private function setup_actions() {

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
		add_action( 'init', array( $this, 'register_shortcode' ), 20 );
		add_action( 'admin_notices', array( $this, 'action_admin_notices' ) );

		add_filter( 'plugin_action_links_' . self::$basename, array( $this, 'action_links' ), 10 );

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

	}

	private function setup_constants() {

		// Plugin's main directory
		if ( ! defined( 'MADMIMI_PLUGIN_DIR' ) ) {

			define( 'MADMIMI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

		}

		// Absolute URL to plugin's dir
		if ( ! defined( 'MADMIMI_PLUGIN_URL' ) ) {

			define( 'MADMIMI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

		}

		// Absolute URL to plugin's dir
		if ( ! defined( 'MADMIMI_PLUGIN_BASE' ) ) {

			define( 'MADMIMI_PLUGIN_BASE', plugin_basename( __FILE__ ) );

		}

		// Plugin version
		if ( ! defined( 'MADMIMI_VERSION' ) ) {

			define( 'MADMIMI_VERSION', '1.5.1' );

		}

		self::$basename = isset( self::$basename ) ? self::$basename : MADMIMI_PLUGIN_BASE;

	}

	// @todo include only some on is_admin()
	private function requirements() {

		require_once MADMIMI_PLUGIN_DIR . 'includes/class-dispatcher.php';

		// the shortcode
		require_once MADMIMI_PLUGIN_DIR . 'includes/class-shortcode.php';

		// the file renders the form
		require_once MADMIMI_PLUGIN_DIR . 'includes/render.php';

		// the main widget
		require_once MADMIMI_PLUGIN_DIR . 'includes/widget.php';

		// settings page, creds validation
		require_once MADMIMI_PLUGIN_DIR . 'includes/settings.php';

	}

	public function init() {

		// enable debug mode?
		$this->debug = (bool) apply_filters( 'madmimi_debug', false );

		// initialize settings
		if ( is_admin() ) {

			$this->settings = new Mad_Mimi_Settings();

		}

		// enqueue scripts n styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		// Load our textdomain to allow multilingual translations
		load_plugin_textdomain( 'mad-mimi-sign-up-forms', false, dirname( self::$basename ) . '/languages/' );

	}

	public function register_shortcode() {

		// register shortcode
		add_shortcode( 'madmimi', array( 'Mad_Mimi_Shortcode', 'render' ) );
		add_shortcode( 'MadMimi', array( 'Mad_Mimi_Shortcode', 'render' ) );

	}

	public function register_widget() {

		register_widget( 'Mad_Mimi_Form_Widget' );

	}

	public function enqueue() {

		$suffix = SCRIPT_DEBUG ? '' : '.min';
		$rtl    = ! is_rtl() ? '' : '-rtl';

		// main JavaScript file
		wp_enqueue_script( 'mimi-main', plugins_url( "js/mimi{$suffix}.js", __FILE__ ), array( 'jquery' ), MADMIMI_VERSION, true );

		// assistance CSS
		wp_enqueue_style( 'mimi-base', plugins_url( "css/mimi{$suffix}.css", __FILE__ ), false, MADMIMI_VERSION );

		// help strings
		wp_localize_script( 'mimi-main', 'MadMimi', array(
			'thankyou'            => __( 'Thank you for signing up!', 'mad-mimi-sign-up-forms' ),
			'thankyou_suppressed' => __( 'Thank you for signing up! Please check your email to confirm your subscription.', 'mad-mimi-sign-up-forms' ),
			'oops'                => __( 'Oops! There was a problem. Please try again.', 'mad-mimi-sign-up-forms' ),
			'fix'                 => __( 'There was a problem. Please fill all required fields.', 'mad-mimi-sign-up-forms' ),
		) );

	}

	public function action_links( $actions ) {

		return array_merge(
			array(
				'settings' => sprintf( '<a href="%s">%s</a>', menu_page_url( 'mad-mimi-settings', false ), __( 'Settings', 'mad-mimi-sign-up-forms' ) ),
			),
			$actions
		);

	}

	public function activate() {
		// nothing to do here (for now)
	}

	public function deactivate() {

		delete_option( 'madmimi-version' );

	}

	public function action_admin_notices() {

		$screen = get_current_screen();

		if ( 'plugins' !== $screen->id ) {

			return;

		}

		$version = get_option( 'madmimi-version' );

		if ( ! $version ) {

			update_option( 'madmimi-version', MADMIMI_VERSION ); ?>

			<div class="updated fade">
				<p>
					<strong><?php esc_html_e( 'Mad Mimi is almost ready.', 'mad-mimi-sign-up-forms' ); ?></strong><?php esc_html_e( 'You must enter your Mad Mimi username &amp; API key for it to work.', 'mad-mimi-sign-up-forms' ); ?>&nbsp;
					<a class="button" href="<?php esc_url( menu_page_url( 'mad-mimi-settings' ) ); ?>"><?php esc_html_e( 'Let\'s do it!', 'mad-mimi-sign-up-forms' ); ?></a>
				</p>
			</div>

			<?php
		}
	}
}

function madmimi() {

	return MadMimi_Official::instance();

}
add_action( 'plugins_loaded', 'madmimi' );
