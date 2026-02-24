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
     * Also applies the `ddcwwfcsc_bulletin_extra_texts` filter to allow other
     * classes (e.g. MOTM) to inject additional ticker items without a DB record.
     *
     * @param WP_Post[] $bulletins Array of bulletin posts.
     * @param int       $speed     Scroll speed in px/s.
     * @return string HTML output.
     */
    public static function render_ticker( $bulletins, $speed = 30 ) {
        $extra_texts = apply_filters( 'ddcwwfcsc_bulletin_extra_texts', array() );

        if ( empty( $bulletins ) && empty( $extra_texts ) ) {
            return '';
        }

        // Build a unified list of plain-text strings.
        $texts = array();
        foreach ( $bulletins as $bulletin ) {
            $texts[] = get_the_title( $bulletin );
        }
        foreach ( $extra_texts as $text ) {
            $texts[] = (string) $text;
        }

        ob_start();
        ?>
        <div class="ddcwwfcsc-bulletin-ticker" data-speed="<?php echo esc_attr( $speed ); ?>">
            <div class="ddcwwfcsc-bulletin-ticker-track">
                <?php
                // Render items twice for seamless CSS loop.
                for ( $i = 0; $i < 2; $i++ ) :
                    foreach ( $texts as $text ) :
                        ?>
                        <span class="ddcwwfcsc-bulletin-ticker-item"><?php echo esc_html( $text ); ?></span>
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
