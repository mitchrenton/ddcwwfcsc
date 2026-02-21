<?php
/**
 * DDCWWFCSC Theme functions and definitions.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

define( 'DDCWWFCSC_THEME_VERSION', '1.0.0' );
define( 'DDCWWFCSC_THEME_DIR', get_template_directory() );
define( 'DDCWWFCSC_THEME_URI', get_template_directory_uri() );

require DDCWWFCSC_THEME_DIR . '/inc/setup.php';
require DDCWWFCSC_THEME_DIR . '/inc/enqueue.php';
require DDCWWFCSC_THEME_DIR . '/inc/customizer.php';
require DDCWWFCSC_THEME_DIR . '/inc/template-tags.php';
require DDCWWFCSC_THEME_DIR . '/inc/dark-mode.php';
