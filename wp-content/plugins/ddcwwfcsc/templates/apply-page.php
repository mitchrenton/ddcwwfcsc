<?php
/**
 * Template: Membership application page.
 *
 * Available variables:
 * @var bool   $submitted Whether the form was successfully submitted.
 * @var string $error     Validation error message (empty if none).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$login_url = add_query_arg( 'ddcwwfcsc_page', 'login', home_url( '/' ) );
?>

<div class="ddcwwfcsc-auth-wrap">
	<div class="ddcwwfcsc-auth-card">

		<div class="ddcwwfcsc-auth-header">
			<p class="ddcwwfcsc-auth-club"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<h1><?php esc_html_e( 'Apply for Membership', 'ddcwwfcsc' ); ?></h1>
		</div>

		<?php if ( $submitted ) : ?>

			<script>window.dataLayer = window.dataLayer || []; window.dataLayer.push( { event: 'membership_apply' } );</script>

			<div class="ddcwwfcsc-auth-notice ddcwwfcsc-auth-notice--success">
				<strong><?php esc_html_e( 'Application received!', 'ddcwwfcsc' ); ?></strong>
				<?php esc_html_e( " The club president will review your application. If it's approved you'll receive an email with a link to create your account.", 'ddcwwfcsc' ); ?>
			</div>

		<?php else : ?>

			<?php if ( $error ) : ?>
				<div class="ddcwwfcsc-auth-notice ddcwwfcsc-auth-notice--error">
					<?php echo esc_html( $error ); ?>
				</div>
			<?php endif; ?>

			<form class="ddcwwfcsc-auth-form" method="post" action="<?php echo esc_url( add_query_arg( 'ddcwwfcsc_page', 'apply', home_url( '/' ) ) ); ?>">
				<?php wp_nonce_field( 'ddcwwfcsc_apply', 'ddcwwfcsc_apply_nonce' ); ?>

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
					<label for="email"><?php esc_html_e( 'Email Address', 'ddcwwfcsc' ); ?></label>
					<input type="email" name="email" id="email" autocomplete="email" required
						value="<?php echo esc_attr( sanitize_email( $_POST['email'] ?? '' ) ); ?>">
				</div>

				<button type="submit" class="btn btn--primary ddcwwfcsc-auth-btn">
					<?php esc_html_e( 'Submit Application', 'ddcwwfcsc' ); ?>
				</button>
			</form>

		<?php endif; ?>

		<p class="ddcwwfcsc-auth-footer-link">
			<?php printf(
				/* translators: %s: login URL */
				wp_kses( __( 'Already a member? <a href="%s">Log in</a>', 'ddcwwfcsc' ), array( 'a' => array( 'href' => array() ) ) ),
				esc_url( $login_url )
			); ?>
		</p>

	</div>
</div>
