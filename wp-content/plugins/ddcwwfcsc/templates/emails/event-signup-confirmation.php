<?php
/**
 * Email template: Event sign-up confirmation (sent to attendee).
 *
 * Available variables:
 * @var string $name
 * @var string $event_title
 * @var string $event_date
 * @var string $meeting_time
 * @var string $meeting_location
 * @var string $venue
 * @var string $cost
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
        .footer { padding: 15px; font-size: 12px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php esc_html_e( 'DDCWWFCSC — Event Sign-up Confirmation', 'ddcwwfcsc' ); ?></h1>
        </div>
        <div class="content">
            <p><?php printf(
                /* translators: %s: attendee's name */
                esc_html__( 'Hi %s,', 'ddcwwfcsc' ),
                esc_html( $name )
            ); ?></p>

            <p><?php esc_html_e( "You're signed up! Here are the event details:", 'ddcwwfcsc' ); ?></p>

            <div class="details">
                <p><strong><?php esc_html_e( 'Event:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $event_title ); ?></p>
                <?php if ( $event_date ) : ?>
                    <p><strong><?php esc_html_e( 'Date:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $event_date ); ?></p>
                <?php endif; ?>
                <?php if ( $meeting_time ) : ?>
                    <p><strong><?php esc_html_e( 'Meeting Time:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $meeting_time ); ?></p>
                <?php endif; ?>
                <?php if ( $meeting_location ) : ?>
                    <p><strong><?php esc_html_e( 'Meeting Location:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $meeting_location ); ?></p>
                <?php endif; ?>
                <?php if ( $venue ) : ?>
                    <p><strong><?php esc_html_e( 'Venue:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $venue ); ?></p>
                <?php endif; ?>
                <?php if ( $cost ) : ?>
                    <p><strong><?php esc_html_e( 'Cost:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $cost ); ?></p>
                <?php endif; ?>
            </div>

            <p><?php esc_html_e( 'If you need to cancel, please contact the club president.', 'ddcwwfcsc' ); ?></p>
        </div>
        <div class="footer">
            <p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
        </div>
    </div>
</body>
</html>
