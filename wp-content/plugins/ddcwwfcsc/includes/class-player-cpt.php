<?php
/**
 * Player CPT — squad roster for MOTM lineup selection.
 *
 * @package DDCWWFCSC
 */

defined( 'ABSPATH' ) || exit;

class DDCWWFCSC_Player_CPT {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_post_meta' ) );
	}

	/**
	 * Register the ddcwwfcsc_player post type.
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'Squad', 'ddcwwfcsc' ),
			'singular_name'      => __( 'Player', 'ddcwwfcsc' ),
			'add_new'            => __( 'Add New Player', 'ddcwwfcsc' ),
			'add_new_item'       => __( 'Add New Player', 'ddcwwfcsc' ),
			'edit_item'          => __( 'Edit Player', 'ddcwwfcsc' ),
			'new_item'           => __( 'New Player', 'ddcwwfcsc' ),
			'view_item'          => __( 'View Player', 'ddcwwfcsc' ),
			'search_items'       => __( 'Search Squad', 'ddcwwfcsc' ),
			'not_found'          => __( 'No players found.', 'ddcwwfcsc' ),
			'not_found_in_trash' => __( 'No players found in trash.', 'ddcwwfcsc' ),
			'all_items'          => __( 'Squad', 'ddcwwfcsc' ),
			'menu_name'          => __( 'Squad', 'ddcwwfcsc' ),
		);

		register_post_type(
			'ddcwwfcsc_player',
			array(
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=ddcwwfcsc_fixture',
				'show_in_rest'    => true,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'has_archive'     => false,
				'rewrite'         => false,
				'menu_icon'       => 'dashicons-groups',
			)
		);
	}

	/**
	 * Register post meta fields.
	 */
	public static function register_post_meta() {
		register_post_meta(
			'ddcwwfcsc_player',
			'_ddcwwfcsc_player_number',
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'ddcwwfcsc_player',
			'_ddcwwfcsc_player_position',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => '',
				'sanitize_callback' => array( __CLASS__, 'sanitize_position' ),
				'auth_callback'     => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Sanitize position — must be one of the allowed values.
	 *
	 * @param string $value Raw value.
	 * @return string Sanitized value or empty string.
	 */
	public static function sanitize_position( $value ) {
		$allowed = array( 'GK', 'DEF', 'MID', 'FWD' );
		$value   = sanitize_text_field( $value );
		return in_array( $value, $allowed, true ) ? $value : '';
	}

	/**
	 * Get all published squad players ordered by squad number ascending.
	 *
	 * @return WP_Post[] Array of WP_Post objects.
	 */
	public static function get_squad() {
		$query = new WP_Query( array(
			'post_type'      => 'ddcwwfcsc_player',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => '_ddcwwfcsc_player_number',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		) );

		return $query->posts;
	}

	/**
	 * Get data for a single player.
	 *
	 * @param int $post_id Player post ID.
	 * @return array Associative array with name, number, position, photo.
	 */
	public static function get_player_data( $post_id ) {
		return array(
			'name'     => get_the_title( $post_id ),
			'number'   => (int) get_post_meta( $post_id, '_ddcwwfcsc_player_number', true ),
			'position' => get_post_meta( $post_id, '_ddcwwfcsc_player_position', true ),
		);
	}
}
