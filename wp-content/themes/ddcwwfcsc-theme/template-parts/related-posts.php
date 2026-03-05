<?php
/**
 * Template part: related posts strip shown after comments on single CPT views.
 *
 * Expected $args:
 *   post_type      (string) CPT slug to query.
 *   template_slug  (string) Suffix for content-{slug} template part.
 *   exclude        (int)    Post ID to exclude (the current post).
 *   heading        (string) Section heading text.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

$post_type     = $args['post_type']     ?? '';
$template_slug = $args['template_slug'] ?? '';
$exclude       = (int) ( $args['exclude'] ?? 0 );
$heading       = $args['heading']       ?? __( 'More', 'ddcwwfcsc-theme' );

if ( ! $post_type || ! $template_slug ) {
	return;
}

$related = new WP_Query( array(
	'post_type'      => $post_type,
	'posts_per_page' => 3,
	'post__not_in'   => array( $exclude ),
	'orderby'        => 'date',
	'order'          => 'DESC',
	'no_found_rows'  => true,
) );

if ( ! $related->have_posts() ) {
	return;
}
?>
<section class="related-posts">
	<h2 class="related-posts__heading"><?php echo esc_html( $heading ); ?></h2>
	<div class="related-posts__grid">
		<?php
		while ( $related->have_posts() ) :
			$related->the_post();
			get_template_part( 'template-parts/content/content', $template_slug );
		endwhile;
		wp_reset_postdata();
		?>
	</div>
</section>
