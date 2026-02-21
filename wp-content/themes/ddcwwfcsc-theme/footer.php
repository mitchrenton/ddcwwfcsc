<?php
/**
 * The footer template.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;
?>
</div><!-- #primary -->

<footer id="colophon" class="site-footer">
	<div class="container site-footer__inner">
		<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
			<div class="site-footer__widgets">
				<?php dynamic_sidebar( 'footer-1' ); ?>
			</div>
		<?php endif; ?>

		<?php
		wp_nav_menu( array(
			'theme_location' => 'footer',
			'menu_id'        => 'footer-menu',
			'container'      => false,
			'fallback_cb'    => false,
			'depth'          => 1,
		) );
		?>

		<div class="site-footer__copy">
			<p>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'ddcwwfcsc-theme' ); ?></p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
