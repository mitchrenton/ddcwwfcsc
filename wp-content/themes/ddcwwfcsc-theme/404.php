<?php
/**
 * The 404 template.
 *
 * @package DDCWWFCSC_Theme
 */

get_header();
?>

<main class="site-main" role="main">
	<div class="container">
		<section class="error-404">
			<h1 class="page-title"><?php esc_html_e( '404', 'ddcwwfcsc-theme' ); ?></h1>
			<p><?php esc_html_e( 'The page you were looking for doesn&rsquo;t exist. It may have been moved or removed.', 'ddcwwfcsc-theme' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--primary"><?php esc_html_e( 'Back to Home', 'ddcwwfcsc-theme' ); ?></a>

			<div style="margin-top:var(--space-2xl); text-align:left; max-width:480px; margin-left:auto; margin-right:auto;">
				<?php get_search_form(); ?>
			</div>
		</section>
	</div>
</main>

<?php
get_footer();
