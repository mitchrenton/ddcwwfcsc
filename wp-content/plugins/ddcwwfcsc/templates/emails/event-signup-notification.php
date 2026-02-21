<?php
/**
 * Email template: New event sign-up notification (sent to President/admins).
 *
 * Available variables:
 * @var string $attendee_name
 * @var string $attendee_email
 * @var string $event_title
 * @var int    $signup_count
 * @var string $event_edit_url
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
        .header { background: #231F20; color: #FDB913; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; }
        .content { padding: 20px; background: #f9f9f9; }
        .details { background: #fff; padding: 15px; border-left: 4px solid #231F20; margin: 15px 0; }
        .details p { margin: 5px 0; }
        .signup-count { font-size: 18px; font-weight: bold; text-align: center; padding: 10px; background: #fff; margin: 15px 0; }
        .footer { padding: 15px; font-size: 12px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php esc_html_e( 'New Event Sign-up', 'ddcwwfcsc' ); ?></h1>
        </div>
        <div class="content">
            <p><?php esc_html_e( 'A new event sign-up has been submitted:', 'ddcwwfcsc' ); ?></p>

            <div class="details">
                <p><strong><?php esc_html_e( 'Event:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $event_title ); ?></p>
                <p><strong><?php esc_html_e( 'Name:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $attendee_name ); ?></p>
                <p><strong><?php esc_html_e( 'Email:', 'ddcwwfcsc' ); ?></strong> <a href="mailto:<?php echo esc_attr( $attendee_email ); ?>"><?php echo esc_html( $attendee_email ); ?></a></p>
            </div>

            <div class="signup-count">
                <?php printf(
                    /* translators: %d: total number of sign-ups */
                    esc_html__( 'Total Sign-ups: %d', 'ddcwwfcsc' ),
                    absint( $signup_count )
                ); ?>
            </div>

            <p><a href="<?php echo esc_url( $event_edit_url ); ?>"><?php esc_html_e( 'View event in admin', 'ddcwwfcsc' ); ?></a></p>
        </div>
        <div class="footer">
            <p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
        </div>
    </div>
</body>
</html>
