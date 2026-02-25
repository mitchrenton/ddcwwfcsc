<?php
/**
 * Front-end member pages: login, register, account settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DDCWWFCSC_Member_Front {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'handle_page' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );

		// Require login to post comments — point to our login page.
		add_filter( 'option_comment_registration', '__return_true' );
		add_filter( 'comment_form_defaults', array( __CLASS__, 'filter_must_log_in' ) );

		// Override the WP login URL for non-admin contexts.
		add_filter( 'login_url', array( __CLASS__, 'filter_login_url' ), 10, 3 );
	}

	/**
	 * Return the front-end login URL.
	 *
	 * @param string $redirect Optional URL to redirect to after login.
	 * @return string
	 */
	public static function get_login_url( $redirect = '' ) {
		$args = array( 'ddcwwfcsc_page' => 'login' );
		if ( $redirect ) {
			$args['redirect_to'] = rawurlencode( $redirect );
		}
		return add_query_arg( $args, home_url( '/' ) );
	}

	/**
	 * Return the front-end account URL.
	 *
	 * @return string
	 */
	public static function get_account_url() {
		return add_query_arg( 'ddcwwfcsc_page', 'account', home_url( '/' ) );
	}

	/**
	 * Override the WordPress login URL so members land on our front-end page.
	 */
	public static function filter_login_url( $login_url, $redirect, $force_reauth ) {
		if ( is_admin() ) {
			return $login_url;
		}
		return self::get_login_url( $redirect );
	}

	/**
	 * Intercept our custom front-end pages via the ddcwwfcsc_page query var.
	 */
	public static function handle_page() {
		$page = isset( $_GET['ddcwwfcsc_page'] ) ? sanitize_key( $_GET['ddcwwfcsc_page'] ) : '';

		if ( empty( $page ) ) {
			return;
		}

		switch ( $page ) {
			case 'login':
				self::handle_login();
				break;
			case 'register':
				self::handle_register();
				break;
			case 'account':
				self::handle_account();
				break;
			case 'apply':
				self::handle_apply();
				break;
		}
	}

	// -------------------------------------------------------------------------
	// Login
	// -------------------------------------------------------------------------

	/**
	 * Handle the login page (GET display + POST processing).
	 */
	private static function handle_login() {
		if ( is_user_logged_in() ) {
			wp_safe_redirect( self::get_account_url() );
			exit;
		}

		$error = '';

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			if ( ! check_ajax_referer( 'ddcwwfcsc_login', 'ddcwwfcsc_login_nonce', false ) ) {
				$error = __( 'Security check failed. Please try again.', 'ddcwwfcsc' );
			} else {
				$login    = sanitize_text_field( $_POST['log'] ?? '' );
				$password = $_POST['pwd'] ?? '';
				$remember = ! empty( $_POST['rememberme'] );

				$user = wp_signon(
					array(
						'user_login'    => $login,
						'user_password' => $password,
						'remember'      => $remember,
					),
					is_ssl()
				);

				if ( is_wp_error( $user ) ) {
					$error = __( 'Incorrect username/email or password.', 'ddcwwfcsc' );
				} else {
					wp_safe_redirect( add_query_arg( 'ae', 'login', self::get_safe_redirect() ) );
					exit;
				}
			}
		}

		self::render_page( 'login', compact( 'error' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Register
	// -------------------------------------------------------------------------

	/**
	 * Handle the registration page.
	 */
	private static function handle_register() {
		if ( is_user_logged_in() ) {
			wp_safe_redirect( self::get_account_url() );
			exit;
		}

		$token  = sanitize_text_field( $_GET['invite'] ?? '' );
		$invite = $token ? DDCWWFCSC_Invites::validate_token( $token ) : null;
		$error  = '';

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			if ( ! check_ajax_referer( 'ddcwwfcsc_register', 'ddcwwfcsc_register_nonce', false ) ) {
				$error = __( 'Security check failed. Please try again.', 'ddcwwfcsc' );
			} else {
				$posted_token = sanitize_text_field( $_POST['invite_token'] ?? '' );
				$first_name   = sanitize_text_field( $_POST['first_name'] ?? '' );
				$last_name    = sanitize_text_field( $_POST['last_name'] ?? '' );
				$password     = $_POST['password'] ?? '';
				$password2    = $_POST['password2'] ?? '';

				if ( ! $first_name || ! $last_name ) {
					$error = __( 'Please enter your first and last name.', 'ddcwwfcsc' );
				} elseif ( strlen( $password ) < 8 ) {
					$error = __( 'Password must be at least 8 characters.', 'ddcwwfcsc' );
				} elseif ( $password !== $password2 ) {
					$error = __( 'Passwords do not match.', 'ddcwwfcsc' );
				} else {
					$result = DDCWWFCSC_Invites::accept_invite( $posted_token, $first_name, $last_name, $password );

					if ( is_wp_error( $result ) ) {
						$error = $result->get_error_message();
					} else {
						wp_set_auth_cookie( $result, false );
						wp_safe_redirect( add_query_arg( 'ae', 'register', self::get_account_url() ) );
						exit;
					}
				}

				// Refresh invite reference after an error.
				$token  = $posted_token;
				$invite = DDCWWFCSC_Invites::validate_token( $posted_token );
			}
		}

		self::render_page( 'register', compact( 'token', 'invite', 'error' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Account settings
	// -------------------------------------------------------------------------

	/**
	 * Handle the account settings page.
	 */
	private static function handle_account() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( self::get_login_url( self::get_account_url() ) );
			exit;
		}

		$current_user = wp_get_current_user();
		$error_email  = '';
		$error_pass   = '';
		$error_avatar = '';
		$success      = '';

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$action = sanitize_key( $_POST['ddcwwfcsc_account_action'] ?? '' );

			if ( 'update_email' === $action ) {
				if ( ! check_ajax_referer( 'ddcwwfcsc_account_email', 'ddcwwfcsc_account_nonce', false ) ) {
					$error_email = __( 'Security check failed.', 'ddcwwfcsc' );
				} else {
					$new_email = sanitize_email( $_POST['new_email'] ?? '' );

					if ( ! is_email( $new_email ) ) {
						$error_email = __( 'Please enter a valid email address.', 'ddcwwfcsc' );
					} elseif ( $new_email !== $current_user->user_email && email_exists( $new_email ) ) {
						$error_email = __( 'That email address is already in use.', 'ddcwwfcsc' );
					} else {
						wp_update_user( array( 'ID' => $current_user->ID, 'user_email' => $new_email ) );
						$success      = __( 'Email address updated.', 'ddcwwfcsc' );
						$current_user = wp_get_current_user();
					}
				}
			} elseif ( 'update_password' === $action ) {
				if ( ! check_ajax_referer( 'ddcwwfcsc_account_password', 'ddcwwfcsc_account_nonce', false ) ) {
					$error_pass = __( 'Security check failed.', 'ddcwwfcsc' );
				} else {
					$current_pass = $_POST['current_password'] ?? '';
					$new_pass     = $_POST['new_password'] ?? '';
					$new_pass2    = $_POST['new_password2'] ?? '';

					if ( ! wp_check_password( $current_pass, $current_user->user_pass, $current_user->ID ) ) {
						$error_pass = __( 'Current password is incorrect.', 'ddcwwfcsc' );
					} elseif ( strlen( $new_pass ) < 8 ) {
						$error_pass = __( 'New password must be at least 8 characters.', 'ddcwwfcsc' );
					} elseif ( $new_pass !== $new_pass2 ) {
						$error_pass = __( 'New passwords do not match.', 'ddcwwfcsc' );
					} else {
						wp_set_password( $new_pass, $current_user->ID );
						wp_set_auth_cookie( $current_user->ID, false );
						$success = __( 'Password updated.', 'ddcwwfcsc' );
					}
				}
			} elseif ( 'update_avatar' === $action ) {
				if ( ! check_ajax_referer( 'ddcwwfcsc_account_avatar', 'ddcwwfcsc_account_nonce', false ) ) {
					$error_avatar = __( 'Security check failed.', 'ddcwwfcsc' );
				} elseif ( isset( $_POST['remove_avatar'] ) ) {
					$existing_id = (int) get_user_meta( $current_user->ID, DDCWWFCSC_Custom_Avatar::META_KEY, true );
					if ( $existing_id ) {
						wp_delete_attachment( $existing_id, true );
					}
					delete_user_meta( $current_user->ID, DDCWWFCSC_Custom_Avatar::META_KEY );
					$success = __( 'Profile photo removed.', 'ddcwwfcsc' );
				} elseif ( ! empty( $_FILES['avatar']['name'] ) ) {
					require_once ABSPATH . 'wp-admin/includes/image.php';
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';

					$attachment_id = media_handle_upload( 'avatar', 0 );

					if ( is_wp_error( $attachment_id ) ) {
						$error_avatar = $attachment_id->get_error_message();
					} else {
						$existing_id = (int) get_user_meta( $current_user->ID, DDCWWFCSC_Custom_Avatar::META_KEY, true );
						if ( $existing_id ) {
							wp_delete_attachment( $existing_id, true );
						}
						update_user_meta( $current_user->ID, DDCWWFCSC_Custom_Avatar::META_KEY, $attachment_id );
						$success = __( 'Profile photo updated.', 'ddcwwfcsc' );
					}
				}
			}
		}

		$paid_season    = get_user_meta( $current_user->ID, '_ddcwwfcsc_paid_season', true );
		$current_season = get_option( 'ddcwwfcsc_current_season', '' );
		$is_paid        = $paid_season && $current_season && $paid_season === $current_season;
		$has_avatar     = (bool) get_user_meta( $current_user->ID, DDCWWFCSC_Custom_Avatar::META_KEY, true );
		$membership_fees = array(
			'standard'      => (float) get_option( 'ddcwwfcsc_membership_fee_standard', 0 ),
			'concessionary' => (float) get_option( 'ddcwwfcsc_membership_fee_concessionary', 0 ),
			'junior'        => (float) get_option( 'ddcwwfcsc_membership_fee_junior', 0 ),
		);

		// When returning from a successful membership checkout, verify with Stripe and persist
		// the paid status — the webhook may not have fired yet (e.g. on staging/localhost).
		if ( ! $success && isset( $_GET['payment_status'] ) && 'membership_paid' === sanitize_key( $_GET['payment_status'] ) ) {
			if ( ! $is_paid ) {
				$session_id = get_user_meta( $current_user->ID, '_ddcwwfcsc_membership_session_id', true );
				if ( $session_id ) {
					$secret_key = get_option( 'ddcwwfcsc_stripe_secret_key', '' );
					if ( $secret_key ) {
						try {
							\Stripe\Stripe::setApiKey( $secret_key );
							$stripe_session = \Stripe\Checkout\Session::retrieve( $session_id );
							if ( 'paid' === $stripe_session->payment_status ) {
								$membership_type = sanitize_key( $stripe_session->metadata->membership_type ?? '' );
								DDCWWFCSC_Payments::mark_membership_as_paid( $current_user->ID, $membership_type );
								// Refresh local vars from the now-updated meta.
								$paid_season = get_user_meta( $current_user->ID, '_ddcwwfcsc_paid_season', true );
								$is_paid     = $paid_season && $current_season && $paid_season === $current_season;
							}
						} catch ( \Exception $e ) {
							error_log( 'DDCWWFCSC membership verification error: ' . $e->getMessage() );
						}
					}
				}
			}

			if ( $is_paid ) {
				$success = $current_season
					? sprintf(
						/* translators: %s: season name e.g. "2024/25" */
						__( 'Annual fee paid for %s — thank you!', 'ddcwwfcsc' ),
						$current_season
					)
					: __( 'Annual fee paid — thank you!', 'ddcwwfcsc' );
			}
		}

		self::render_page( 'account', compact( 'current_user', 'error_email', 'error_pass', 'error_avatar', 'success', 'paid_season', 'current_season', 'is_paid', 'has_avatar', 'membership_fees' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Apply
	// -------------------------------------------------------------------------

	/**
	 * Handle the membership application page.
	 */
	private static function handle_apply() {
		if ( is_user_logged_in() ) {
			wp_safe_redirect( self::get_account_url() );
			exit;
		}

		$submitted = false;
		$error     = '';

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			if ( ! check_ajax_referer( 'ddcwwfcsc_apply', 'ddcwwfcsc_apply_nonce', false ) ) {
				$error = __( 'Security check failed. Please try again.', 'ddcwwfcsc' );
			} else {
				$first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
				$last_name  = sanitize_text_field( $_POST['last_name'] ?? '' );
				$email      = sanitize_email( $_POST['email'] ?? '' );

				if ( ! $first_name || ! $last_name ) {
					$error = __( 'Please enter your first and last name.', 'ddcwwfcsc' );
				} elseif ( ! is_email( $email ) ) {
					$error = __( 'Please enter a valid email address.', 'ddcwwfcsc' );
				} else {
					// If the email already has an account or a pending application,
					// show success without revealing that — prevents enumeration.
					if ( ! email_exists( $email ) ) {
						global $wpdb;
						$table    = $wpdb->prefix . 'ddcwwfcsc_applications';
						$existing = $wpdb->get_var( $wpdb->prepare(
							"SELECT id FROM {$table} WHERE email = %s AND status = 'pending'",
							$email
						) );

						if ( ! $existing ) {
							$wpdb->insert(
								$table,
								array(
									'first_name' => $first_name,
									'last_name'  => $last_name,
									'email'      => $email,
									'status'     => 'pending',
									'applied_at' => current_time( 'mysql' ),
								),
								array( '%s', '%s', '%s', '%s', '%s' )
							);

							self::notify_president_of_application( $first_name, $last_name, $email );
						}
					}

					$submitted = true;
				}
			}
		}

		self::render_page( 'apply', compact( 'submitted', 'error' ) );
		exit;
	}

	/**
	 * Send a new-application notification to the president and admins.
	 *
	 * @param string $first_name Applicant first name.
	 * @param string $last_name  Applicant last name.
	 * @param string $email      Applicant email.
	 */
	private static function notify_president_of_application( $first_name, $last_name, $email ) {
		$full_name = trim( $first_name . ' ' . $last_name );
		$admin_url = add_query_arg( 'page', 'ddcwwfcsc-members', admin_url( 'admin.php' ) );

		$subject = sprintf(
			/* translators: %s: applicant name */
			__( 'New Membership Application — %s', 'ddcwwfcsc' ),
			$full_name
		);

		ob_start();
		$template_path = DDCWWFCSC_PLUGIN_DIR . 'templates/emails/application-notification.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
		$body = ob_get_clean();

		$recipients = array();
		foreach ( get_users( array( 'role' => 'ddcwwfcsc_president' ) ) as $u ) {
			$recipients[] = $u->user_email;
		}
		foreach ( get_users( array( 'role' => 'administrator' ) ) as $u ) {
			$recipients[] = $u->user_email;
		}
		$recipients = array_unique( $recipients );

		foreach ( $recipients as $recipient ) {
			wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
		}
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Render a front-end member page inside the theme header/footer.
	 *
	 * @param string $template Template slug: login, register, or account.
	 * @param array  $vars     Variables extracted into template scope.
	 */
	private static function render_page( $template, $vars = array() ) {
		wp_enqueue_style(
			'ddcwwfcsc-member-auth',
			DDCWWFCSC_PLUGIN_URL . 'assets/css/member-auth.css',
			array(),
			DDCWWFCSC_VERSION
		);

		get_header();

		$template_path = DDCWWFCSC_PLUGIN_DIR . 'templates/' . $template . '-page.php';
		if ( file_exists( $template_path ) ) {
			extract( $vars, EXTR_SKIP );
			include $template_path;
		}

		get_footer();
	}

	/**
	 * Enqueue the member auth stylesheet when on a member page.
	 */
	public static function enqueue_styles() {
		$page = isset( $_GET['ddcwwfcsc_page'] ) ? sanitize_key( $_GET['ddcwwfcsc_page'] ) : '';
		if ( in_array( $page, array( 'login', 'register', 'account', 'apply' ), true ) ) {
			wp_enqueue_style(
				'ddcwwfcsc-member-auth',
				DDCWWFCSC_PLUGIN_URL . 'assets/css/member-auth.css',
				array(),
				DDCWWFCSC_VERSION
			);
		}
	}

	/**
	 * Get a safe post-login redirect URL (never wp-admin or wp-login.php).
	 *
	 * @return string
	 */
	private static function get_safe_redirect() {
		$redirect = isset( $_REQUEST['redirect_to'] ) ? rawurldecode( $_REQUEST['redirect_to'] ) : '';

		if ( $redirect ) {
			$redirect = wp_sanitize_redirect( $redirect );
			if (
				strpos( $redirect, admin_url() ) !== false ||
				strpos( $redirect, 'wp-login.php' ) !== false
			) {
				return home_url( '/' );
			}
			return $redirect;
		}

		$referer = wp_get_referer();
		if ( $referer && strpos( $referer, 'ddcwwfcsc_page=login' ) === false ) {
			return $referer;
		}

		return home_url( '/' );
	}

	/**
	 * Customise the "must be logged in" comment form message to point to our
	 * front-end login page.
	 *
	 * @param array $defaults Comment form defaults.
	 * @return array
	 */
	public static function filter_must_log_in( $defaults ) {
		$login_url              = self::get_login_url( get_permalink() );
		$defaults['must_log_in'] = '<p class="must-log-in">' . sprintf(
			/* translators: %s: login URL */
			__( 'You must be <a href="%s">logged in</a> to post a comment.', 'ddcwwfcsc' ),
			esc_url( $login_url )
		) . '</p>';
		return $defaults;
	}
}
