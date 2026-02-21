<?php
/**
 * Front page template — curated homepage.
 *
 * @package DDCWWFCSC_Theme
 */

get_header();

get_template_part( 'template-parts/homepage/hero' );
?>

<main class="site-main" role="main">
	<div class="container">
		<?php get_template_part( 'template-parts/homepage/fixtures-preview' ); ?>
		<?php get_template_part( 'template-parts/homepage/events-preview' ); ?>
		<?php get_template_part( 'template-parts/homepage/honorary-preview' ); ?>

		<?php
		// If a static page is set, render its content.
		if ( have_posts() ) :
			while ( have_posts() ) : the_post();
				$content = get_the_content();
				if ( trim( $content ) ) :
					?>
					<section class="homepage-section homepage-section--content">
						<div class="entry-content">
							<?php the_content(); ?>
						</div>
					</section>
					<?php
				endif;
			endwhile;
		endif;
		?>
	</div>
</main>

<?php
get_footer();
