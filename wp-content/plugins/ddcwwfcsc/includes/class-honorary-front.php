<?php
/**
 * Honorary Member front-end rendering.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Honorary_Front {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /**
     * Render a compact grid card for a single honorary member.
     *
     * @param int $post_id Post ID.
     * @return string HTML output.
     */
    public static function render_grid_card( $post_id ) {
        $data      = DDCWWFCSC_Honorary_CPT::get_member_data( $post_id );
        $permalink = get_permalink( $post_id );

        ob_start();
        ?>
        <a href="<?php echo esc_url( $permalink ); ?>" class="ddcwwfcsc-honorary-card">
            <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                <div class="ddcwwfcsc-honorary-card-photo">
                    <?php echo get_the_post_thumbnail( $post_id, 'medium' ); ?>
                </div>
            <?php else : ?>
                <div class="ddcwwfcsc-honorary-card-photo ddcwwfcsc-honorary-card-photo--placeholder">
                    <span class="dashicons dashicons-awards"></span>
                </div>
            <?php endif; ?>

            <div class="ddcwwfcsc-honorary-card-info">
                <h3 class="ddcwwfcsc-honorary-card-name"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
                <?php if ( $data['position'] ) : ?>
                    <p class="ddcwwfcsc-honorary-card-position"><?php echo esc_html( $data['position'] ); ?></p>
                <?php endif; ?>
                <?php if ( $data['years_at_wolves'] ) : ?>
                    <p class="ddcwwfcsc-honorary-card-years"><?php echo esc_html( $data['years_at_wolves'] ); ?></p>
                <?php endif; ?>
            </div>
        </a>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue front-end assets on singular and archive views.
     */
    public static function enqueue_assets() {
        if ( ! is_singular( 'ddcwwfcsc_honorary' ) && ! is_post_type_archive( 'ddcwwfcsc_honorary' ) ) {
            return;
        }

        wp_enqueue_style(
            'ddcwwfcsc-honorary-front',
            DDCWWFCSC_PLUGIN_URL . 'assets/css/honorary-front.css',
            array(),
            DDCWWFCSC_VERSION
        );
    }
}
