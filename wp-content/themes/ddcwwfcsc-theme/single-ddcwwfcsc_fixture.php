<?php
/**
 * Single fixture template — all data from post meta, no the_content().
 *
 * @package DDCWWFCSC_Theme
 */

get_header();

$post_id     = get_the_ID();
$opponent    = class_exists( 'DDCWWFCSC_Fixture_CPT' ) ? DDCWWFCSC_Fixture_CPT::get_opponent( $post_id ) : null;
$venue       = get_post_meta( $post_id, '_ddcwwfcsc_venue', true );
$is_home     = 'home' === $venue;
$is_upcoming = ddcwwfcsc_is_fixture_upcoming( $post_id );
$remaining   = (int) get_post_meta( $post_id, '_ddcwwfcsc_tickets_remaining', true );
$on_sale     = (bool) get_post_meta( $post_id, '_ddcwwfcsc_on_sale', true );
$max_pp      = (int) get_post_meta( $post_id, '_ddcwwfcsc_max_per_person', true );

// Match date for hero display.
$match_date_raw = get_post_meta( $post_id, '_ddcwwfcsc_match_date', true );
$hero_date      = $match_date_raw ? wp_date( 'l j F Y', strtotime( $match_date_raw ) ) : '';
$hero_ko        = $match_date_raw ? wp_date( 'H:i', strtotime( $match_date_raw ) ) : '';

// Wolves badge URL.
$wolves_url = defined( 'DDCWWFCSC_PLUGIN_URL' ) ? DDCWWFCSC_PLUGIN_URL . 'assets/img/clubs/wolves.png' : '';

// Opponent data.
$opponent_name  = $opponent ? $opponent['name'] : get_the_title();
$opponent_badge = ( $opponent && ! empty( $opponent['badge_url'] ) ) ? $opponent['badge_url'] : '';

// Competition name.
$competition = '';
$comp_terms  = get_the_terms( $post_id, 'ddcwwfcsc_competition' );
if ( $comp_terms && ! is_wp_error( $comp_terms ) ) {
	$competition = $comp_terms[0]->name;
}

// Price categories.
$price_terms = get_the_terms( $post_id, 'ddcwwfcsc_price_category' );

// Unit price for the ticket form total.
$unit_price = 0;
if ( $price_terms && ! is_wp_error( $price_terms ) ) {
	$first_price_val = get_term_meta( $price_terms[0]->term_id, '_ddcwwfcsc_price', true );
	if ( $first_price_val ) {
		$unit_price = (float) $first_price_val;
	}
}

// Hero background resolution:
// 1. Home team's stadium — Wolves term for home fixtures, opponent's term for away fixtures.
// 2. Per-fixture featured image (for one-off overrides e.g. neutral venues).
// 3. Global customizer setting.
// 4. Theme default image.
$hero_bg = '';
if ( $is_home && class_exists( 'DDCWWFCSC_Fixture_CPT' ) ) {
	$wolves_term = get_term_by( 'name', 'Wolves', 'ddcwwfcsc_opponent' );
	if ( $wolves_term && ! is_wp_error( $wolves_term ) ) {
		$hero_bg = DDCWWFCSC_Fixture_CPT::get_stadium_url_for_term( $wolves_term->term_id );
	}
} elseif ( ! $is_home && $opponent && ! empty( $opponent['stadium_url'] ) ) {
	$hero_bg = $opponent['stadium_url'];
}
if ( ! $hero_bg && has_post_thumbnail( $post_id ) ) {
	$hero_bg = get_the_post_thumbnail_url( $post_id, 'full' );
}
if ( ! $hero_bg ) {
	$hero_bg = get_theme_mod( 'ddcwwfcsc_fixture_hero_image', '' );
}
if ( ! $hero_bg ) {
	$hero_bg = defined( 'DDCWWFCSC_THEME_URI' ) ? DDCWWFCSC_THEME_URI . '/assets/img/hero-flag.jpg' : '';
}

// Venue-aware badge and title arrangement.
if ( $is_home ) {
	$fixture_title = 'Wolves v ' . $opponent_name;
	$left_badge    = $wolves_url;
	$left_alt      = 'Wolves';
	$right_badge   = $opponent_badge;
	$right_alt     = $opponent_name;
} else {
	$fixture_title = $opponent_name . ' v Wolves';
	$left_badge    = $opponent_badge;
	$left_alt      = $opponent_name;
	$right_badge   = $wolves_url;
	$right_alt     = 'Wolves';
}
?>

