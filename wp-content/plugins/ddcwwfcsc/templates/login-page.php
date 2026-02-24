<?php
/**
 * Template: Front-end member login page.
 *
 * Available variables:
 * @var string $error Error message (empty string if none).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$redirect_to = isset( $_GET['redirect_to'] ) ? rawurlencode( rawurldecode( $_GET['redirect_to'] ) ) : '';
?>

<div class="ddcwwfcsc-auth-wrap">
	<div class="ddcwwfcsc-auth-card">

		<div class="ddcwwfcsc-auth-header">
			<p class="ddcwwfcsc-auth-club"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<h1><?php esc_html_e( 'Member Login', 'ddcwwfcsc' ); ?></h1>
		</div>

		<?php if ( $error ) : ?>
			<div class="ddcwwfcsc-auth-notice ddcwwfcsc-auth-notice--error">
				<?php echo esc_html( $error ); ?>
			</div>
		<?php endif; ?>

		<form class="ddcwwfcsc-auth-form" method="post" action="<?php echo esc_url( add_query_arg( 'ddcwwfcsc_page', 'login', home_url( '/' ) ) ); ?>">
			<?php wp_nonce_field( 'ddcwwfcsc_login', 'ddcwwfcsc_login_nonce' ); ?>
			<?php if ( $redirect_to ) : ?>
				<input type="hidden" name="redirect_to" value="<?php echo esc_attr( rawurldecode( $redirect_to ) ); ?>">
			<?php endif; ?>

			<div class="ddcwwfcsc-auth-field">
				<label for="log"><?php esc_html_e( 'Username or Email', 'ddcwwfcsc' ); ?></label>
				<input type="text" name="log" id="log" autocomplete="username" required
					value="<?php echo esc_attr( sanitize_text_field( $_POST['log'] ?? '' ) ); ?>">
			</div>

			<div class="ddcwwfcsc-auth-field">
				<label for="pwd"><?php esc_html_e( 'Password', 'ddcwwfcsc' ); ?></label>
				<input type="password" name="pwd" id="pwd" autocomplete="current-password" required>
			</div>

			<label class="ddcwwfcsc-auth-remember">
				<input type="checkbox" name="rememberme" value="forever">
				<?php esc_html_e( 'Remember me', 'ddcwwfcsc' ); ?>
			</label>

			<button type="submit" class="btn btn--primary ddcwwfcsc-auth-btn">
				<?php esc_html_e( 'Log In', 'ddcwwfcsc' ); ?>
			</button>
		</form>

			<p class="ddcwwfcsc-auth-footer-link">
			<?php printf(
				/* translators: %s: apply URL */
				wp_kses( __( 'Not a member? <a href="%s">Apply for membership</a>', 'ddcwwfcsc' ), array( 'a' => array( 'href' => array() ) ) ),
				esc_url( add_query_arg( 'ddcwwfcsc_page', 'apply', home_url( '/' ) ) )
			); ?>
		</p>

	</div>
</div>
