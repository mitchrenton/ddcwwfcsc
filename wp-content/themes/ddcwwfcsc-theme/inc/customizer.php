<?php
/**
 * Customizer settings for the hero section.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', 'ddcwwfcsc_theme_customizer' );

function ddcwwfcsc_theme_customizer( $wp_customize ) {

	// --- Hero Section ---
	$wp_customize->add_section( 'ddcwwfcsc_hero', array(
		'title'    => __( 'Homepage Hero', 'ddcwwfcsc-theme' ),
		'priority' => 30,
	) );

	// Hero image.
	$wp_customize->add_setting( 'ddcwwfcsc_hero_image', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'ddcwwfcsc_hero_image', array(
		'label'   => __( 'Hero Image', 'ddcwwfcsc-theme' ),
		'section' => 'ddcwwfcsc_hero',
	) ) );

	// Headline.
	$wp_customize->add_setting( 'ddcwwfcsc_hero_heading', array(
		'default'           => __( 'Welcome to the Dun Cow', 'ddcwwfcsc-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'ddcwwfcsc_hero_heading', array(
		'label'   => __( 'Heading', 'ddcwwfcsc-theme' ),
		'section' => 'ddcwwfcsc_hero',
		'type'    => 'text',
	) );

	// Subheading.
	$wp_customize->add_setting( 'ddcwwfcsc_hero_subheading', array(
		'default'           => __( 'Daventry Dun Cow Wolverhampton Wanderers FC Supporters Club', 'ddcwwfcsc-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'ddcwwfcsc_hero_subheading', array(
		'label'   => __( 'Subheading', 'ddcwwfcsc-theme' ),
		'section' => 'ddcwwfcsc_hero',
		'type'    => 'text',
	) );

	// CTA text.
	$wp_customize->add_setting( 'ddcwwfcsc_hero_cta_text', array(
		'default'           => __( 'View Fixtures', 'ddcwwfcsc-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'ddcwwfcsc_hero_cta_text', array(
		'label'   => __( 'CTA Button Text', 'ddcwwfcsc-theme' ),
		'section' => 'ddcwwfcsc_hero',
		'type'    => 'text',
	) );

	// CTA URL.
	$wp_customize->add_setting( 'ddcwwfcsc_hero_cta_url', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'ddcwwfcsc_hero_cta_url', array(
		'label'   => __( 'CTA Button URL', 'ddcwwfcsc-theme' ),
		'section' => 'ddcwwfcsc_hero',
		'type'    => 'url',
	) );

	// Overlay opacity.
	$wp_customize->add_setting( 'ddcwwfcsc_hero_overlay_opacity', array(
		'default'           => 55,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'ddcwwfcsc_hero_overlay_opacity', array(
		'label'       => __( 'Overlay Opacity (%)', 'ddcwwfcsc-theme' ),
		'section'     => 'ddcwwfcsc_hero',
		'type'        => 'range',
		'input_attrs' => array( 'min' => 0, 'max' => 100, 'step' => 5 ),
	) );
}
