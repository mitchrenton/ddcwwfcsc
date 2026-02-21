<?php
/**
 * Beerwolf admin: meta boxes, columns, asset enqueueing.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Beerwolf_Admin {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_ddcwwfcsc_beerwolf', array( __CLASS__, 'save_post' ), 10, 2 );
        add_filter( 'manage_ddcwwfcsc_beerwolf_posts_columns', array( __CLASS__, 'add_columns' ) );
        add_action( 'manage_ddcwwfcsc_beerwolf_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'disable_block_editor' ), 10, 2 );
    }

    /**
     * Force classic editor for beerwolf posts.
     */
    public static function disable_block_editor( $use, $post_type ) {
        if ( 'ddcwwfcsc_beerwolf' === $post_type ) {
            return false;
        }
        return $use;
    }

    /**
     * Register meta boxes.
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'ddcwwfcsc_beerwolf_opponent',
            __( 'Opponent', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_opponent_box' ),
            'ddcwwfcsc_beerwolf',
            'side',
            'high'
        );

        add_meta_box(
            'ddcwwfcsc_beerwolf_pubs',
            __( 'Pubs', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_pubs_box' ),
            'ddcwwfcsc_beerwolf',
            'normal',
            'high'
        );
    }

    /**
     * Render the opponent selector meta box.
     */
    public static function render_opponent_box( $post ) {
        wp_nonce_field( 'ddcwwfcsc_beerwolf_meta', 'ddcwwfcsc_beerwolf_nonce' );

        $current_opponent = 0;
        $terms = get_the_terms( $post->ID, 'ddcwwfcsc_opponent' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            $current_opponent = $terms[0]->term_id;
        }

        $opponents = get_terms( array(
            'taxonomy'   => 'ddcwwfcsc_opponent',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );
        ?>
        <select name="ddcwwfcsc_beerwolf_opponent" id="ddcwwfcsc_beerwolf_opponent" style="width:100%;">
            <option value=""><?php esc_html_e( '— Select Opponent —', 'ddcwwfcsc' ); ?></option>
            <?php if ( ! is_wp_error( $opponents ) ) : ?>
                <?php foreach ( $opponents as $opp ) : ?>
                    <option value="<?php echo esc_attr( $opp->term_id ); ?>" <?php selected( $current_opponent, $opp->term_id ); ?>>
                        <?php echo esc_html( $opp->name ); ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <p class="description"><?php esc_html_e( 'Select the away opponent this pub guide is for.', 'ddcwwfcsc' ); ?></p>
        <?php
    }

    /**
     * Render the pubs repeater meta box.
     */
    public static function render_pubs_box( $post ) {
        $pubs = DDCWWFCSC_Beerwolf_CPT::get_pubs( $post->ID );
        ?>
        <div id="ddcwwfcsc-pubs-repeater">
            <?php if ( ! empty( $pubs ) ) : ?>
                <?php foreach ( $pubs as $i => $pub ) : ?>
                    <div class="ddcwwfcsc-pub-fieldset">
                        <div class="ddcwwfcsc-pub-header">
                            <strong class="ddcwwfcsc-pub-number"><?php printf( __( 'Pub #%d', 'ddcwwfcsc' ), $i + 1 ); ?></strong>
                            <span class="ddcwwfcsc-pub-actions">
                                <button type="button" class="button ddcwwfcsc-pub-move-up" title="<?php esc_attr_e( 'Move Up', 'ddcwwfcsc' ); ?>">&uarr;</button>
                                <button type="button" class="button ddcwwfcsc-pub-move-down" title="<?php esc_attr_e( 'Move Down', 'ddcwwfcsc' ); ?>">&darr;</button>
                                <button type="button" class="button ddcwwfcsc-pub-remove" title="<?php esc_attr_e( 'Remove', 'ddcwwfcsc' ); ?>">&times;</button>
                            </span>
                        </div>
                        <table class="form-table">
                            <tr>
                                <th><label><?php esc_html_e( 'Name', 'ddcwwfcsc' ); ?></label></th>
                                <td><input type="text" name="ddcwwfcsc_pubs[<?php echo $i; ?>][name]" value="<?php echo esc_attr( $pub['name'] ); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label><?php esc_html_e( 'Description', 'ddcwwfcsc' ); ?></label></th>
                                <td><textarea name="ddcwwfcsc_pubs[<?php echo $i; ?>][description]" rows="3" class="large-text"><?php echo esc_textarea( $pub['description'] ); ?></textarea></td>
                            </tr>
                            <tr>
                                <th><label><?php esc_html_e( 'Address', 'ddcwwfcsc' ); ?></label></th>
                                <td><input type="text" name="ddcwwfcsc_pubs[<?php echo $i; ?>][address]" value="<?php echo esc_attr( $pub['address'] ); ?>" class="regular-text ddcwwfcsc-pub-address"></td>
                            </tr>
                            <tr>
                                <th><label><?php esc_html_e( 'Latitude', 'ddcwwfcsc' ); ?></label></th>
                                <td><input type="text" name="ddcwwfcsc_pubs[<?php echo $i; ?>][lat]" value="<?php echo esc_attr( $pub['lat'] ); ?>" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label><?php esc_html_e( 'Longitude', 'ddcwwfcsc' ); ?></label></th>
                                <td><input type="text" name="ddcwwfcsc_pubs[<?php echo $i; ?>][lng]" value="<?php echo esc_attr( $pub['lng'] ); ?>" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label><?php esc_html_e( 'Distance from Ground', 'ddcwwfcsc' ); ?></label></th>
                                <td><input type="text" name="ddcwwfcsc_pubs[<?php echo $i; ?>][distance]" value="<?php echo esc_attr( $pub['distance'] ); ?>" class="small-text" placeholder="e.g. 0.3 miles"></td>
                            </tr>
                            <tr>
                                <th><label><?php esc_html_e( 'Image', 'ddcwwfcsc' ); ?></label></th>
                                <td>
                                    <div class="ddcwwfcsc-pub-image-preview">
                                        <?php if ( ! empty( $pub['image_url'] ) ) : ?>
                                            <img src="<?php echo esc_url( $pub['image_url'] ); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" name="ddcwwfcsc_pubs[<?php echo $i; ?>][image_id]" value="<?php echo esc_attr( $pub['image_id'] ); ?>" class="ddcwwfcsc-pub-image-id">
                                    <button type="button" class="button ddcwwfcsc-pub-image-upload"><?php esc_html_e( 'Select Image', 'ddcwwfcsc' ); ?></button>
                                    <button type="button" class="button ddcwwfcsc-pub-image-remove" style="<?php echo empty( $pub['image_id'] ) ? 'display:none;' : ''; ?>"><?php esc_html_e( 'Remove', 'ddcwwfcsc' ); ?></button>
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <p>
            <button type="button" class="button button-primary" id="ddcwwfcsc-add-pub"><?php esc_html_e( 'Add Pub', 'ddcwwfcsc' ); ?></button>
        </p>

        <script type="text/html" id="tmpl-ddcwwfcsc-pub-fieldset">
            <div class="ddcwwfcsc-pub-fieldset">
                <div class="ddcwwfcsc-pub-header">
                    <strong class="ddcwwfcsc-pub-number"><?php esc_html_e( 'Pub #', 'ddcwwfcsc' ); ?></strong>
                    <span class="ddcwwfcsc-pub-actions">
                        <button type="button" class="button ddcwwfcsc-pub-move-up" title="<?php esc_attr_e( 'Move Up', 'ddcwwfcsc' ); ?>">&uarr;</button>
                        <button type="button" class="button ddcwwfcsc-pub-move-down" title="<?php esc_attr_e( 'Move Down', 'ddcwwfcsc' ); ?>">&darr;</button>
                        <button type="button" class="button ddcwwfcsc-pub-remove" title="<?php esc_attr_e( 'Remove', 'ddcwwfcsc' ); ?>">&times;</button>
                    </span>
                </div>
                <table class="form-table">
                    <tr>
                        <th><label><?php esc_html_e( 'Name', 'ddcwwfcsc' ); ?></label></th>
                        <td><input type="text" name="ddcwwfcsc_pubs[{{data.index}}][name]" value="" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Description', 'ddcwwfcsc' ); ?></label></th>
                        <td><textarea name="ddcwwfcsc_pubs[{{data.index}}][description]" rows="3" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Address', 'ddcwwfcsc' ); ?></label></th>
                        <td><input type="text" name="ddcwwfcsc_pubs[{{data.index}}][address]" value="" class="regular-text ddcwwfcsc-pub-address"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Latitude', 'ddcwwfcsc' ); ?></label></th>
                        <td><input type="text" name="ddcwwfcsc_pubs[{{data.index}}][lat]" value="" class="small-text"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Longitude', 'ddcwwfcsc' ); ?></label></th>
                        <td><input type="text" name="ddcwwfcsc_pubs[{{data.index}}][lng]" value="" class="small-text"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Distance from Ground', 'ddcwwfcsc' ); ?></label></th>
                        <td><input type="text" name="ddcwwfcsc_pubs[{{data.index}}][distance]" value="" class="small-text" placeholder="e.g. 0.3 miles"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Image', 'ddcwwfcsc' ); ?></label></th>
                        <td>
                            <div class="ddcwwfcsc-pub-image-preview"></div>
                            <input type="hidden" name="ddcwwfcsc_pubs[{{data.index}}][image_id]" value="" class="ddcwwfcsc-pub-image-id">
                            <button type="button" class="button ddcwwfcsc-pub-image-upload"><?php esc_html_e( 'Select Image', 'ddcwwfcsc' ); ?></button>
                            <button type="button" class="button ddcwwfcsc-pub-image-remove" style="display:none;"><?php esc_html_e( 'Remove', 'ddcwwfcsc' ); ?></button>
                        </td>
                    </tr>
                </table>
            </div>
        </script>
        <?php
    }

    /**
     * Save beerwolf post data.
     */
    public static function save_post( $post_id, $post ) {
        if ( ! isset( $_POST['ddcwwfcsc_beerwolf_nonce'] ) || ! wp_verify_nonce( $_POST['ddcwwfcsc_beerwolf_nonce'], 'ddcwwfcsc_beerwolf_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save opponent term.
        if ( isset( $_POST['ddcwwfcsc_beerwolf_opponent'] ) ) {
            $opponent_id = absint( $_POST['ddcwwfcsc_beerwolf_opponent'] );
            if ( $opponent_id ) {
                wp_set_object_terms( $post_id, $opponent_id, 'ddcwwfcsc_opponent' );
            } else {
                wp_set_object_terms( $post_id, array(), 'ddcwwfcsc_opponent' );
            }
        }

        // Save pubs meta. The sanitize callback on register_post_meta handles sanitization.
        $pubs = isset( $_POST['ddcwwfcsc_pubs'] ) && is_array( $_POST['ddcwwfcsc_pubs'] ) ? $_POST['ddcwwfcsc_pubs'] : array();
        update_post_meta( $post_id, '_ddcwwfcsc_pubs', $pubs );
    }

    /**
     * Add custom columns.
     */
    public static function add_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
            if ( 'title' === $key ) {
                $new_columns['opponent']  = __( 'Opponent', 'ddcwwfcsc' );
                $new_columns['pub_count'] = __( 'Pubs', 'ddcwwfcsc' );
            }
        }
        return $new_columns;
    }

    /**
     * Render custom column values.
     */
    public static function render_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'opponent':
                $terms = get_the_terms( $post_id, 'ddcwwfcsc_opponent' );
                if ( $terms && ! is_wp_error( $terms ) ) {
                    $term = $terms[0];
                    $badge_url = '';
                    $badge_id = (int) get_term_meta( $term->term_id, '_ddcwwfcsc_badge_id', true );
                    if ( $badge_id ) {
                        $badge_url = wp_get_attachment_image_url( $badge_id, 'thumbnail' );
                    }
                    if ( ! $badge_url ) {
                        $badge = get_term_meta( $term->term_id, '_ddcwwfcsc_badge', true );
                        if ( $badge ) {
                            $badge_url = DDCWWFCSC_PLUGIN_URL . 'assets/img/clubs/' . $badge;
                        }
                    }
                    if ( $badge_url ) {
                        echo '<img src="' . esc_url( $badge_url ) . '" alt="" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;">';
                    }
                    echo esc_html( $term->name );
                } else {
                    echo '—';
                }
                break;

            case 'pub_count':
                $pubs = DDCWWFCSC_Beerwolf_CPT::get_pubs( $post_id );
                echo count( $pubs );
                break;
        }
    }

    /**
     * Enqueue admin assets on beerwolf edit screens.
     */
    public static function enqueue_assets( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || 'ddcwwfcsc_beerwolf' !== $screen->post_type ) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'ddcwwfcsc-beerwolf-admin',
            DDCWWFCSC_PLUGIN_URL . 'assets/css/beerwolf-admin.css',
            array(),
            DDCWWFCSC_VERSION
        );

        $repeater_deps = array( 'jquery', 'wp-util' );

        $maps_api_key = get_option( 'ddcwwfcsc_google_maps_api_key', '' );
        if ( $maps_api_key ) {
            wp_enqueue_script(
                'google-maps-api',
                'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode( $maps_api_key ) . '&libraries=places',
                array(),
                null,
                true
            );
            $repeater_deps[] = 'google-maps-api';
        }

        wp_enqueue_script(
            'ddcwwfcsc-beerwolf-repeater',
            DDCWWFCSC_PLUGIN_URL . 'assets/js/beerwolf-repeater.js',
            $repeater_deps,
            DDCWWFCSC_VERSION,
            true
        );
    }
}
