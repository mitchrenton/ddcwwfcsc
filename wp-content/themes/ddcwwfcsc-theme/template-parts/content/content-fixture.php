<?php
/**
 * Template part for displaying a fixture in archive lists.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

$post_id    = get_the_ID();
$opponent   = class_exists( 'DDCWWFCSC_Fixture_CPT' ) ? DDCWWFCSC_Fixture_CPT::get_opponent( $post_id ) : null;
$date       = ddcwwfcsc_fixture_date( $post_id );
$venue      = get_post_meta( $post_id, '_ddcwwfcsc_venue', true );
$is_home    = 'home' === $venue;
$wolves_url = defined( 'DDCWWFCSC_PLUGIN_URL' ) ? DDCWWFCSC_PLUGIN_URL . 'assets/img/clubs/wolves.png' : '';

// Competition.
$competition = '';
$comp_terms  = get_the_terms( $post_id, 'ddcwwfcsc_competition' );
if ( $comp_terms && ! is_wp_error( $comp_terms ) ) {
	$competition = $comp_terms[0]->name;
}

// Scores.
$home_score = (int) get_post_meta( $post_id, '_ddcwwfcsc_home_score', true );
$away_score = (int) get_post_meta( $post_id, '_ddcwwfcsc_away_score', true );
$has_score  = $home_score >= 0 && $away_score >= 0;

// Team names.
$opponent_name  = $opponent ? $opponent['name'] : get_the_title();
$opponent_badge = ( $opponent && ! empty( $opponent['badge_url'] ) ) ? $opponent['badge_url'] : '';

// Home team is on the left, away team on the right.
if ( $is_home ) {
	$left_name   = 'Wolves';
	$left_badge  = $wolves_url;
	$left_score  = $home_score;
	$right_name  = $opponent_name;
	$right_badge = $opponent_badge;
	$right_score = $away_score;
} else {
	$left_name   = $opponent_name;
	$left_badge  = $opponent_badge;
	$left_score  = $home_score;
	$right_name  = 'Wolves';
	$right_badge = $wolves_url;
	$right_score = $away_score;
}
?>
<li class="fixture-list__item">
	<a href="<?php the_permalink(); ?>" class="fixture-list__matchup">
		<span class="fixture-list__team fixture-list__team--left">
			<?php if ( $left_badge ) : ?>
				<img class="fixture-list__badge" src="<?php echo esc_url( $left_badge ); ?>" alt="<?php echo esc_attr( $left_name ); ?>">
			<?php endif; ?>
			<span class="fixture-list__team-name"><?php echo esc_html( $left_name ); ?></span>
		</span>

		<?php if ( $has_score ) : ?>
			<span class="fixture-list__score"><?php echo esc_html( $left_score . ' - ' . $right_score ); ?></span>
		<?php else : ?>
			<span class="fixture-list__vs">v</span>
		<?php endif; ?>

		<span class="fixture-list__team fixture-list__team--right">
			<span class="fixture-list__team-name"><?php echo esc_html( $right_name ); ?></span>
			<?php if ( $right_badge ) : ?>
				<img class="fixture-list__badge" src="<?php echo esc_url( $right_badge ); ?>" alt="<?php echo esc_attr( $right_name ); ?>">
			<?php endif; ?>
		</span>
	</a>

	<div class="fixture-list__meta">
		<?php if ( $date ) : ?>
			<span class="fixture-list__date"><?php echo esc_html( $date ); ?></span>
		<?php endif; ?>
		<?php if ( $competition ) : ?>
			<span class="fixture-list__competition"><?php echo esc_html( $competition ); ?></span>
		<?php endif; ?>
		<?php ddcwwfcsc_fixture_badges( $post_id ); ?>
	</div>
</li>
