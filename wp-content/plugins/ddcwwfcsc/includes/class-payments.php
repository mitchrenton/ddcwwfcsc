<?php
/**
 * Stripe payment integration for ticket requests.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Payments {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_webhook_route' ) );
        add_action( 'template_redirect', array( __CLASS__, 'handle_payment_page' ) );
        add_action( 'template_redirect', array( __CLASS__, 'handle_membership_checkout' ) );
        add_action( 'ddcwwfcsc_expire_payment_links', array( __CLASS__, 'expire_payment_links' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_payment_styles' ) );
    }

    /**
     * Approve a ticket request and send a payment link.
     *
     * @param int $request_id The ticket request ID.
     * @return bool True on success, false on failure.
     */
    public static function approve_request( $request_id ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'ddcwwfcsc_ticket_requests';
        $request = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $request_id ) );

        if ( ! $request || 'pending' !== $request->status ) {
            return false;
        }

        $token      = self::generate_payment_token();
        $amount     = self::calculate_amount( $request->fixture_id, $request->num_tickets );
        $now        = current_time( 'mysql' );
        $expires_at = gmdate( 'Y-m-d H:i:s', strtotime( $now ) + DAY_IN_SECONDS );

        $wpdb->update(
            $table,
            array(
                'status'             => 'approved',
                'payment_token'      => $token,
                'amount'             => $amount,
                'approved_at'        => $now,
                'payment_expires_at' => $expires_at,
            ),
            array( 'id' => $request_id ),
            array( '%s', '%s', '%f', '%s', '%s' ),
            array( '%d' )
        );

        DDCWWFCSC_Notifications::send_payment_link(
            $request_id,
            $request->fixture_id,
            $request->name,
            $request->email,
            $request->num_tickets,
            $amount,
            $token
        );

        return true;
    }

    /**
     * Calculate the total amount for a ticket request.
     *
     * @param int $fixture_id  The fixture post ID.
     * @param int $num_tickets Number of tickets.
     * @return float The total amount.
     */
    public static function calculate_amount( $fixture_id, $num_tickets ) {
        $terms = get_the_terms( $fixture_id, 'ddcwwfcsc_price_category' );

        if ( ! $terms || is_wp_error( $terms ) ) {
            return 0.00;
        }

        $term       = $terms[0];
        $unit_price = (float) get_term_meta( $term->term_id, '_ddcwwfcsc_price', true );

        return round( $unit_price * (int) $num_tickets, 2 );
    }

    /**
     * Generate a secure payment token.
     *
     * @return string 64-character hex token.
     */
    private static function generate_payment_token() {
        return bin2hex( random_bytes( 32 ) );
    }

    /**
     * Get the payment page URL for a token.
     *
     * @param string $token The payment token.
     * @return string The payment URL.
     */
    public static function get_payment_url( $token ) {
        return add_query_arg( 'ddcwwfcsc_payment', $token, home_url( '/' ) );
    }

    /**
     * Handle payment page rendering on template_redirect.
     */
    public static function handle_payment_page() {
        if ( ! isset( $_GET['ddcwwfcsc_payment'] ) ) {
            return;
        }

        $token = sanitize_text_field( $_GET['ddcwwfcsc_payment'] );
        if ( empty( $token ) ) {
            return;
        }

        global $wpdb;
        $table   = $wpdb->prefix . 'ddcwwfcsc_ticket_requests';
        $request = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE payment_token = %s",
            $token
        ) );

        // Determine the page state.
        if ( ! $request ) {
            $state = 'invalid';
        } elseif ( 'paid' === $request->status ) {
            // If the user just returned from Stripe, show the success message
            // even though the webhook already marked it as paid.
            $state = ( isset( $_GET['payment_status'] ) && 'success' === $_GET['payment_status'] )
                ? 'success'
                : 'already_paid';
        } elseif ( 'cancelled' === $request->status ) {
            $state = 'expired';
        } elseif ( 'approved' === $request->status && $request->payment_expires_at && strtotime( $request->payment_expires_at ) < current_time( 'timestamp' ) ) {
            $state = 'expired';
        } elseif ( 'approved' !== $request->status ) {
            $state = 'invalid';
        } else {
            // Check for Stripe redirect parameters.
            if ( isset( $_GET['payment_status'] ) && 'success' === $_GET['payment_status'] ) {
                $state = 'success';
            } elseif ( isset( $_GET['payment_status'] ) && 'cancelled' === $_GET['payment_status'] ) {
                $state = 'cancelled_checkout';
            } elseif ( isset( $_GET['checkout'] ) && '1' === $_GET['checkout'] ) {
                self::redirect_to_stripe( $request );
                return;
            } else {
                $state = 'ready';
            }
        }

        self::render_payment_page( $state, $request );
        exit;
    }

    /**
     * Create a Stripe Checkout Session and redirect.
     *
     * @param object $request The ticket request row.
     */
    private static function redirect_to_stripe( $request ) {
        $secret_key = get_option( 'ddcwwfcsc_stripe_secret_key', '' );

        if ( empty( $secret_key ) ) {
            wp_die( esc_html__( 'Stripe is not configured. Please contact the club.', 'ddcwwfcsc' ) );
        }

        \Stripe\Stripe::setApiKey( $secret_key );

        $fixture       = get_post( $request->fixture_id );
        $opponent_data = DDCWWFCSC_Fixture_CPT::get_opponent( $request->fixture_id );
        $opponent      = $opponent_data ? $opponent_data['name'] : '';
        $match_label   = $opponent ? 'Wolves v ' . $opponent : ( $fixture ? $fixture->post_title : 'Match Ticket' );

        $terms      = get_the_terms( $request->fixture_id, 'ddcwwfcsc_price_category' );
        $unit_price = 0;
        if ( $terms && ! is_wp_error( $terms ) ) {
            $unit_price = (float) get_term_meta( $terms[0]->term_id, '_ddcwwfcsc_price', true );
        }

        $payment_url = self::get_payment_url( $request->payment_token );
        $success_url = add_query_arg( 'payment_status', 'success', $payment_url );
        $cancel_url  = add_query_arg( 'payment_status', 'cancelled', $payment_url );

        // Calculate seconds until expiry for Stripe session.
        $expires_in = strtotime( $request->payment_expires_at ) - time();
        // Stripe requires at least 30 minutes and at most 24 hours.
        $stripe_expires = max( 1800, min( $expires_in, 86400 ) );

        try {
            $session = \Stripe\Checkout\Session::create( array(
                'payment_method_types' => array( 'card' ),
                'mode'                 => 'payment',
                'customer_email'       => $request->email,
                'line_items'           => array(
                    array(
                        'price_data' => array(
                            'currency'     => 'gbp',
                            'unit_amount'  => (int) round( $unit_price * 100 ),
                            'product_data' => array(
                                'name' => $match_label,
                            ),
                        ),
                        'quantity' => (int) $request->num_tickets,
                    ),
                ),
                'metadata' => array(
                    'request_id'    => $request->id,
                    'payment_token' => $request->payment_token,
                ),
                'success_url'          => $success_url,
                'cancel_url'           => $cancel_url,
                'expires_at'           => time() + $stripe_expires,
            ) );

            // Store the session ID.
            global $wpdb;
            $table = $wpdb->prefix . 'ddcwwfcsc_ticket_requests';
            $wpdb->update(
                $table,
                array( 'stripe_session_id' => $session->id ),
                array( 'id' => $request->id ),
                array( '%s' ),
                array( '%d' )
            );

            wp_redirect( $session->url );
            exit;
        } catch ( \Exception $e ) {
            error_log( 'DDCWWFCSC Stripe error: ' . $e->getMessage() );
            wp_die( esc_html__( 'Unable to connect to the payment provider. Please try again or contact the club.', 'ddcwwfcsc' ) );
        }
    }

    /**
     * Register the Stripe webhook REST API route.
     */
    public static function register_webhook_route() {
        register_rest_route( 'ddcwwfcsc/v1', '/stripe-webhook', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'handle_webhook' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * Handle incoming Stripe webhook events.
     *
     * @param WP_REST_Request $wp_request The REST request.
     * @return WP_REST_Response
     */
    public static function handle_webhook( $wp_request ) {
        $payload   = $wp_request->get_body();
        $sig       = $wp_request->get_header( 'stripe-signature' );
        $secret    = get_option( 'ddcwwfcsc_stripe_webhook_secret', '' );

        if ( empty( $secret ) ) {
            return new WP_REST_Response( array( 'error' => 'Webhook not configured' ), 400 );
        }

        try {
            $event = \Stripe\Webhook::constructEvent( $payload, $sig, $secret );
        } catch ( \UnexpectedValueException $e ) {
            return new WP_REST_Response( array( 'error' => 'Invalid payload' ), 400 );
        } catch ( \Stripe\Exception\SignatureVerificationException $e ) {
            return new WP_REST_Response( array( 'error' => 'Invalid signature' ), 400 );
        }

        if ( 'checkout.session.completed' === $event->type ) {
            $session = $event->data->object;
            $type    = $session->metadata->type ?? 'ticket';

            if ( 'membership' === $type ) {
                $user_id         = (int) ( $session->metadata->user_id ?? 0 );
                $membership_type = sanitize_key( $session->metadata->membership_type ?? '' );
                if ( $user_id ) {
                    self::mark_membership_as_paid( $user_id, $membership_type );
                }
            } else {
                $request_id = $session->metadata->request_id ?? null;
                if ( $request_id ) {
                    self::mark_as_paid( (int) $request_id, 'stripe', $session->id );
                }
            }
        }

        return new WP_REST_Response( array( 'received' => true ), 200 );
    }

    /**
     * Mark a ticket request as paid.
     *
     * @param int    $request_id     The ticket request ID.
     * @param string $payment_method 'stripe' or 'manual'.
     * @param string $stripe_session Optional Stripe session ID.
     * @return bool True on success, false on failure.
     */
    public static function mark_as_paid( $request_id, $payment_method = 'manual', $stripe_session = null ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'ddcwwfcsc_ticket_requests';
        $request = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $request_id ) );

        if ( ! $request || 'paid' === $request->status ) {
            return false;
        }

        $update_data = array(
            'status'         => 'paid',
            'payment_method' => $payment_method,
            'paid_at'        => current_time( 'mysql' ),
        );
        $update_format = array( '%s', '%s', '%s' );

        if ( $stripe_session ) {
            $update_data['stripe_session_id'] = $stripe_session;
            $update_format[]                  = '%s';
        }

        // If marking paid from pending (manual), set the amount now.
        if ( 'pending' === $request->status && null === $request->amount ) {
            $update_data['amount'] = self::calculate_amount( $request->fixture_id, $request->num_tickets );
            $update_format[]       = '%f';
            $update_data['approved_at'] = current_time( 'mysql' );
            $update_format[]            = '%s';
        }

        $wpdb->update(
            $table,
            $update_data,
            array( 'id' => $request_id ),
            $update_format,
            array( '%d' )
        );

        // Send payment confirmation email.
        $amount = $update_data['amount'] ?? $request->amount;
        DDCWWFCSC_Notifications::send_payment_confirmation(
            $request_id,
            $request->fixture_id,
            $request->name,
            $request->email,
            $request->num_tickets,
            (float) $amount,
            $payment_method
        );

        return true;
    }

    /**
     * Return display labels keyed by membership type slug.
     *
     * @return array<string, string>
     */
    public static function get_membership_type_labels() {
        return array(
            'standard'      => __( 'Standard', 'ddcwwfcsc' ),
            'concessionary' => __( 'Non-working', 'ddcwwfcsc' ),
            'junior'        => __( 'Junior / Student', 'ddcwwfcsc' ),
        );
    }

    /**
     * Handle the membership Stripe checkout redirect.
     * Triggered by /?ddcwwfcsc_membership_checkout=<type> on template_redirect.
     */
    public static function handle_membership_checkout() {
        if ( ! isset( $_GET['ddcwwfcsc_membership_checkout'] ) ) {
            return;
        }

        $type        = sanitize_key( $_GET['ddcwwfcsc_membership_checkout'] );
        $fee_options = array(
            'standard'      => 'ddcwwfcsc_membership_fee_standard',
            'concessionary' => 'ddcwwfcsc_membership_fee_concessionary',
            'junior'        => 'ddcwwfcsc_membership_fee_junior',
        );

        if ( ! array_key_exists( $type, $fee_options ) ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( DDCWWFCSC_Member_Front::get_login_url( DDCWWFCSC_Member_Front::get_account_url() ) );
            exit;
        }

        $user           = wp_get_current_user();
        $paid_season    = get_user_meta( $user->ID, '_ddcwwfcsc_paid_season', true );
        $current_season = get_option( 'ddcwwfcsc_current_season', '' );
        $is_paid        = $paid_season && $current_season && $paid_season === $current_season;

        if ( $is_paid ) {
            wp_safe_redirect( DDCWWFCSC_Member_Front::get_account_url() );
            exit;
        }

        $fee = (float) get_option( $fee_options[ $type ], 0 );
        if ( $fee <= 0 ) {
            wp_die( esc_html__( 'That membership type is not currently available. Please contact the club.', 'ddcwwfcsc' ) );
        }

        $secret_key = get_option( 'ddcwwfcsc_stripe_secret_key', '' );
        if ( empty( $secret_key ) ) {
            wp_die( esc_html__( 'Stripe is not configured. Please contact the club.', 'ddcwwfcsc' ) );
        }

        \Stripe\Stripe::setApiKey( $secret_key );

        $labels       = self::get_membership_type_labels();
        $type_label   = $labels[ $type ] ?? $type;
        $account_url  = DDCWWFCSC_Member_Front::get_account_url();
        $success_url  = add_query_arg( 'payment_status', 'membership_paid', $account_url );
        $cancel_url   = add_query_arg( 'payment_status', 'membership_cancelled', $account_url );
        $season_label = $current_season
            ? sprintf( '%s Membership — %s', $type_label, $current_season )
            : sprintf( '%s Membership', $type_label );

        try {
            $session = \Stripe\Checkout\Session::create( array(
                'payment_method_types' => array( 'card' ),
                'mode'                 => 'payment',
                'customer_email'       => $user->user_email,
                'line_items'           => array(
                    array(
                        'price_data' => array(
                            'currency'     => 'gbp',
                            'unit_amount'  => (int) round( $fee * 100 ),
                            'product_data' => array(
                                'name' => $season_label,
                            ),
                        ),
                        'quantity' => 1,
                    ),
                ),
                'metadata'    => array(
                    'type'            => 'membership',
                    'membership_type' => $type,
                    'user_id'         => $user->ID,
                ),
                'success_url' => $success_url,
                'cancel_url'  => $cancel_url,
            ) );

            wp_redirect( $session->url );
            exit;
        } catch ( \Exception $e ) {
            error_log( 'DDCWWFCSC Membership Stripe error: ' . $e->getMessage() );
            wp_die( esc_html__( 'Unable to connect to the payment provider. Please try again or contact the club.', 'ddcwwfcsc' ) );
        }
    }

    /**
     * Mark a member's annual fee as paid for the current season.
     *
     * @param int    $user_id         The WordPress user ID.
     * @param string $membership_type Membership type slug (standard|concessionary|junior).
     * @return bool True on success, false if no current season is configured.
     */
    public static function mark_membership_as_paid( $user_id, $membership_type = '' ) {
        $current_season = get_option( 'ddcwwfcsc_current_season', '' );
        if ( ! $current_season ) {
            return false;
        }
        update_user_meta( $user_id, '_ddcwwfcsc_paid_season', $current_season );
        if ( $membership_type ) {
            update_user_meta( $user_id, '_ddcwwfcsc_membership_type', $membership_type );
        }
        return true;
    }

    /**
     * Expire approved payment links that have passed their expiry time.
     * Called by the hourly cron event.
     */
    public static function expire_payment_links() {
        global $wpdb;
        $table = $wpdb->prefix . 'ddcwwfcsc_ticket_requests';
        $now   = current_time( 'mysql' );

        $expired = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE status = 'approved' AND payment_expires_at IS NOT NULL AND payment_expires_at < %s",
            $now
        ) );

        if ( empty( $expired ) ) {
            return;
        }

        foreach ( $expired as $request ) {
            // Return tickets to the pool atomically.
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->postmeta}
                     SET meta_value = meta_value + %d
                     WHERE post_id = %d AND meta_key = '_ddcwwfcsc_tickets_remaining'",
                    $request->num_tickets,
                    $request->fixture_id
                )
            );
            wp_cache_delete( $request->fixture_id, 'post_meta' );

            // Mark as cancelled.
            $wpdb->update(
                $table,
                array( 'status' => 'cancelled' ),
                array( 'id' => $request->id ),
                array( '%s' ),
                array( '%d' )
            );
        }
    }

    /**
     * Conditionally enqueue payment page CSS.
     */
    public static function enqueue_payment_styles() {
        if ( ! isset( $_GET['ddcwwfcsc_payment'] ) ) {
            return;
        }

        wp_enqueue_style(
            'ddcwwfcsc-payment-page',
            DDCWWFCSC_PLUGIN_URL . 'assets/css/payment-page.css',
            array(),
            DDCWWFCSC_VERSION
        );
    }

    /**
     * Render the payment page inside the theme's header/footer.
     *
     * @param string      $state   The page state (ready, invalid, expired, already_paid, success, cancelled_checkout).
     * @param object|null $request The ticket request row or null.
     */
    private static function render_payment_page( $state, $request ) {
        // Enqueue styles directly since template_redirect fires before wp_enqueue_scripts for this path.
        wp_enqueue_style(
            'ddcwwfcsc-payment-page',
            DDCWWFCSC_PLUGIN_URL . 'assets/css/payment-page.css',
            array(),
            DDCWWFCSC_VERSION
        );

        get_header();

        $template_path = DDCWWFCSC_PLUGIN_DIR . 'templates/payment-page.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        }

        get_footer();
    }
}
