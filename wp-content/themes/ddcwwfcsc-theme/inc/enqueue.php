<?php
/**
 * Enqueue fonts, styles, and scripts.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'ddcwwfcsc_theme_enqueue' );

function ddcwwfcsc_theme_enqueue() {
	// Google Fonts.
	wp_enqueue_style(
		'ddcwwfcsc-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;700&display=swap',
		array(),
		null
	);

	// Main stylesheet.
	wp_enqueue_style(
		'ddcwwfcsc-theme-style',
		DDCWWFCSC_THEME_URI . '/assets/css/style.css',
		array( 'ddcwwfcsc-fonts' ),
		DDCWWFCSC_THEME_VERSION
	);

	// Navigation script.
	wp_enqueue_script(
		'ddcwwfcsc-navigation',
		DDCWWFCSC_THEME_URI . '/assets/js/navigation.js',
		array(),
		DDCWWFCSC_THEME_VERSION,
		true
	);

	// Dark mode toggle (localStorage read is in inline head script, this handles the button).
	wp_enqueue_script(
		'ddcwwfcsc-dark-mode',
		DDCWWFCSC_THEME_URI . '/assets/js/dark-mode.js',
		array(),
		DDCWWFCSC_THEME_VERSION,
		true
	);
}
