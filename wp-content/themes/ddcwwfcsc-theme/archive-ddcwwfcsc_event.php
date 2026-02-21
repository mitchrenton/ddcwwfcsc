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
			<?php get_template_part( 'template-parts/content/content', 'none' ); ?>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
