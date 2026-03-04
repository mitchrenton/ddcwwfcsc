<?php
/**
 * Custom template tags for the theme.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prints entry meta (date, author).
 */
function ddcwwfcsc_entry_meta() {
	$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time>';
	$time_string = sprintf( $time_string,
		esc_attr( get_the_date( DATE_W3C ) ),
		esc_html( get_the_date() )
	);

	printf(
		'<span class="posted-on">%s</span><span class="byline"> &mdash; %s</span>',
		$time_string,
		esc_html( get_the_author() )
	);
}

/**
 * Prints category and tag links for a post.
 */
function ddcwwfcsc_entry_footer() {
	if ( 'post' !== get_post_type() ) {
		return;
	}

	$categories_list = get_the_category_list( ', ' );
	if ( $categories_list ) {
		printf( '<span class="cat-links">%s %s</span>', esc_html__( 'Filed under:', 'ddcwwfcsc-theme' ), $categories_list );
	}

	$tags_list = get_the_tag_list( '', ', ' );
	if ( $tags_list ) {
		printf( '<span class="tags-links">%s %s</span>', esc_html__( 'Tagged:', 'ddcwwfcsc-theme' ), $tags_list );
	}
}

/**
 * Returns a formatted fixture date string from post meta.
 */
function ddcwwfcsc_fixture_date( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$raw     = get_post_meta( $post_id, '_ddcwwfcsc_match_date', true );
	if ( ! $raw ) {
		return '';
	}
	$ts = strtotime( $raw );
	return $ts ? date_i18n( 'l j F Y, g:i A', $ts ) : esc_html( $raw );
}

/**
 * Returns a formatted event date string from post meta.
 */
function ddcwwfcsc_event_date( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$raw     = get_post_meta( $post_id, '_ddcwwfcsc_event_date', true );
	if ( ! $raw ) {
		return '';
	}
	$ts = strtotime( $raw );
	return $ts ? date_i18n( 'l j F Y', $ts ) : esc_html( $raw );
}

/**
 * Returns true if a fixture match date is in the future.
 */
function ddcwwfcsc_is_fixture_upcoming( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$raw     = get_post_meta( $post_id, '_ddcwwfcsc_match_date', true );
	if ( ! $raw ) {
		return false;
	}
	return strtotime( $raw ) > current_time( 'timestamp' );
}

/**
 * Returns true if an event date is in the future.
 */
function ddcwwfcsc_is_event_upcoming( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$raw     = get_post_meta( $post_id, '_ddcwwfcsc_event_date', true );
	if ( ! $raw ) {
		return false;
	}
	return strtotime( $raw ) > current_time( 'timestamp' );
}

/**
 * Returns "Home" or "Away" for a fixture, or empty string if unset.
 */
function ddcwwfcsc_fixture_venue( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$venue   = get_post_meta( $post_id, '_ddcwwfcsc_venue', true );
	if ( 'home' === $venue ) {
		return __( 'Home', 'ddcwwfcsc-theme' );
	}
	if ( 'away' === $venue ) {
		return __( 'Away', 'ddcwwfcsc-theme' );
	}
	return '';
}

/**
 * Renders a single comment for wp_list_comments().
 *
 * @param WP_Comment $comment Comment object.
 * @param array      $args    wp_list_comments() args.
 * @param int        $depth   Nesting depth.
 */
function ddcwwfcsc_comment( $comment, $args, $depth ) {
	$tag = 'div' === $args['style'] ? 'div' : 'li';
	?>
	<<?php echo esc_attr( $tag ); ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( 'comment-item', $comment ); ?>>
		<article class="comment-body">
			<header class="comment-meta">
				<div class="comment-author">
					<?php echo get_avatar( $comment, $args['avatar_size'], '', '', array( 'class' => 'comment-avatar' ) ); ?>
					<div class="comment-author__info">
						<span class="comment-author__name"><?php comment_author( $comment ); ?></span>
						<time class="comment-author__date" datetime="<?php comment_date( DATE_W3C, $comment ); ?>">
							<?php comment_date( get_option( 'date_format' ), $comment ); ?>
						</time>
					</div>
				</div>
				<?php if ( '0' === $comment->comment_approved ) : ?>
					<p class="comment-awaiting-moderation"><?php esc_html_e( 'Your comment is awaiting moderation.', 'ddcwwfcsc-theme' ); ?></p>
				<?php endif; ?>
			</header>

			<div class="comment-content">
				<?php comment_text( $comment ); ?>
			</div>

			<footer class="comment-footer">
				<?php
				comment_reply_link( array_merge( $args, array(
					'add_below' => 'comment',
					'depth'     => $depth,
					'max_depth' => $args['max_depth'],
					'before'    => '<span class="comment-reply">',
					'after'     => '</span>',
				) ) );
				?>
			</footer>
		</article>
	<?php
}

/**
 * Prints status badges for a fixture (on-sale/sold-out only — venue and upcoming/past are
 * conveyed by team position and archive section headings respectively).
 */
function ddcwwfcsc_fixture_badges( $post_id = null ) {
	$post_id   = $post_id ?: get_the_ID();
	$upcoming  = ddcwwfcsc_is_fixture_upcoming( $post_id );
	$on_sale   = (bool) get_post_meta( $post_id, '_ddcwwfcsc_on_sale', true );
	$remaining = (int) get_post_meta( $post_id, '_ddcwwfcsc_tickets_remaining', true );

	if ( ! $upcoming ) {
		return;
	}

	if ( $on_sale && $remaining > 0 ) {
		echo '<span class="badge badge--on-sale">' . esc_html__( 'On Sale', 'ddcwwfcsc-theme' ) . '</span>';
	} elseif ( $on_sale && $remaining <= 0 ) {
		echo '<span class="badge badge--sold-out">' . esc_html__( 'Sold Out', 'ddcwwfcsc-theme' ) . '</span>';
	}
}
