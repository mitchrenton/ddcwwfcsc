<?php
/**
 * Bulletin Custom Post Type registration and helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Bulletin_CPT {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
        add_action( 'init', array( __CLASS__, 'register_post_meta' ) );
    }

    /**
     * Register the Bulletin CPT.
     */
    public static function register_post_type() {
        $labels = array(
            'name'                  => __( 'Bulletins', 'ddcwwfcsc' ),
            'singular_name'         => __( 'Bulletin', 'ddcwwfcsc' ),
            'add_new'               => __( 'Add New', 'ddcwwfcsc' ),
            'add_new_item'          => __( 'Add New Bulletin', 'ddcwwfcsc' ),
            'edit_item'             => __( 'Edit Bulletin', 'ddcwwfcsc' ),
            'new_item'              => __( 'New Bulletin', 'ddcwwfcsc' ),
            'view_item'             => __( 'View Bulletin', 'ddcwwfcsc' ),
            'view_items'            => __( 'View Bulletins', 'ddcwwfcsc' ),
            'search_items'          => __( 'Search Bulletins', 'ddcwwfcsc' ),
            'not_found'             => __( 'No bulletins found.', 'ddcwwfcsc' ),
            'not_found_in_trash'    => __( 'No bulletins found in Trash.', 'ddcwwfcsc' ),
            'all_items'             => __( 'All Bulletins', 'ddcwwfcsc' ),
            'archives'              => __( 'Bulletin Archives', 'ddcwwfcsc' ),
            'menu_name'             => __( 'Bulletins', 'ddcwwfcsc' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => false,
            'has_archive'         => false,
            'show_in_rest'        => true,
            'supports'            => array( 'title' ),
            'menu_icon'           => 'dashicons-megaphone',
            'rewrite'             => false,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'show_in_menu'        => true,
        );

        register_post_type( 'ddcwwfcsc_bulletin', $args );
    }

    /**
     * Register post meta for bulletins.
     */
    public static function register_post_meta() {
        register_post_meta( 'ddcwwfcsc_bulletin', '_ddcwwfcsc_bulletin_expiry', array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function () {
                return current_user_can( 'edit_posts' );
            },
        ) );
    }

    /**
     * Get active (non-expired) bulletins.
     *
     * @param int $limit Maximum number of bulletins to return.
     * @return WP_Post[] Array of bulletin posts.
     */
    public static function get_active_bulletins( $limit = 10 ) {
        $today = current_time( 'Y-m-d' );

        $args = array(
            'post_type'      => 'ddcwwfcsc_bulletin',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_ddcwwfcsc_bulletin_expiry',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_ddcwwfcsc_bulletin_expiry',
                    'value'   => '',
                    'compare' => '=',
                ),
                array(
                    'key'     => '_ddcwwfcsc_bulletin_expiry',
                    'value'   => $today,
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
            ),
        );

        return get_posts( $args );
    }

    /**
     * Get the expiry date for a bulletin.
     *
     * @param int $post_id Post ID.
     * @return string Expiry date (Y-m-d) or empty string.
     */
    public static function get_expiry( $post_id ) {
        return get_post_meta( $post_id, '_ddcwwfcsc_bulletin_expiry', true );
    }
}
