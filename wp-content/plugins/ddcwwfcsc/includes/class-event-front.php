<?php
/**
 * Event front-end rendering and sign-up AJAX handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Event_Front {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_filter( 'the_content', array( __CLASS__, 'filter_content' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'wp_ajax_ddcwwfcsc_event_signup', array( __CLASS__, 'handle_signup' ) );
    }

    /**
     * Prepend event details to singular event content.
     * The signup form is rendered in the sidebar by the theme template.
     */
    public static function filter_content( $content ) {
        if ( ! is_singular( 'ddcwwfcsc_event' ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        return self::render_details( get_the_ID() ) . $content;
    }

    /**
     * Render the event details card and sign-up form.
     *
     * @param int $post_id Post ID.
     * @return string HTML output.
     */
    public static function render_event_html( $post_id ) {
        return self::render_details( $post_id ) . self::render_signup_form( $post_id );
    }

    /**
     * Render the event details card only.
     *
     * @param int $post_id Post ID.
     * @return string HTML output.
     */
    public static function render_details( $post_id ) {
        $event_date       = get_post_meta( $post_id, '_ddcwwfcsc_event_date', true );
        $meeting_time     = get_post_meta( $post_id, '_ddcwwfcsc_event_meeting_time', true );
        $meeting_location = get_post_meta( $post_id, '_ddcwwfcsc_event_meeting_location', true );
        $location         = get_post_meta( $post_id, '_ddcwwfcsc_event_location', true );
        $price_member     = get_post_meta( $post_id, '_ddcwwfcsc_event_price_member', true );
        $price_non_member = get_post_meta( $post_id, '_ddcwwfcsc_event_price_non_member', true );
        $lat              = get_post_meta( $post_id, '_ddcwwfcsc_event_lat', true );
        $lng              = get_post_meta( $post_id, '_ddcwwfcsc_event_lng', true );
        $signups          = DDCWWFCSC_Event_CPT::get_signups( $post_id );
        $api_key          = get_option( 'ddcwwfcsc_google_maps_api_key', '' );
        $is_upcoming      = $event_date && strtotime( $event_date ) > current_time( 'timestamp' );

        // Check if the current user has already signed up.
        $already_signed_up = false;
        if ( is_user_logged_in() ) {
            $current_email = wp_get_current_user()->user_email;
            foreach ( $signups as $signup ) {
                if ( isset( $signup['email'] ) && $signup['email'] === $current_email ) {
                    $already_signed_up = true;
                    break;
                }
            }
        }

        ob_start();
        ?>
        <div class="ddcwwfcsc-event-details">
            <dl class="ddcwwfcsc-event-details-list">
                <?php if ( $event_date ) : ?>
                    <div class="ddcwwfcsc-event-detail">
                        <dt><?php esc_html_e( 'Date', 'ddcwwfcsc' ); ?></dt>
                        <dd><?php echo esc_html( wp_date( 'l j F Y, g:i a', strtotime( $event_date ) ) ); ?></dd>
                    </div>
                <?php endif; ?>

                <?php if ( $meeting_time ) : ?>
                    <div class="ddcwwfcsc-event-detail">
                        <dt><?php esc_html_e( 'Meeting Time', 'ddcwwfcsc' ); ?></dt>
                        <dd><?php echo esc_html( wp_date( 'g:i a', strtotime( $meeting_time ) ) ); ?></dd>
                    </div>
                <?php endif; ?>

                <?php if ( $meeting_location ) : ?>
                    <div class="ddcwwfcsc-event-detail">
                        <dt><?php esc_html_e( 'Meeting Location', 'ddcwwfcsc' ); ?></dt>
                        <dd><?php echo esc_html( $meeting_location ); ?></dd>
                    </div>
                <?php endif; ?>

                <?php if ( $location ) : ?>
                    <div class="ddcwwfcsc-event-detail">
                        <dt><?php esc_html_e( 'Venue', 'ddcwwfcsc' ); ?></dt>
                        <dd><?php echo esc_html( $location ); ?></dd>
                    </div>
                <?php endif; ?>

                <?php if ( $price_member || $price_non_member ) : ?>
                    <div class="ddcwwfcsc-event-detail">
                        <dt><?php esc_html_e( 'Cost', 'ddcwwfcsc' ); ?></dt>
                        <dd>
                            <?php
                            $parts = array();
                            if ( $price_member ) {
                                $parts[] = sprintf( __( 'Members: £%s', 'ddcwwfcsc' ), number_format( (float) $price_member, 2 ) );
                            }
                            if ( $price_non_member ) {
                                $parts[] = sprintf( __( 'Non-members: £%s', 'ddcwwfcsc' ), number_format( (float) $price_non_member, 2 ) );
                            }
                            echo esc_html( implode( ' / ', $parts ) );
                            ?>
                        </dd>
                    </div>
                <?php endif; ?>
            </dl>

            <?php if ( $lat && $lng && $api_key ) : ?>
                <?php self::enqueue_map_scripts( $api_key ); ?>
                <div class="ddcwwfcsc-event-map" data-lat="<?php echo esc_attr( $lat ); ?>" data-lng="<?php echo esc_attr( $lng ); ?>" data-name="<?php echo esc_attr( $location ); ?>"></div>
            <?php endif; ?>

            <?php if ( $is_upcoming ) : ?>
                <div class="ddcwwfcsc-event-signup-row">
                    <?php if ( $already_signed_up ) : ?>
                        <p class="ddcwwfcsc-event-signup-confirmed"><?php esc_html_e( "You're signed up!", 'ddcwwfcsc' ); ?></p>
                    <?php elseif ( is_user_logged_in() ) : ?>
                        <form class="ddcwwfcsc-event-signup-form" data-event-id="<?php echo esc_attr( $post_id ); ?>">
                            <button type="submit" class="btn btn--primary ddcwwfcsc-event-signup-btn"><?php esc_html_e( 'Sign Up', 'ddcwwfcsc' ); ?></button>
                            <div class="ddcwwfcsc-event-signup-message"></div>
                        </form>
                    <?php else : ?>
                        <p class="ddcwwfcsc-login-prompt">
                            <a href="<?php echo esc_url( wp_login_url( get_permalink( $post_id ) ) ); ?>"><?php esc_html_e( 'Log in', 'ddcwwfcsc' ); ?></a> <?php esc_html_e( 'to sign up.', 'ddcwwfcsc' ); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the sign-up form only.
     * Sign-up is now inline in render_details(); this returns empty.
     *
     * @param int $post_id Post ID.
     * @return string HTML output.
     */
    public static function render_signup_form( $post_id ) {
        return '';
    }

    /**
     * Enqueue front-end assets on singular event pages.
     */
    public static function enqueue_assets() {
        if ( ! is_singular( 'ddcwwfcsc_event' ) ) {
            return;
        }

        wp_enqueue_style(
            'ddcwwfcsc-event-front',
            DDCWWFCSC_PLUGIN_URL . 'assets/css/event-front.css',
            array(),
            DDCWWFCSC_VERSION
        );

        if ( is_user_logged_in() ) {
            wp_enqueue_script(
                'ddcwwfcsc-event-signup',
                DDCWWFCSC_PLUGIN_URL . 'assets/js/event-signup.js',
                array(),
                DDCWWFCSC_VERSION,
                true
            );

            wp_localize_script( 'ddcwwfcsc-event-signup', 'ddcwwfcsc_event_signup', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'ddcwwfcsc_event_signup' ),
                'post_id'  => get_the_ID(),
            ) );
        }
    }

    /**
     * Handle the sign-up AJAX request.
     */
    public static function handle_signup() {
        // Require login (defence-in-depth).
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in to sign up.', 'ddcwwfcsc' ) ) );
        }

        check_ajax_referer( 'ddcwwfcsc_event_signup', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        if ( ! $post_id || 'ddcwwfcsc_event' !== get_post_type( $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid event.', 'ddcwwfcsc' ) ) );
        }

        $current_user = wp_get_current_user();
        $name         = $current_user->display_name;
        $email        = $current_user->user_email;

        // Prevent duplicate sign-ups.
        $signups = DDCWWFCSC_Event_CPT::get_signups( $post_id );
        foreach ( $signups as $signup ) {
            if ( isset( $signup['email'] ) && $signup['email'] === $email ) {
                wp_send_json_error( array( 'message' => __( "You're already signed up for this event.", 'ddcwwfcsc' ) ) );
            }
        }

        $count = DDCWWFCSC_Event_CPT::add_signup( $post_id, $name, $email );

        DDCWWFCSC_Notifications::send_event_signup_confirmation( $post_id, $name, $email );
        DDCWWFCSC_Notifications::send_event_signup_notification( $post_id, $name, $email, $count );

        wp_send_json_success( array(
            'message' => __( 'You have been signed up successfully!', 'ddcwwfcsc' ),
            'count'   => $count,
        ) );
    }

    /**
     * Enqueue Google Maps API and event map script.
     */
    private static function enqueue_map_scripts( $api_key ) {
        wp_enqueue_script(
            'ddcwwfcsc-event-map',
            DDCWWFCSC_PLUGIN_URL . 'assets/js/event-map.js',
            array(),
            DDCWWFCSC_VERSION,
            true
        );

        wp_enqueue_script(
            'google-maps-api',
            'https://maps.googleapis.com/maps/api/js?key=' . urlencode( $api_key ) . '&callback=ddcwwfcscInitEventMaps',
            array( 'ddcwwfcsc-event-map' ),
            null,
            true
        );
    }
}
