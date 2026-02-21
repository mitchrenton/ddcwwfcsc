<?php
/**
 * Homepage hero section.
 *
 * The default image is the Daventry flag photo shipped with the theme.
 * It can be overridden in Appearance → Customize → Homepage Hero.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

$default_image = DDCWWFCSC_THEME_URI . '/assets/img/hero-flag.jpg';
$image         = get_theme_mod( 'ddcwwfcsc_hero_image', '' );
$hero_src      = $image ? $image : $default_image;
$heading       = get_theme_mod( 'ddcwwfcsc_hero_heading', __( 'Welcome to the DDCWWFCSC', 'ddcwwfcsc-theme' ) );
$subheading    = get_theme_mod( 'ddcwwfcsc_hero_subheading', __( 'Daventry Dun Cow Wolverhampton Wanderers FC Supporters Club', 'ddcwwfcsc-theme' ) );
$cta_text      = get_theme_mod( 'ddcwwfcsc_hero_cta_text', __( 'View Fixtures', 'ddcwwfcsc-theme' ) );
$cta_url       = get_theme_mod( 'ddcwwfcsc_hero_cta_url', '' );
$opacity       = get_theme_mod( 'ddcwwfcsc_hero_overlay_opacity', 55 );

$ov_opacity = $opacity / 100;
?>
<section class="hero">
	<div class="hero__media">
		<img
			class="hero__img"
			src="<?php echo esc_url( $hero_src ); ?>"
			alt="<?php echo esc_attr( $heading ); ?>"
			loading="eager"
			fetchpriority="high"
		>
		<div class="hero__tint"></div>
	</div>

	<div class="hero__content">
		<?php if ( $heading ) : ?>
			<h1 class="hero__heading"><?php echo esc_html( $heading ); ?></h1>
		<?php endif; ?>
		<?php if ( $subheading ) : ?>
			<p class="hero__subheading"><?php echo esc_html( $subheading ); ?></p>
		<?php endif; ?>
		<?php if ( $cta_text && $cta_url ) : ?>
			<a href="<?php echo esc_url( $cta_url ); ?>" class="btn btn--primary"><?php echo esc_html( $cta_text ); ?></a>
		<?php endif; ?>
	</div>
</section>
