<?php
/**
 * Email template: Ticket request confirmation (sent to requester).
 *
 * Available variables:
 * @var string $name
 * @var int    $num_tickets
 * @var string $opponent
 * @var string $match_date
 * @var string $price_text
 * @var string $fixture_title
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
            <h1><?php esc_html_e( 'DDCWWFCSC — Ticket Request Confirmation', 'ddcwwfcsc' ); ?></h1>
        </div>
        <div class="content">
            <p><?php printf(
                /* translators: %s: requester's name */
                esc_html__( 'Hi %s,', 'ddcwwfcsc' ),
                esc_html( $name )
            ); ?></p>

            <p><?php esc_html_e( 'Your ticket request has been received. Here are the details:', 'ddcwwfcsc' ); ?></p>

            <div class="details">
                <p><strong><?php esc_html_e( 'Match:', 'ddcwwfcsc' ); ?></strong> Wolves v <?php echo esc_html( $opponent ); ?></p>
                <p><strong><?php esc_html_e( 'Date:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $match_date ); ?></p>
                <p><strong><?php esc_html_e( 'Tickets Requested:', 'ddcwwfcsc' ); ?></strong> <?php echo absint( $num_tickets ); ?></p>
                <?php if ( $price_text ) : ?>
                    <p><strong><?php esc_html_e( 'Price:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $price_text ); ?> <?php esc_html_e( 'per ticket', 'ddcwwfcsc' ); ?></p>
                <?php endif; ?>
            </div>

            <p><?php esc_html_e( 'Your request is being reviewed. Once approved, you will receive a payment link by email.', 'ddcwwfcsc' ); ?></p>

            <p><?php esc_html_e( 'If you need to cancel your request, please contact the club president.', 'ddcwwfcsc' ); ?></p>
        </div>
        <div class="footer">
            <p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
        </div>
    </div>
</body>
</html>
