<?php
/**
 * Bulletin admin: meta boxes, columns, classic editor.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Bulletin_Admin {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_ddcwwfcsc_bulletin', array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
        add_filter( 'manage_ddcwwfcsc_bulletin_posts_columns', array( __CLASS__, 'add_columns' ) );
        add_action( 'manage_ddcwwfcsc_bulletin_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );
        add_filter( 'manage_edit-ddcwwfcsc_bulletin_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
        add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'disable_block_editor' ), 10, 2 );
        add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
    }

    /**
     * Force classic editor for bulletin posts.
     */
    public static function disable_block_editor( $use, $post_type ) {
        if ( 'ddcwwfcsc_bulletin' === $post_type ) {
            return false;
        }
        return $use;
    }

    /**
     * Change the title placeholder for bulletins.
     */
    public static function title_placeholder( $title, $post ) {
        if ( 'ddcwwfcsc_bulletin' === $post->post_type ) {
            return __( 'Bulletin message...', 'ddcwwfcsc' );
        }
        return $title;
    }

    /**
     * Register meta boxes.
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'ddcwwfcsc_bulletin_details',
            __( 'Expiry Date', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_details_box' ),
            'ddcwwfcsc_bulletin',
            'side',
            'default'
        );
    }

    /**
     * Render the expiry date meta box.
     */
    public static function render_details_box( $post ) {
        wp_nonce_field( 'ddcwwfcsc_bulletin_meta', 'ddcwwfcsc_bulletin_nonce' );

        $expiry = DDCWWFCSC_Bulletin_CPT::get_expiry( $post->ID );
        ?>
        <p>
            <label for="ddcwwfcsc_bulletin_expiry"><?php esc_html_e( 'Expires on:', 'ddcwwfcsc' ); ?></label><br>
            <input type="date" id="ddcwwfcsc_bulletin_expiry" name="ddcwwfcsc_bulletin_expiry" value="<?php echo esc_attr( $expiry ); ?>" style="width:100%;">
        </p>
        <p class="description"><?php esc_html_e( 'Leave blank for no expiry.', 'ddcwwfcsc' ); ?></p>
        <?php
    }

    /**
     * Save meta box data.
     */
    public static function save_meta_boxes( $post_id, $post ) {
        if ( ! isset( $_POST['ddcwwfcsc_bulletin_nonce'] ) || ! wp_verify_nonce( $_POST['ddcwwfcsc_bulletin_nonce'], 'ddcwwfcsc_bulletin_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $expiry = isset( $_POST['ddcwwfcsc_bulletin_expiry'] ) ? sanitize_text_field( $_POST['ddcwwfcsc_bulletin_expiry'] ) : '';
        update_post_meta( $post_id, '_ddcwwfcsc_bulletin_expiry', $expiry );
    }

    /**
     * Add custom columns to the bulletins list table.
     */
    public static function add_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
            if ( 'title' === $key ) {
                $new_columns['bulletin_expiry'] = __( 'Expiry', 'ddcwwfcsc' );
            }
        }
        return $new_columns;
    }

    /**
     * Render custom column values.
     */
    public static function render_columns( $column, $post_id ) {
        if ( 'bulletin_expiry' !== $column ) {
            return;
        }

        $expiry = DDCWWFCSC_Bulletin_CPT::get_expiry( $post_id );

        if ( ! $expiry ) {
            echo '—';
            return;
        }

        $timestamp = strtotime( $expiry );
        $formatted = wp_date( 'j M Y', $timestamp );
        $is_past   = $expiry < current_time( 'Y-m-d' );

        if ( $is_past ) {
            printf( '<span style="color:#d63638;font-weight:600;">%s (Expired)</span>', esc_html( $formatted ) );
        } else {
            echo esc_html( $formatted );
        }
    }

    /**
     * Define sortable columns.
     */
    public static function sortable_columns( $columns ) {
        $columns['bulletin_expiry'] = 'bulletin_expiry';
        return $columns;
    }
}
