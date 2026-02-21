<?php
/**
 * The template for displaying all pages.
 *
 * @package DDCWWFCSC_Theme
 */

get_header();
?>

<main class="site-main" role="main">
	<div class="container">
		<?php
		while ( have_posts() ) :
			the_post();

			$page_id     = get_the_ID();
			$parent_id   = wp_get_post_parent_id( $page_id );
			$top_parent  = $parent_id ? $parent_id : $page_id;

			$child_pages = wp_list_pages( array(
				'child_of'    => $top_parent,
				'title_li'    => '',
				'sort_column' => 'menu_order, post_title',
				'depth'       => 1,
				'echo'        => 0,
			) );

			$has_subnav = (bool) $child_pages;
			?>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="post-thumbnail">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
			<?php endif; ?>

			<div class="<?php echo $has_subnav ? 'page-layout' : ''; ?>">
				<?php if ( $has_subnav ) : ?>
					<aside class="page-sidebar">
						<nav class="page-subnav" aria-label="<?php esc_attr_e( 'Sub-pages', 'ddcwwfcsc-theme' ); ?>">
							<ul>
								<li class="page_item<?php echo ( $page_id === $top_parent ) ? ' current_page_item' : ''; ?>">
									<a href="<?php echo esc_url( get_permalink( $top_parent ) ); ?>"><?php echo esc_html( get_the_title( $top_parent ) ); ?></a>
								</li>
								<?php echo $child_pages; ?>
							</ul>
						</nav>
					</aside>
				<?php endif; ?>

				<article id="post-<?php the_ID(); ?>" <?php post_class( 'hentry' ); ?>>
					<header class="entry-header">
						<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
					</header>

					<div class="entry-content">
						<?php the_content(); ?>
					</div>
				</article>
			</div>
			<?php
		endwhile;
		?>
	</div>
</main>

<?php
get_footer();
