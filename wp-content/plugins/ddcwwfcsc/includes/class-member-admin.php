<?php
/**
 * Admin page: Members management and invites.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DDCWWFCSC_Member_Admin {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_page' ) );
		add_action( 'admin_post_ddcwwfcsc_send_invite', array( __CLASS__, 'handle_send_invite' ) );
		add_action( 'admin_post_ddcwwfcsc_set_paid_season', array( __CLASS__, 'handle_set_paid_season' ) );
		add_action( 'admin_post_ddcwwfcsc_approve_application', array( __CLASS__, 'handle_approve_application' ) );
		add_action( 'admin_post_ddcwwfcsc_decline_application', array( __CLASS__, 'handle_decline_application' ) );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'add_dashboard_widget' ) );
	}

	/**
	 * Register the top-level Members menu page.
	 */
	public static function add_admin_page() {
		add_menu_page(
			__( 'Members', 'ddcwwfcsc' ),
			__( 'Members', 'ddcwwfcsc' ),
			'manage_ddcwwfcsc_members',
			'ddcwwfcsc-members',
			array( __CLASS__, 'render_admin_page' ),
			'dashicons-groups',
			30
		);
	}

	/**
	 * Render the Members admin page.
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_ddcwwfcsc_members' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage members.', 'ddcwwfcsc' ) );
		}

		$notice = isset( $_GET['ddcwwfcsc_notice'] ) ? sanitize_key( $_GET['ddcwwfcsc_notice'] ) : '';
		$msg    = isset( $_GET['ddcwwfcsc_msg'] ) ? sanitize_text_field( urldecode( $_GET['ddcwwfcsc_msg'] ) ) : '';

		$members         = get_users( array( 'role' => 'ddcwwfcsc_member', 'orderby' => 'registered', 'order' => 'DESC' ) );
		$all_invites     = DDCWWFCSC_Invites::get_all_invites();
		$pending_invites = array_filter( $all_invites, static function ( $inv ) {
			return is_null( $inv->accepted_at );
		} );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Members', 'ddcwwfcsc' ); ?></h1>

			<?php if ( 'invite_sent' === $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Invite sent successfully.', 'ddcwwfcsc' ); ?></p>
				</div>
			<?php elseif ( 'invite_error' === $notice ) : ?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html( $msg ?: __( 'Could not send invite.', 'ddcwwfcsc' ) ); ?></p>
				</div>
			<?php elseif ( 'paid_updated' === $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Membership status updated.', 'ddcwwfcsc' ); ?></p>
				</div>
			<?php elseif ( 'application_approved' === $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Application approved — invite sent.', 'ddcwwfcsc' ); ?></p>
				</div>
			<?php elseif ( 'application_declined' === $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Application declined.', 'ddcwwfcsc' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Pending applications -->
			<?php $applications = self::get_pending_applications(); ?>
			<?php if ( ! empty( $applications ) ) : ?>
				<h2 style="margin-top:0">
					<?php esc_html_e( 'Membership Applications', 'ddcwwfcsc' ); ?>
					<span style="display:inline-flex;align-items:center;justify-content:center;background:#d63638;color:#fff;border-radius:50%;width:22px;height:22px;font-size:12px;font-weight:700;margin-left:6px;vertical-align:middle"><?php echo count( $applications ); ?></span>
				</h2>
				<table class="wp-list-table widefat fixed striped" style="margin-bottom:2em">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'ddcwwfcsc' ); ?></th>
							<th><?php esc_html_e( 'Email', 'ddcwwfcsc' ); ?></th>
							<th style="width:120px"><?php esc_html_e( 'Applied', 'ddcwwfcsc' ); ?></th>
							<th style="width:180px"><?php esc_html_e( 'Actions', 'ddcwwfcsc' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $applications as $app ) : ?>
							<tr>
								<td><strong><?php echo esc_html( trim( $app->first_name . ' ' . $app->last_name ) ); ?></strong></td>
								<td><?php echo esc_html( $app->email ); ?></td>
								<td><?php echo esc_html( wp_date( 'j M Y', strtotime( $app->applied_at ) ) ); ?></td>
								<td style="display:flex;gap:6px">
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<?php wp_nonce_field( 'ddcwwfcsc_approve_application_' . $app->id, 'ddcwwfcsc_app_nonce' ); ?>
										<input type="hidden" name="action" value="ddcwwfcsc_approve_application">
										<input type="hidden" name="application_id" value="<?php echo absint( $app->id ); ?>">
										<button type="submit" class="button button-primary button-small"><?php esc_html_e( 'Approve', 'ddcwwfcsc' ); ?></button>
									</form>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<?php wp_nonce_field( 'ddcwwfcsc_decline_application_' . $app->id, 'ddcwwfcsc_app_nonce' ); ?>
										<input type="hidden" name="action" value="ddcwwfcsc_decline_application">
										<input type="hidden" name="application_id" value="<?php echo absint( $app->id ); ?>">
										<button type="submit" class="button button-small" style="color:#d63638"><?php esc_html_e( 'Decline', 'ddcwwfcsc' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<!-- Invite form -->
			<div style="background:#fff;border:1px solid #c3c4c7;padding:16px 20px;margin:20px 0;max-width:600px">
				<h2 style="margin-top:0"><?php esc_html_e( 'Invite a Member', 'ddcwwfcsc' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'ddcwwfcsc_send_invite', 'ddcwwfcsc_invite_nonce' ); ?>
					<input type="hidden" name="action" value="ddcwwfcsc_send_invite">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="invite_email"><?php esc_html_e( 'Email Address', 'ddcwwfcsc' ); ?></label>
							</th>
							<td>
								<input type="email" name="invite_email" id="invite_email" class="regular-text" required>
								<p class="description"><?php esc_html_e( 'An invite link will be emailed to this address.', 'ddcwwfcsc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Annual Fee', 'ddcwwfcsc' ); ?></th>
							<td>
								<label>
									<input type="checkbox" id="invite_mark_paid" name="invite_mark_paid" value="1">
									<?php esc_html_e( 'Mark as paid on account creation', 'ddcwwfcsc' ); ?>
								</label>
								<div id="invite_paid_until_row" style="margin-top:8px;display:none">
									<label for="invite_paid_season"><?php esc_html_e( 'Season:', 'ddcwwfcsc' ); ?></label>
									<?php
									$_invite_current = get_option( 'ddcwwfcsc_current_season', '' );
									$_invite_seasons = get_terms( array( 'taxonomy' => 'ddcwwfcsc_season', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'DESC' ) );
									if ( ! is_wp_error( $_invite_seasons ) && ! empty( $_invite_seasons ) ) :
									?>
									<select name="invite_paid_season" id="invite_paid_season">
										<?php foreach ( $_invite_seasons as $_s ) : ?>
											<option value="<?php echo esc_attr( $_s->name ); ?>" <?php selected( $_invite_current, $_s->name ); ?>><?php echo esc_html( $_s->name ); ?></option>
										<?php endforeach; ?>
									</select>
									<?php else : ?>
									<input type="text" name="invite_paid_season" id="invite_paid_season" value="<?php echo esc_attr( $_invite_current ); ?>" placeholder="e.g. 2024/25" class="regular-text">
									<?php endif; ?>
								</div>
								<script>
								(function () {
									var cb  = document.getElementById('invite_mark_paid');
									var row = document.getElementById('invite_paid_until_row');
									cb.addEventListener('change', function () {
										row.style.display = this.checked ? '' : 'none';
									});
								})();
								</script>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Send Invite', 'ddcwwfcsc' ), 'primary', 'submit', false ); ?>
				</form>
			</div>

			<!-- Members table -->
			<h2><?php esc_html_e( 'Current Members', 'ddcwwfcsc' ); ?></h2>
			<?php if ( empty( $members ) ) : ?>
				<p><?php esc_html_e( 'No members yet. Invite someone above.', 'ddcwwfcsc' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'ddcwwfcsc' ); ?></th>
							<th><?php esc_html_e( 'Email', 'ddcwwfcsc' ); ?></th>
							<th><?php esc_html_e( 'Joined', 'ddcwwfcsc' ); ?></th>
							<th><?php esc_html_e( 'Fee Status', 'ddcwwfcsc' ); ?></th>
							<th><?php esc_html_e( 'Season Paid', 'ddcwwfcsc' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $members as $member ) :
							$paid_season    = get_user_meta( $member->ID, '_ddcwwfcsc_paid_season', true );
							$current_season = get_option( 'ddcwwfcsc_current_season', '' );
							$is_current     = $paid_season && $current_season && $paid_season === $current_season;
							?>
							<tr>
								<td><strong><?php echo esc_html( $member->display_name ); ?></strong></td>
								<td><?php echo esc_html( $member->user_email ); ?></td>
								<td><?php echo esc_html( wp_date( 'j M Y', strtotime( $member->user_registered ) ) ); ?></td>
								<td>
									<?php if ( ! $paid_season ) : ?>
										<span style="color:#d63638"><?php esc_html_e( 'Not paid', 'ddcwwfcsc' ); ?></span>
									<?php elseif ( $is_current ) : ?>
										<span style="color:#00a32a"><?php echo esc_html( $paid_season ); ?></span>
									<?php else : ?>
										<span style="color:#d63638"><?php esc_html_e( 'Expired', 'ddcwwfcsc' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
										<?php wp_nonce_field( 'ddcwwfcsc_set_paid_season_' . $member->ID, 'ddcwwfcsc_paid_nonce' ); ?>
										<input type="hidden" name="action" value="ddcwwfcsc_set_paid_season">
										<input type="hidden" name="user_id" value="<?php echo absint( $member->ID ); ?>">
										<?php
										$_seasons = get_terms( array( 'taxonomy' => 'ddcwwfcsc_season', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'DESC' ) );
										if ( ! is_wp_error( $_seasons ) && ! empty( $_seasons ) ) :
										?>
										<select name="paid_season" style="padding:2px 4px">
											<option value=""><?php esc_html_e( '— not paid —', 'ddcwwfcsc' ); ?></option>
											<?php foreach ( $_seasons as $_s ) : ?>
												<option value="<?php echo esc_attr( $_s->name ); ?>" <?php selected( $paid_season, $_s->name ); ?>><?php echo esc_html( $_s->name ); ?></option>
											<?php endforeach; ?>
										</select>
										<?php else : ?>
										<input type="text" name="paid_season" value="<?php echo esc_attr( $paid_season ); ?>" placeholder="e.g. 2024/25" style="padding:2px 6px;width:90px">
										<?php endif; ?>
										<button type="submit" class="button button-small"><?php esc_html_e( 'Save', 'ddcwwfcsc' ); ?></button>
										<?php $current_season = get_option( 'ddcwwfcsc_current_season', '' ); if ( $current_season ) : ?>
										<button type="submit" name="paid_season" value="<?php echo esc_attr( $current_season ); ?>" class="button button-small button-primary"><?php esc_html_e( 'Mark as Paid', 'ddcwwfcsc' ); ?></button>
										<?php endif; ?>
										<?php if ( $paid_season ) : ?>
											<button type="submit" name="paid_season" value="" class="button button-small" style="color:#d63638"><?php esc_html_e( 'Clear', 'ddcwwfcsc' ); ?></button>
										<?php endif; ?>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<!-- Pending invites -->
			<?php if ( ! empty( $pending_invites ) ) : ?>
				<h2 style="margin-top:2em"><?php esc_html_e( 'Pending Invites', 'ddcwwfcsc' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width:220px"><?php esc_html_e( 'Email', 'ddcwwfcsc' ); ?></th>
							<th style="width:120px"><?php esc_html_e( 'Invited', 'ddcwwfcsc' ); ?></th>
							<th><?php esc_html_e( 'Invite Link', 'ddcwwfcsc' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $pending_invites as $invite ) : ?>
							<tr>
								<td><?php echo esc_html( $invite->email ); ?></td>
								<td><?php echo esc_html( wp_date( 'j M Y', strtotime( $invite->invited_at ) ) ); ?></td>
								<td>
									<input type="text" readonly
										value="<?php echo esc_url( DDCWWFCSC_Invites::get_register_url( $invite->token ) ); ?>"
										style="width:100%;font-size:12px"
										onclick="this.select()">
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

		</div>
		<?php
	}

	/**
	 * Handle the send invite form submission.
	 */
	public static function handle_send_invite() {
		if ( ! current_user_can( 'manage_ddcwwfcsc_members' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ddcwwfcsc' ) );
		}

		check_admin_referer( 'ddcwwfcsc_send_invite', 'ddcwwfcsc_invite_nonce' );

		$email      = sanitize_email( $_POST['invite_email'] ?? '' );
		$paid_season = '';
		if ( ! empty( $_POST['invite_mark_paid'] ) && ! empty( $_POST['invite_paid_season'] ) ) {
			$paid_season = sanitize_text_field( $_POST['invite_paid_season'] );
		}
		$result = DDCWWFCSC_Invites::send_invite( $email, get_current_user_id(), $paid_season );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg(
				array(
					'page'             => 'ddcwwfcsc-members',
					'ddcwwfcsc_notice' => 'invite_error',
					'ddcwwfcsc_msg'    => rawurlencode( $result->get_error_message() ),
				),
				admin_url( 'admin.php' )
			) );
		} else {
			wp_safe_redirect( add_query_arg(
				array(
					'page'             => 'ddcwwfcsc-members',
					'ddcwwfcsc_notice' => 'invite_sent',
				),
				admin_url( 'admin.php' )
			) );
		}
		exit;
	}

	/**
	 * Handle the set paid-season form submission.
	 */
	public static function handle_set_paid_season() {
		if ( ! current_user_can( 'manage_ddcwwfcsc_members' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ddcwwfcsc' ) );
		}

		$user_id = absint( $_POST['user_id'] ?? 0 );
		check_admin_referer( 'ddcwwfcsc_set_paid_season_' . $user_id, 'ddcwwfcsc_paid_nonce' );

		if ( ! $user_id ) {
			wp_die( esc_html__( 'Invalid user.', 'ddcwwfcsc' ) );
		}

		$paid_season = sanitize_text_field( $_POST['paid_season'] ?? '' );

		if ( $paid_season ) {
			update_user_meta( $user_id, '_ddcwwfcsc_paid_season', $paid_season );
		} else {
			delete_user_meta( $user_id, '_ddcwwfcsc_paid_season' );
		}

		wp_safe_redirect( add_query_arg(
			array(
				'page'             => 'ddcwwfcsc-members',
				'ddcwwfcsc_notice' => 'paid_updated',
			),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * Get all pending membership applications.
	 *
	 * @return array
	 */
	private static function get_pending_applications() {
		global $wpdb;
		$table = $wpdb->prefix . 'ddcwwfcsc_applications';
		return $wpdb->get_results( "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY applied_at ASC" );
	}

	/**
	 * Approve an application: send invite and mark reviewed.
	 */
	public static function handle_approve_application() {
		if ( ! current_user_can( 'manage_ddcwwfcsc_members' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ddcwwfcsc' ) );
		}

		$app_id = absint( $_POST['application_id'] ?? 0 );
		check_admin_referer( 'ddcwwfcsc_approve_application_' . $app_id, 'ddcwwfcsc_app_nonce' );

		global $wpdb;
		$table = $wpdb->prefix . 'ddcwwfcsc_applications';
		$app   = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d AND status = 'pending'",
			$app_id
		) );

		if ( ! $app ) {
			wp_safe_redirect( add_query_arg(
				array(
					'page'             => 'ddcwwfcsc-members',
					'ddcwwfcsc_notice' => 'invite_error',
					'ddcwwfcsc_msg'    => rawurlencode( __( 'Application not found.', 'ddcwwfcsc' ) ),
				),
				admin_url( 'admin.php' )
			) );
			exit;
		}

		$result = DDCWWFCSC_Invites::send_invite( $app->email, get_current_user_id() );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg(
				array(
					'page'             => 'ddcwwfcsc-members',
					'ddcwwfcsc_notice' => 'invite_error',
					'ddcwwfcsc_msg'    => rawurlencode( $result->get_error_message() ),
				),
				admin_url( 'admin.php' )
			) );
			exit;
		}

		$wpdb->update(
			$table,
			array(
				'status'      => 'approved',
				'reviewed_at' => current_time( 'mysql' ),
				'reviewed_by' => get_current_user_id(),
			),
			array( 'id' => $app_id ),
			array( '%s', '%s', '%d' ),
			array( '%d' )
		);

		wp_safe_redirect( add_query_arg(
			array(
				'page'             => 'ddcwwfcsc-members',
				'ddcwwfcsc_notice' => 'application_approved',
			),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * Register dashboard widgets.
	 * Admins/presidents get a membership overview widget.
	 * Members get a personal membership status widget.
	 */
	public static function add_dashboard_widget() {
		if ( current_user_can( 'manage_ddcwwfcsc_members' ) ) {
			wp_add_dashboard_widget(
				'ddcwwfcsc_admin_member_widget',
				__( 'Membership Overview', 'ddcwwfcsc' ),
				array( __CLASS__, 'render_admin_dashboard_widget' )
			);
			return;
		}

		$user = wp_get_current_user();
		if ( in_array( 'ddcwwfcsc_member', (array) $user->roles, true ) ) {
			wp_add_dashboard_widget(
				'ddcwwfcsc_member_widget',
				get_bloginfo( 'name' ) . ' — ' . __( 'Membership', 'ddcwwfcsc' ),
				array( __CLASS__, 'render_dashboard_widget' )
			);
		}
	}

	/**
	 * Render the admin membership overview dashboard widget.
	 */
	public static function render_admin_dashboard_widget() {
		$pending_applications = self::get_pending_applications();
		$unpaid_members       = self::get_unpaid_members();
		$all_clear            = empty( $pending_applications ) && empty( $unpaid_members );

		if ( $all_clear ) : ?>
			<p style="color:#00a32a;font-weight:600;margin:0">&#10003; <?php esc_html_e( 'All members are paid up and no applications are pending.', 'ddcwwfcsc' ); ?></p>
		<?php else : ?>

			<?php if ( ! empty( $pending_applications ) ) : ?>
				<h3 style="margin:0 0 8px;font-size:13px">
					<?php esc_html_e( 'Pending Applications', 'ddcwwfcsc' ); ?>
					<span style="display:inline-flex;align-items:center;justify-content:center;background:#d63638;color:#fff;border-radius:50%;width:18px;height:18px;font-size:11px;font-weight:700;margin-left:5px;vertical-align:middle"><?php echo count( $pending_applications ); ?></span>
				</h3>
				<table class="widefat striped" style="margin-bottom:16px">
					<tbody>
						<?php foreach ( $pending_applications as $app ) : ?>
							<tr>
								<td style="width:40%">
									<strong><?php echo esc_html( trim( $app->first_name . ' ' . $app->last_name ) ); ?></strong><br>
									<span style="color:#888;font-size:12px"><?php echo esc_html( $app->email ); ?></span>
								</td>
								<td style="color:#888;font-size:12px;vertical-align:middle">
									<?php echo esc_html( wp_date( 'j M Y', strtotime( $app->applied_at ) ) ); ?>
								</td>
								<td style="vertical-align:middle">
									<div style="display:flex;gap:4px;justify-content:flex-end">
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
											<?php wp_nonce_field( 'ddcwwfcsc_approve_application_' . $app->id, 'ddcwwfcsc_app_nonce' ); ?>
											<input type="hidden" name="action" value="ddcwwfcsc_approve_application">
											<input type="hidden" name="application_id" value="<?php echo absint( $app->id ); ?>">
											<button type="submit" class="button button-primary button-small"><?php esc_html_e( 'Approve', 'ddcwwfcsc' ); ?></button>
										</form>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
											<?php wp_nonce_field( 'ddcwwfcsc_decline_application_' . $app->id, 'ddcwwfcsc_app_nonce' ); ?>
											<input type="hidden" name="action" value="ddcwwfcsc_decline_application">
											<input type="hidden" name="application_id" value="<?php echo absint( $app->id ); ?>">
											<button type="submit" class="button button-small" style="color:#d63638"><?php esc_html_e( 'Decline', 'ddcwwfcsc' ); ?></button>
										</form>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( ! empty( $unpaid_members ) ) : ?>
				<h3 style="margin:0 0 8px;font-size:13px"><?php esc_html_e( 'Outstanding Fees', 'ddcwwfcsc' ); ?></h3>
				<table class="widefat striped" style="margin-bottom:12px">
					<tbody>
						<?php foreach ( $unpaid_members as $member ) :
							$paid_season = get_user_meta( $member->ID, '_ddcwwfcsc_paid_season', true );
						?>
							<tr>
								<td>
									<strong><?php echo esc_html( $member->display_name ); ?></strong><br>
									<span style="color:#888;font-size:12px"><?php echo esc_html( $member->user_email ); ?></span>
								</td>
								<td style="vertical-align:middle;text-align:right">
									<?php if ( $paid_season ) : ?>
										<span style="color:#d63638;font-size:12px">
											<?php printf(
												/* translators: %s: date */
												esc_html__( 'Expired %s', 'ddcwwfcsc' ),
												esc_html( $paid_season )
											); ?>
										</span>
									<?php else : ?>
										<span style="color:#d63638;font-size:12px"><?php esc_html_e( 'Not paid', 'ddcwwfcsc' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

		<?php endif; ?>

		<p style="margin:8px 0 0;border-top:1px solid #eee;padding-top:8px">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ddcwwfcsc-members' ) ); ?>" style="font-size:12px">
				<?php esc_html_e( 'Manage Members →', 'ddcwwfcsc' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Get all members with no paid-until date or an expired one.
	 *
	 * @return WP_User[]
	 */
	private static function get_unpaid_members() {
		$members        = get_users( array( 'role' => 'ddcwwfcsc_member' ) );
		$current_season = get_option( 'ddcwwfcsc_current_season', '' );
		$unpaid         = array();

		foreach ( $members as $member ) {
			$paid_season = get_user_meta( $member->ID, '_ddcwwfcsc_paid_season', true );
			if ( ! $current_season || $paid_season !== $current_season ) {
				$unpaid[] = $member;
			}
		}

		return $unpaid;
	}

	/**
	 * Render the member dashboard widget.
	 */
	public static function render_dashboard_widget() {
		$user       = wp_get_current_user();
		$paid_season    = get_user_meta( $user->ID, '_ddcwwfcsc_paid_season', true );
		$current_season = get_option( 'ddcwwfcsc_current_season', '' );
		$is_paid        = $paid_season && $current_season && $paid_season === $current_season;
		?>
		<p><?php printf(
			/* translators: %s: member display name */
			esc_html__( 'Welcome, %s.', 'ddcwwfcsc' ),
			esc_html( $user->display_name )
		); ?></p>

		<table class="widefat striped" style="margin-bottom:1em">
			<tbody>
				<tr>
					<td style="width:40%;font-weight:600"><?php esc_html_e( 'Annual fee', 'ddcwwfcsc' ); ?></td>
					<td>
						<?php if ( $is_paid ) : ?>
							<span style="color:#00a32a;font-weight:600"><?php printf(
								/* translators: %s: season name */
								esc_html__( 'Paid — %s', 'ddcwwfcsc' ),
								esc_html( $paid_season )
							); ?></span>
						<?php elseif ( $current_season ) : ?>
							<span style="color:#d63638;font-weight:600"><?php printf(
								/* translators: %s: season name */
								esc_html__( 'Not paid for %s', 'ddcwwfcsc' ),
								esc_html( $current_season )
							); ?></span>
						<?php else : ?>
							<span style="color:#d63638;font-weight:600"><?php esc_html_e( 'Not yet paid', 'ddcwwfcsc' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>

		<p>
			<a href="<?php echo esc_url( DDCWWFCSC_Member_Front::get_account_url() ); ?>" class="button button-primary">
				<?php esc_html_e( 'My Account', 'ddcwwfcsc' ); ?>
			</a>
			&nbsp;
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button">
				<?php esc_html_e( 'View Site', 'ddcwwfcsc' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Silently decline an application.
	 */
	public static function handle_decline_application() {
		if ( ! current_user_can( 'manage_ddcwwfcsc_members' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ddcwwfcsc' ) );
		}

		$app_id = absint( $_POST['application_id'] ?? 0 );
		check_admin_referer( 'ddcwwfcsc_decline_application_' . $app_id, 'ddcwwfcsc_app_nonce' );

		global $wpdb;
		$table = $wpdb->prefix . 'ddcwwfcsc_applications';
		$wpdb->update(
			$table,
			array(
				'status'      => 'declined',
				'reviewed_at' => current_time( 'mysql' ),
				'reviewed_by' => get_current_user_id(),
			),
			array( 'id' => $app_id, 'status' => 'pending' ),
			array( '%s', '%s', '%d' ),
			array( '%d', '%s' )
		);

		wp_safe_redirect( add_query_arg(
			array(
				'page'             => 'ddcwwfcsc-members',
				'ddcwwfcsc_notice' => 'application_declined',
			),
			admin_url( 'admin.php' )
		) );
		exit;
	}
}
