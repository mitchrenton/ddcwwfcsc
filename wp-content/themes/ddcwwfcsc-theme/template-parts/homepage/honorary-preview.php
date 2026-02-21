<?php
/**
 * Homepage honorary members preview — 3 random members.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! post_type_exists( 'ddcwwfcsc_honorary' ) ) {
	return;
}

$members = new WP_Query( array(
	'post_type'      => 'ddcwwfcsc_honorary',
	'posts_per_page' => 3,
	'post_status'    => 'publish',
	'orderby'        => 'rand',
) );

if ( ! $members->have_posts() ) {
	return;
}

// Enqueue honorary card styles.
wp_enqueue_style(
	'ddcwwfcsc-honorary-front',
	DDCWWFCSC_PLUGIN_URL . 'assets/css/honorary-front.css',
	array(),
	DDCWWFCSC_VERSION
);
?>
<section class="homepage-section homepage-section--honorary">
	<div class="homepage-section__header">
		<h2 class="section-heading"><?php esc_html_e( 'Honorary Members', 'ddcwwfcsc-theme' ); ?></h2>
		<a href="<?php echo esc_url( get_post_type_archive_link( 'ddcwwfcsc_honorary' ) ); ?>" class="homepage-section__more"><?php esc_html_e( 'View all members &rarr;', 'ddcwwfcsc-theme' ); ?></a>
	</div>
	<div class="homepage-section__grid">
		<?php
		while ( $members->have_posts() ) :
			$members->the_post();
			echo DDCWWFCSC_Honorary_Front::render_grid_card( get_the_ID() );
		endwhile;
		?>
	</div>
</section>
<?php
wp_reset_postdata();
