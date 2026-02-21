<?php
/**
 * The header template.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="https://gmpg.org/xfn/11">
<?php ddcwwfcsc_dark_mode_inline_script(); ?>
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'ddcwwfcsc-theme' ); ?></a>

<header id="masthead" class="site-header">
	<div class="container site-header__inner">
		<div class="site-branding">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php endif; ?>
			<div class="site-branding__text">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-title" rel="home">
					<?php bloginfo( 'name' ); ?>
				</a>
				<?php
				$description = get_bloginfo( 'description', 'display' );
				if ( $description ) :
					?>
					<p class="site-description"><?php echo $description; // phpcs:ignore ?></p>
				<?php endif; ?>
			</div>
		</div>

		<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Menu', 'ddcwwfcsc-theme' ); ?>">
			<span class="menu-toggle__bar"></span>
			<span class="menu-toggle__bar"></span>
			<span class="menu-toggle__bar"></span>
		</button>

		<nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Primary', 'ddcwwfcsc-theme' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'menu_id'        => 'primary-menu',
				'container'      => false,
				'fallback_cb'    => false,
			) );
			?>
			<?php ddcwwfcsc_dark_mode_toggle(); ?>

			<?php
			// Member navigation.
			$member_login_url   = add_query_arg( 'ddcwwfcsc_page', 'login', home_url( '/' ) );
			$member_account_url = add_query_arg( 'ddcwwfcsc_page', 'account', home_url( '/' ) );
			?>
			<div class="site-member-nav">
				<?php if ( is_user_logged_in() ) :
					$current_user = wp_get_current_user();
					$name_parts   = array_filter( explode( ' ', trim( $current_user->display_name ) ) );
					$initials     = '';
					foreach ( $name_parts as $part ) {
						$initials .= mb_strtoupper( mb_substr( $part, 0, 1 ) );
					}
					$initials   = mb_substr( $initials, 0, 2 ) ?: '?';
					$logout_url = wp_logout_url( home_url( '/' ) );
				?>
					<details class="site-member-nav__details">
						<summary class="site-member-nav__trigger">
							<span class="site-member-nav__avatar" aria-hidden="true"><?php echo esc_html( $initials ); ?></span>
							<span class="site-member-nav__name"><?php echo esc_html( $current_user->display_name ); ?></span>
							<svg class="site-member-nav__chevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
						</summary>
						<ul class="site-member-nav__dropdown">
							<li><a href="<?php echo esc_url( $member_account_url ); ?>"><?php esc_html_e( 'My Account', 'ddcwwfcsc-theme' ); ?></a></li>
							<li class="site-member-nav__sep"></li>
							<li><a href="<?php echo esc_url( $logout_url ); ?>"><?php esc_html_e( 'Log Out', 'ddcwwfcsc-theme' ); ?></a></li>
						</ul>
					</details>
				<?php else : ?>
					<a href="<?php echo esc_url( add_query_arg( 'ddcwwfcsc_page', 'apply', home_url( '/' ) ) ); ?>" class="site-member-nav__apply">
						<?php esc_html_e( 'Apply', 'ddcwwfcsc-theme' ); ?>
					</a>
					<a href="<?php echo esc_url( $member_login_url ); ?>" class="site-member-nav__login">
						<?php esc_html_e( 'Log In', 'ddcwwfcsc-theme' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</nav>
	</div>
</header>

<div id="primary" class="site-content">
