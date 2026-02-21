<?php
/**
 * Email notification handling.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Notifications {

    /**
     * Initialize hooks.
     */
    public static function init() {
        // No global hooks needed — headers are set per email.
    }

    /**
     * Send an HTML email via wp_mail.
     */
    private static function send_html_mail( $to, $subject, $body ) {
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $to, $subject, $body, $headers );
    }

    /**
     * Send confirmation email to the ticket requester.
     */
    public static function send_confirmation( $request_id, $fixture_id, $name, $email, $num_tickets ) {
        $fixture       = get_post( $fixture_id );
        $opponent_data = DDCWWFCSC_Fixture_CPT::get_opponent( $fixture_id );
        $opponent      = $opponent_data ? $opponent_data['name'] : '';
        $date          = get_post_meta( $fixture_id, '_ddcwwfcsc_match_date', true );
        // Get price info.
        $price_text = self::get_price_text( $fixture_id );

        $formatted_date = $date ? wp_date( 'l j F Y, H:i', strtotime( $date ) ) : __( 'TBC', 'ddcwwfcsc' );

        $template_vars = array(
            'name'           => $name,
            'num_tickets'    => $num_tickets,
            'opponent'       => $opponent,
            'match_date'     => $formatted_date,
            'price_text'     => $price_text,
            'fixture_title'  => $fixture ? $fixture->post_title : '',
        );

        $subject = sprintf(
            /* translators: %s: opponent team name */
            __( 'Ticket Request Received — %s', 'ddcwwfcsc' ),
            $opponent ?: ( $fixture ? $fixture->post_title : '' )
        );

        $body = self::render_template( 'request-confirmation', $template_vars );

        self::send_html_mail( $email, $subject, $body );
    }

    /**
     * Send notification email to all users with the President role.
     */
    public static function send_president_notification( $request_id, $fixture_id, $name, $email, $num_tickets, $remaining ) {
        $fixture       = get_post( $fixture_id );
        $opponent_data = DDCWWFCSC_Fixture_CPT::get_opponent( $fixture_id );
        $opponent      = $opponent_data ? $opponent_data['name'] : '';
        $total         = get_post_meta( $fixture_id, '_ddcwwfcsc_total_tickets', true );

        $template_vars = array(
            'requester_name'  => $name,
            'requester_email' => $email,
            'num_tickets'     => $num_tickets,
            'opponent'        => $opponent,
            'fixture_title'   => $fixture ? $fixture->post_title : '',
            'remaining'       => $remaining,
            'total'           => $total,
        );

        $subject = sprintf(
            /* translators: %s: opponent team name */
            __( 'New Ticket Request — %s', 'ddcwwfcsc' ),
            $opponent ?: ( $fixture ? $fixture->post_title : '' )
        );

        $body = self::render_template( 'request-notification', $template_vars );

        // Get all President users.
        $presidents = get_users( array( 'role' => 'ddcwwfcsc_president' ) );
        $recipients = wp_list_pluck( $presidents, 'user_email' );

        // Also include administrators.
        $admins     = get_users( array( 'role' => 'administrator' ) );
        $recipients = array_merge( $recipients, wp_list_pluck( $admins, 'user_email' ) );
        $recipients = array_unique( $recipients );

        foreach ( $recipients as $recipient ) {
            self::send_html_mail( $recipient, $subject, $body );
        }
    }

    /**
     * Send confirmation email to the event sign-up attendee.
     */
    public static function send_event_signup_confirmation( $post_id, $name, $email ) {
        $event            = get_post( $post_id );
        $event_date       = get_post_meta( $post_id, '_ddcwwfcsc_event_date', true );
        $meeting_time     = get_post_meta( $post_id, '_ddcwwfcsc_event_meeting_time', true );
        $meeting_location = get_post_meta( $post_id, '_ddcwwfcsc_event_meeting_location', true );
        $venue            = get_post_meta( $post_id, '_ddcwwfcsc_event_location', true );
        $price_member     = get_post_meta( $post_id, '_ddcwwfcsc_event_price_member', true );
        $price_non_member = get_post_meta( $post_id, '_ddcwwfcsc_event_price_non_member', true );

        $cost_parts = array();
        if ( $price_member ) {
            $cost_parts[] = sprintf( __( 'Members: £%s', 'ddcwwfcsc' ), number_format( (float) $price_member, 2 ) );
        }
        if ( $price_non_member ) {
            $cost_parts[] = sprintf( __( 'Non-members: £%s', 'ddcwwfcsc' ), number_format( (float) $price_non_member, 2 ) );
        }
        $cost = implode( ' / ', $cost_parts );

        $template_vars = array(
            'name'             => $name,
            'event_title'      => $event ? $event->post_title : '',
            'event_date'       => $event_date ? wp_date( 'l j F Y, g:i a', strtotime( $event_date ) ) : '',
            'meeting_time'     => $meeting_time ? wp_date( 'g:i a', strtotime( $meeting_time ) ) : '',
            'meeting_location' => $meeting_location,
            'venue'            => $venue,
            'cost'             => $cost,
        );

        $subject = sprintf(
            /* translators: %s: event title */
            __( 'Event Sign-up Confirmation — %s', 'ddcwwfcsc' ),
            $event ? $event->post_title : ''
        );

        $body = self::render_template( 'event-signup-confirmation', $template_vars );

        self::send_html_mail( $email, $subject, $body );
    }

    /**
     * Send notification email to president + admins about a new event sign-up.
     */
    public static function send_event_signup_notification( $post_id, $name, $email, $count ) {
        $event = get_post( $post_id );

        $template_vars = array(
            'attendee_name'  => $name,
            'attendee_email' => $email,
            'event_title'    => $event ? $event->post_title : '',
            'signup_count'   => $count,
            'event_edit_url' => get_edit_post_link( $post_id, 'raw' ),
        );

        $subject = sprintf(
            /* translators: %s: event title */
            __( 'New Event Sign-up — %s', 'ddcwwfcsc' ),
            $event ? $event->post_title : ''
        );

        $body = self::render_template( 'event-signup-notification', $template_vars );

        // Get all President users.
        $presidents = get_users( array( 'role' => 'ddcwwfcsc_president' ) );
        $recipients = wp_list_pluck( $presidents, 'user_email' );

        // Also include administrators.
        $admins     = get_users( array( 'role' => 'administrator' ) );
        $recipients = array_merge( $recipients, wp_list_pluck( $admins, 'user_email' ) );
        $recipients = array_unique( $recipients );

        foreach ( $recipients as $recipient ) {
            self::send_html_mail( $recipient, $subject, $body );
        }
    }

    /**
     * Send payment link email to the ticket requester.
     */
    public static function send_payment_link( $request_id, $fixture_id, $name, $email, $num_tickets, $amount, $token ) {
        $fixture       = get_post( $fixture_id );
        $opponent_data = DDCWWFCSC_Fixture_CPT::get_opponent( $fixture_id );
        $opponent      = $opponent_data ? $opponent_data['name'] : '';
        $date          = get_post_meta( $fixture_id, '_ddcwwfcsc_match_date', true );
        $formatted_date = $date ? wp_date( 'l j F Y, H:i', strtotime( $date ) ) : __( 'TBC', 'ddcwwfcsc' );
        $payment_url   = DDCWWFCSC_Payments::get_payment_url( $token );

        $template_vars = array(
            'name'          => $name,
            'num_tickets'   => $num_tickets,
            'opponent'      => $opponent,
            'match_date'    => $formatted_date,
            'amount'        => $amount,
            'payment_url'   => $payment_url,
            'fixture_title' => $fixture ? $fixture->post_title : '',
        );

        $subject = sprintf(
            /* translators: %s: opponent team name */
            __( 'Payment Required — %s', 'ddcwwfcsc' ),
            $opponent ?: ( $fixture ? $fixture->post_title : '' )
        );

        $body = self::render_template( 'payment-link', $template_vars );

        self::send_html_mail( $email, $subject, $body );
    }

    /**
     * Send payment confirmation email to the ticket requester.
     */
    public static function send_payment_confirmation( $request_id, $fixture_id, $name, $email, $num_tickets, $amount, $payment_method ) {
        $fixture       = get_post( $fixture_id );
        $opponent_data = DDCWWFCSC_Fixture_CPT::get_opponent( $fixture_id );
        $opponent      = $opponent_data ? $opponent_data['name'] : '';
        $date          = get_post_meta( $fixture_id, '_ddcwwfcsc_match_date', true );
        $formatted_date = $date ? wp_date( 'l j F Y, H:i', strtotime( $date ) ) : __( 'TBC', 'ddcwwfcsc' );

        $template_vars = array(
            'name'           => $name,
            'num_tickets'    => $num_tickets,
            'opponent'       => $opponent,
            'match_date'     => $formatted_date,
            'amount'         => $amount,
            'payment_method' => $payment_method,
            'fixture_title'  => $fixture ? $fixture->post_title : '',
        );

        $subject = sprintf(
            /* translators: %s: opponent team name */
            __( 'Payment Confirmed — %s', 'ddcwwfcsc' ),
            $opponent ?: ( $fixture ? $fixture->post_title : '' )
        );

        $body = self::render_template( 'payment-confirmation', $template_vars );

        self::send_html_mail( $email, $subject, $body );
    }

    /**
     * Render an email template with variables.
     */
    private static function render_template( $template_name, $vars ) {
        $template_path = DDCWWFCSC_PLUGIN_DIR . 'templates/emails/' . $template_name . '.php';

        if ( ! file_exists( $template_path ) ) {
            return '';
        }

        extract( $vars, EXTR_SKIP );
        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Get formatted price text for a fixture.
     */
    private static function get_price_text( $fixture_id ) {
        $terms = get_the_terms( $fixture_id, 'ddcwwfcsc_price_category' );

        if ( ! $terms || is_wp_error( $terms ) ) {
            return '';
        }

        $term  = $terms[0];
        $price = get_term_meta( $term->term_id, '_ddcwwfcsc_price', true );

        if ( ! $price ) {
            return $term->name;
        }

        return $term->name . ' — £' . number_format( (float) $price, 2 );
    }
}
