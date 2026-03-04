<?php
/**
 * The comments template.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>

<section class="comments-area" id="comments">

	<?php if ( have_comments() ) : ?>
		<h2 class="comments-area__title">
			<?php
			$comment_count = get_comments_number();
			printf(
				/* translators: %s: number of comments */
				esc_html( _nx( '%s Comment', '%s Comments', $comment_count, 'comments title', 'ddcwwfcsc-theme' ) ),
				esc_html( number_format_i18n( $comment_count ) )
			);
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments( array(
				'style'       => 'ol',
				'short_ping'  => true,
				'avatar_size' => 48,
				'callback'    => 'ddcwwfcsc_comment',
			) );
			?>
		</ol>

		<?php the_comments_pagination( array(
			'prev_text' => '&larr; ' . esc_html__( 'Older comments', 'ddcwwfcsc-theme' ),
			'next_text' => esc_html__( 'Newer comments', 'ddcwwfcsc-theme' ) . ' &rarr;',
		) ); ?>

	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
		<p class="comments-area__closed"><?php esc_html_e( 'Comments are closed.', 'ddcwwfcsc-theme' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form( array(
		'title_reply'          => esc_html__( 'Leave a Comment', 'ddcwwfcsc-theme' ),
		'title_reply_before'   => '<h2 class="comments-area__reply-title" id="reply-title">',
		'title_reply_after'    => '</h2>',
		'title_reply_to'       => esc_html__( 'Leave a Reply to %s', 'ddcwwfcsc-theme' ),
		'cancel_reply_link'    => esc_html__( 'Cancel reply', 'ddcwwfcsc-theme' ),
		'label_submit'         => esc_html__( 'Post Comment', 'ddcwwfcsc-theme' ),
		'submit_button'        => '<button name="%1$s" type="submit" id="%2$s" class="%3$s btn btn--primary">%4$s</button>',
		'submit_field'         => '<div class="form-submit">%1$s %2$s</div>',
		'comment_field'        => '<div class="comment-form__field"><label for="comment">' . esc_html__( 'Comment', 'ddcwwfcsc-theme' ) . ' <span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="6" required></textarea></div>',
		'fields'               => array(
			'author' => '<div class="comment-form__field"><label for="author">' . esc_html__( 'Name', 'ddcwwfcsc-theme' ) . ' <span class="required">*</span></label><input id="author" name="author" type="text" value="' . esc_attr( wp_get_current_user()->display_name ) . '" required autocomplete="name"></div>',
			'email'  => '<div class="comment-form__field"><label for="email">' . esc_html__( 'Email', 'ddcwwfcsc-theme' ) . ' <span class="required">*</span></label><input id="email" name="email" type="email" value="' . esc_attr( wp_get_current_user()->user_email ) . '" required autocomplete="email"></div>',
			'cookies' => '',
		),
		'logged_in_as'         => '',
		'must_log_in'          => '<p class="must-log-in">' . sprintf(
			/* translators: %s: login URL */
			wp_kses( __( 'You must be <a href="%s">logged in</a> to post a comment.', 'ddcwwfcsc-theme' ), array( 'a' => array( 'href' => array() ) ) ),
			esc_url( wp_login_url( get_permalink() ) )
		) . '</p>',
	) );
	?>

</section>
