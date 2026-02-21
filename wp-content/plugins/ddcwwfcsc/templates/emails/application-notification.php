<?php
/**
 * Email template: New membership application (sent to president/admins).
 *
 * Available variables (extracted by class-member-front.php):
 * @var string $full_name  Applicant full name.
 * @var string $email      Applicant email.
 * @var string $admin_url  URL to the Members admin page.
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
		.details { background: #fff; padding: 15px; border-left: 4px solid #FDB913; margin: 15px 0; }
		.details p { margin: 5px 0; }
		.cta { text-align: center; margin: 25px 0; }
		.cta a { display: inline-block; background: #FDB913; color: #231F20; text-decoration: none; padding: 14px 32px; font-size: 16px; font-weight: 700; border-radius: 4px; }
		.footer { padding: 15px; font-size: 12px; color: #666; text-align: center; }
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<h1><?php echo esc_html( get_bloginfo( 'name' ) ); ?> — <?php esc_html_e( 'New Membership Application', 'ddcwwfcsc' ); ?></h1>
		</div>
		<div class="content">
			<p><?php esc_html_e( 'A new membership application has been submitted.', 'ddcwwfcsc' ); ?></p>

			<div class="details">
				<p><strong><?php esc_html_e( 'Name:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $full_name ); ?></p>
				<p><strong><?php esc_html_e( 'Email:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $email ); ?></p>
			</div>

			<p><?php esc_html_e( 'Log in to the Members admin to approve or decline this application.', 'ddcwwfcsc' ); ?></p>

			<div class="cta">
				<a href="<?php echo esc_url( $admin_url ); ?>">
					<?php esc_html_e( 'Review Application', 'ddcwwfcsc' ); ?>
				</a>
			</div>
		</div>
		<div class="footer">
			<p><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
		</div>
	</div>
</body>
</html>
