<?php
/**
 * Single honorary member template.
 * Full-width hero with featured image, content + sidebar layout.
 *
 * @package DDCWWFCSC_Theme
 */

get_header();

while ( have_posts() ) :
	the_post();

	$member = DDCWWFCSC_Honorary_CPT::get_member_data( get_the_ID() );
	?>

	<section class="hero hero--honorary">
		<div class="hero__media">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'full', array( 'class' => 'hero__img' ) ); ?>
			<?php endif; ?>
		</div>
		<div class="hero__tint"></div>
		<div class="hero__content">
			<h1 class="hero__heading"><?php the_title(); ?></h1>
			<?php if ( $member['position'] ) : ?>
				<p class="hero__subheading"><?php echo esc_html( $member['position'] ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<main class="site-main" role="main">
		<div class="container">
			<div class="honorary-layout">
				<div class="honorary-layout__content entry-content">
					<?php the_content(); ?>
				</div>

				<aside class="honorary-layout__sidebar">
					<div class="honorary-sidebar">
						<h3 class="honorary-sidebar__title"><?php esc_html_e( 'Details', 'ddcwwfcsc' ); ?></h3>
						<dl class="honorary-sidebar__list">
							<?php if ( $member['position'] ) : ?>
								<div class="honorary-sidebar__row">
									<dt><?php esc_html_e( 'Position', 'ddcwwfcsc' ); ?></dt>
									<dd><?php echo esc_html( $member['position'] ); ?></dd>
								</div>
							<?php endif; ?>

							<?php if ( $member['years_at_wolves'] ) : ?>
								<div class="honorary-sidebar__row">
									<dt><?php esc_html_e( 'Years at Wolves', 'ddcwwfcsc' ); ?></dt>
									<dd><?php echo esc_html( $member['years_at_wolves'] ); ?></dd>
								</div>
							<?php endif; ?>

							<?php if ( $member['appearances'] ) : ?>
								<div class="honorary-sidebar__row">
									<dt><?php esc_html_e( 'Appearances', 'ddcwwfcsc' ); ?></dt>
									<dd><?php echo esc_html( $member['appearances'] ); ?></dd>
								</div>
							<?php endif; ?>

							<?php if ( $member['year_granted'] ) : ?>
								<div class="honorary-sidebar__row">
									<dt><?php esc_html_e( 'Honorary Member Since', 'ddcwwfcsc' ); ?></dt>
									<dd><?php echo esc_html( $member['year_granted'] ); ?></dd>
								</div>
							<?php endif; ?>
						</dl>
					</div>
				</aside>
			</div>
		</div>
	</main>

<?php endwhile;

get_footer();
