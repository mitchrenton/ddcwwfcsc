<?php
/**
 * Single fixture template — all data from post meta, no the_content().
 *
 * @package DDCWWFCSC_Theme
 */

get_header();

$post_id   = get_the_ID();
$opponent  = class_exists( 'DDCWWFCSC_Fixture_CPT' ) ? DDCWWFCSC_Fixture_CPT::get_opponent( $post_id ) : null;
$date      = ddcwwfcsc_fixture_date( $post_id );
$venue     = get_post_meta( $post_id, '_ddcwwfcsc_venue', true );
$venue_label = ddcwwfcsc_fixture_venue( $post_id );
$remaining = (int) get_post_meta( $post_id, '_ddcwwfcsc_tickets_remaining', true );
$total     = (int) get_post_meta( $post_id, '_ddcwwfcsc_total_tickets', true );
$on_sale   = (bool) get_post_meta( $post_id, '_ddcwwfcsc_on_sale', true );
$max_pp    = (int) get_post_meta( $post_id, '_ddcwwfcsc_max_per_person', true );

// Price categories.
$price_terms = get_the_terms( $post_id, 'ddcwwfcsc_price_category' );
?>

<main class="site-main" role="main">
	<div class="container">
		<article id="post-<?php echo esc_attr( $post_id ); ?>" <?php post_class( 'hentry' ); ?>>

			<div class="fixture-header">
				<?php if ( $opponent && ! empty( $opponent['badge_url'] ) ) : ?>
					<img class="fixture-header__badge" src="<?php echo esc_url( $opponent['badge_url'] ); ?>" alt="<?php echo esc_attr( $opponent['name'] ); ?>">
				<?php endif; ?>
				<div>
					<h1 class="entry-title" style="margin-bottom:var(--space-sm);">
						<?php
						if ( $opponent ) {
							printf( '%s %s', esc_html__( 'vs', 'ddcwwfcsc-theme' ), esc_html( $opponent['name'] ) );
						} else {
							the_title();
						}
						?>
					</h1>
					<div><?php ddcwwfcsc_fixture_badges( $post_id ); ?></div>
				</div>
			</div>

			<div class="fixture-details">
				<?php if ( $date ) : ?>
					<div class="fixture-detail-row">
						<span class="fixture-detail-row__label"><?php esc_html_e( 'Date', 'ddcwwfcsc-theme' ); ?></span>
						<span class="fixture-detail-row__value"><?php echo esc_html( $date ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $venue_label ) : ?>
					<div class="fixture-detail-row">
						<span class="fixture-detail-row__label"><?php esc_html_e( 'Venue', 'ddcwwfcsc-theme' ); ?></span>
						<span class="fixture-detail-row__value">
							<span class="badge badge--<?php echo esc_attr( $venue ); ?>"><?php echo esc_html( $venue_label ); ?></span>
						</span>
					</div>
				<?php endif; ?>

				<?php if ( $total ) : ?>
					<div class="fixture-detail-row">
						<span class="fixture-detail-row__label"><?php esc_html_e( 'Tickets', 'ddcwwfcsc-theme' ); ?></span>
						<span class="fixture-detail-row__value"><?php printf( '%d / %d %s', $remaining, $total, esc_html__( 'remaining', 'ddcwwfcsc-theme' ) ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $max_pp ) : ?>
					<div class="fixture-detail-row">
						<span class="fixture-detail-row__label"><?php esc_html_e( 'Max per person', 'ddcwwfcsc-theme' ); ?></span>
						<span class="fixture-detail-row__value"><?php echo esc_html( $max_pp ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $price_terms && ! is_wp_error( $price_terms ) ) : ?>
					<?php foreach ( $price_terms as $term ) :
						$price = get_term_meta( $term->term_id, '_ddcwwfcsc_price', true );
						?>
						<div class="fixture-detail-row">
							<span class="fixture-detail-row__label"><?php echo esc_html( $term->name ); ?></span>
							<span class="fixture-detail-row__value">&pound;<?php echo esc_html( number_format( (float) $price, 2 ) ); ?></span>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<?php
			// Venue-aware CTA.
			if ( 'home' === $venue && $on_sale && $remaining > 0 ) : ?>
				<div class="fixture-cta">
					<a href="#ticket-form" class="btn btn--primary"><?php esc_html_e( 'Request Tickets', 'ddcwwfcsc-theme' ); ?></a>
				</div>
			<?php elseif ( 'away' === $venue && $opponent && class_exists( 'DDCWWFCSC_Beerwolf_CPT' ) ) :
				$beerwolf_post = DDCWWFCSC_Beerwolf_CPT::get_beerwolf_for_opponent( $opponent['term_id'] );
				if ( $beerwolf_post ) : ?>
					<div class="fixture-cta">
						<a href="<?php echo esc_url( get_permalink( $beerwolf_post->ID ) ); ?>" class="btn btn--primary"><?php esc_html_e( 'View Pub Guide', 'ddcwwfcsc-theme' ); ?></a>
					</div>
				<?php endif; ?>
			<?php endif; ?>

		</article>

		<?php
		if ( class_exists( 'DDCWWFCSC_MOTM_Front' ) ) {
			echo DDCWWFCSC_MOTM_Front::render_voting_section( $post_id );
		}
		?>
	</div>
</main>

<?php
get_footer();
