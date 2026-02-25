<?php
/**
 * Template: Payment page (rendered inside theme header/footer).
 *
 * Available variables:
 * @var string      $state   Page state (ready, invalid, expired, already_paid, success, cancelled_checkout).
 * @var object|null $request The ticket request row.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="ddcwwfcsc-payment-wrap">

    <?php if ( 'invalid' === $state ) : ?>

        <div class="ddcwwfcsc-payment-card ddcwwfcsc-payment-message">
            <h1><?php esc_html_e( 'Invalid Payment Link', 'ddcwwfcsc' ); ?></h1>
            <p><?php esc_html_e( 'This payment link is invalid. Please check the link in your email and try again.', 'ddcwwfcsc' ); ?></p>
        </div>

    <?php elseif ( 'expired' === $state ) : ?>

        <div class="ddcwwfcsc-payment-card ddcwwfcsc-payment-message">
            <h1><?php esc_html_e( 'Payment Link Expired', 'ddcwwfcsc' ); ?></h1>
            <p><?php esc_html_e( 'This payment link has expired. Please contact Coxy to arrange an alternative.', 'ddcwwfcsc' ); ?></p>
        </div>

    <?php elseif ( 'already_paid' === $state ) : ?>

        <div class="ddcwwfcsc-payment-card ddcwwfcsc-payment-message ddcwwfcsc-payment-success">
            <h1><?php esc_html_e( 'Already Paid', 'ddcwwfcsc' ); ?></h1>
            <p><?php esc_html_e( 'Payment has already been received for this ticket request. See you on match day!', 'ddcwwfcsc' ); ?></p>
        </div>

    <?php elseif ( 'success' === $state ) : ?>

        <div class="ddcwwfcsc-payment-card ddcwwfcsc-payment-message ddcwwfcsc-payment-success">
            <h1><?php esc_html_e( 'Payment Successful!', 'ddcwwfcsc' ); ?></h1>
            <p><?php esc_html_e( 'Thank you for your payment. You will receive a confirmation email shortly. See you on match day!', 'ddcwwfcsc' ); ?></p>
        </div>

        <?php if ( $request ) : ?>
        <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push( {
            event:          'purchase',
            transaction_id: '<?php echo absint( $request->id ); ?>',
            currency:       'GBP',
            value:          <?php echo (float) $request->amount; ?>,
            num_tickets:    <?php echo absint( $request->num_tickets ); ?>,
            fixture_id:     <?php echo absint( $request->fixture_id ); ?>,
        } );
        </script>
        <?php endif; ?>

    <?php elseif ( 'cancelled_checkout' === $state ) : ?>

        <div class="ddcwwfcsc-payment-card ddcwwfcsc-payment-message">
            <h1><?php esc_html_e( 'Payment Cancelled', 'ddcwwfcsc' ); ?></h1>
            <p><?php esc_html_e( 'You cancelled the payment. You can try again using the button below.', 'ddcwwfcsc' ); ?></p>
            <div class="ddcwwfcsc-payment-actions">
                <a href="<?php echo esc_url( DDCWWFCSC_Payments::get_payment_url( $request->payment_token ) ); ?>" class="btn btn--primary ddcwwfcsc-payment-btn">
                    <?php esc_html_e( 'Try Again', 'ddcwwfcsc' ); ?>
                </a>
            </div>
        </div>

    <?php elseif ( 'ready' === $state && $request ) : ?>

        <?php
        $opponent_data = DDCWWFCSC_Fixture_CPT::get_opponent( $request->fixture_id );
        $opponent_name = $opponent_data ? $opponent_data['name'] : '';
        $badge_file    = $opponent_data ? ( $opponent_data['badge'] ?? '' ) : '';
        $match_date    = get_post_meta( $request->fixture_id, '_ddcwwfcsc_match_date', true );
        $formatted_date = $match_date ? wp_date( 'l j F Y, H:i', strtotime( $match_date ) ) : __( 'TBC', 'ddcwwfcsc' );
        $checkout_url  = add_query_arg( 'checkout', '1', DDCWWFCSC_Payments::get_payment_url( $request->payment_token ) );
        ?>

        <div class="ddcwwfcsc-payment-card">
            <h1><?php esc_html_e( 'Complete Your Payment', 'ddcwwfcsc' ); ?></h1>

            <div class="ddcwwfcsc-payment-fixture">
                <?php if ( $badge_file ) : ?>
                    <img class="ddcwwfcsc-payment-badge"
                         src="<?php echo esc_url( DDCWWFCSC_PLUGIN_URL . 'assets/img/clubs/' . $badge_file ); ?>"
                         alt="<?php echo esc_attr( $opponent_name ); ?>"
                         width="64" height="64">
                <?php endif; ?>

                <div class="ddcwwfcsc-payment-fixture-details">
                    <p class="ddcwwfcsc-payment-match">Wolves v <?php echo esc_html( $opponent_name ); ?></p>
                    <p class="ddcwwfcsc-payment-date"><?php echo esc_html( $formatted_date ); ?></p>
                </div>
            </div>

            <div class="ddcwwfcsc-payment-summary">
                <div class="ddcwwfcsc-payment-row">
                    <span><?php esc_html_e( 'Tickets', 'ddcwwfcsc' ); ?></span>
                    <span><?php echo absint( $request->num_tickets ); ?></span>
                </div>
                <div class="ddcwwfcsc-payment-row ddcwwfcsc-payment-total">
                    <span><?php esc_html_e( 'Total', 'ddcwwfcsc' ); ?></span>
                    <span>&pound;<?php echo esc_html( number_format( (float) $request->amount, 2 ) ); ?></span>
                </div>
            </div>

            <div class="ddcwwfcsc-payment-actions">
                <a href="<?php echo esc_url( $checkout_url ); ?>" class="btn btn--primary ddcwwfcsc-payment-btn">
                    <?php printf(
                        /* translators: %s: formatted amount */
                        esc_html__( 'Pay Now — %s', 'ddcwwfcsc' ),
                        '£' . number_format( (float) $request->amount, 2 )
                    ); ?>
                </a>
            </div>

            <p class="ddcwwfcsc-payment-secure"><?php esc_html_e( 'Payments are processed securely by Stripe.', 'ddcwwfcsc' ); ?></p>
        </div>

        <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push( {
            event:       'begin_checkout',
            currency:    'GBP',
            value:       <?php echo (float) $request->amount; ?>,
            num_tickets: <?php echo absint( $request->num_tickets ); ?>,
            fixture_id:  <?php echo absint( $request->fixture_id ); ?>,
        } );
        </script>

    <?php endif; ?>

</div>
