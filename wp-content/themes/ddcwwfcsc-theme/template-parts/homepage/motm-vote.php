<?php
/**
 * Homepage MOTM callout — shown to logged-in users with an open unvoted MOTM.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	return;
}

if ( ! class_exists( 'DDCWWFCSC_MOTM_Front' ) ) {
	return;
}

$fixture_id = DDCWWFCSC_MOTM_Front::get_open_vote_fixture();

if ( ! $fixture_id ) {
	return;
}

$user_vote = DDCWWFCSC_MOTM_Votes::get_user_vote( $fixture_id, get_current_user_id() );

if ( $user_vote ) {
	return;
}

$opponent      = class_exists( 'DDCWWFCSC_Fixture_CPT' ) ? DDCWWFCSC_Fixture_CPT::get_opponent( $fixture_id ) : null;
$opponent_name = $opponent ? $opponent['name'] : get_the_title( $fixture_id );
$fixture_url   = get_permalink( $fixture_id );
?>
<section class="homepage-section homepage-section--motm">
	<a href="<?php echo esc_url( $fixture_url ); ?>" class="motm-callout">
		<div class="motm-callout__body">
			<p class="motm-callout__label"><?php esc_html_e( 'Man of the Match', 'ddcwwfcsc-theme' ); ?></p>
			<p class="motm-callout__match">
				<?php
				printf(
					/* translators: %s: opponent name */
					esc_html__( 'Wolves v %s — who gets your vote?', 'ddcwwfcsc-theme' ),
					esc_html( $opponent_name )
				);
				?>
			</p>
		</div>
		<span class="motm-callout__cta"><?php esc_html_e( 'Vote now', 'ddcwwfcsc-theme' ); ?></span>
	</a>
</section>
