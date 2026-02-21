<?php
/**
 * The search results template.
 *
 * @package DDCWWFCSC_Theme
 */

get_header();
?>

<main class="site-main" role="main">
	<div class="container">
		<header class="page-header">
			<h1 class="page-title">
				<?php printf( esc_html__( 'Search results for: %s', 'ddcwwfcsc-theme' ), '<span>' . get_search_query() . '</span>' ); ?>
			</h1>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="grid grid--2">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content/content', 'post' );
				endwhile;
				?>
			</div>
			<?php get_template_part( 'template-parts/global/pagination' ); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content/content', 'none' ); ?>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
