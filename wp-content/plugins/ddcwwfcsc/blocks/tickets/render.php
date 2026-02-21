<?php
/**
 * Server-side render for the ddcwwfcsc/tickets block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Query fixtures currently on sale and still upcoming.
$fixtures = get_posts( array(
    'post_type'      => 'ddcwwfcsc_fixture',
    'post_status'    => 'publish',
    'posts_per_page' => 2,
    'meta_query'     => array(
        'relation' => 'AND',
        array(
            'key'   => '_ddcwwfcsc_on_sale',
            'value' => '1',
        ),
        array(
            'key'     => '_ddcwwfcsc_match_date',
            'value'   => current_time( 'Y-m-d\TH:i' ),
            'compare' => '>=',
        ),
    ),
    'meta_key'       => '_ddcwwfcsc_match_date',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
) );

// Only enqueue form script for logged-in users.
if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();

    wp_enqueue_script(
        'ddcwwfcsc-ticket-form',
        DDCWWFCSC_PLUGIN_URL . 'assets/js/ticket-form.js',
        array(),
        DDCWWFCSC_VERSION,
        true
    );

    wp_localize_script( 'ddcwwfcsc-ticket-form', 'ddcwwfcsc', array(
        'ajax_url'   => admin_url( 'admin-ajax.php' ),
        'nonce'      => wp_create_nonce( 'ddcwwfcsc_ticket_request' ),
        'user_name'  => $current_user->display_name,
        'user_email' => $current_user->user_email,
    ) );
}
?>

<div <?php echo get_block_wrapper_attributes( array( 'class' => 'ddcwwfcsc-tickets' ) ); ?>>

    <?php if ( empty( $fixtures ) ) : ?>
        <div class="ddcwwfcsc-no-fixtures">
            <p><?php esc_html_e( 'No tickets are currently on sale. Check back soon!', 'ddcwwfcsc' ); ?></p>
        </div>
    <?php else : ?>

        <?php foreach ( $fixtures as $fixture ) :
            $opponent_data  = DDCWWFCSC_Fixture_CPT::get_opponent( $fixture->ID );
            $opponent       = $opponent_data ? $opponent_data['name'] : '';
            $badge_url      = $opponent_data ? $opponent_data['badge_url'] : '';
            $venue          = get_post_meta( $fixture->ID, '_ddcwwfcsc_venue', true );
            $match_date     = get_post_meta( $fixture->ID, '_ddcwwfcsc_match_date', true );
            $total          = (int) get_post_meta( $fixture->ID, '_ddcwwfcsc_total_tickets', true );
            $remaining      = (int) get_post_meta( $fixture->ID, '_ddcwwfcsc_tickets_remaining', true );
            $max_per_person = (int) get_post_meta( $fixture->ID, '_ddcwwfcsc_max_per_person', true );
            $formatted_date = $match_date ? wp_date( 'l j F Y, H:i', strtotime( $match_date ) ) : __( 'TBC', 'ddcwwfcsc' );

            // Price category.
            $price_category_name = '';
            $price_amount        = '';
            $terms = get_the_terms( $fixture->ID, 'ddcwwfcsc_price_category' );
            if ( $terms && ! is_wp_error( $terms ) ) {
                $term  = $terms[0];
                $price = get_term_meta( $term->term_id, '_ddcwwfcsc_price', true );
                $price_category_name = $term->name;
                if ( $price ) {
                    $price_amount = '£' . number_format( (float) $price, 2 );
                }
            }

            $max_selectable = min( $max_per_person, $remaining );
        ?>
            <div class="ddcwwfcsc-fixture" data-fixture-id="<?php echo esc_attr( $fixture->ID ); ?>">
                <div class="ddcwwfcsc-fixture-header">
                    <h3 class="ddcwwfcsc-fixture-title">
                        <?php if ( 'away' === $venue ) : ?>
                            <?php if ( $badge_url ) : ?>
                                <img src="<?php echo esc_url( $badge_url ); ?>" alt="<?php echo esc_attr( $opponent ); ?>" class="ddcwwfcsc-badge">
                            <?php endif; ?>
                            <?php echo esc_html( $opponent ); ?> v Wolves
                            <img src="<?php echo esc_url( DDCWWFCSC_PLUGIN_URL . 'assets/img/clubs/wolves.png' ); ?>" alt="Wolves" class="ddcwwfcsc-badge">
                        <?php else : ?>
                            <img src="<?php echo esc_url( DDCWWFCSC_PLUGIN_URL . 'assets/img/clubs/wolves.png' ); ?>" alt="Wolves" class="ddcwwfcsc-badge">
                            Wolves v <?php echo esc_html( $opponent ); ?>
                            <?php if ( $badge_url ) : ?>
                                <img src="<?php echo esc_url( $badge_url ); ?>" alt="<?php echo esc_attr( $opponent ); ?>" class="ddcwwfcsc-badge">
                            <?php endif; ?>
                        <?php endif; ?>
                    </h3>
                </div>

                <div class="ddcwwfcsc-fixture-details">
                    <div class="ddcwwfcsc-detail">
                        <span class="ddcwwfcsc-detail-label"><?php esc_html_e( 'Date', 'ddcwwfcsc' ); ?></span>
                        <span class="ddcwwfcsc-detail-value"><?php echo esc_html( $formatted_date ); ?></span>
                    </div>
                    <?php if ( $price_amount ) : ?>
                        <div class="ddcwwfcsc-detail">
                            <span class="ddcwwfcsc-detail-label"><?php echo esc_html( $price_category_name ); ?></span>
                            <span class="ddcwwfcsc-detail-value ddcwwfcsc-price"><?php echo esc_html( $price_amount ); ?> <span class="ddcwwfcsc-per-ticket"><?php esc_html_e( 'per ticket', 'ddcwwfcsc' ); ?></span></span>
                        </div>
                    <?php elseif ( $price_category_name ) : ?>
                        <div class="ddcwwfcsc-detail">
                            <span class="ddcwwfcsc-detail-label"><?php esc_html_e( 'Category', 'ddcwwfcsc' ); ?></span>
                            <span class="ddcwwfcsc-detail-value"><?php echo esc_html( $price_category_name ); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="ddcwwfcsc-detail">
                        <span class="ddcwwfcsc-detail-label"><?php esc_html_e( 'Availability', 'ddcwwfcsc' ); ?></span>
                        <span class="ddcwwfcsc-detail-value ddcwwfcsc-remaining" data-fixture-id="<?php echo esc_attr( $fixture->ID ); ?>">
                            <?php
                            printf(
                                /* translators: 1: remaining tickets, 2: total tickets */
                                esc_html__( '%1$d of %2$d remaining', 'ddcwwfcsc' ),
                                $remaining,
                                $total
                            );
                            ?>
                        </span>
                    </div>
                </div>

                <?php if ( $remaining > 0 ) : ?>
                    <?php if ( ! is_user_logged_in() ) : ?>
                        <p class="ddcwwfcsc-login-prompt">
                            <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'ddcwwfcsc' ); ?></a> <?php esc_html_e( 'to request tickets.', 'ddcwwfcsc' ); ?>
                        </p>
                    <?php else : ?>
                    <form class="ddcwwfcsc-ticket-form" data-fixture-id="<?php echo esc_attr( $fixture->ID ); ?>">
                        <h4><?php esc_html_e( 'Request Tickets', 'ddcwwfcsc' ); ?></h4>

                        <div class="ddcwwfcsc-form-row">
                            <label for="ddcwwfcsc-name-<?php echo esc_attr( $fixture->ID ); ?>"><?php esc_html_e( 'Name', 'ddcwwfcsc' ); ?></label>
                            <input type="text" id="ddcwwfcsc-name-<?php echo esc_attr( $fixture->ID ); ?>" name="name" value="<?php echo esc_attr( $current_user->display_name ); ?>" required>
                        </div>

                        <div class="ddcwwfcsc-form-row">
                            <label for="ddcwwfcsc-email-<?php echo esc_attr( $fixture->ID ); ?>"><?php esc_html_e( 'Email', 'ddcwwfcsc' ); ?></label>
                            <input type="email" id="ddcwwfcsc-email-<?php echo esc_attr( $fixture->ID ); ?>" name="email" value="<?php echo esc_attr( $current_user->user_email ); ?>" required>
                        </div>

                        <div class="ddcwwfcsc-form-row">
                            <label for="ddcwwfcsc-tickets-<?php echo esc_attr( $fixture->ID ); ?>"><?php esc_html_e( 'Number of Tickets', 'ddcwwfcsc' ); ?></label>
                            <select id="ddcwwfcsc-tickets-<?php echo esc_attr( $fixture->ID ); ?>" name="num_tickets" required>
                                <?php for ( $i = 1; $i <= $max_selectable; $i++ ) : ?>
                                    <option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="ddcwwfcsc-form-row">
                            <button type="submit" class="ddcwwfcsc-submit-btn"><?php esc_html_e( 'Request Tickets', 'ddcwwfcsc' ); ?></button>
                        </div>

                        <div class="ddcwwfcsc-form-message" aria-live="polite"></div>
                    </form>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="ddcwwfcsc-sold-out">
                        <p><?php esc_html_e( 'Sold Out', 'ddcwwfcsc' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>
