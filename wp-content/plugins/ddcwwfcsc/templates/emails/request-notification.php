<?php
/**
 * Email template: New ticket request notification (sent to President).
 *
 * Available variables:
 * @var string $requester_name
 * @var string $requester_email
 * @var int    $num_tickets
 * @var string $opponent
 * @var string $fixture_title
 * @var int    $remaining
 * @var int    $total
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
        .remaining { font-size: 18px; font-weight: bold; text-align: center; padding: 10px; background: #fff; margin: 15px 0; }
        .footer { padding: 15px; font-size: 12px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php esc_html_e( 'New Ticket Request', 'ddcwwfcsc' ); ?></h1>
        </div>
        <div class="content">
            <p><?php esc_html_e( 'A new ticket request has been submitted:', 'ddcwwfcsc' ); ?></p>

            <div class="details">
                <p><strong><?php esc_html_e( 'Fixture:', 'ddcwwfcsc' ); ?></strong> Wolves v <?php echo esc_html( $opponent ); ?></p>
                <p><strong><?php esc_html_e( 'Requester:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $requester_name ); ?></p>
                <p><strong><?php esc_html_e( 'Email:', 'ddcwwfcsc' ); ?></strong> <a href="mailto:<?php echo esc_attr( $requester_email ); ?>"><?php echo esc_html( $requester_email ); ?></a></p>
                <p><strong><?php esc_html_e( 'Tickets Requested:', 'ddcwwfcsc' ); ?></strong> <?php echo absint( $num_tickets ); ?></p>
            </div>

            <div class="remaining">
                <?php printf(
                    /* translators: 1: tickets remaining, 2: total tickets */
                    esc_html__( 'Tickets Remaining: %1$d of %2$d', 'ddcwwfcsc' ),
                    absint( $remaining ),
                    absint( $total )
                ); ?>
            </div>

            <p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ddcwwfcsc_fixture&page=ddcwwfcsc-ticket-requests' ) ); ?>"><?php esc_html_e( 'View all ticket requests', 'ddcwwfcsc' ); ?></a></p>
        </div>
        <div class="footer">
            <p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
        </div>
    </div>
</body>
</html>
