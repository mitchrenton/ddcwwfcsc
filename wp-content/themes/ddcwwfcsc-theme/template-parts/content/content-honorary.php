<?php
/**
 * Template part for displaying an honorary member card in archives.
 * Delegates to the plugin's render_grid_card() if available.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'DDCWWFCSC_Honorary_Front' ) && method_exists( 'DDCWWFCSC_Honorary_Front', 'render_grid_card' ) ) {
	echo DDCWWFCSC_Honorary_Front::render_grid_card( get_the_ID() );
	return;
}

// Fallback if plugin not active.
$data = array(
	'position'    => get_post_meta( get_the_ID(), '_ddcwwfcsc_honorary_position', true ),
	'year_granted' => get_post_meta( get_the_ID(), '_ddcwwfcsc_honorary_year_granted', true ),
);
?>
<article <?php post_class( 'card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="card__image">
			<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'ddcwwfcsc-card' ); ?></a>
		</div>
	<?php endif; ?>
	<div class="card__body">
		<h3 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<?php if ( $data['position'] ) : ?>
			<p class="text-muted text-sm"><?php echo esc_html( $data['position'] ); ?></p>
		<?php endif; ?>
	</div>
</article>
