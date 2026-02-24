<?php
/**
 * Template part for displaying a fixture in archive lists.
 *
 * @var array $args Optional. 'context' => 'upcoming' | 'results'
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

$post_id     = get_the_ID();
$opponent    = class_exists( 'DDCWWFCSC_Fixture_CPT' ) ? DDCWWFCSC_Fixture_CPT::get_opponent( $post_id ) : null;
$venue       = get_post_meta( $post_id, '_ddcwwfcsc_venue', true );
$is_home     = 'home' === $venue;
$match_date  = get_post_meta( $post_id, '_ddcwwfcsc_match_date', true );
$context     = isset( $args['context'] ) ? $args['context'] : 'upcoming';
$is_upcoming = 'upcoming' === $context;
$wolves_url  = defined( 'DDCWWFCSC_PLUGIN_URL' ) ? DDCWWFCSC_PLUGIN_URL . 'assets/img/clubs/wolves.png' : '';

// Date and time.
$date_label = $match_date ? wp_date( 'l jS F', strtotime( $match_date ) ) : '';
$time_label = $match_date ? wp_date( 'H:i', strtotime( $match_date ) ) : '';

// Competition.
$competition = '';
$comp_terms  = get_the_terms( $post_id, 'ddcwwfcsc_competition' );
if ( $comp_terms && ! is_wp_error( $comp_terms ) ) {
	$competition = $comp_terms[0]->name;
}

// Team names and badges.
$opponent_name  = $opponent ? $opponent['name'] : get_the_title();
$opponent_badge = ( $opponent && ! empty( $opponent['badge_url'] ) ) ? $opponent['badge_url'] : '';

if ( $is_home ) {
	$left_name   = 'Wolves';
	$left_badge  = $wolves_url;
	$right_name  = $opponent_name;
	$right_badge = $opponent_badge;
} else {
	$left_name   = $opponent_name;
	$left_badge  = $opponent_badge;
	$right_name  = 'Wolves';
	$right_badge = $wolves_url;
}

// Scores (for results).
$home_score_raw = get_post_meta( $post_id, '_ddcwwfcsc_home_score', true );
$away_score_raw = get_post_meta( $post_id, '_ddcwwfcsc_away_score', true );
$has_score      = '' !== $home_score_raw && '' !== $away_score_raw;
$left_score     = (int) $home_score_raw;
$right_score    = (int) $away_score_raw;

// Action flags.
$show_tickets  = false;
$show_beerwolf = false;
$beerwolf_url  = '';
$show_motm     = false;

if ( $is_upcoming ) {
	if ( $is_home ) {
		$on_sale      = (bool) get_post_meta( $post_id, '_ddcwwfcsc_on_sale', true );
		$remaining    = (int) get_post_meta( $post_id, '_ddcwwfcsc_tickets_remaining', true );
		$show_tickets = $on_sale && $remaining > 0;
	} else {
		if ( $opponent && class_exists( 'DDCWWFCSC_Beerwolf_CPT' ) ) {
			$beerwolf_post = DDCWWFCSC_Beerwolf_CPT::get_beerwolf_for_opponent( $opponent['term_id'] );
			if ( $beerwolf_post ) {
				$beerwolf_url  = get_permalink( $beerwolf_post->ID );
				$show_beerwolf = true;
			}
		}
	}
} else {
	// Results: MOTM voting.
	if ( class_exists( 'DDCWWFCSC_MOTM_Front' ) && DDCWWFCSC_MOTM_Front::is_voting_open( $post_id ) ) {
		$user_vote = is_user_logged_in()
			? DDCWWFCSC_MOTM_Votes::get_user_vote( $post_id, get_current_user_id() )
			: null;
		$show_motm = ! $user_vote;
	}
}
?>
<li class="fixture-list__item">

	<a href="<?php the_permalink(); ?>" class="fixture-list__link">

		<div class="fixture-list__header">
			<?php if ( $date_label ) : ?>
				<p class="fixture-list__date"><?php echo esc_html( $date_label ); ?></p>
			<?php endif; ?>
			<?php if ( $competition ) : ?>
				<p class="fixture-list__competition"><?php echo esc_html( $competition ); ?></p>
			<?php endif; ?>
		</div>

		<div class="fixture-list__matchup">
			<span class="fixture-list__team fixture-list__team--left">
				<span class="fixture-list__team-name"><?php echo esc_html( $left_name ); ?></span>
				<?php if ( $left_badge ) : ?>
					<img class="fixture-list__badge" src="<?php echo esc_url( $left_badge ); ?>" alt="">
				<?php endif; ?>
			</span>

			<?php if ( ! $is_upcoming && $has_score ) : ?>
				<span class="fixture-list__score-wrap">
					<span class="fixture-list__score-val"><?php echo esc_html( $left_score ); ?></span>
					<span class="fixture-list__score-sep">|</span>
					<span class="fixture-list__score-val"><?php echo esc_html( $right_score ); ?></span>
				</span>
			<?php elseif ( $is_upcoming && $time_label ) : ?>
				<span class="fixture-list__time"><?php echo esc_html( $time_label ); ?></span>
			<?php else : ?>
				<span class="fixture-list__vs">v</span>
			<?php endif; ?>

			<span class="fixture-list__team fixture-list__team--right">
				<?php if ( $right_badge ) : ?>
					<img class="fixture-list__badge" src="<?php echo esc_url( $right_badge ); ?>" alt="">
				<?php endif; ?>
				<span class="fixture-list__team-name"><?php echo esc_html( $right_name ); ?></span>
			</span>
		</div>

		<?php if ( ! $is_upcoming && $has_score ) : ?>
			<p class="fixture-list__ft"><?php esc_html_e( 'FT', 'ddcwwfcsc-theme' ); ?></p>
		<?php endif; ?>

	</a>

	<?php if ( $show_tickets || $show_beerwolf || $show_motm ) : ?>
		<div class="fixture-list__labels">
			<?php if ( $show_tickets ) : ?>
				<span class="fixture-list__label fixture-list__label--tickets"><?php esc_html_e( 'Tickets on sale', 'ddcwwfcsc-theme' ); ?></span>
			<?php endif; ?>
			<?php if ( $show_beerwolf ) : ?>
				<span class="fixture-list__label fixture-list__label--beerwolf"><?php esc_html_e( 'Beerwolf available', 'ddcwwfcsc-theme' ); ?></span>
			<?php endif; ?>
			<?php if ( $show_motm ) : ?>
				<span class="fixture-list__label fixture-list__label--motm"><?php esc_html_e( 'MOTM open', 'ddcwwfcsc-theme' ); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>

</li>