<?php // ── Hero ───────────────────────────────────────────────────────────── ?>
<div class="hero hero--fixture">
	<div class="hero__media">
		<?php if ( $hero_bg ) : ?>
			<img class="hero__img" src="<?php echo esc_url( $hero_bg ); ?>" alt="" loading="eager">
		<?php endif; ?>
		<div class="hero__tint"></div>
	</div>

	<div class="hero__content hero__content--fixture">
		<div class="fixture-hero-matchup">
			<?php if ( $left_badge ) : ?>
				<img class="fixture-hero-badge" src="<?php echo esc_url( $left_badge ); ?>" alt="<?php echo esc_attr( $left_alt ); ?>">
			<?php endif; ?>
			<span class="fixture-hero-vs">v</span>
			<?php if ( $right_badge ) : ?>
				<img class="fixture-hero-badge" src="<?php echo esc_url( $right_badge ); ?>" alt="<?php echo esc_attr( $right_alt ); ?>">
			<?php endif; ?>
		</div>
		<h1 class="hero__heading fixture-hero__heading"><?php echo esc_html( $fixture_title ); ?></h1>
		<?php if ( $competition ) : ?>
			<p class="hero__subheading"><?php echo esc_html( $competition ); ?></p>
		<?php endif; ?>
		<?php if ( $hero_date ) : ?>
			<p class="hero__fixture-meta">
				<?php echo esc_html( $hero_date ); ?>
				<?php if ( $hero_ko && '00:00' !== $hero_ko ) : ?>
					&bull; KO <?php echo esc_html( $hero_ko ); ?>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</div>
</div>

