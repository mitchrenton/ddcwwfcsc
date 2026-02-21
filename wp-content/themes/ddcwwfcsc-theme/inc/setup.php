<?php
/**
 * Theme setup: supports, menus, image sizes.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'ddcwwfcsc_theme_setup' );

function ddcwwfcsc_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor-style.css' );

	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'ddcwwfcsc-theme' ),
		'footer'  => __( 'Footer Menu', 'ddcwwfcsc-theme' ),
	) );

	add_image_size( 'ddcwwfcsc-hero', 1600, 700, true );
	add_image_size( 'ddcwwfcsc-card', 600, 400, true );
}

add_action( 'widgets_init', 'ddcwwfcsc_theme_widgets' );

function ddcwwfcsc_theme_widgets() {
	register_sidebar( array(
		'name'          => __( 'Sidebar', 'ddcwwfcsc-theme' ),
		'id'            => 'sidebar-1',
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Footer', 'ddcwwfcsc-theme' ),
		'id'            => 'footer-1',
		'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="footer-widget-title">',
		'after_title'   => '</h4>',
	) );
}
