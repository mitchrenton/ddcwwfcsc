<?php
/**
 * Event admin: meta boxes, columns, classic editor.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Event_Admin {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_ddcwwfcsc_event', array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
        add_filter( 'manage_ddcwwfcsc_event_posts_columns', array( __CLASS__, 'add_columns' ) );
        add_action( 'manage_ddcwwfcsc_event_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );
        add_filter( 'manage_edit-ddcwwfcsc_event_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
        add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'disable_block_editor' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /**
     * Force classic editor for event posts.
     */
    public static function disable_block_editor( $use, $post_type ) {
        if ( 'ddcwwfcsc_event' === $post_type ) {
            return false;
        }
        return $use;
    }

    /**
     * Register meta boxes.
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'ddcwwfcsc_event_details',
            __( 'Event Details', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_details_box' ),
            'ddcwwfcsc_event',
            'normal',
            'high'
        );

        add_meta_box(
            'ddcwwfcsc_event_signups',
            __( 'Sign-ups', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_signups_box' ),
            'ddcwwfcsc_event',
            'normal',
            'default'
        );
    }

    /**
     * Render the Event Details meta box.
     */
    public static function render_details_box( $post ) {
        wp_nonce_field( 'ddcwwfcsc_event_meta', 'ddcwwfcsc_event_nonce' );

        $event_date       = get_post_meta( $post->ID, '_ddcwwfcsc_event_date', true );
        $meeting_time     = get_post_meta( $post->ID, '_ddcwwfcsc_event_meeting_time', true );
        $meeting_location = get_post_meta( $post->ID, '_ddcwwfcsc_event_meeting_location', true );
        $location         = get_post_meta( $post->ID, '_ddcwwfcsc_event_location', true );
        $price_member     = get_post_meta( $post->ID, '_ddcwwfcsc_event_price_member', true );
        $price_non_member = get_post_meta( $post->ID, '_ddcwwfcsc_event_price_non_member', true );
        $lat              = get_post_meta( $post->ID, '_ddcwwfcsc_event_lat', true );
        $lng              = get_post_meta( $post->ID, '_ddcwwfcsc_event_lng', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="ddcwwfcsc_event_date"><?php esc_html_e( 'Event Date', 'ddcwwfcsc' ); ?></label></th>
                <td><input type="datetime-local" id="ddcwwfcsc_event_date" name="ddcwwfcsc_event_date" value="<?php echo esc_attr( $event_date ); ?>"></td>
            </tr>
            <tr>
                <th><label for="ddcwwfcsc_event_meeting_time"><?php esc_html_e( 'Meeting Time', 'ddcwwfcsc' ); ?></label></th>
                <td><input type="time" id="ddcwwfcsc_event_meeting_time" name="ddcwwfcsc_event_meeting_time" value="<?php echo esc_attr( $meeting_time ); ?>"></td>
            </tr>
            <tr>
                <th><label for="ddcwwfcsc_event_meeting_location"><?php esc_html_e( 'Meeting Location', 'ddcwwfcsc' ); ?></label></th>
                <td><input type="text" id="ddcwwfcsc_event_meeting_location" name="ddcwwfcsc_event_meeting_location" value="<?php echo esc_attr( $meeting_location ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="ddcwwfcsc_event_location"><?php esc_html_e( 'Location (Venue)', 'ddcwwfcsc' ); ?></label></th>
                <td>
                    <input type="text" id="ddcwwfcsc_event_location" name="ddcwwfcsc_event_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text ddcwwfcsc-event-location">
                    <input type="hidden" id="ddcwwfcsc_event_lat" name="ddcwwfcsc_event_lat" value="<?php echo esc_attr( $lat ); ?>">
                    <input type="hidden" id="ddcwwfcsc_event_lng" name="ddcwwfcsc_event_lng" value="<?php echo esc_attr( $lng ); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="ddcwwfcsc_event_price_member"><?php esc_html_e( 'Member Price (£)', 'ddcwwfcsc' ); ?></label></th>
                <td><input type="number" step="0.01" min="0" id="ddcwwfcsc_event_price_member" name="ddcwwfcsc_event_price_member" value="<?php echo esc_attr( $price_member ); ?>" class="small-text"></td>
            </tr>
            <tr>
                <th><label for="ddcwwfcsc_event_price_non_member"><?php esc_html_e( 'Non-Member Price (£)', 'ddcwwfcsc' ); ?></label></th>
                <td><input type="number" step="0.01" min="0" id="ddcwwfcsc_event_price_non_member" name="ddcwwfcsc_event_price_non_member" value="<?php echo esc_attr( $price_non_member ); ?>" class="small-text"></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render the Sign-ups meta box (read-only).
     */
    public static function render_signups_box( $post ) {
        $signups = DDCWWFCSC_Event_CPT::get_signups( $post->ID );
        $count   = count( $signups );
        ?>
        <p>
            <strong><?php printf( esc_html__( 'Total sign-ups: %d', 'ddcwwfcsc' ), $count ); ?></strong>
        </p>

        <?php if ( $count > 0 ) : ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Name', 'ddcwwfcsc' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'ddcwwfcsc' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $signups as $signup ) : ?>
                        <tr>
                            <td><?php echo esc_html( $signup['name'] ); ?></td>
                            <td><?php echo esc_html( $signup['email'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'No sign-ups yet.', 'ddcwwfcsc' ); ?></p>
        <?php endif;
    }

    /**
     * Save meta box data.
     */
    public static function save_meta_boxes( $post_id, $post ) {
        if ( ! isset( $_POST['ddcwwfcsc_event_nonce'] ) || ! wp_verify_nonce( $_POST['ddcwwfcsc_event_nonce'], 'ddcwwfcsc_event_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = array(
            'ddcwwfcsc_event_date'             => '_ddcwwfcsc_event_date',
            'ddcwwfcsc_event_meeting_time'     => '_ddcwwfcsc_event_meeting_time',
            'ddcwwfcsc_event_meeting_location' => '_ddcwwfcsc_event_meeting_location',
            'ddcwwfcsc_event_location'         => '_ddcwwfcsc_event_location',
            'ddcwwfcsc_event_price_member'     => '_ddcwwfcsc_event_price_member',
            'ddcwwfcsc_event_price_non_member' => '_ddcwwfcsc_event_price_non_member',
            'ddcwwfcsc_event_lat'              => '_ddcwwfcsc_event_lat',
            'ddcwwfcsc_event_lng'              => '_ddcwwfcsc_event_lng',
        );

        foreach ( $fields as $form_key => $meta_key ) {
            if ( isset( $_POST[ $form_key ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $form_key ] ) );
            }
        }
    }

    /**
     * Add custom columns to the events list table.
     */
    public static function add_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
            if ( 'title' === $key ) {
                $new_columns['event_date'] = __( 'Event Date', 'ddcwwfcsc' );
                $new_columns['signups']    = __( 'Sign-ups', 'ddcwwfcsc' );
            }
        }
        return $new_columns;
    }

    /**
     * Render custom column values.
     */
    public static function render_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'event_date':
                $date = get_post_meta( $post_id, '_ddcwwfcsc_event_date', true );
                if ( $date ) {
                    $timestamp = strtotime( $date );
                    echo esc_html( wp_date( 'j M Y, H:i', $timestamp ) );
                } else {
                    echo '—';
                }
                break;

            case 'signups':
                $signups = DDCWWFCSC_Event_CPT::get_signups( $post_id );
                echo esc_html( count( $signups ) );
                break;
        }
    }

    /**
     * Define sortable columns.
     */
    public static function sortable_columns( $columns ) {
        $columns['event_date'] = 'event_date';
        return $columns;
    }

    /**
     * Enqueue Google Places autocomplete on event edit screens.
     */
    public static function enqueue_assets( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || 'ddcwwfcsc_event' !== $screen->post_type ) {
            return;
        }

        $api_key = get_option( 'ddcwwfcsc_google_maps_api_key', '' );
        if ( ! $api_key ) {
            return;
        }

        wp_enqueue_script(
            'ddcwwfcsc-event-admin-location',
            DDCWWFCSC_PLUGIN_URL . 'assets/js/event-admin-location.js',
            array(),
            DDCWWFCSC_VERSION,
            true
        );

        wp_enqueue_script(
            'google-maps-api',
            'https://maps.googleapis.com/maps/api/js?key=' . urlencode( $api_key ) . '&libraries=places&callback=ddcwwfcscInitEventAutocomplete',
            array( 'ddcwwfcsc-event-admin-location' ),
            null,
            true
        );
    }
}
