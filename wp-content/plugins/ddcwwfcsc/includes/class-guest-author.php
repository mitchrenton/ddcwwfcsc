<?php
/**
 * Guest Author: meta box and author name override.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Guest_Author {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
        add_action( 'save_post', array( __CLASS__, 'save_meta_box' ) );
        add_filter( 'the_author', array( __CLASS__, 'filter_author' ) );
    }

    /**
     * Register the Guest Author meta box on posts.
     */
    public static function add_meta_box() {
        add_meta_box(
            'ddcwwfcsc_guest_author',
            __( 'Guest Author', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_meta_box' ),
            'post',
            'side',
            'default'
        );
    }

    /**
     * Render the meta box.
     */
    public static function render_meta_box( $post ) {
        wp_nonce_field( 'ddcwwfcsc_guest_author_meta', 'ddcwwfcsc_guest_author_nonce' );

        $value = get_post_meta( $post->ID, '_ddcwwfcsc_guest_author', true );
        ?>
        <p>
            <label for="ddcwwfcsc_guest_author"><?php esc_html_e( 'Name:', 'ddcwwfcsc' ); ?></label><br>
            <input type="text" id="ddcwwfcsc_guest_author" name="ddcwwfcsc_guest_author" value="<?php echo esc_attr( $value ); ?>" class="regular-text" style="width:100%;">
        </p>
        <p class="description"><?php esc_html_e( 'Leave blank to use the default WordPress author.', 'ddcwwfcsc' ); ?></p>
        <?php
    }

    /**
     * Save meta box data.
     */
    public static function save_meta_box( $post_id ) {
        if ( ! isset( $_POST['ddcwwfcsc_guest_author_nonce'] ) || ! wp_verify_nonce( $_POST['ddcwwfcsc_guest_author_nonce'], 'ddcwwfcsc_guest_author_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( 'post' !== get_post_type( $post_id ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $guest = isset( $_POST['ddcwwfcsc_guest_author'] ) ? sanitize_text_field( $_POST['ddcwwfcsc_guest_author'] ) : '';
        update_post_meta( $post_id, '_ddcwwfcsc_guest_author', $guest );
    }

    /**
     * Override the author display name when a guest author is set.
     */
    public static function filter_author( $author ) {
        $post_id = get_the_ID();

        if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
            return $author;
        }

        $guest = get_post_meta( $post_id, '_ddcwwfcsc_guest_author', true );

        if ( $guest ) {
            return $guest;
        }

        return $author;
    }

}
