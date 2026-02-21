<?php
/**
 * Beerwolf Custom Post Type registration and helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Beerwolf_CPT {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
        add_action( 'init', array( __CLASS__, 'register_post_meta' ) );
    }

    /**
     * Register the Beerwolf CPT.
     */
    public static function register_post_type() {
        $labels = array(
            'name'                  => __( 'Beerwolf Guides', 'ddcwwfcsc' ),
            'singular_name'         => __( 'Beerwolf Guide', 'ddcwwfcsc' ),
            'add_new'               => __( 'Add New', 'ddcwwfcsc' ),
            'add_new_item'          => __( 'Add New Beerwolf Guide', 'ddcwwfcsc' ),
            'edit_item'             => __( 'Edit Beerwolf Guide', 'ddcwwfcsc' ),
            'new_item'              => __( 'New Beerwolf Guide', 'ddcwwfcsc' ),
            'view_item'             => __( 'View Beerwolf Guide', 'ddcwwfcsc' ),
            'view_items'            => __( 'View Beerwolf Guides', 'ddcwwfcsc' ),
            'search_items'          => __( 'Search Beerwolf Guides', 'ddcwwfcsc' ),
            'not_found'             => __( 'No Beerwolf guides found.', 'ddcwwfcsc' ),
            'not_found_in_trash'    => __( 'No Beerwolf guides found in Trash.', 'ddcwwfcsc' ),
            'all_items'             => __( 'All Beerwolf Guides', 'ddcwwfcsc' ),
            'archives'              => __( 'Beerwolf Archives', 'ddcwwfcsc' ),
            'menu_name'             => __( 'Beerwolf', 'ddcwwfcsc' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => array( 'title', 'editor' ),
            'menu_icon'           => 'dashicons-beer',
            'rewrite'             => array( 'slug' => 'beerwolf' ),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'show_in_menu'        => true,
        );

        register_post_type( 'ddcwwfcsc_beerwolf', $args );
    }

    /**
     * Register post meta for the serialized pubs data.
     */
    public static function register_post_meta() {
        register_post_meta( 'ddcwwfcsc_beerwolf', '_ddcwwfcsc_pubs', array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => false,
            'sanitize_callback' => array( __CLASS__, 'sanitize_pubs' ),
        ) );
    }

    /**
     * Sanitize the pubs array from $_POST and return serialized string.
     *
     * @param mixed $value Raw input (array from form or serialized string).
     * @return string Serialized array.
     */
    public static function sanitize_pubs( $value ) {
        if ( ! is_array( $value ) ) {
            return serialize( array() );
        }

        $sanitized = array();

        foreach ( $value as $pub ) {
            if ( ! is_array( $pub ) ) {
                continue;
            }

            $name = isset( $pub['name'] ) ? sanitize_text_field( $pub['name'] ) : '';

            // Skip pubs with no name.
            if ( '' === $name ) {
                continue;
            }

            $sanitized[] = array(
                'name'        => $name,
                'description' => isset( $pub['description'] ) ? sanitize_textarea_field( $pub['description'] ) : '',
                'address'     => isset( $pub['address'] ) ? sanitize_text_field( $pub['address'] ) : '',
                'lat'         => isset( $pub['lat'] ) ? floatval( $pub['lat'] ) : 0,
                'lng'         => isset( $pub['lng'] ) ? floatval( $pub['lng'] ) : 0,
                'image_id'    => isset( $pub['image_id'] ) ? absint( $pub['image_id'] ) : 0,
                'distance'    => isset( $pub['distance'] ) ? sanitize_text_field( $pub['distance'] ) : '',
            );
        }

        return serialize( array_values( $sanitized ) );
    }

    /**
     * Get pubs data for a beerwolf post with resolved image URLs.
     *
     * @param int $post_id Post ID.
     * @return array Array of pub data.
     */
    public static function get_pubs( $post_id ) {
        $raw = get_post_meta( $post_id, '_ddcwwfcsc_pubs', true );

        if ( ! $raw ) {
            return array();
        }

        $pubs = maybe_unserialize( $raw );

        if ( ! is_array( $pubs ) ) {
            return array();
        }

        foreach ( $pubs as &$pub ) {
            $pub['image_url'] = '';
            if ( ! empty( $pub['image_id'] ) ) {
                $url = wp_get_attachment_image_url( $pub['image_id'], 'medium' );
                if ( $url ) {
                    $pub['image_url'] = $url;
                }
            }
        }

        return $pubs;
    }

    /**
     * Find the published beerwolf guide for a given opponent term.
     *
     * @param int $term_id Opponent term ID.
     * @return WP_Post|null Post object or null.
     */
    public static function get_beerwolf_for_opponent( $term_id ) {
        $posts = get_posts( array(
            'post_type'      => 'ddcwwfcsc_beerwolf',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'ddcwwfcsc_opponent',
                    'field'    => 'term_id',
                    'terms'    => (int) $term_id,
                ),
            ),
        ) );

        return ! empty( $posts ) ? $posts[0] : null;
    }
}
