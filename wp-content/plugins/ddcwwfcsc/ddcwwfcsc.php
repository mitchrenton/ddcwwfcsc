<?php
/**
 * Plugin Name: DDCWWFCSC
 * Plugin URI:  https://ddcwwfcsc.co.uk
 * Description: Daventry Dun Cow Wolverhampton Wanderers FC Supporters Club — ticketing and club management.
 * Version:     1.0.1
 * Author:      DDCWWFCSC
 * Author URI:  https://ddcwwfcsc.co.uk
 * License:     GPL-2.0-or-later
 * Text Domain: ddcwwfcsc
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DDCWWFCSC_VERSION', '1.0.0' );
define( 'DDCWWFCSC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DDCWWFCSC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DDCWWFCSC_PLUGIN_FILE', __FILE__ );

// Composer autoloader for Stripe SDK.
if ( file_exists( DDCWWFCSC_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once DDCWWFCSC_PLUGIN_DIR . 'vendor/autoload.php';
}

// Include all class files.
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-activator.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-fixture-cpt.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-fixture-admin.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-ticket-requests.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-notifications.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-payments.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-settings.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-beerwolf-cpt.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-beerwolf-admin.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-beerwolf-front.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-event-cpt.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-event-admin.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-event-front.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-honorary-cpt.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-honorary-admin.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-honorary-front.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-bulletin-cpt.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-bulletin-admin.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-bulletin-front.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-guest-author.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-custom-avatar.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-fixture-sync.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-player-cpt.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-player-admin.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-motm-votes.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-motm-lineup.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-motm-admin.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-motm-front.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-dashboard-widgets.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-invites.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-member-admin.php';
require_once DDCWWFCSC_PLUGIN_DIR . 'includes/class-member-front.php';

// Activation and deactivation hooks.
register_activation_hook( __FILE__, array( 'DDCWWFCSC_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'DDCWWFCSC_Deactivator', 'deactivate' ) );

/**
 * Initialize the plugin.
 */
function ddcwwfcsc_init() {
    DDCWWFCSC_Fixture_CPT::init();
    DDCWWFCSC_Fixture_Admin::init();
    DDCWWFCSC_Ticket_Requests::init();
    DDCWWFCSC_Notifications::init();
    DDCWWFCSC_Payments::init();
    DDCWWFCSC_Settings::init();
    DDCWWFCSC_Beerwolf_CPT::init();
    DDCWWFCSC_Beerwolf_Admin::init();
    DDCWWFCSC_Beerwolf_Front::init();
    DDCWWFCSC_Event_CPT::init();
    DDCWWFCSC_Event_Admin::init();
    DDCWWFCSC_Event_Front::init();
    DDCWWFCSC_Honorary_CPT::init();
    DDCWWFCSC_Honorary_Admin::init();
    DDCWWFCSC_Honorary_Front::init();
    DDCWWFCSC_Bulletin_CPT::init();
    DDCWWFCSC_Bulletin_Admin::init();
    DDCWWFCSC_Bulletin_Front::init();
    DDCWWFCSC_Guest_Author::init();
    DDCWWFCSC_Custom_Avatar::init();
    DDCWWFCSC_Fixture_Sync::init();
    DDCWWFCSC_Player_CPT::init();
    DDCWWFCSC_Player_Admin::init();
    DDCWWFCSC_MOTM_Lineup::init();
    DDCWWFCSC_MOTM_Admin::init();
    DDCWWFCSC_MOTM_Front::init();
    DDCWWFCSC_Dashboard_Widgets::init();
    DDCWWFCSC_Member_Admin::init();
    DDCWWFCSC_Member_Front::init();
}
add_action( 'plugins_loaded', 'ddcwwfcsc_init' );

// Ensure opponent terms are seeded (runs once per version).
add_action( 'admin_init', array( 'DDCWWFCSC_Activator', 'maybe_seed_opponents' ) );

// Apply any pending schema migrations (e.g. new columns added to existing tables).
add_action( 'admin_init', array( 'DDCWWFCSC_Activator', 'maybe_upgrade_schema' ) );

/**
 * Register the Gutenberg block.
 */
function ddcwwfcsc_register_blocks() {
    $blocks = array( 'tickets', 'beerwolf', 'events', 'honorary-members', 'bulletins' );
    foreach ( $blocks as $block ) {
        $dir    = DDCWWFCSC_PLUGIN_DIR . 'blocks/' . $block;
        $result = register_block_type( $dir );
        error_log( 'DDCWWFCSC block register [' . $block . ']: ' . ( $result ? 'OK' : 'FAILED, dir=' . $dir ) );
    }
}
add_action( 'init', 'ddcwwfcsc_register_blocks' );

/**
 * Register shared component stylesheets so blocks and templates can enqueue by handle.
 */
function ddcwwfcsc_register_shared_styles() {
    wp_register_style(
        'ddcwwfcsc-ticket-front',
        DDCWWFCSC_PLUGIN_URL . 'assets/css/ticket-front.css',
        array(),
        DDCWWFCSC_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'ddcwwfcsc_register_shared_styles', 5 );

/**
 * Enqueue front-end assets.
 */
function ddcwwfcsc_enqueue_public_assets() {
    wp_enqueue_style(
        'ddcwwfcsc-public',
        DDCWWFCSC_PLUGIN_URL . 'assets/css/public.css',
        array(),
        DDCWWFCSC_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'ddcwwfcsc_enqueue_public_assets' );
