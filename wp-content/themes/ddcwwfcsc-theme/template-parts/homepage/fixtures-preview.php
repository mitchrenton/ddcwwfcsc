<?php
/**
 * Homepage fixtures preview — next home + away fixtures.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! post_type_exists( 'ddcwwfcsc_fixture' ) ) {
	return;
}

$now = current_time( 'Y-m-d\TH:i' );

// Next 2 upcoming home fixtures.
$home_query = new WP_Query( array(
	'post_type'      => 'ddcwwfcsc_fixture',
	'posts_per_page' => 2,
	'post_status'    => 'publish',
	'meta_key'       => '_ddcwwfcsc_match_date',
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'meta_query'     => array(
		'relation' => 'AND',
		array(
			'key'     => '_ddcwwfcsc_venue',
			'value'   => 'home',
		),
		array(
			'key'     => '_ddcwwfcsc_match_date',
			'value'   => $now,
			'compare' => '>=',
		),
	),
) );

// Next upcoming away fixture.
$away_query = new WP_Query( array(
	'post_type'      => 'ddcwwfcsc_fixture',
	'posts_per_page' => 1,
	'post_status'    => 'publish',
	'meta_key'       => '_ddcwwfcsc_match_date',
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'meta_query'     => array(
		'relation' => 'AND',
		array(
			'key'     => '_ddcwwfcsc_venue',
			'value'   => 'away',
		),
		array(
			'key'     => '_ddcwwfcsc_match_date',
			'value'   => $now,
			'compare' => '>=',
		),
	),
) );

if ( ! $home_query->have_posts() && ! $away_query->have_posts() ) {
	return;
}
?>
<section class="homepage-section homepage-section--fixtures">
	<div class="homepage-section__header">
		<h2 class="section-heading"><?php esc_html_e( 'Upcoming Fixtures', 'ddcwwfcsc-theme' ); ?></h2>
		<a href="<?php echo esc_url( get_post_type_archive_link( 'ddcwwfcsc_fixture' ) ); ?>" class="homepage-section__more"><?php esc_html_e( 'View all fixtures &rarr;', 'ddcwwfcsc-theme' ); ?></a>
	</div>

	<div class="fixtures-preview">
		<?php if ( $home_query->have_posts() ) : ?>
			<div class="fixtures-preview__column">
				<h3 class="fixtures-preview__column-heading"><?php esc_html_e( 'Home', 'ddcwwfcsc-theme' ); ?></h3>
				<?php
				$card_index    = 0;
				$home_fixtures = array(); // Collect data for modals.
				while ( $home_query->have_posts() ) : $home_query->the_post();
					$fixture_id = get_the_ID();
					$opponent   = DDCWWFCSC_Fixture_CPT::get_opponent( $fixture_id );
					$date       = ddcwwfcsc_fixture_date( $fixture_id );
					$variant    = 0 === $card_index ? 'fixture-card--featured' : 'fixture-card--compact';
					$on_sale    = get_post_meta( $fixture_id, '_ddcwwfcsc_on_sale', true );
					$remaining  = (int) get_post_meta( $fixture_id, '_ddcwwfcsc_tickets_remaining', true );
				?>
					<div class="fixture-card <?php echo esc_attr( $variant ); ?>">
						<?php if ( $opponent && ! empty( $opponent['badge_url'] ) ) : ?>
							<img class="fixture-card__badge" src="<?php echo esc_url( $opponent['badge_url'] ); ?>" alt="<?php echo esc_attr( $opponent['name'] ); ?>">
						<?php endif; ?>
						<div class="fixture-card__body">
							<p class="fixture-card__opponent"><?php echo esc_html( $opponent ? $opponent['name'] : get_the_title() ); ?></p>
							<?php if ( $date ) : ?>
								<p class="fixture-card__date"><?php echo esc_html( $date ); ?></p>
							<?php endif; ?>
							<?php if ( $on_sale && $remaining > 0 ) : ?>
								<?php if ( is_user_logged_in() ) : ?>
									<button type="button" class="btn btn--sm" data-open-ticket-modal="<?php echo esc_attr( $fixture_id ); ?>"><?php esc_html_e( 'Request Tickets', 'ddcwwfcsc-theme' ); ?></button>
									<?php $home_fixtures[] = $fixture_id; ?>
								<?php else : ?>
									<a href="<?php echo esc_url( wp_login_url( home_url() ) ); ?>" class="btn btn--sm"><?php esc_html_e( 'Log in to Request Tickets', 'ddcwwfcsc-theme' ); ?></a>
								<?php endif; ?>
							<?php elseif ( $on_sale && $remaining < 1 ) : ?>
								<span class="fixture-card__status fixture-card__status--sold-out"><?php esc_html_e( 'Sold Out', 'ddcwwfcsc-theme' ); ?></span>
							<?php else : ?>
								<span class="fixture-card__status fixture-card__status--not-on-sale"><?php esc_html_e( 'Not Yet On Sale', 'ddcwwfcsc-theme' ); ?></span>
							<?php endif; ?>
						</div>
					</div>
				<?php
					$card_index++;
				endwhile;
				?>
			</div>

			<?php
			// Enqueue ticket component styles for the modals.
			wp_enqueue_style( 'ddcwwfcsc-ticket-front' );

			// Render ticket modals for home fixtures.
			foreach ( $home_fixtures as $modal_fixture_id ) :
				$m_opponent       = DDCWWFCSC_Fixture_CPT::get_opponent( $modal_fixture_id );
				$m_match_date     = get_post_meta( $modal_fixture_id, '_ddcwwfcsc_match_date', true );
				$m_formatted_date = $m_match_date ? wp_date( 'l j F Y, H:i', strtotime( $m_match_date ) ) : __( 'TBC', 'ddcwwfcsc' );
				$m_total          = (int) get_post_meta( $modal_fixture_id, '_ddcwwfcsc_total_tickets', true );
				$m_remaining      = (int) get_post_meta( $modal_fixture_id, '_ddcwwfcsc_tickets_remaining', true );
				$m_max_per_person = (int) get_post_meta( $modal_fixture_id, '_ddcwwfcsc_max_per_person', true );
				$m_max_selectable = min( $m_max_per_person, $m_remaining );

				$m_price_amount = '';
				$m_price_label  = '';
				$m_terms = get_the_terms( $modal_fixture_id, 'ddcwwfcsc_price_category' );
				if ( $m_terms && ! is_wp_error( $m_terms ) ) {
					$m_term  = $m_terms[0];
					$m_price = get_term_meta( $m_term->term_id, '_ddcwwfcsc_price', true );
					$m_price_label = $m_term->name;
					if ( $m_price ) {
						$m_price_amount = '£' . number_format( (float) $m_price, 2 );
					}
				}
			?>
				<dialog class="ticket-modal" id="ticket-modal-<?php echo esc_attr( $modal_fixture_id ); ?>">
					<div class="ticket-modal__inner">
						<button type="button" class="ticket-modal__close" aria-label="<?php esc_attr_e( 'Close', 'ddcwwfcsc-theme' ); ?>">&times;</button>

						<div class="ddcwwfcsc-fixture" data-fixture-id="<?php echo esc_attr( $modal_fixture_id ); ?>">
							<div class="ddcwwfcsc-fixture-header">
								<h3 class="ddcwwfcsc-fixture-title">
									<img src="<?php echo esc_url( DDCWWFCSC_PLUGIN_URL . 'assets/img/clubs/wolves.png' ); ?>" alt="Wolves" class="ddcwwfcsc-badge">
									Wolves v <?php echo esc_html( $m_opponent ? $m_opponent['name'] : get_the_title( $modal_fixture_id ) ); ?>
									<?php if ( $m_opponent && ! empty( $m_opponent['badge_url'] ) ) : ?>
										<img src="<?php echo esc_url( $m_opponent['badge_url'] ); ?>" alt="<?php echo esc_attr( $m_opponent['name'] ); ?>" class="ddcwwfcsc-badge">
									<?php endif; ?>
								</h3>
							</div>

							<div class="ddcwwfcsc-fixture-details">
								<div class="ddcwwfcsc-detail">
									<span class="ddcwwfcsc-detail-label"><?php esc_html_e( 'Date', 'ddcwwfcsc' ); ?></span>
									<span class="ddcwwfcsc-detail-value"><?php echo esc_html( $m_formatted_date ); ?></span>
								</div>
								<?php if ( $m_price_amount ) : ?>
									<div class="ddcwwfcsc-detail">
										<span class="ddcwwfcsc-detail-label"><?php echo esc_html( $m_price_label ); ?></span>
										<span class="ddcwwfcsc-detail-value ddcwwfcsc-price"><?php echo esc_html( $m_price_amount ); ?> <span class="ddcwwfcsc-per-ticket"><?php esc_html_e( 'per ticket', 'ddcwwfcsc' ); ?></span></span>
									</div>
								<?php endif; ?>
								<div class="ddcwwfcsc-detail">
									<span class="ddcwwfcsc-detail-label"><?php esc_html_e( 'Availability', 'ddcwwfcsc' ); ?></span>
									<span class="ddcwwfcsc-detail-value ddcwwfcsc-remaining" data-fixture-id="<?php echo esc_attr( $modal_fixture_id ); ?>">
										<?php
										printf(
											esc_html__( '%1$d of %2$d remaining', 'ddcwwfcsc' ),
											$m_remaining,
											$m_total
										);
										?>
									</span>
								</div>
							</div>

							<?php $modal_user = wp_get_current_user(); ?>
							<form class="ddcwwfcsc-ticket-form" data-fixture-id="<?php echo esc_attr( $modal_fixture_id ); ?>">
								<h4><?php esc_html_e( 'Request Tickets', 'ddcwwfcsc' ); ?></h4>

								<div class="ddcwwfcsc-form-row">
									<label for="ddcwwfcsc-modal-name-<?php echo esc_attr( $modal_fixture_id ); ?>"><?php esc_html_e( 'Name', 'ddcwwfcsc' ); ?></label>
									<input type="text" id="ddcwwfcsc-modal-name-<?php echo esc_attr( $modal_fixture_id ); ?>" name="name" value="<?php echo esc_attr( $modal_user->display_name ); ?>" required>
								</div>

								<div class="ddcwwfcsc-form-row">
									<label for="ddcwwfcsc-modal-email-<?php echo esc_attr( $modal_fixture_id ); ?>"><?php esc_html_e( 'Email', 'ddcwwfcsc' ); ?></label>
									<input type="email" id="ddcwwfcsc-modal-email-<?php echo esc_attr( $modal_fixture_id ); ?>" name="email" value="<?php echo esc_attr( $modal_user->user_email ); ?>" required>
								</div>

								<div class="ddcwwfcsc-form-row">
									<label for="ddcwwfcsc-modal-tickets-<?php echo esc_attr( $modal_fixture_id ); ?>"><?php esc_html_e( 'Number of Tickets', 'ddcwwfcsc' ); ?></label>
									<select id="ddcwwfcsc-modal-tickets-<?php echo esc_attr( $modal_fixture_id ); ?>" name="num_tickets" required>
										<?php for ( $i = 1; $i <= $m_max_selectable; $i++ ) : ?>
											<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
										<?php endfor; ?>
									</select>
								</div>

								<div class="ddcwwfcsc-form-row">
									<button type="submit" class="ddcwwfcsc-submit-btn"><?php esc_html_e( 'Request Tickets', 'ddcwwfcsc' ); ?></button>
								</div>

								<div class="ddcwwfcsc-form-message" aria-live="polite"></div>
							</form>
						</div>
					</div>
				</dialog>
			<?php endforeach; ?>

			<?php
			// Enqueue ticket form script + AJAX data for modals.
			if ( ! empty( $home_fixtures ) ) {
				$hp_user = wp_get_current_user();
				wp_enqueue_script(
					'ddcwwfcsc-ticket-form',
					DDCWWFCSC_PLUGIN_URL . 'assets/js/ticket-form.js',
					array(),
					defined( 'DDCWWFCSC_VERSION' ) ? DDCWWFCSC_VERSION : '1.0.0',
					true
				);
				wp_localize_script( 'ddcwwfcsc-ticket-form', 'ddcwwfcsc', array(
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'nonce'      => wp_create_nonce( 'ddcwwfcsc_ticket_request' ),
					'user_name'  => $hp_user->display_name,
					'user_email' => $hp_user->user_email,
				) );
			}
			?>
		<?php endif; ?>

		<?php if ( $away_query->have_posts() ) : ?>
			<div class="fixtures-preview__column">
				<h3 class="fixtures-preview__column-heading"><?php esc_html_e( 'Away', 'ddcwwfcsc-theme' ); ?></h3>
				<?php
				while ( $away_query->have_posts() ) : $away_query->the_post();
					$opponent = DDCWWFCSC_Fixture_CPT::get_opponent( get_the_ID() );
					$date     = ddcwwfcsc_fixture_date( get_the_ID() );

					// Check for a beerwolf guide for this opponent.
					$beerwolf_url = '';
					if ( $opponent && class_exists( 'DDCWWFCSC_Beerwolf_CPT' ) ) {
						$beerwolf_post = DDCWWFCSC_Beerwolf_CPT::get_beerwolf_for_opponent( $opponent['term_id'] );
						if ( $beerwolf_post ) {
							$beerwolf_url = get_permalink( $beerwolf_post->ID );
						}
					}
				?>
					<div class="fixture-card fixture-card--featured">
						<?php if ( $opponent && ! empty( $opponent['badge_url'] ) ) : ?>
							<img class="fixture-card__badge" src="<?php echo esc_url( $opponent['badge_url'] ); ?>" alt="<?php echo esc_attr( $opponent['name'] ); ?>">
						<?php endif; ?>
						<div class="fixture-card__body">
							<p class="fixture-card__opponent"><?php echo esc_html( $opponent ? $opponent['name'] : get_the_title() ); ?></p>
							<?php if ( $date ) : ?>
								<p class="fixture-card__date"><?php echo esc_html( $date ); ?></p>
							<?php endif; ?>
							<?php if ( $beerwolf_url ) : ?>
								<a href="<?php echo esc_url( $beerwolf_url ); ?>" class="btn btn--sm"><?php esc_html_e( 'View Pub Guide', 'ddcwwfcsc-theme' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
				<?php endwhile; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php
wp_reset_postdata();
