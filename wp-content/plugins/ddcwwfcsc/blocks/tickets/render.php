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
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'ddcwwfcsc_ticket_request' ),
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
			$remaining      = (int) get_post_meta( $fixture->ID, '_ddcwwfcsc_tickets_remaining', true );
			$max_per_person = (int) get_post_meta( $fixture->ID, '_ddcwwfcsc_max_per_person', true );
			$formatted_date = $match_date ? wp_date( 'l j F Y, H:i', strtotime( $match_date ) ) : __( 'TBC', 'ddcwwfcsc' );

			// Price.
			$price_amount = '';
			$unit_price   = 0;
			$terms        = get_the_terms( $fixture->ID, 'ddcwwfcsc_price_category' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$price_val = get_term_meta( $terms[0]->term_id, '_ddcwwfcsc_price', true );
				if ( $price_val ) {
					$unit_price   = (float) $price_val;
					$price_amount = '£' . number_format( $unit_price, 2 );
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
				</div>

				<?php if ( $remaining > 0 ) : ?>
					<?php if ( ! is_user_logged_in() ) : ?>
						<p class="ddcwwfcsc-login-prompt">
							<span class="ddcwwfcsc-remaining" data-fixture-id="<?php echo esc_attr( $fixture->ID ); ?>"><?php printf( esc_html__( '%d tickets available', 'ddcwwfcsc' ), $remaining ); ?></span> &mdash;
							<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'ddcwwfcsc' ); ?></a> <?php esc_html_e( 'to request tickets.', 'ddcwwfcsc' ); ?>
						</p>
					<?php else : ?>
						<form class="ddcwwfcsc-ticket-form"
							  data-fixture-id="<?php echo esc_attr( $fixture->ID ); ?>"
							  data-max-per-person="<?php echo esc_attr( $max_per_person ); ?>"
							  <?php if ( $unit_price ) : ?>data-price="<?php echo esc_attr( $unit_price ); ?>"<?php endif; ?>>

							<input type="hidden" name="name" value="<?php echo esc_attr( $current_user->display_name ); ?>">
							<input type="hidden" name="email" value="<?php echo esc_attr( $current_user->user_email ); ?>">

							<div class="ddcwwfcsc-ticket-meta">
								<span class="ddcwwfcsc-remaining" data-fixture-id="<?php echo esc_attr( $fixture->ID ); ?>">
									<?php printf( esc_html__( '%d tickets available', 'ddcwwfcsc' ), $remaining ); ?>
								</span>
								<?php if ( $max_per_person ) : ?>
									<span class="ddcwwfcsc-max-per"><?php printf( esc_html__( 'Max %d per member', 'ddcwwfcsc' ), $max_per_person ); ?></span>
								<?php endif; ?>
								<?php if ( $price_amount ) : ?>
									<span class="ddcwwfcsc-price-per"><?php echo esc_html( $price_amount ); ?> <?php esc_html_e( 'per ticket', 'ddcwwfcsc' ); ?></span>
								<?php endif; ?>
							</div>

							<div class="ddcwwfcsc-form-row ddcwwfcsc-form-row--stepper">
								<div class="ddcwwfcsc-stepper">
									<button type="button" class="ddcwwfcsc-stepper-btn ddcwwfcsc-stepper-dec" aria-label="<?php esc_attr_e( 'Decrease', 'ddcwwfcsc' ); ?>">&#8722;</button>
									<input type="number" name="num_tickets" class="ddcwwfcsc-stepper-input"
										   value="1" min="1" max="<?php echo esc_attr( $max_selectable ); ?>"
										   readonly aria-label="<?php esc_attr_e( 'Number of tickets', 'ddcwwfcsc' ); ?>">
									<button type="button" class="ddcwwfcsc-stepper-btn ddcwwfcsc-stepper-inc" aria-label="<?php esc_attr_e( 'Increase', 'ddcwwfcsc' ); ?>">+</button>
								</div>
								<?php if ( $unit_price ) : ?>
									<span class="ddcwwfcsc-ticket-total"><?php printf( esc_html__( 'Total: %s', 'ddcwwfcsc' ), '£' . number_format( $unit_price, 2 ) ); ?></span>
								<?php endif; ?>
							</div>

							<div class="ddcwwfcsc-form-row">
								<button type="submit" class="btn btn--primary ddcwwfcsc-submit-btn"><?php esc_html_e( 'Request 1 ticket', 'ddcwwfcsc' ); ?></button>
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
