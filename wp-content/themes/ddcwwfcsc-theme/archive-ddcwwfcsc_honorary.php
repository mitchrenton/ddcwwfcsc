<?php
/**
 * Honorary members archive template.
 *
 * @package DDCWWFCSC_Theme
 */

get_header();
?>

<main class="site-main" role="main">
	<div class="container">
		<header class="page-header">
			<h1 class="page-title"><?php esc_html_e( 'Honorary Members', 'ddcwwfcsc-theme' ); ?></h1>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="honorary-grid">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php get_template_part( 'template-parts/content/content', 'honorary' ); ?>
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
