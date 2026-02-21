<?php
/**
 * Single beerwolf template.
 * Map hero (full-width) above content + pub cards.
 *
 * @package DDCWWFCSC_Theme
 */

get_header();

while ( have_posts() ) :
	the_post();

	$map_html = DDCWWFCSC_Beerwolf_Front::render_map_html( get_the_ID() );
	if ( $map_html ) :
	?>
	<section class="beerwolf-hero">
		<?php echo $map_html; ?>
	</section>
	<?php endif; ?>

	<main class="site-main" role="main">
		<div class="container">
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'hentry' ); ?>>
				<header class="entry-header">
					<?php
					$opponent_data = class_exists( 'DDCWWFCSC_Fixture_CPT' ) ? DDCWWFCSC_Fixture_CPT::get_opponent( get_the_ID() ) : null;
					if ( $opponent_data && $opponent_data['badge_url'] ) :
					?>
						<img class="beerwolf-single__badge" src="<?php echo esc_url( $opponent_data['badge_url'] ); ?>" alt="<?php echo esc_attr( $opponent_data['name'] ); ?>">
					<?php endif; ?>
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</header>

				<div class="entry-content">
					<?php the_content(); ?>
				</div>

				<?php echo DDCWWFCSC_Beerwolf_Front::render_pubs_html( get_the_ID() ); ?>
			</article>
		</div>
	</main>

<?php endwhile;

get_footer();
