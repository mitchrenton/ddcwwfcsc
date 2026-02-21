<?php
/**
 * Beerwolf front-end rendering: the_content filter and shared HTML output.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Beerwolf_Front {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_filter( 'the_content', array( __CLASS__, 'filter_content' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
    }

    /**
     * Append pub guide HTML to singular beerwolf content.
     *
     * Skipped when the theme template controls layout directly.
     */
    public static function filter_content( $content ) {
        if ( ! is_singular( 'ddcwwfcsc_beerwolf' ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        // The single-ddcwwfcsc_beerwolf.php template calls render_map_html()
        // and render_pubs_html() directly, so skip appending here.
        return $content;
    }

    /**
     * Enqueue front-end styles on singular beerwolf pages.
     */
    public static function enqueue_styles() {
        if ( is_singular( 'ddcwwfcsc_beerwolf' ) ) {
            wp_enqueue_style(
                'ddcwwfcsc-beerwolf-front',
                DDCWWFCSC_PLUGIN_URL . 'blocks/beerwolf/style.css',
                array(),
                DDCWWFCSC_VERSION
            );
        }
    }

    /**
     * Render the map container for a given beerwolf post.
     *
     * @param int $post_id Post ID.
     * @return string HTML output.
     */
    public static function render_map_html( $post_id ) {
        $pubs = DDCWWFCSC_Beerwolf_CPT::get_pubs( $post_id );

        if ( empty( $pubs ) ) {
            return '';
        }

        $map_pubs = self::build_map_data( $pubs );
        $api_key  = get_option( 'ddcwwfcsc_google_maps_api_key', '' );

        if ( empty( $map_pubs ) || ! $api_key ) {
            return '';
        }

        self::enqueue_map_scripts( $api_key );

        return '<div class="ddcwwfcsc-beerwolf-map" data-pubs="' . esc_attr( wp_json_encode( $map_pubs ) ) . '"></div>';
    }

    /**
     * Render pub cards for a given beerwolf post.
     *
     * @param int $post_id Post ID.
     * @return string HTML output.
     */
    public static function render_pubs_html( $post_id ) {
        $pubs = DDCWWFCSC_Beerwolf_CPT::get_pubs( $post_id );

        if ( empty( $pubs ) ) {
            return '';
        }

        return self::render_pub_cards( $pubs );
    }

    /**
     * Render the beerwolf pub guide HTML for a given post.
     *
     * Used by the block render.php.
     *
     * @param int $post_id Post ID.
     * @return string HTML output.
     */
    public static function render_beerwolf_html( $post_id ) {
        $pubs = DDCWWFCSC_Beerwolf_CPT::get_pubs( $post_id );

        if ( empty( $pubs ) ) {
            return '';
        }

        $map_pubs = self::build_map_data( $pubs );
        $api_key  = get_option( 'ddcwwfcsc_google_maps_api_key', '' );

        ob_start();

        // Map container.
        if ( ! empty( $map_pubs ) && $api_key ) :
            self::enqueue_map_scripts( $api_key );
        ?>
            <div class="ddcwwfcsc-beerwolf-map" data-pubs="<?php echo esc_attr( wp_json_encode( $map_pubs ) ); ?>"></div>
        <?php endif;

        echo self::render_pub_cards( $pubs );

        return ob_get_clean();
    }

    /**
     * Build map marker data from pubs array.
     *
     * @param array $pubs Pubs data.
     * @return array Map-ready pub data.
     */
    private static function build_map_data( $pubs ) {
        $map_pubs = array();
        foreach ( $pubs as $pub ) {
            if ( ! empty( $pub['lat'] ) && ! empty( $pub['lng'] ) ) {
                $map_pubs[] = array(
                    'name'     => $pub['name'],
                    'address'  => $pub['address'],
                    'distance' => $pub['distance'],
                    'lat'      => (float) $pub['lat'],
                    'lng'      => (float) $pub['lng'],
                );
            }
        }
        return $map_pubs;
    }

    /**
     * Render portrait pub cards HTML.
     *
     * @param array $pubs Pubs data.
     * @return string HTML output.
     */
    private static function render_pub_cards( $pubs ) {
        ob_start();
        ?>
        <div class="ddcwwfcsc-beerwolf-pubs">
            <?php foreach ( $pubs as $pub ) : ?>
                <div class="ddcwwfcsc-beerwolf-pub-card">
                    <?php if ( ! empty( $pub['image_url'] ) ) : ?>
                        <div class="ddcwwfcsc-beerwolf-pub-image">
                            <img src="<?php echo esc_url( $pub['image_url'] ); ?>" alt="<?php echo esc_attr( $pub['name'] ); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="ddcwwfcsc-beerwolf-pub-info">
                        <h3 class="ddcwwfcsc-beerwolf-pub-name"><?php echo esc_html( $pub['name'] ); ?></h3>
                        <?php if ( ! empty( $pub['address'] ) ) : ?>
                            <p class="ddcwwfcsc-beerwolf-pub-address"><?php echo esc_html( $pub['address'] ); ?></p>
                        <?php endif; ?>
                        <?php if ( ! empty( $pub['distance'] ) ) : ?>
                            <p class="ddcwwfcsc-beerwolf-pub-distance"><?php echo esc_html( $pub['distance'] ); ?> <?php esc_html_e( 'from the ground', 'ddcwwfcsc' ); ?></p>
                        <?php endif; ?>
                        <?php if ( ! empty( $pub['description'] ) ) : ?>
                            <div class="ddcwwfcsc-beerwolf-pub-desc"><?php echo wpautop( esc_html( $pub['description'] ) ); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue Google Maps API and map script.
     */
    private static function enqueue_map_scripts( $api_key ) {
        wp_enqueue_script(
            'ddcwwfcsc-beerwolf-map',
            DDCWWFCSC_PLUGIN_URL . 'assets/js/beerwolf-map.js',
            array(),
            DDCWWFCSC_VERSION,
            true
        );

        wp_enqueue_script(
            'google-maps-api',
            'https://maps.googleapis.com/maps/api/js?key=' . urlencode( $api_key ) . '&callback=ddcwwfcscInitMaps',
            array( 'ddcwwfcsc-beerwolf-map' ),
            null,
            true
        );
    }
}
