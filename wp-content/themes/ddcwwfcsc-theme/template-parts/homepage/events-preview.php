<?php
/**
 * Homepage events preview — shows upcoming events.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! post_type_exists( 'ddcwwfcsc_event' ) ) {
	return;
}

$events = new WP_Query( array(
	'post_type'      => 'ddcwwfcsc_event',
	'posts_per_page' => 3,
	'meta_key'       => '_ddcwwfcsc_event_date',
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'meta_query'     => array( array(
		'key'     => '_ddcwwfcsc_event_date',
		'value'   => current_time( 'Y-m-d' ),
		'compare' => '>=',
		'type'    => 'DATE',
	) ),
) );

if ( ! $events->have_posts() ) {
	return;
}
?>
<section class="homepage-section homepage-section--events">
	<div class="homepage-section__header">
		<h2 class="section-heading"><?php esc_html_e( 'Upcoming Events', 'ddcwwfcsc-theme' ); ?></h2>
		<a href="<?php echo esc_url( get_post_type_archive_link( 'ddcwwfcsc_event' ) ); ?>" class="homepage-section__more"><?php esc_html_e( 'View all events &rarr;', 'ddcwwfcsc-theme' ); ?></a>
	</div>
	<div class="homepage-section__grid">
		<?php while ( $events->have_posts() ) : $events->the_post(); ?>
			<?php get_template_part( 'template-parts/content/content', 'event' ); ?>
		<?php endwhile; ?>
	</div>
</section>
<?php
wp_reset_postdata();
