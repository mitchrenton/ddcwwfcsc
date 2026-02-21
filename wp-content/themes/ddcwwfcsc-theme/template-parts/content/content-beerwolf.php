<?php
/**
 * Template part for displaying a beerwolf card in archives.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

$post_id  = get_the_ID();
$pubs     = class_exists( 'DDCWWFCSC_Beerwolf_CPT' ) ? DDCWWFCSC_Beerwolf_CPT::get_pubs( $post_id ) : array();
$pub_count = is_array( $pubs ) ? count( $pubs ) : 0;

// Try to get opponent data from taxonomy.
$opponent_data = class_exists( 'DDCWWFCSC_Fixture_CPT' ) ? DDCWWFCSC_Fixture_CPT::get_opponent( $post_id ) : null;
$opponent_name = $opponent_data ? $opponent_data['name'] : '';
$badge_url     = $opponent_data ? $opponent_data['badge_url'] : '';
?>
<article class="beerwolf-archive-card">
	<?php if ( $badge_url ) : ?>
		<div class="beerwolf-archive-card__badge">
			<img src="<?php echo esc_url( $badge_url ); ?>" alt="<?php echo esc_attr( $opponent_name ); ?>">
		</div>
	<?php endif; ?>
	<div class="beerwolf-archive-card__body">
		<h3 class="beerwolf-archive-card__opponent">
			<a href="<?php the_permalink(); ?>">
				<?php echo $opponent_name ? esc_html( $opponent_name ) : get_the_title(); ?>
			</a>
		</h3>
		<?php if ( $pub_count > 0 ) : ?>
			<p class="beerwolf-archive-card__count">
				<?php printf( esc_html( _n( '%d pub', '%d pubs', $pub_count, 'ddcwwfcsc-theme' ) ), $pub_count ); ?>
			</p>
		<?php endif; ?>
	</div>
</article>
