<?php
/**
 * Template: Front-end member registration page (invite-only).
 *
 * Available variables:
 * @var string      $token  The invite token from the URL.
 * @var object|null $invite The invite row from the DB, or null if invalid.
 * @var string      $error  Error message (empty string if none).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="ddcwwfcsc-auth-wrap">
	<div class="ddcwwfcsc-auth-card">

		<div class="ddcwwfcsc-auth-header">
			<p class="ddcwwfcsc-auth-club"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<h1><?php esc_html_e( 'Create Your Account', 'ddcwwfcsc' ); ?></h1>
		</div>

		<?php if ( ! $invite ) : ?>

			<div class="ddcwwfcsc-auth-notice ddcwwfcsc-auth-notice--error">
				<?php esc_html_e( 'This invite link is invalid or has already been used. Please contact Coxy.', 'ddcwwfcsc' ); ?>
			</div>

		<?php else : ?>

			<p class="ddcwwfcsc-auth-intro">
				<?php printf(
					/* translators: %s: site name */
					esc_html__( "You've been invited to join the %s members area. Fill in your details below to get started.", 'ddcwwfcsc' ),
					esc_html( get_bloginfo( 'name' ) )
				); ?>
			</p>

			<?php if ( $error ) : ?>
				<div class="ddcwwfcsc-auth-notice ddcwwfcsc-auth-notice--error">
					<?php echo esc_html( $error ); ?>
				</div>
			<?php endif; ?>

			<form class="ddcwwfcsc-auth-form" method="post" action="<?php echo esc_url( DDCWWFCSC_Invites::get_register_url( $token ) ); ?>">
				<?php wp_nonce_field( 'ddcwwfcsc_register', 'ddcwwfcsc_register_nonce' ); ?>
				<input type="hidden" name="invite_token" value="<?php echo esc_attr( $token ); ?>">

				<div class="ddcwwfcsc-auth-field">
					<label><?php esc_html_e( 'Email Address', 'ddcwwfcsc' ); ?></label>
					<input type="email" value="<?php echo esc_attr( $invite->email ); ?>" disabled>
				</div>

				<div class="ddcwwfcsc-auth-field-row">
					<div class="ddcwwfcsc-auth-field">
						<label for="first_name"><?php esc_html_e( 'First Name', 'ddcwwfcsc' ); ?></label>
						<input type="text" name="first_name" id="first_name" autocomplete="given-name" required
							value="<?php echo esc_attr( sanitize_text_field( $_POST['first_name'] ?? '' ) ); ?>">
					</div>
					<div class="ddcwwfcsc-auth-field">
						<label for="last_name"><?php esc_html_e( 'Last Name', 'ddcwwfcsc' ); ?></label>
						<input type="text" name="last_name" id="last_name" autocomplete="family-name" required
							value="<?php echo esc_attr( sanitize_text_field( $_POST['last_name'] ?? '' ) ); ?>">
					</div>
				</div>

				<div class="ddcwwfcsc-auth-field">
					<label for="password"><?php esc_html_e( 'Password', 'ddcwwfcsc' ); ?></label>
					<input type="password" name="password" id="password" autocomplete="new-password" required minlength="8">
					<span class="ddcwwfcsc-auth-hint"><?php esc_html_e( 'Minimum 8 characters.', 'ddcwwfcsc' ); ?></span>
				</div>

				<div class="ddcwwfcsc-auth-field">
					<label for="password2"><?php esc_html_e( 'Confirm Password', 'ddcwwfcsc' ); ?></label>
					<input type="password" name="password2" id="password2" autocomplete="new-password" required minlength="8">
				</div>

				<button type="submit" class="btn btn--primary ddcwwfcsc-auth-btn">
					<?php esc_html_e( 'Create Account', 'ddcwwfcsc' ); ?>
				</button>
			</form>

		<?php endif; ?>

	</div>
</div>
