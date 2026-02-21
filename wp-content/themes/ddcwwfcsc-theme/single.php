<?php
/**
 * The template for displaying single posts.
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

			$guest_author = get_post_meta( get_the_ID(), '_ddcwwfcsc_guest_author', true );
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'hentry hentry--single-post' ); ?>>
				<header class="entry-header entry-header--post">
					<?php if ( $guest_author ) : ?>
						<div class="entry-author-avatar entry-author-avatar--placeholder">
							<span class="dashicons dashicons-admin-users"></span>
						</div>
					<?php else : ?>
						<div class="entry-author-avatar">
							<?php echo get_avatar( get_the_author_meta( 'ID' ), 80 ); ?>
						</div>
					<?php endif; ?>

					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

					<div class="entry-meta">
						<?php echo esc_html( get_the_author() ); ?> &middot; <?php echo esc_html( get_the_date() ); ?>
					</div>

					<hr class="entry-divider">
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<div class="post-thumbnail">
						<?php the_post_thumbnail( 'large' ); ?>
					</div>
				<?php endif; ?>

				<div class="entry-content">
					<?php the_content(); ?>
				</div>

				<footer class="entry-footer">
					<?php ddcwwfcsc_entry_footer(); ?>
				</footer>
			</article>

			<?php
			the_post_navigation( array(
				'prev_text' => '<span class="nav-subtitle">' . esc_html__( 'Previous', 'ddcwwfcsc-theme' ) . '</span><span class="nav-title">%title</span>',
				'next_text' => '<span class="nav-subtitle">' . esc_html__( 'Next', 'ddcwwfcsc-theme' ) . '</span><span class="nav-title">%title</span>',
			) );
			?>
		<?php endwhile; ?>
	</div>
</main>

<?php
get_footer();
