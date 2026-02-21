<?php
/**
 * Beerwolf archive template.
 *
 * @package DDCWWFCSC_Theme
 */

get_header();
?>

<main class="site-main" role="main">
	<div class="container">
		<header class="page-header">
			<h1 class="page-title"><?php esc_html_e( 'Beerwolf Guides', 'ddcwwfcsc-theme' ); ?></h1>
			<p class="page-description"><?php esc_html_e( 'Pre-match pub guides for every away ground.', 'ddcwwfcsc-theme' ); ?></p>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="beerwolf-archive-grid">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php get_template_part( 'template-parts/content/content', 'beerwolf' ); ?>
				<?php endwhile; ?>
			</div>
			<?php get_template_part( 'template-parts/global/pagination' ); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content/content', 'none' ); ?>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
