<?php
/**
 * Email template: Payment link (sent to requester after approval).
 *
 * Available variables:
 * @var string $name
 * @var int    $num_tickets
 * @var string $opponent
 * @var string $match_date
 * @var float  $amount
 * @var string $payment_url
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
        .cta { text-align: center; margin: 25px 0; }
        .cta a { display: inline-block; background: #FDB913; color: #231F20; text-decoration: none; padding: 14px 32px; font-size: 16px; font-weight: 700; border-radius: 4px; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 12px 15px; border-radius: 4px; margin: 15px 0; font-size: 14px; }
        .footer { padding: 15px; font-size: 12px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php esc_html_e( 'DDCWWFCSC — Payment Required', 'ddcwwfcsc' ); ?></h1>
        </div>
        <div class="content">
            <p><?php printf(
                /* translators: %s: requester's name */
                esc_html__( 'Hi %s,', 'ddcwwfcsc' ),
                esc_html( $name )
            ); ?></p>

            <p><?php esc_html_e( 'Your ticket request has been approved! Please complete your payment using the link below.', 'ddcwwfcsc' ); ?></p>

            <div class="details">
                <p><strong><?php esc_html_e( 'Match:', 'ddcwwfcsc' ); ?></strong> Wolves v <?php echo esc_html( $opponent ); ?></p>
                <p><strong><?php esc_html_e( 'Date:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $match_date ); ?></p>
                <p><strong><?php esc_html_e( 'Tickets:', 'ddcwwfcsc' ); ?></strong> <?php echo absint( $num_tickets ); ?></p>
                <p><strong><?php esc_html_e( 'Total:', 'ddcwwfcsc' ); ?></strong> &pound;<?php echo esc_html( number_format( (float) $amount, 2 ) ); ?></p>
            </div>

            <div class="cta">
                <a href="<?php echo esc_url( $payment_url ); ?>">
                    <?php printf(
                        /* translators: %s: formatted amount */
                        esc_html__( 'Pay Now — %s', 'ddcwwfcsc' ),
                        '£' . number_format( (float) $amount, 2 )
                    ); ?>
                </a>
            </div>

            <div class="warning">
                <?php esc_html_e( 'This payment link expires in 24 hours. If you do not complete payment in time, your ticket request will be cancelled and the tickets returned to the pool.', 'ddcwwfcsc' ); ?>
            </div>

            <p><?php esc_html_e( 'If you have any questions, please contact Coxy.', 'ddcwwfcsc' ); ?></p>
        </div>
        <div class="footer">
            <p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
        </div>
    </div>
</body>
</html>
