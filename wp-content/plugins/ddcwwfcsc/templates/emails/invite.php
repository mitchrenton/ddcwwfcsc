<?php
/**
 * Email template: Member invite.
 *
 * Available variables (extracted by class-invites.php):
 * @var string $email        The invitee's email address.
 * @var string $register_url The tokenised registration URL.
 * @var string $site_name    The site name.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #333; line-height: 1.6; }
		.container { max-width: 600px; margin: 0 auto; padding: 20px; }
		.header { background: #FDB913; color: #231F20; padding: 20px; text-align: center; }
		.header h1 { margin: 0; font-size: 20px; }
		.content { padding: 20px; background: #f9f9f9; }
		.cta { text-align: center; margin: 25px 0; }
		.cta a { display: inline-block; background: #FDB913; color: #231F20; text-decoration: none; padding: 14px 32px; font-size: 16px; font-weight: 700; border-radius: 4px; }
		.note { background: #fff; border-left: 4px solid #FDB913; padding: 12px 15px; margin: 15px 0; font-size: 14px; }
		.footer { padding: 15px; font-size: 12px; color: #666; text-align: center; }
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<h1><?php echo esc_html( $site_name ); ?></h1>
		</div>
		<div class="content">
			<p><?php esc_html_e( "You've been invited to join the members area.", 'ddcwwfcsc' ); ?></p>

			<p><?php esc_html_e( 'Click the button below to create your account. It only takes a moment.', 'ddcwwfcsc' ); ?></p>

			<div class="cta">
				<a href="<?php echo esc_url( $register_url ); ?>">
					<?php esc_html_e( 'Create My Account', 'ddcwwfcsc' ); ?>
				</a>
			</div>

			<div class="note">
				<?php esc_html_e( 'This link is personal to you — please do not share it. It will remain active until your account is created.', 'ddcwwfcsc' ); ?>
			</div>

			<p><?php esc_html_e( 'If you were not expecting this email, you can safely ignore it.', 'ddcwwfcsc' ); ?></p>
		</div>
		<div class="footer">
			<p><?php echo esc_html( $site_name ); ?></p>
		</div>
	</div>
</body>
</html>
