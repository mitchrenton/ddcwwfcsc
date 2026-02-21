<?php
/**
 * Email template: MOTM vote reminder.
 *
 * Available variables (from class-motm-front.php send_vote_reminder):
 * @var WP_User $member        The member being emailed.
 * @var string  $fixture_title The fixture post title.
 * @var string  $fixture_url   The fixture permalink.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name = get_bloginfo( 'name' );
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
		.footer { padding: 15px; font-size: 12px; color: #666; text-align: center; }
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<h1><?php echo esc_html( $site_name ); ?></h1>
		</div>
		<div class="content">
			<p><?php printf(
				/* translators: %s: member display name */
				esc_html__( 'Hi %s,', 'ddcwwfcsc' ),
				esc_html( $member->display_name )
			); ?></p>

			<p><?php printf(
				/* translators: %s: fixture title */
				esc_html__( "The lineup is in for %s — cast your vote for Man of the Match.", 'ddcwwfcsc' ),
				esc_html( $fixture_title )
			); ?></p>

			<div class="cta">
				<a href="<?php echo esc_url( $fixture_url ); ?>">
					<?php esc_html_e( 'Vote Now', 'ddcwwfcsc' ); ?>
				</a>
			</div>

			<p><?php esc_html_e( 'Voting closes before the next fixture kicks off.', 'ddcwwfcsc' ); ?></p>
		</div>
		<div class="footer">
			<p><?php echo esc_html( $site_name ); ?></p>
		</div>
	</div>
</body>
</html>
