<?php
/**
 * Single event template.
 * The event details card is rendered directly in the sidebar; the plugin's
 * the_content filter is removed so the body only contains the post content.
 *
 * @package DDCWWFCSC_Theme
 */

get_header();

while ( have_posts() ) :
	the_post();

	// Render the details card explicitly in the sidebar below — prevent the
	// plugin from also prepending it inside the_content().
	remove_filter( 'the_content', array( 'DDCWWFCSC_Event_Front', 'filter_content' ) );
	?>

	<main class="site-main" role="main">
		<div class="container">
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'hentry' ); ?>>

				<header class="entry-header">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
					<?php $event_date = ddcwwfcsc_event_date(); ?>
					<?php if ( $event_date ) : ?>
						<div class="entry-meta">
							<span class="posted-on"><?php echo esc_html( $event_date ); ?></span>
							<?php if ( ddcwwfcsc_is_event_upcoming() ) : ?>
								<span class="badge badge--upcoming"><?php esc_html_e( 'Upcoming', 'ddcwwfcsc-theme' ); ?></span>
							<?php else : ?>
								<span class="badge badge--past"><?php esc_html_e( 'Past', 'ddcwwfcsc-theme' ); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</header>

				<div class="event-layout">
					<div class="event-layout__content entry-content">
						<?php the_content(); ?>
					</div>

					<?php if ( class_exists( 'DDCWWFCSC_Event_Front' ) ) : ?>
						<aside class="event-layout__sidebar">
							<?php echo DDCWWFCSC_Event_Front::render_details( get_the_ID() ); ?>
						</aside>
					<?php endif; ?>
				</div>

			</article>

			<?php if ( comments_open() || get_comments_number() ) : ?>
				<?php comments_template(); ?>
			<?php endif; ?>

			<?php get_template_part( 'template-parts/related-posts', null, array(
				'post_type'     => 'ddcwwfcsc_event',
				'template_slug' => 'event',
				'exclude'       => get_the_ID(),
				'heading'       => __( 'More Events', 'ddcwwfcsc-theme' ),
			) ); ?>
		</div>
	</main>

<?php endwhile;

get_footer();
