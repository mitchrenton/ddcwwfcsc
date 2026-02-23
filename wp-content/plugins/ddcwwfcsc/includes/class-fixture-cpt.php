<?php
/**
 * Fixture Custom Post Type and Price Category taxonomy.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Fixture_CPT {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
        add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
        add_action( 'init', array( __CLASS__, 'register_opponent_taxonomy' ) );
        add_action( 'init', array( __CLASS__, 'register_competition_taxonomy' ) );
        add_action( 'init', array( __CLASS__, 'register_season_taxonomy' ) );
        add_action( 'init', array( __CLASS__, 'register_post_meta' ) );
        add_action( 'pre_get_posts', array( __CLASS__, 'order_archive_by_match_date' ) );
    }

    /**
     * Order the fixture archive by match date and filter by season.
     */
    public static function order_archive_by_match_date( $query ) {
        if ( is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( ! is_post_type_archive( 'ddcwwfcsc_fixture' ) ) {
            return;
        }

        $query->set( 'meta_key', '_ddcwwfcsc_match_date' );
        $query->set( 'orderby', 'meta_value' );
        $query->set( 'order', 'ASC' );

        // Filter by season — default to the latest season.
        $season_slug = isset( $_GET['season'] ) ? sanitize_text_field( $_GET['season'] ) : '';

        if ( ! $season_slug ) {
            $latest = self::get_latest_season();
            if ( $latest ) {
                $season_slug = $latest->slug;
            }
        }

        if ( $season_slug && 'all' !== $season_slug ) {
            $query->set( 'tax_query', array(
                array(
                    'taxonomy' => 'ddcwwfcsc_season',
                    'field'    => 'slug',
                    'terms'    => $season_slug,
                ),
            ) );
        }
    }

    /**
     * Get the latest (most recent) season term.
     *
     * Sorts by name descending so "2025-26" comes before "2024-25".
     *
     * @return WP_Term|null
     */
    public static function get_latest_season() {
        $seasons = get_terms( array(
            'taxonomy'   => 'ddcwwfcsc_season',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'DESC',
            'number'     => 1,
        ) );

        if ( is_wp_error( $seasons ) || empty( $seasons ) ) {
            return null;
        }

        return $seasons[0];
    }

    /**
     * Register the Fixture CPT.
     */
    public static function register_post_type() {
        $labels = array(
            'name'                  => __( 'Fixtures', 'ddcwwfcsc' ),
            'singular_name'         => __( 'Fixture', 'ddcwwfcsc' ),
            'add_new'               => __( 'Add New', 'ddcwwfcsc' ),
            'add_new_item'          => __( 'Add New Fixture', 'ddcwwfcsc' ),
            'edit_item'             => __( 'Edit Fixture', 'ddcwwfcsc' ),
            'new_item'              => __( 'New Fixture', 'ddcwwfcsc' ),
            'view_item'             => __( 'View Fixture', 'ddcwwfcsc' ),
            'view_items'            => __( 'View Fixtures', 'ddcwwfcsc' ),
            'search_items'          => __( 'Search Fixtures', 'ddcwwfcsc' ),
            'not_found'             => __( 'No fixtures found.', 'ddcwwfcsc' ),
            'not_found_in_trash'    => __( 'No fixtures found in Trash.', 'ddcwwfcsc' ),
            'all_items'             => __( 'All Fixtures', 'ddcwwfcsc' ),
            'archives'              => __( 'Fixture Archives', 'ddcwwfcsc' ),
            'attributes'            => __( 'Fixture Attributes', 'ddcwwfcsc' ),
            'insert_into_item'      => __( 'Insert into fixture', 'ddcwwfcsc' ),
            'uploaded_to_this_item' => __( 'Uploaded to this fixture', 'ddcwwfcsc' ),
            'menu_name'             => __( 'Fixtures', 'ddcwwfcsc' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => array( 'thumbnail' ),
            'menu_icon'           => 'dashicons-tickets',
            'rewrite'             => array( 'slug' => 'fixtures' ),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'show_in_menu'        => true,
        );

        register_post_type( 'ddcwwfcsc_fixture', $args );
    }

    /**
     * Register the Price Category taxonomy.
     */
    public static function register_taxonomy() {
        $labels = array(
            'name'              => __( 'Price Categories', 'ddcwwfcsc' ),
            'singular_name'     => __( 'Price Category', 'ddcwwfcsc' ),
            'search_items'      => __( 'Search Price Categories', 'ddcwwfcsc' ),
            'all_items'         => __( 'All Price Categories', 'ddcwwfcsc' ),
            'parent_item'       => __( 'Parent Price Category', 'ddcwwfcsc' ),
            'parent_item_colon' => __( 'Parent Price Category:', 'ddcwwfcsc' ),
            'edit_item'         => __( 'Edit Price Category', 'ddcwwfcsc' ),
            'update_item'       => __( 'Update Price Category', 'ddcwwfcsc' ),
            'add_new_item'      => __( 'Add New Price Category', 'ddcwwfcsc' ),
            'new_item_name'     => __( 'New Price Category Name', 'ddcwwfcsc' ),
            'menu_name'         => __( 'Price Categories', 'ddcwwfcsc' ),
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'show_ui'           => false,
            'meta_box_cb'       => false,
            'rewrite'           => array( 'slug' => 'price-category' ),
        );

        register_taxonomy( 'ddcwwfcsc_price_category', 'ddcwwfcsc_fixture', $args );

        // Register term meta for price.
        register_term_meta( 'ddcwwfcsc_price_category', '_ddcwwfcsc_price', array(
            'type'              => 'number',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => function ( $value ) {
                return floatval( $value );
            },
        ) );
    }

    /**
     * Register the Opponent taxonomy.
     */
    public static function register_opponent_taxonomy() {
        $labels = array(
            'name'              => __( 'Opponents', 'ddcwwfcsc' ),
            'singular_name'     => __( 'Opponent', 'ddcwwfcsc' ),
            'search_items'      => __( 'Search Opponents', 'ddcwwfcsc' ),
            'all_items'         => __( 'All Opponents', 'ddcwwfcsc' ),
            'edit_item'         => __( 'Edit Opponent', 'ddcwwfcsc' ),
            'update_item'       => __( 'Update Opponent', 'ddcwwfcsc' ),
            'add_new_item'      => __( 'Add New Opponent', 'ddcwwfcsc' ),
            'new_item_name'     => __( 'New Opponent Name', 'ddcwwfcsc' ),
            'menu_name'         => __( 'Opponents', 'ddcwwfcsc' ),
        );

        register_taxonomy( 'ddcwwfcsc_opponent', array( 'ddcwwfcsc_fixture', 'ddcwwfcsc_beerwolf' ), array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'show_in_rest'      => true,
            'show_admin_column' => false, // We render our own column with badge in class-fixture-admin.
            'show_ui'           => true,
            'meta_box_cb'       => false, // We render our own dropdown in the meta box.
            'rewrite'           => array( 'slug' => 'opponent' ),
        ) );

        // Register term meta for the badge image filename (static assets).
        register_term_meta( 'ddcwwfcsc_opponent', '_ddcwwfcsc_badge', array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_file_name',
        ) );

        // Register term meta for media library badge (attachment ID).
        register_term_meta( 'ddcwwfcsc_opponent', '_ddcwwfcsc_badge_id', array(
            'type'              => 'integer',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'absint',
        ) );

        // Register term meta for API crest URL (from football-data.org).
        register_term_meta( 'ddcwwfcsc_opponent', '_ddcwwfcsc_crest_url', array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'esc_url_raw',
        ) );

        // Register term meta for stadium image (media library attachment ID).
        register_term_meta( 'ddcwwfcsc_opponent', '_ddcwwfcsc_stadium_id', array(
            'type'              => 'integer',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'absint',
        ) );

        // Badge fields on taxonomy forms.
        add_action( 'ddcwwfcsc_opponent_add_form_fields', array( __CLASS__, 'add_badge_field' ) );
        add_action( 'ddcwwfcsc_opponent_edit_form_fields', array( __CLASS__, 'edit_badge_field' ), 10, 2 );
        add_action( 'created_ddcwwfcsc_opponent', array( __CLASS__, 'save_badge_field' ) );
        add_action( 'edited_ddcwwfcsc_opponent', array( __CLASS__, 'save_badge_field' ) );

        // Stadium image fields on taxonomy forms.
        add_action( 'ddcwwfcsc_opponent_add_form_fields', array( __CLASS__, 'add_stadium_field' ) );
        add_action( 'ddcwwfcsc_opponent_edit_form_fields', array( __CLASS__, 'edit_stadium_field' ), 10, 2 );
        add_action( 'created_ddcwwfcsc_opponent', array( __CLASS__, 'save_stadium_field' ) );
        add_action( 'edited_ddcwwfcsc_opponent', array( __CLASS__, 'save_stadium_field' ) );

        // Enqueue media uploader on opponent taxonomy pages.
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_badge_uploader' ) );

        // Add badge column to opponent list table.
        add_filter( 'manage_edit-ddcwwfcsc_opponent_columns', array( __CLASS__, 'add_badge_column' ) );
        add_filter( 'manage_ddcwwfcsc_opponent_custom_column', array( __CLASS__, 'render_badge_column' ), 10, 3 );
    }

    /**
     * Get opponent data for a fixture.
     *
     * @param int $fixture_id Post ID.
     * @return array{name: string, badge_url: string}|null
     */
    public static function get_opponent( $fixture_id ) {
        $terms = get_the_terms( $fixture_id, 'ddcwwfcsc_opponent' );

        if ( ! $terms || is_wp_error( $terms ) ) {
            return null;
        }

        $term      = $terms[0];
        $badge_url = '';

        // Priority 1: Media library upload.
        $badge_id = (int) get_term_meta( $term->term_id, '_ddcwwfcsc_badge_id', true );
        if ( $badge_id ) {
            $badge_url = wp_get_attachment_image_url( $badge_id, 'thumbnail' );
        }

        // Priority 2: API crest URL (from football-data.org sync).
        if ( ! $badge_url ) {
            $crest_url = get_term_meta( $term->term_id, '_ddcwwfcsc_crest_url', true );
            if ( $crest_url ) {
                $badge_url = $crest_url;
            }
        }

        // Priority 3: Static file from term meta.
        if ( ! $badge_url ) {
            $badge = get_term_meta( $term->term_id, '_ddcwwfcsc_badge', true );
            if ( $badge ) {
                $badge_url = DDCWWFCSC_PLUGIN_URL . 'assets/img/clubs/' . $badge;
            }
        }

        // Priority 4: Fallback — match by slug.
        if ( ! $badge_url ) {
            $candidate = $term->slug . '.png';
            if ( file_exists( DDCWWFCSC_PLUGIN_DIR . 'assets/img/clubs/' . $candidate ) ) {
                update_term_meta( $term->term_id, '_ddcwwfcsc_badge', $candidate );
                $badge_url = DDCWWFCSC_PLUGIN_URL . 'assets/img/clubs/' . $candidate;
            }
        }

        return array(
            'name'        => $term->name,
            'slug'        => $term->slug,
            'term_id'     => $term->term_id,
            'badge_url'   => $badge_url,
            'stadium_url' => self::get_stadium_url_for_term( $term->term_id ),
        );
    }

    /**
     * Get the stadium image URL for an opponent term.
     *
     * @param int $term_id
     * @return string Full URL or empty string.
     */
    public static function get_stadium_url_for_term( $term_id ) {
        $stadium_id = (int) get_term_meta( $term_id, '_ddcwwfcsc_stadium_id', true );
        if ( $stadium_id ) {
            $url = wp_get_attachment_image_url( $stadium_id, 'full' );
            if ( $url ) {
                return $url;
            }
        }
        return '';
    }

    /**
     * Register the Competition taxonomy.
     */
    public static function register_competition_taxonomy() {
        $labels = array(
            'name'              => __( 'Competitions', 'ddcwwfcsc' ),
            'singular_name'     => __( 'Competition', 'ddcwwfcsc' ),
            'search_items'      => __( 'Search Competitions', 'ddcwwfcsc' ),
            'all_items'         => __( 'All Competitions', 'ddcwwfcsc' ),
            'edit_item'         => __( 'Edit Competition', 'ddcwwfcsc' ),
            'update_item'       => __( 'Update Competition', 'ddcwwfcsc' ),
            'add_new_item'      => __( 'Add New Competition', 'ddcwwfcsc' ),
            'new_item_name'     => __( 'New Competition Name', 'ddcwwfcsc' ),
            'menu_name'         => __( 'Competitions', 'ddcwwfcsc' ),
        );

        register_taxonomy( 'ddcwwfcsc_competition', 'ddcwwfcsc_fixture', array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'show_ui'           => true,
            'meta_box_cb'       => false,
            'rewrite'           => array( 'slug' => 'competition' ),
        ) );
    }

    /**
     * Register the Season taxonomy.
     */
    public static function register_season_taxonomy() {
        $labels = array(
            'name'              => __( 'Seasons', 'ddcwwfcsc' ),
            'singular_name'     => __( 'Season', 'ddcwwfcsc' ),
            'search_items'      => __( 'Search Seasons', 'ddcwwfcsc' ),
            'all_items'         => __( 'All Seasons', 'ddcwwfcsc' ),
            'edit_item'         => __( 'Edit Season', 'ddcwwfcsc' ),
            'update_item'       => __( 'Update Season', 'ddcwwfcsc' ),
            'add_new_item'      => __( 'Add New Season', 'ddcwwfcsc' ),
            'new_item_name'     => __( 'New Season Name', 'ddcwwfcsc' ),
            'menu_name'         => __( 'Seasons', 'ddcwwfcsc' ),
        );

        register_taxonomy( 'ddcwwfcsc_season', 'ddcwwfcsc_fixture', array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'show_ui'           => true,
            'meta_box_cb'       => false,
            'rewrite'           => array( 'slug' => 'season' ),
        ) );
    }

    /**
     * Register post meta for fixtures.
     */
    public static function register_post_meta() {
        $meta_fields = array(
            '_ddcwwfcsc_match_date'        => array( 'type' => 'string', 'default' => '' ),
            '_ddcwwfcsc_venue'             => array( 'type' => 'string', 'default' => '' ),
            '_ddcwwfcsc_total_tickets'     => array( 'type' => 'integer', 'default' => 8 ),
            '_ddcwwfcsc_tickets_remaining' => array( 'type' => 'integer', 'default' => 8 ),
            '_ddcwwfcsc_on_sale'           => array( 'type' => 'boolean', 'default' => false ),
            '_ddcwwfcsc_max_per_person'    => array( 'type' => 'integer', 'default' => 2 ),
            '_ddcwwfcsc_home_score'        => array( 'type' => 'integer', 'default' => -1 ),
            '_ddcwwfcsc_away_score'        => array( 'type' => 'integer', 'default' => -1 ),
        );

        foreach ( $meta_fields as $key => $config ) {
            $sanitize = $config['type'] === 'integer' ? 'absint' :
                        ( $config['type'] === 'boolean' ? 'rest_sanitize_boolean' : 'sanitize_text_field' );

            // Venue field: constrain to home/away/empty.
            if ( '_ddcwwfcsc_venue' === $key ) {
                $sanitize = function ( $value ) {
                    return in_array( $value, array( 'home', 'away' ), true ) ? $value : '';
                };
            }

            register_post_meta( 'ddcwwfcsc_fixture', $key, array(
                'type'              => $config['type'],
                'single'            => true,
                'show_in_rest'      => true,
                'default'           => $config['default'],
                'sanitize_callback' => $sanitize,
                'auth_callback'     => function () {
                    return current_user_can( 'edit_posts' );
                },
            ) );
        }
    }

    // -------------------------------------------------------------------------
    // Opponent badge fields
    // -------------------------------------------------------------------------

    /**
     * Get the current badge URL for an opponent term.
     */
    private static function get_badge_url_for_term( $term_id ) {
        // Priority 1: Media library upload.
        $badge_id = (int) get_term_meta( $term_id, '_ddcwwfcsc_badge_id', true );
        if ( $badge_id ) {
            return wp_get_attachment_image_url( $badge_id, 'thumbnail' );
        }

        // Priority 2: API crest URL.
        $crest_url = get_term_meta( $term_id, '_ddcwwfcsc_crest_url', true );
        if ( $crest_url ) {
            return $crest_url;
        }

        // Priority 3: Static file meta.
        $badge = get_term_meta( $term_id, '_ddcwwfcsc_badge', true );
        if ( $badge ) {
            return DDCWWFCSC_PLUGIN_URL . 'assets/img/clubs/' . $badge;
        }

        // Priority 4: Slug fallback.
        $term = get_term( $term_id, 'ddcwwfcsc_opponent' );
        if ( $term && ! is_wp_error( $term ) ) {
            $candidate = $term->slug . '.png';
            if ( file_exists( DDCWWFCSC_PLUGIN_DIR . 'assets/img/clubs/' . $candidate ) ) {
                return DDCWWFCSC_PLUGIN_URL . 'assets/img/clubs/' . $candidate;
            }
        }

        return '';
    }

    /**
     * Add badge field to the "Add New Opponent" form.
     */
    public static function add_badge_field() {
        ?>
        <div class="form-field">
            <label><?php esc_html_e( 'Badge', 'ddcwwfcsc' ); ?></label>
            <div id="ddcwwfcsc-badge-preview"></div>
            <input type="hidden" name="ddcwwfcsc_badge_id" id="ddcwwfcsc-badge-id" value="">
            <button type="button" class="button" id="ddcwwfcsc-badge-upload"><?php esc_html_e( 'Upload Badge', 'ddcwwfcsc' ); ?></button>
            <button type="button" class="button" id="ddcwwfcsc-badge-remove" style="display:none;"><?php esc_html_e( 'Remove', 'ddcwwfcsc' ); ?></button>
            <p><?php esc_html_e( 'Upload a club badge image. Pre-loaded opponents already have badges.', 'ddcwwfcsc' ); ?></p>
        </div>
        <?php
    }

    /**
     * Add badge field to the "Edit Opponent" form.
     */
    public static function edit_badge_field( $term, $taxonomy ) {
        $badge_id  = (int) get_term_meta( $term->term_id, '_ddcwwfcsc_badge_id', true );
        $badge_url = self::get_badge_url_for_term( $term->term_id );
        ?>
        <tr class="form-field">
            <th scope="row"><label><?php esc_html_e( 'Badge', 'ddcwwfcsc' ); ?></label></th>
            <td>
                <div id="ddcwwfcsc-badge-preview">
                    <?php if ( $badge_url ) : ?>
                        <img src="<?php echo esc_url( $badge_url ); ?>" style="max-width:80px;max-height:80px;">
                    <?php endif; ?>
                </div>
                <input type="hidden" name="ddcwwfcsc_badge_id" id="ddcwwfcsc-badge-id" value="<?php echo esc_attr( $badge_id ); ?>">
                <button type="button" class="button" id="ddcwwfcsc-badge-upload"><?php esc_html_e( 'Upload Badge', 'ddcwwfcsc' ); ?></button>
                <button type="button" class="button" id="ddcwwfcsc-badge-remove" style="<?php echo $badge_id ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'ddcwwfcsc' ); ?></button>
                <p class="description"><?php esc_html_e( 'Upload a club badge image. Pre-loaded opponents already have badges.', 'ddcwwfcsc' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save the badge field for an opponent term.
     */
    public static function save_badge_field( $term_id ) {
        if ( isset( $_POST['ddcwwfcsc_badge_id'] ) ) {
            $badge_id = absint( $_POST['ddcwwfcsc_badge_id'] );
            if ( $badge_id ) {
                update_term_meta( $term_id, '_ddcwwfcsc_badge_id', $badge_id );
            } else {
                delete_term_meta( $term_id, '_ddcwwfcsc_badge_id' );
            }
        }
    }

    // -------------------------------------------------------------------------
    // Opponent stadium image fields
    // -------------------------------------------------------------------------

    /**
     * Add stadium image field to the "Add New Opponent" form.
     */
    public static function add_stadium_field() {
        ?>
        <div class="form-field">
            <label><?php esc_html_e( 'Stadium Image', 'ddcwwfcsc' ); ?></label>
            <div id="ddcwwfcsc-stadium-preview"></div>
            <input type="hidden" name="ddcwwfcsc_stadium_id" id="ddcwwfcsc-stadium-id" value="">
            <button type="button" class="button" id="ddcwwfcsc-stadium-upload"><?php esc_html_e( 'Upload Stadium Image', 'ddcwwfcsc' ); ?></button>
            <button type="button" class="button" id="ddcwwfcsc-stadium-remove" style="display:none;"><?php esc_html_e( 'Remove', 'ddcwwfcsc' ); ?></button>
            <p><?php esc_html_e( "Used as the hero background on this opponent's fixture pages.", 'ddcwwfcsc' ); ?></p>
        </div>
        <?php
    }

    /**
     * Add stadium image field to the "Edit Opponent" form.
     */
    public static function edit_stadium_field( $term, $taxonomy ) {
        $stadium_id  = (int) get_term_meta( $term->term_id, '_ddcwwfcsc_stadium_id', true );
        $stadium_url = self::get_stadium_url_for_term( $term->term_id );
        ?>
        <tr class="form-field">
            <th scope="row"><label><?php esc_html_e( 'Stadium Image', 'ddcwwfcsc' ); ?></label></th>
            <td>
                <div id="ddcwwfcsc-stadium-preview">
                    <?php if ( $stadium_url ) : ?>
                        <img src="<?php echo esc_url( $stadium_url ); ?>" style="max-width:200px;max-height:120px;object-fit:cover;border-radius:4px;">
                    <?php endif; ?>
                </div>
                <input type="hidden" name="ddcwwfcsc_stadium_id" id="ddcwwfcsc-stadium-id" value="<?php echo esc_attr( $stadium_id ); ?>">
                <button type="button" class="button" id="ddcwwfcsc-stadium-upload" style="margin-top:6px;"><?php esc_html_e( 'Upload Stadium Image', 'ddcwwfcsc' ); ?></button>
                <button type="button" class="button" id="ddcwwfcsc-stadium-remove" style="margin-top:6px;<?php echo $stadium_id ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'ddcwwfcsc' ); ?></button>
                <p class="description"><?php esc_html_e( "Used as the hero background on this opponent's fixture pages.", 'ddcwwfcsc' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save the stadium image field for an opponent term.
     */
    public static function save_stadium_field( $term_id ) {
        if ( isset( $_POST['ddcwwfcsc_stadium_id'] ) ) {
            $stadium_id = absint( $_POST['ddcwwfcsc_stadium_id'] );
            if ( $stadium_id ) {
                update_term_meta( $term_id, '_ddcwwfcsc_stadium_id', $stadium_id );
            } else {
                delete_term_meta( $term_id, '_ddcwwfcsc_stadium_id' );
            }
        }
    }

    /**
     * Enqueue the media uploader on opponent taxonomy pages.
     */
    public static function enqueue_badge_uploader( $hook ) {
        if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || 'ddcwwfcsc_opponent' !== $screen->taxonomy ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'ddcwwfcsc-badge-uploader',
            DDCWWFCSC_PLUGIN_URL . 'assets/js/badge-uploader.js',
            array( 'jquery' ),
            DDCWWFCSC_VERSION,
            true
        );
    }

    /**
     * Add the Badge column to the opponent list table.
     */
    public static function add_badge_column( $columns ) {
        // Insert badge column after the checkbox.
        $new_columns = array();
        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
            if ( 'cb' === $key ) {
                $new_columns['badge'] = __( 'Badge', 'ddcwwfcsc' );
            }
        }
        return $new_columns;
    }

    /**
     * Render the Badge column value.
     */
    public static function render_badge_column( $content, $column_name, $term_id ) {
        if ( 'badge' === $column_name ) {
            $badge_url = self::get_badge_url_for_term( $term_id );
            if ( $badge_url ) {
                $content = '<img src="' . esc_url( $badge_url ) . '" style="width:24px;height:24px;object-fit:contain;">';
            } else {
                $content = '—';
            }
        }
        return $content;
    }
}
