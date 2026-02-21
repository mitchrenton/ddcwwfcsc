<?php
/**
 * Template part for displaying a post card in archives.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="card__image">
			<a href="<?php the_permalink(); ?>">
				<?php the_post_thumbnail( 'ddcwwfcsc-card' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<div class="card__body">
		<h3 class="card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>
		<p class="card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
		<div class="card__meta">
			<?php ddcwwfcsc_entry_meta(); ?>
		</div>
	</div>
</article>
