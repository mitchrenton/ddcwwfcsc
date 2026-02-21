<?php
/**
 * Event Custom Post Type registration and helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Event_CPT {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
        add_action( 'init', array( __CLASS__, 'register_post_meta' ) );
        add_action( 'pre_get_posts', array( __CLASS__, 'order_archive_by_event_date' ) );
    }

    /**
     * Register the Event CPT.
     */
    public static function register_post_type() {
        $labels = array(
            'name'                  => __( 'Events', 'ddcwwfcsc' ),
            'singular_name'         => __( 'Event', 'ddcwwfcsc' ),
            'add_new'               => __( 'Add New', 'ddcwwfcsc' ),
            'add_new_item'          => __( 'Add New Event', 'ddcwwfcsc' ),
            'edit_item'             => __( 'Edit Event', 'ddcwwfcsc' ),
            'new_item'              => __( 'New Event', 'ddcwwfcsc' ),
            'view_item'             => __( 'View Event', 'ddcwwfcsc' ),
            'view_items'            => __( 'View Events', 'ddcwwfcsc' ),
            'search_items'          => __( 'Search Events', 'ddcwwfcsc' ),
            'not_found'             => __( 'No events found.', 'ddcwwfcsc' ),
            'not_found_in_trash'    => __( 'No events found in Trash.', 'ddcwwfcsc' ),
            'all_items'             => __( 'All Events', 'ddcwwfcsc' ),
            'archives'              => __( 'Event Archives', 'ddcwwfcsc' ),
            'attributes'            => __( 'Event Attributes', 'ddcwwfcsc' ),
            'insert_into_item'      => __( 'Insert into event', 'ddcwwfcsc' ),
            'uploaded_to_this_item' => __( 'Uploaded to this event', 'ddcwwfcsc' ),
            'menu_name'             => __( 'Events', 'ddcwwfcsc' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => array( 'title', 'editor' ),
            'menu_icon'           => 'dashicons-calendar-alt',
            'rewrite'             => array( 'slug' => 'events' ),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'show_in_menu'        => true,
        );

        register_post_type( 'ddcwwfcsc_event', $args );
    }

    /**
     * Register post meta for events.
     */
    public static function register_post_meta() {
        $meta_fields = array(
            '_ddcwwfcsc_event_date'             => 'string',
            '_ddcwwfcsc_event_meeting_time'     => 'string',
            '_ddcwwfcsc_event_meeting_location' => 'string',
            '_ddcwwfcsc_event_location'         => 'string',
            '_ddcwwfcsc_event_price_member'     => 'string',
            '_ddcwwfcsc_event_price_non_member' => 'string',
            '_ddcwwfcsc_event_lat'              => 'string',
            '_ddcwwfcsc_event_lng'              => 'string',
        );

        foreach ( $meta_fields as $key => $type ) {
            register_post_meta( 'ddcwwfcsc_event', $key, array(
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

        // Sign-ups are stored as a serialized array, not exposed via REST.
        register_post_meta( 'ddcwwfcsc_event', '_ddcwwfcsc_event_signups', array(
            'type'         => 'array',
            'single'       => true,
            'show_in_rest' => false,
            'default'      => array(),
            'auth_callback' => function () {
                return current_user_can( 'edit_posts' );
            },
        ) );
    }

    /**
     * Get sign-ups for an event.
     *
     * @param int $post_id Post ID.
     * @return array Array of {name, email} entries.
     */
    public static function get_signups( $post_id ) {
        $signups = get_post_meta( $post_id, '_ddcwwfcsc_event_signups', true );

        if ( ! is_array( $signups ) ) {
            return array();
        }

        return $signups;
    }

    /**
     * Add a sign-up to an event.
     *
     * @param int    $post_id Post ID.
     * @param string $name    Attendee name.
     * @param string $email   Attendee email.
     * @return int Updated sign-up count.
     */
    public static function add_signup( $post_id, $name, $email ) {
        $signups = self::get_signups( $post_id );

        $signups[] = array(
            'name'  => $name,
            'email' => $email,
        );

        update_post_meta( $post_id, '_ddcwwfcsc_event_signups', $signups );

        return count( $signups );
    }

    /**
     * Order the event archive by event date ascending.
     *
     * @param WP_Query $query The query object.
     */
    public static function order_archive_by_event_date( $query ) {
        if ( is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( ! $query->is_post_type_archive( 'ddcwwfcsc_event' ) ) {
            return;
        }

        $query->set( 'meta_key', '_ddcwwfcsc_event_date' );
        $query->set( 'orderby', 'meta_value' );
        $query->set( 'order', 'ASC' );
    }
}
