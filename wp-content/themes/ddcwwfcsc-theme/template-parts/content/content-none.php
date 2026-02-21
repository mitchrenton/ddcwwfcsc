<?php
/**
 * Template part for displaying a "no posts found" message.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="no-results not-found">
	<header class="page-header">
		<h1 class="page-title"><?php esc_html_e( 'Nothing found', 'ddcwwfcsc-theme' ); ?></h1>
	</header>

	<div class="entry-content">
		<?php if ( is_search() ) : ?>
			<p><?php esc_html_e( 'No results matched your search. Please try again with different keywords.', 'ddcwwfcsc-theme' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'It seems we can&rsquo;t find what you&rsquo;re looking for.', 'ddcwwfcsc-theme' ); ?></p>
		<?php endif; ?>

		<?php get_search_form(); ?>
	</div>
</section>