<main class="site-main" role="main">
	<div class="container">
		<div class="fixture-single-layout">

			<article id="post-<?php echo esc_attr( $post_id ); ?>" class="fixture-single-main">

	
				<?php // ── Ticket section — home fixtures only ───────────────── ?>
				<?php if ( $is_home && $is_upcoming ) : ?>

					<?php if ( $on_sale && $remaining > 0 ) : ?>
						<?php if ( is_user_logged_in() ) :
							$current_user   = wp_get_current_user();
							$max_selectable = min( $max_pp, $remaining );
							wp_enqueue_style( 'ddcwwfcsc-ticket-front' );
							wp_enqueue_script(
								'ddcwwfcsc-ticket-form',
								DDCWWFCSC_PLUGIN_URL . 'assets/js/ticket-form.js',
								array(),
								defined( 'DDCWWFCSC_VERSION' ) ? DDCWWFCSC_VERSION : '1.0.0',
								true
							);
							wp_localize_script( 'ddcwwfcsc-ticket-form', 'ddcwwfcsc', array(
								'ajax_url' => admin_url( 'admin-ajax.php' ),
								'nonce'    => wp_create_nonce( 'ddcwwfcsc_ticket_request' ),
							) );
						?>
							<div class="ddcwwfcsc-fixture" data-fixture-id="<?php echo esc_attr( $post_id ); ?>">
								<form class="ddcwwfcsc-ticket-form"
									  data-fixture-id="<?php echo esc_attr( $post_id ); ?>"
									  data-max-per-person="<?php echo esc_attr( $max_pp ); ?>"
									  <?php if ( $unit_price ) : ?>data-price="<?php echo esc_attr( $unit_price ); ?>"<?php endif; ?>>

									<input type="hidden" name="name" value="<?php echo esc_attr( $current_user->display_name ); ?>">
									<input type="hidden" name="email" value="<?php echo esc_attr( $current_user->user_email ); ?>">

									<div class="ddcwwfcsc-ticket-meta">
										<span class="ddcwwfcsc-remaining" data-fixture-id="<?php echo esc_attr( $post_id ); ?>">
											<?php printf( esc_html__( '%d tickets available', 'ddcwwfcsc' ), $remaining ); ?>
										</span>
										<?php if ( $max_pp ) : ?>
											<span class="ddcwwfcsc-max-per"><?php printf( esc_html__( 'Max %d per member', 'ddcwwfcsc' ), $max_pp ); ?></span>
										<?php endif; ?>
										<?php if ( $unit_price ) : ?>
											<span class="ddcwwfcsc-price-per">&pound;<?php echo esc_html( number_format( $unit_price, 2 ) ); ?> <?php esc_html_e( 'per ticket', 'ddcwwfcsc' ); ?></span>
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
							</div>

						<?php else : ?>
							<div class="fixture-not-on-sale">
								<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/></svg>
								<h2><?php esc_html_e( 'Tickets On Sale', 'ddcwwfcsc-theme' ); ?></h2>
								<p><?php esc_html_e( 'Tickets are available for this fixture.', 'ddcwwfcsc-theme' ); ?></p>
								<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="btn btn--primary"><?php esc_html_e( 'Log in to Request Tickets', 'ddcwwfcsc-theme' ); ?></a>
							</div>
						<?php endif; ?>

					<?php elseif ( $on_sale && $remaining <= 0 ) : ?>
						<div class="fixture-not-on-sale">
							<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/></svg>
							<h2><?php esc_html_e( 'Sold Out', 'ddcwwfcsc-theme' ); ?></h2>
							<p><?php esc_html_e( 'Sorry, all tickets for this fixture have been allocated.', 'ddcwwfcsc-theme' ); ?></p>
						</div>

					<?php else : ?>
						<div class="fixture-not-on-sale">
							<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/></svg>
							<h2><?php esc_html_e( 'Tickets Not Yet On Sale', 'ddcwwfcsc-theme' ); ?></h2>
							<p><?php esc_html_e( 'Tickets for this fixture are not yet available. Check back closer to the match.', 'ddcwwfcsc-theme' ); ?></p>
						</div>
					<?php endif; ?>

				<?php elseif ( ! $is_home && $opponent && class_exists( 'DDCWWFCSC_Beerwolf_CPT' ) ) : ?>
					<?php $beerwolf_post = DDCWWFCSC_Beerwolf_CPT::get_beerwolf_for_opponent( $opponent['term_id'] ); ?>
					<?php if ( $beerwolf_post ) : ?>
						<div class="fixture-cta">
							<a href="<?php echo esc_url( get_permalink( $beerwolf_post->ID ) ); ?>" class="btn btn--primary"><?php esc_html_e( 'View Pub Guide', 'ddcwwfcsc-theme' ); ?></a>
						</div>
					<?php else : ?>
						<div class="fixture-not-on-sale">
							<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
							<h2><?php esc_html_e( 'Pub Guide Coming Soon', 'ddcwwfcsc-theme' ); ?></h2>
							<p><?php printf( esc_html__( "We haven't added a pub guide for %s yet. Check back closer to the match.", 'ddcwwfcsc-theme' ), esc_html( $opponent_name ) ); ?></p>
						</div>
					<?php endif; ?>
				<?php endif; ?>

				<?php
				if ( class_exists( 'DDCWWFCSC_MOTM_Front' ) ) {
					echo DDCWWFCSC_MOTM_Front::render_voting_section( $post_id );
				}
				?>

			</article>

			<?php // ── Sidebar: upcoming fixtures ─────────────────────────────── ?>
			<aside class="fixture-single-sidebar">
				<h2 class="section-heading"><?php esc_html_e( 'Upcoming Fixtures', 'ddcwwfcsc-theme' ); ?></h2>
				<?php
				$now           = current_time( 'Y-m-d\TH:i' );
				$sidebar_query = new WP_Query( array(
					'post_type'      => 'ddcwwfcsc_fixture',
					'posts_per_page' => 5,
					'post_status'    => 'publish',
					'post__not_in'   => array( $post_id ),
					'meta_key'       => '_ddcwwfcsc_match_date',
					'orderby'        => 'meta_value',
					'order'          => 'ASC',
					'meta_query'     => array( array(
						'key'     => '_ddcwwfcsc_match_date',
						'value'   => $now,
						'compare' => '>=',
					) ),
				) );
				?>
				<?php if ( $sidebar_query->have_posts() ) : ?>
					<ul class="fixture-sidebar-list">
						<?php while ( $sidebar_query->have_posts() ) : $sidebar_query->the_post();
							$sf_id       = get_the_ID();
							$sf_opponent = class_exists( 'DDCWWFCSC_Fixture_CPT' ) ? DDCWWFCSC_Fixture_CPT::get_opponent( $sf_id ) : null;
							$sf_venue    = get_post_meta( $sf_id, '_ddcwwfcsc_venue', true );
							$sf_date_raw = get_post_meta( $sf_id, '_ddcwwfcsc_match_date', true );
							$sf_date     = $sf_date_raw ? date_i18n( 'j M, H:i', strtotime( $sf_date_raw ) ) : '';
							$sf_is_home  = 'home' === $sf_venue;
							$sf_name     = $sf_opponent ? $sf_opponent['name'] : get_the_title();
						?>
							<li class="fixture-sidebar-list__item">
								<a href="<?php the_permalink(); ?>" class="fixture-sidebar-list__link">
									<span class="fixture-sidebar-list__teams">
										<?php if ( $sf_is_home ) : ?>
											<span class="fixture-sidebar-list__team">Wolves</span>
											<span class="fixture-sidebar-list__sep">v</span>
											<span class="fixture-sidebar-list__team"><?php echo esc_html( $sf_name ); ?></span>
										<?php else : ?>
											<span class="fixture-sidebar-list__team"><?php echo esc_html( $sf_name ); ?></span>
											<span class="fixture-sidebar-list__sep">v</span>
											<span class="fixture-sidebar-list__team">Wolves</span>
										<?php endif; ?>
									</span>
									<?php if ( $sf_date ) : ?>
										<span class="fixture-sidebar-list__date"><?php echo esc_html( $sf_date ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endwhile; wp_reset_postdata(); ?>
					</ul>
				<?php else : ?>
					<p class="fixture-sidebar-list__empty"><?php esc_html_e( 'No upcoming fixtures scheduled.', 'ddcwwfcsc-theme' ); ?></p>
				<?php endif; ?>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'ddcwwfcsc_fixture' ) ); ?>" class="btn btn--ghost btn--sm"><?php esc_html_e( 'View all fixtures', 'ddcwwfcsc-theme' ); ?></a>
			</aside>

		</div>
	</div>
</main>

<?php
get_footer();
