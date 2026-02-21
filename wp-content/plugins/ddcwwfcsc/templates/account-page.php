<?php
/**
 * Template: Front-end member account settings page.
 *
 * Available variables:
 * @var WP_User $current_user  The logged-in user.
 * @var string  $error_email   Email update error (empty if none).
 * @var string  $error_pass    Password update error (empty if none).
 * @var string  $error_avatar  Avatar update error (empty if none).
 * @var string  $success       Success message (empty if none).
 * @var string  $paid_season    Season name (e.g. "2024/25") or empty string.
 * @var string  $current_season The configured current season (e.g. "2024/25").
 * @var bool    $is_paid        Whether the membership fee is paid for the current season.
 * @var bool    $has_avatar    Whether the user has a custom avatar set.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logout_url = wp_logout_url( home_url( '/' ) );
?>

<div class="ddcwwfcsc-auth-wrap ddcwwfcsc-account-wrap">

	<div class="ddcwwfcsc-auth-card">
		<div class="ddcwwfcsc-auth-header">
			<p class="ddcwwfcsc-auth-club"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<h1><?php esc_html_e( 'My Account', 'ddcwwfcsc' ); ?></h1>
		</div>

		<?php if ( $success ) : ?>
			<div class="ddcwwfcsc-auth-notice ddcwwfcsc-auth-notice--success">
				<?php echo esc_html( $success ); ?>
			</div>
		<?php endif; ?>

		<!-- Membership status -->
		<div class="ddcwwfcsc-account-status">
			<h2><?php esc_html_e( 'Membership Status', 'ddcwwfcsc' ); ?></h2>
			<div class="ddcwwfcsc-account-status-row">
				<span class="ddcwwfcsc-account-label"><?php esc_html_e( 'Name', 'ddcwwfcsc' ); ?></span>
				<span><?php echo esc_html( $current_user->display_name ); ?></span>
			</div>
			<div class="ddcwwfcsc-account-status-row">
				<span class="ddcwwfcsc-account-label"><?php esc_html_e( 'Annual fee', 'ddcwwfcsc' ); ?></span>
				<?php if ( $is_paid ) : ?>
					<span class="ddcwwfcsc-account-paid">
						<?php printf(
							/* translators: %s: season name e.g. "2024/25" */
							esc_html__( 'Paid — %s', 'ddcwwfcsc' ),
							esc_html( $paid_season )
						); ?>
					</span>
				<?php elseif ( $current_season ) : ?>
					<span class="ddcwwfcsc-account-unpaid">
						<?php printf(
							/* translators: %s: season name e.g. "2024/25" */
							esc_html__( 'Not paid for %s', 'ddcwwfcsc' ),
							esc_html( $current_season )
						); ?>
					</span>
				<?php else : ?>
					<span class="ddcwwfcsc-account-unpaid"><?php esc_html_e( 'Not yet paid', 'ddcwwfcsc' ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<!-- Profile photo -->
		<div class="ddcwwfcsc-account-section">
			<h2><?php esc_html_e( 'Profile Photo', 'ddcwwfcsc' ); ?></h2>

			<?php if ( $error_avatar ) : ?>
				<div class="ddcwwfcsc-auth-notice ddcwwfcsc-auth-notice--error">
					<?php echo esc_html( $error_avatar ); ?>
				</div>
			<?php endif; ?>

			<form class="ddcwwfcsc-auth-form ddcwwfcsc-avatar-form" method="post"
				enctype="multipart/form-data"
				action="<?php echo esc_url( DDCWWFCSC_Member_Front::get_account_url() ); ?>">
				<?php wp_nonce_field( 'ddcwwfcsc_account_avatar', 'ddcwwfcsc_account_nonce' ); ?>
				<input type="hidden" name="ddcwwfcsc_account_action" value="update_avatar">

				<div class="ddcwwfcsc-avatar-row">
					<div class="ddcwwfcsc-avatar-preview" id="ddcwwfcsc-avatar-preview">
						<?php echo get_avatar( $current_user->ID, 80, '', $current_user->display_name, array( 'class' => 'ddcwwfcsc-avatar-img' ) ); ?>
					</div>
					<div class="ddcwwfcsc-avatar-controls">
						<label for="avatar_file" class="ddcwwfcsc-auth-btn ddcwwfcsc-auth-btn--secondary ddcwwfcsc-avatar-choose">
							<?php esc_html_e( 'Choose Photo', 'ddcwwfcsc' ); ?>
						</label>
						<input type="file" id="avatar_file" name="avatar" accept="image/*">
						<button type="submit" class="ddcwwfcsc-auth-btn ddcwwfcsc-auth-btn--secondary ddcwwfcsc-avatar-save" style="display:none">
							<?php esc_html_e( 'Save Photo', 'ddcwwfcsc' ); ?>
						</button>
					</div>
				</div>

				<?php if ( $has_avatar ) : ?>
					<button type="submit" name="remove_avatar" value="1" class="ddcwwfcsc-avatar-remove">
						<?php esc_html_e( 'Remove photo', 'ddcwwfcsc' ); ?>
					</button>
				<?php endif; ?>
			</form>

			<script>
			(function () {
				var input   = document.getElementById('avatar_file');
				var save    = document.querySelector('.ddcwwfcsc-avatar-save');
				var preview = document.getElementById('ddcwwfcsc-avatar-preview');
				if (!input) return;
				input.addEventListener('change', function () {
					var file = this.files && this.files[0];
					if (!file) return;
					save.style.display = '';
					var reader = new FileReader();
					reader.onload = function (e) {
						var img = preview.querySelector('img');
						if (img) {
							img.src = e.target.result;
						} else {
							preview.innerHTML = '<img src="' + e.target.result + '" class="ddcwwfcsc-avatar-img">';
						}
					};
					reader.readAsDataURL(file);
				});
			})();
			</script>
		</div>

		<!-- Update email -->
		<div class="ddcwwfcsc-account-section">
			<h2><?php esc_html_e( 'Email Address', 'ddcwwfcsc' ); ?></h2>

			<?php if ( $error_email ) : ?>
				<div class="ddcwwfcsc-auth-notice ddcwwfcsc-auth-notice--error">
					<?php echo esc_html( $error_email ); ?>
				</div>
			<?php endif; ?>

			<form class="ddcwwfcsc-auth-form" method="post" action="<?php echo esc_url( DDCWWFCSC_Member_Front::get_account_url() ); ?>">
				<?php wp_nonce_field( 'ddcwwfcsc_account_email', 'ddcwwfcsc_account_nonce' ); ?>
				<input type="hidden" name="ddcwwfcsc_account_action" value="update_email">

				<div class="ddcwwfcsc-auth-field">
					<label for="new_email"><?php esc_html_e( 'Email Address', 'ddcwwfcsc' ); ?></label>
					<input type="email" name="new_email" id="new_email" autocomplete="email" required
						value="<?php echo esc_attr( $current_user->user_email ); ?>">
				</div>

				<button type="submit" class="ddcwwfcsc-auth-btn ddcwwfcsc-auth-btn--secondary">
					<?php esc_html_e( 'Update Email', 'ddcwwfcsc' ); ?>
				</button>
			</form>
		</div>

		<!-- Change password -->
		<div class="ddcwwfcsc-account-section">
			<h2><?php esc_html_e( 'Change Password', 'ddcwwfcsc' ); ?></h2>

			<?php if ( $error_pass ) : ?>
				<div class="ddcwwfcsc-auth-notice ddcwwfcsc-auth-notice--error">
					<?php echo esc_html( $error_pass ); ?>
				</div>
			<?php endif; ?>

			<form class="ddcwwfcsc-auth-form" method="post" action="<?php echo esc_url( DDCWWFCSC_Member_Front::get_account_url() ); ?>">
				<?php wp_nonce_field( 'ddcwwfcsc_account_password', 'ddcwwfcsc_account_nonce' ); ?>
				<input type="hidden" name="ddcwwfcsc_account_action" value="update_password">

				<div class="ddcwwfcsc-auth-field">
					<label for="current_password"><?php esc_html_e( 'Current Password', 'ddcwwfcsc' ); ?></label>
					<input type="password" name="current_password" id="current_password" autocomplete="current-password" required>
				</div>

				<div class="ddcwwfcsc-auth-field">
					<label for="new_password"><?php esc_html_e( 'New Password', 'ddcwwfcsc' ); ?></label>
					<input type="password" name="new_password" id="new_password" autocomplete="new-password" required minlength="8">
					<span class="ddcwwfcsc-auth-hint"><?php esc_html_e( 'Minimum 8 characters.', 'ddcwwfcsc' ); ?></span>
				</div>

				<div class="ddcwwfcsc-auth-field">
					<label for="new_password2"><?php esc_html_e( 'Confirm New Password', 'ddcwwfcsc' ); ?></label>
					<input type="password" name="new_password2" id="new_password2" autocomplete="new-password" required minlength="8">
				</div>

				<button type="submit" class="ddcwwfcsc-auth-btn ddcwwfcsc-auth-btn--secondary">
					<?php esc_html_e( 'Update Password', 'ddcwwfcsc' ); ?>
				</button>
			</form>
		</div>

		<!-- Log out -->
		<div class="ddcwwfcsc-account-logout">
			<a href="<?php echo esc_url( $logout_url ); ?>"><?php esc_html_e( 'Log out', 'ddcwwfcsc' ); ?></a>
		</div>

	</div>
</div>
