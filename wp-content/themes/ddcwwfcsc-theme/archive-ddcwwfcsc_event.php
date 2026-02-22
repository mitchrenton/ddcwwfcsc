<?php
/**
 * Event archive — two sections: upcoming + past.
 * Bypasses the main loop with custom WP_Query calls.
 *
 * @package DDCWWFCSC_Theme
 */

get_header();

$today = current_time( 'Y-m-d' );

// Upcoming events.
$upcoming = new WP_Query( array(
	'post_type'      => 'ddcwwfcsc_event',
	'posts_per_page' => 20,
	'meta_key'       => '_ddcwwfcsc_event_date',
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'meta_query'     => array( array(
		'key'     => '_ddcwwfcsc_event_date',
		'value'   => $today,
		'compare' => '>=',
		'type'    => 'DATE',
	) ),
) );

// Past events.
$past = new WP_Query( array(
	'post_type'      => 'ddcwwfcsc_event',
	'posts_per_page' => 20,
	'meta_key'       => '_ddcwwfcsc_event_date',
	'orderby'        => 'meta_value',
	'order'          => 'DESC',
	'meta_query'     => array( array(
		'key'     => '_ddcwwfcsc_event_date',
		'value'   => $today,
		'compare' => '<',
		'type'    => 'DATE',
	) ),
) );
?>

<main class="site-main" role="main">
	<div class="container">
		<header class="page-header">
			<h1 class="page-title"><?php esc_html_e( 'Events', 'ddcwwfcsc-theme' ); ?></h1>
		</header>

		<?php if ( $upcoming->have_posts() ) : ?>
			<section class="event-archive-section">
				<h2 class="section-heading"><?php esc_html_e( 'Upcoming', 'ddcwwfcsc-theme' ); ?></h2>
				<div class="grid grid--2">
					<?php while ( $upcoming->have_posts() ) : $upcoming->the_post(); ?>
						<?php get_template_part( 'template-parts/content/content', 'event' ); ?>
					<?php endwhile; ?>
				</div>
			</section>
		<?php elseif ( $past->have_posts() ) : ?>
			<section class="event-archive-section">
				<h2 class="section-heading"><?php esc_html_e( 'Upcoming', 'ddcwwfcsc-theme' ); ?></h2>
				<div class="event-empty-state">
					<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
					<p><?php esc_html_e( 'No events currently scheduled — check back soon.', 'ddcwwfcsc-theme' ); ?></p>
				</div>
			</section>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>

		<?php if ( $past->have_posts() ) : ?>
			<section class="event-archive-section">
				<h2 class="section-heading"><?php esc_html_e( 'Past', 'ddcwwfcsc-theme' ); ?></h2>
				<div class="grid grid--2">
					<?php while ( $past->have_posts() ) : $past->the_post(); ?>
						<?php get_template_part( 'template-parts/content/content', 'event' ); ?>
					<?php endwhile; ?>
				</div>
			</section>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>

		<?php if ( ! $upcoming->have_posts() && ! $past->have_posts() ) : ?>
			<div class="event-empty-state event-empty-state--full">
				<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
				<h2><?php esc_html_e( 'No events yet', 'ddcwwfcsc-theme' ); ?></h2>
				<p><?php esc_html_e( 'Nothing planned just yet — check back soon for upcoming events.', 'ddcwwfcsc-theme' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
