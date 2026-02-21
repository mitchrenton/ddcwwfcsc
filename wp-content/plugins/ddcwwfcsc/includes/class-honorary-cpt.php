<?php
/**
 * Honorary Member Custom Post Type registration and helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Honorary_CPT {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
        add_action( 'init', array( __CLASS__, 'register_post_meta' ) );
    }

    /**
     * Register the Honorary Member CPT.
     */
    public static function register_post_type() {
        $labels = array(
            'name'                  => __( 'Honorary Members', 'ddcwwfcsc' ),
            'singular_name'         => __( 'Honorary Member', 'ddcwwfcsc' ),
            'add_new'               => __( 'Add New', 'ddcwwfcsc' ),
            'add_new_item'          => __( 'Add New Honorary Member', 'ddcwwfcsc' ),
            'edit_item'             => __( 'Edit Honorary Member', 'ddcwwfcsc' ),
            'new_item'              => __( 'New Honorary Member', 'ddcwwfcsc' ),
            'view_item'             => __( 'View Honorary Member', 'ddcwwfcsc' ),
            'view_items'            => __( 'View Honorary Members', 'ddcwwfcsc' ),
            'search_items'          => __( 'Search Honorary Members', 'ddcwwfcsc' ),
            'not_found'             => __( 'No honorary members found.', 'ddcwwfcsc' ),
            'not_found_in_trash'    => __( 'No honorary members found in Trash.', 'ddcwwfcsc' ),
            'all_items'             => __( 'All Honorary Members', 'ddcwwfcsc' ),
            'archives'              => __( 'Honorary Member Archives', 'ddcwwfcsc' ),
            'attributes'            => __( 'Honorary Member Attributes', 'ddcwwfcsc' ),
            'insert_into_item'      => __( 'Insert into honorary member', 'ddcwwfcsc' ),
            'uploaded_to_this_item' => __( 'Uploaded to this honorary member', 'ddcwwfcsc' ),
            'menu_name'             => __( 'Honorary Members', 'ddcwwfcsc' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => array( 'title', 'editor', 'thumbnail' ),
            'menu_icon'           => 'dashicons-awards',
            'rewrite'             => array( 'slug' => 'honorary-members' ),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'show_in_menu'        => true,
        );

        register_post_type( 'ddcwwfcsc_honorary', $args );
    }

    /**
     * Register post meta for honorary members.
     */
    public static function register_post_meta() {
        $meta_fields = array(
            '_ddcwwfcsc_honorary_position'       => 'string',
            '_ddcwwfcsc_honorary_years_at_wolves' => 'string',
            '_ddcwwfcsc_honorary_year_granted'    => 'string',
            '_ddcwwfcsc_honorary_appearances'    => 'string',
        );

        foreach ( $meta_fields as $key => $type ) {
            register_post_meta( 'ddcwwfcsc_honorary', $key, array(
                'type'              => $type,
                'single'            => true,
                'show_in_rest'      => true,
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback'     => function () {
                    return current_user_can( 'edit_posts' );
                },
            ) );
        }
    }

    /**
     * Get all meta data for an honorary member.
     *
     * @param int $post_id Post ID.
     * @return array Associative array of meta fields.
     */
    public static function get_member_data( $post_id ) {
        return array(
            'position'       => get_post_meta( $post_id, '_ddcwwfcsc_honorary_position', true ),
            'years_at_wolves' => get_post_meta( $post_id, '_ddcwwfcsc_honorary_years_at_wolves', true ),
            'year_granted'    => get_post_meta( $post_id, '_ddcwwfcsc_honorary_year_granted', true ),
            'appearances'    => get_post_meta( $post_id, '_ddcwwfcsc_honorary_appearances', true ),
        );
    }
}
