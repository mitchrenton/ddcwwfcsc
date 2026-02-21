<?php
/**
 * Honorary Member admin: meta boxes, columns, classic editor.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Honorary_Admin {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_ddcwwfcsc_honorary', array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
        add_filter( 'manage_ddcwwfcsc_honorary_posts_columns', array( __CLASS__, 'add_columns' ) );
        add_action( 'manage_ddcwwfcsc_honorary_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );
        add_filter( 'manage_edit-ddcwwfcsc_honorary_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
        add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'disable_block_editor' ), 10, 2 );
    }

    /**
     * Force classic editor for honorary member posts.
     */
    public static function disable_block_editor( $use, $post_type ) {
        if ( 'ddcwwfcsc_honorary' === $post_type ) {
            return false;
        }
        return $use;
    }

    /**
     * Register meta boxes.
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'ddcwwfcsc_honorary_details',
            __( 'Honorary Member Details', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_details_box' ),
            'ddcwwfcsc_honorary',
            'normal',
            'high'
        );
    }

    /**
     * Render the Honorary Member Details meta box.
     */
    public static function render_details_box( $post ) {
        wp_nonce_field( 'ddcwwfcsc_honorary_meta', 'ddcwwfcsc_honorary_nonce' );

        $position       = get_post_meta( $post->ID, '_ddcwwfcsc_honorary_position', true );
        $years_at_wolves = get_post_meta( $post->ID, '_ddcwwfcsc_honorary_years_at_wolves', true );
        $appearances    = get_post_meta( $post->ID, '_ddcwwfcsc_honorary_appearances', true );
        $year_granted    = get_post_meta( $post->ID, '_ddcwwfcsc_honorary_year_granted', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="ddcwwfcsc_honorary_position"><?php esc_html_e( 'Position', 'ddcwwfcsc' ); ?></label></th>
                <td><input type="text" id="ddcwwfcsc_honorary_position" name="ddcwwfcsc_honorary_position" value="<?php echo esc_attr( $position ); ?>" class="regular-text" placeholder="e.g. Striker, Goalkeeper"></td>
            </tr>
            <tr>
                <th><label for="ddcwwfcsc_honorary_years_at_wolves"><?php esc_html_e( 'Years at Wolves', 'ddcwwfcsc' ); ?></label></th>
                <td><input type="text" id="ddcwwfcsc_honorary_years_at_wolves" name="ddcwwfcsc_honorary_years_at_wolves" value="<?php echo esc_attr( $years_at_wolves ); ?>" class="regular-text" placeholder="e.g. 1986–1997"></td>
            </tr>
            <tr>
                <th><label for="ddcwwfcsc_honorary_appearances"><?php esc_html_e( 'Appearances', 'ddcwwfcsc' ); ?></label></th>
                <td><input type="number" id="ddcwwfcsc_honorary_appearances" name="ddcwwfcsc_honorary_appearances" value="<?php echo esc_attr( $appearances ); ?>" class="regular-text" placeholder="e.g. 412" min="0"></td>
            </tr>
            <tr>
                <th><label for="ddcwwfcsc_honorary_year_granted"><?php esc_html_e( 'Year Granted', 'ddcwwfcsc' ); ?></label></th>
                <td><input type="text" id="ddcwwfcsc_honorary_year_granted" name="ddcwwfcsc_honorary_year_granted" value="<?php echo esc_attr( $year_granted ); ?>" class="regular-text" placeholder="e.g. 2024"></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save meta box data.
     */
    public static function save_meta_boxes( $post_id, $post ) {
        if ( ! isset( $_POST['ddcwwfcsc_honorary_nonce'] ) || ! wp_verify_nonce( $_POST['ddcwwfcsc_honorary_nonce'], 'ddcwwfcsc_honorary_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = array(
            'ddcwwfcsc_honorary_position'       => '_ddcwwfcsc_honorary_position',
            'ddcwwfcsc_honorary_years_at_wolves' => '_ddcwwfcsc_honorary_years_at_wolves',
            'ddcwwfcsc_honorary_appearances'     => '_ddcwwfcsc_honorary_appearances',
            'ddcwwfcsc_honorary_year_granted'    => '_ddcwwfcsc_honorary_year_granted',
        );

        foreach ( $fields as $form_key => $meta_key ) {
            if ( isset( $_POST[ $form_key ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $form_key ] ) );
            }
        }
    }

    /**
     * Add custom columns to the honorary members list table.
     */
    public static function add_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $label ) {
            if ( 'title' === $key ) {
                $new_columns['photo'] = __( 'Photo', 'ddcwwfcsc' );
            }
            $new_columns[ $key ] = $label;
            if ( 'title' === $key ) {
                $new_columns['position']       = __( 'Position', 'ddcwwfcsc' );
                $new_columns['years_at_wolves'] = __( 'Years at Wolves', 'ddcwwfcsc' );
                $new_columns['appearances']    = __( 'Appearances', 'ddcwwfcsc' );
                $new_columns['year_granted']    = __( 'Year Granted', 'ddcwwfcsc' );
            }
        }
        return $new_columns;
    }

    /**
     * Render custom column values.
     */
    public static function render_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'photo':
                $thumb = get_the_post_thumbnail( $post_id, array( 50, 50 ) );
                echo $thumb ? $thumb : '—';
                break;

            case 'position':
                $val = get_post_meta( $post_id, '_ddcwwfcsc_honorary_position', true );
                echo $val ? esc_html( $val ) : '—';
                break;

            case 'years_at_wolves':
                $val = get_post_meta( $post_id, '_ddcwwfcsc_honorary_years_at_wolves', true );
                echo $val ? esc_html( $val ) : '—';
                break;

            case 'appearances':
                $val = get_post_meta( $post_id, '_ddcwwfcsc_honorary_appearances', true );
                echo $val ? esc_html( $val ) : '—';
                break;

            case 'year_granted':
                $val = get_post_meta( $post_id, '_ddcwwfcsc_honorary_year_granted', true );
                echo $val ? esc_html( $val ) : '—';
                break;
        }
    }

    /**
     * Define sortable columns.
     */
    public static function sortable_columns( $columns ) {
        $columns['year_granted'] = 'year_granted';
        return $columns;
    }
}
