<?php
/**
 * Fixture archive — two sections: upcoming fixtures + results.
 * Bypasses the main loop with custom WP_Query calls.
 *
 * @package DDCWWFCSC_Theme
 */

get_header();

$now = current_time( 'Y-m-d\TH:i' );

// Get all seasons for the dropdown.
$seasons = get_terms( array(
	'taxonomy'   => 'ddcwwfcsc_season',
	'hide_empty' => true,
	'orderby'    => 'name',
	'order'      => 'DESC',
) );

$current_season = isset( $_GET['season'] ) ? sanitize_text_field( $_GET['season'] ) : '';

// If no season param, default to the latest.
if ( ! $current_season && ! is_wp_error( $seasons ) && ! empty( $seasons ) ) {
	$current_season = $seasons[0]->slug;
}

// Build shared query args.
$base_args = array(
	'post_type'      => 'ddcwwfcsc_fixture',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'meta_key'       => '_ddcwwfcsc_match_date',
	'orderby'        => 'meta_value',
);

// Season tax query.
$tax_query = array();
if ( $current_season && 'all' !== $current_season ) {
	$tax_query[] = array(
		'taxonomy' => 'ddcwwfcsc_season',
		'field'    => 'slug',
		'terms'    => $current_season,
	);
}

// Upcoming fixtures (soonest first).
$upcoming = new WP_Query( array_merge( $base_args, array(
	'order'      => 'ASC',
	'meta_query' => array( array(
		'key'     => '_ddcwwfcsc_match_date',
		'value'   => $now,
		'compare' => '>=',
	) ),
	'tax_query'  => $tax_query,
) ) );

// Past results (most recent first).
$results = new WP_Query( array_merge( $base_args, array(
	'order'      => 'DESC',
	'meta_query' => array( array(
		'key'     => '_ddcwwfcsc_match_date',
		'value'   => $now,
		'compare' => '<',
	) ),
	'tax_query'  => $tax_query,
) ) );

$archive_url = get_post_type_archive_link( 'ddcwwfcsc_fixture' );
?>

<main class="site-main" role="main">
	<div class="container">
		<header class="page-header">
			<h1 class="page-title"><?php esc_html_e( 'Fixtures & Results', 'ddcwwfcsc-theme' ); ?></h1>
		</header>

		<?php if ( ! is_wp_error( $seasons ) && ! empty( $seasons ) ) : ?>
			<form class="fixture-season-filter" method="get" action="<?php echo esc_url( $archive_url ); ?>">
				<label for="season-select" class="screen-reader-text"><?php esc_html_e( 'Season', 'ddcwwfcsc-theme' ); ?></label>
				<select name="season" id="season-select" onchange="this.form.submit()">
					<?php foreach ( $seasons as $season ) : ?>
						<option value="<?php echo esc_attr( $season->slug ); ?>" <?php selected( $current_season, $season->slug ); ?>>
							<?php echo esc_html( $season->name ); ?>
						</option>
					<?php endforeach; ?>
					<option value="all" <?php selected( $current_season, 'all' ); ?>><?php esc_html_e( 'All Seasons', 'ddcwwfcsc-theme' ); ?></option>
				</select>
			</form>
		<?php endif; ?>

		<?php if ( $upcoming->have_posts() ) : ?>
			<section class="fixture-archive-section">
				<h2 class="section-heading"><?php esc_html_e( 'Fixtures', 'ddcwwfcsc-theme' ); ?></h2>
				<ul class="fixture-list">
					<?php while ( $upcoming->have_posts() ) : $upcoming->the_post(); ?>
						<?php get_template_part( 'template-parts/content/content', 'fixture', array( 'context' => 'upcoming' ) ); ?>
					<?php endwhile; ?>
				</ul>
			</section>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>

		<?php if ( $results->have_posts() ) : ?>
			<section class="fixture-archive-section">
				<h2 class="section-heading"><?php esc_html_e( 'Results', 'ddcwwfcsc-theme' ); ?></h2>
				<ul class="fixture-list">
					<?php while ( $results->have_posts() ) : $results->the_post(); ?>
						<?php get_template_part( 'template-parts/content/content', 'fixture', array( 'context' => 'results' ) ); ?>
					<?php endwhile; ?>
				</ul>
			</section>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>

		<?php if ( ! $upcoming->have_posts() && ! $results->have_posts() ) : ?>
			<?php get_template_part( 'template-parts/content/content', 'none' ); ?>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
