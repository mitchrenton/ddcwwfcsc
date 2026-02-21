<?php
/**
 * Member invite management.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DDCWWFCSC_Invites {

	/**
	 * Generate a new invite and send the email.
	 * If a pending invite already exists for the email, it is resent.
	 *
	 * @param string $email      Invitee email address.
	 * @param int    $invited_by Inviter user ID.
	 * @param string $paid_season Optional season name (e.g. "2024/25") to pre-mark the account as paid for.
	 * @return true|WP_Error
	 */
	public static function send_invite( $email, $invited_by, $paid_season = '' ) {
		$email      = sanitize_email( $email );
		$paid_season = $paid_season ? sanitize_text_field( $paid_season ) : null;

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'ddcwwfcsc' ) );
		}

		if ( email_exists( $email ) ) {
			return new WP_Error( 'already_member', __( 'A member account already exists for this email address.', 'ddcwwfcsc' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'ddcwwfcsc_invites';

		// Resend if there is already a pending invite for this email, updating
		// the paid_season in case it has changed.
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE email = %s AND accepted_at IS NULL",
			$email
		) );

		if ( $existing ) {
			if ( $paid_season !== $existing->paid_season ) {
				$wpdb->update(
					$table,
					array( 'paid_season' => $paid_season ),
					array( 'id' => $existing->id ),
					array( $paid_season ? '%s' : 'NULL' ),
					array( '%d' )
				);
			}
			self::send_invite_email( $email, $existing->token );
			return true;
		}

		$token = bin2hex( random_bytes( 32 ) );

		$wpdb->insert(
			$table,
			array(
				'email'      => $email,
				'token'      => $token,
				'invited_by' => absint( $invited_by ),
				'invited_at' => current_time( 'mysql' ),
				'paid_season' => $paid_season,
			),
			array( '%s', '%s', '%d', '%s', $paid_season ? '%s' : 'NULL' )
		);

		self::send_invite_email( $email, $token );

		return true;
	}

	/**
	 * Validate an invite token.
	 *
	 * @param string $token The invite token.
	 * @return object|null The invite row, or null if invalid or already used.
	 */
	public static function validate_token( $token ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ddcwwfcsc_invites';

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE token = %s AND accepted_at IS NULL",
			$token
		) );
	}

	/**
	 * Accept an invite and create the member account.
	 *
	 * @param string $token      The invite token.
	 * @param string $first_name First name.
	 * @param string $last_name  Last name.
	 * @param string $password   The chosen password.
	 * @return int|WP_Error New user ID on success, WP_Error on failure.
	 */
	public static function accept_invite( $token, $first_name, $last_name, $password ) {
		$invite = self::validate_token( $token );

		if ( ! $invite ) {
			return new WP_Error( 'invalid_token', __( 'This invite link is invalid or has already been used.', 'ddcwwfcsc' ) );
		}

		if ( email_exists( $invite->email ) ) {
			return new WP_Error( 'already_registered', __( 'An account already exists for this email address.', 'ddcwwfcsc' ) );
		}

		// Generate a unique username from the member's name.
		$base     = sanitize_user( strtolower( $first_name . '.' . $last_name ), true );
		$username = $base;
		$counter  = 1;
		while ( username_exists( $username ) ) {
			$username = $base . $counter;
			$counter++;
		}

		$user_id = wp_create_user( $username, $password, $invite->email );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		wp_update_user( array(
			'ID'           => $user_id,
			'first_name'   => sanitize_text_field( $first_name ),
			'last_name'    => sanitize_text_field( $last_name ),
			'display_name' => sanitize_text_field( trim( $first_name . ' ' . $last_name ) ),
			'role'         => 'ddcwwfcsc_member',
		) );

		// Apply pre-paid status if the invite carried one.
		if ( ! empty( $invite->paid_season ) ) {
			update_user_meta( $user_id, '_ddcwwfcsc_paid_season', $invite->paid_season );
		}

		// Mark invite as accepted.
		global $wpdb;
		$table = $wpdb->prefix . 'ddcwwfcsc_invites';
		$wpdb->update(
			$table,
			array( 'accepted_at' => current_time( 'mysql' ) ),
			array( 'id' => $invite->id ),
			array( '%s' ),
			array( '%d' )
		);

		return $user_id;
	}

	/**
	 * Get all invites for the admin view.
	 *
	 * @return array
	 */
	public static function get_all_invites() {
		global $wpdb;
		$table = $wpdb->prefix . 'ddcwwfcsc_invites';
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY invited_at DESC" );
	}

	/**
	 * Get the registration URL for a given invite token.
	 *
	 * @param string $token The invite token.
	 * @return string
	 */
	public static function get_register_url( $token ) {
		return add_query_arg(
			array(
				'ddcwwfcsc_page' => 'register',
				'invite'         => $token,
			),
			home_url( '/' )
		);
	}

	/**
	 * Send the invite email to the invitee.
	 *
	 * @param string $email Recipient email.
	 * @param string $token Invite token.
	 */
	private static function send_invite_email( $email, $token ) {
		$register_url = self::get_register_url( $token );
		$site_name    = get_bloginfo( 'name' );

		$subject = sprintf(
			/* translators: %s: site name */
			__( "You've been invited to join %s", 'ddcwwfcsc' ),
			$site_name
		);

		ob_start();
		$template_path = DDCWWFCSC_PLUGIN_DIR . 'templates/emails/invite.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
		$body = ob_get_clean();

		wp_mail( $email, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}
}
