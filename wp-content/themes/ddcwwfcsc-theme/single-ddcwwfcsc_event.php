<?php
/**
 * Single event template.
 * The plugin's the_content filter prepends the event details card.
 * The signup form is rendered in a sticky sidebar.
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
			?>
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
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'hentry' ); ?>>
					<div class="entry-content">
						<?php the_content(); ?>
					</div>
				</article>

				<aside class="event-sidebar">
					<?php echo DDCWWFCSC_Event_Front::render_signup_form( get_the_ID() ); ?>
				</aside>
			</div>
		<?php endwhile; ?>
	</div>
</main>

<?php
get_footer();
