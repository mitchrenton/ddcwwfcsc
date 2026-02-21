<?php
/**
 * Bulletin front-end rendering.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Bulletin_Front {

    /**
     * Initialize hooks.
     */
    public static function init() {
        // No singular view — assets are enqueued by the block render.php.
    }

    /**
     * Render the ticker HTML for a set of bulletins.
     *
     * @param WP_Post[] $bulletins Array of bulletin posts.
     * @param int       $speed    Scroll speed in px/s.
     * @return string HTML output.
     */
    public static function render_ticker( $bulletins, $speed = 30 ) {
        if ( empty( $bulletins ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="ddcwwfcsc-bulletin-ticker" data-speed="<?php echo esc_attr( $speed ); ?>">
            <div class="ddcwwfcsc-bulletin-ticker-track">
                <?php
                // Render items twice for seamless CSS loop.
                for ( $i = 0; $i < 2; $i++ ) :
                    foreach ( $bulletins as $bulletin ) :
                        ?>
                        <span class="ddcwwfcsc-bulletin-ticker-item"><?php echo esc_html( get_the_title( $bulletin ) ); ?></span>
                        <span class="ddcwwfcsc-bulletin-ticker-separator" aria-hidden="true">&bull;</span>
                        <?php
                    endforeach;
                endfor;
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
