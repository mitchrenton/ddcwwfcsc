<?php
/**
 * Pagination partial.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

the_posts_pagination( array(
	'mid_size'  => 2,
	'prev_text' => '&laquo;',
	'next_text' => '&raquo;',
) );
