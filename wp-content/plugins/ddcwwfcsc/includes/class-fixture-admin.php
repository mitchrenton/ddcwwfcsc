<?php
/**
 * Fixture admin meta boxes and custom columns.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Fixture_Admin {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_ddcwwfcsc_fixture', array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
        add_filter( 'manage_ddcwwfcsc_fixture_posts_columns', array( __CLASS__, 'add_columns' ) );
        add_action( 'manage_ddcwwfcsc_fixture_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );
        add_filter( 'manage_edit-ddcwwfcsc_fixture_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
        add_action( 'restrict_manage_posts', array( __CLASS__, 'render_season_filter' ) );
        add_action( 'restrict_manage_posts', array( __CLASS__, 'render_on_sale_filter' ) );
        add_action( 'pre_get_posts', array( __CLASS__, 'apply_on_sale_filter' ) );
        add_action( 'pre_get_posts', array( __CLASS__, 'apply_admin_sorting' ) );

        // Force classic editor for fixtures — block editor hides classic meta boxes.
        add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'disable_block_editor' ), 10, 2 );

        // Admin notice for on-sale validation.
        add_action( 'admin_notices', array( __CLASS__, 'render_admin_notices' ) );
    }

    /**
     * Disable the block editor for the fixture CPT.
     */
    public static function disable_block_editor( $use, $post_type ) {
        if ( 'ddcwwfcsc_fixture' === $post_type ) {
            return false;
        }
        return $use;
    }

    /**
     * Render admin notices for fixture validation errors.
     */
    public static function render_admin_notices() {
        $screen = get_current_screen();
        if ( ! $screen || 'ddcwwfcsc_fixture' !== $screen->post_type ) {
            return;
        }

        if ( isset( $_GET['ddcwwfcsc_notice'] ) && 'no_price_category' === $_GET['ddcwwfcsc_notice'] ) {
            echo '<div class="notice notice-error is-dismissible"><p>';
            esc_html_e( 'Tickets cannot be put on sale without a price category. Please assign a price category first.', 'ddcwwfcsc' );
            echo '</p></div>';
        }
    }

    /**
     * Register meta boxes for the fixture editor.
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'ddcwwfcsc_match_details',
            __( 'Match Details', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_match_details_box' ),
            'ddcwwfcsc_fixture',
            'normal',
            'high'
        );

        add_meta_box(
            'ddcwwfcsc_ticket_settings',
            __( 'Ticket Settings', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_ticket_settings_box' ),
            'ddcwwfcsc_fixture',
            'normal',
            'high'
        );

        add_meta_box(
            'ddcwwfcsc_on_sale',
            __( 'Ticket Sales', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_on_sale_box' ),
            'ddcwwfcsc_fixture',
            'side',
            'high'
        );

        // Remove the slug meta box — title/slug are auto-generated.
        remove_meta_box( 'slugdiv', 'ddcwwfcsc_fixture', 'normal' );

        // Ensure the WYSIWYG editor is removed.
        remove_post_type_support( 'ddcwwfcsc_fixture', 'editor' );
        remove_post_type_support( 'ddcwwfcsc_fixture', 'title' );
    }

    /**
     * Render the Match Details meta box.
     */
    public static function render_match_details_box( $post ) {
        wp_nonce_field( 'ddcwwfcsc_fixture_meta', 'ddcwwfcsc_fixture_nonce' );

        $match_date = get_post_meta( $post->ID, '_ddcwwfcsc_match_date', true );
        // Get currently assigned opponent.
        $current_opponent = 0;
        $opponent_terms   = get_the_terms( $post->ID, 'ddcwwfcsc_opponent' );
        if ( $opponent_terms && ! is_wp_error( $opponent_terms ) ) {
            $current_opponent = $opponent_terms[0]->term_id;
        }

        // Get all opponents, sorted alphabetically.
        $opponents = get_terms( array(
            'taxonomy'   => 'ddcwwfcsc_opponent',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="ddcwwfcsc_opponent"><?php esc_html_e( 'Opponent', 'ddcwwfcsc' ); ?></label></th>
                <td>
                    <select id="ddcwwfcsc_opponent" name="ddcwwfcsc_opponent" class="regular-text">
                        <option value=""><?php esc_html_e( '— Select Opponent —', 'ddcwwfcsc' ); ?></option>
                        <?php if ( ! is_wp_error( $opponents ) ) : ?>
                            <?php foreach ( $opponents as $opp ) :
                                $badge = get_term_meta( $opp->term_id, '_ddcwwfcsc_badge', true );
                            ?>
                                <option value="<?php echo esc_attr( $opp->term_id ); ?>" <?php selected( $current_opponent, $opp->term_id ); ?>>
                                    <?php echo esc_html( $opp->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e( 'Venue', 'ddcwwfcsc' ); ?></label></th>
                <td>
                    <?php $venue = get_post_meta( $post->ID, '_ddcwwfcsc_venue', true ); ?>
                    <label style="margin-right: 16px;">
                        <input type="radio" name="ddcwwfcsc_venue" value="home" <?php checked( $venue, 'home' ); ?>>
                        <?php esc_html_e( 'Home', 'ddcwwfcsc' ); ?>
                    </label>
                    <label>
                        <input type="radio" name="ddcwwfcsc_venue" value="away" <?php checked( $venue, 'away' ); ?>>
                        <?php esc_html_e( 'Away', 'ddcwwfcsc' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="ddcwwfcsc_match_date"><?php esc_html_e( 'Match Date & Time', 'ddcwwfcsc' ); ?></label></th>
                <td><input type="datetime-local" id="ddcwwfcsc_match_date" name="ddcwwfcsc_match_date" value="<?php echo esc_attr( $match_date ); ?>"></td>
            </tr>
            <tr>
                <th><label for="ddcwwfcsc_competition"><?php esc_html_e( 'Competition', 'ddcwwfcsc' ); ?></label></th>
                <td>
                    <?php
                    $current_competition = 0;
                    $competition_terms   = get_the_terms( $post->ID, 'ddcwwfcsc_competition' );
                    if ( $competition_terms && ! is_wp_error( $competition_terms ) ) {
                        $current_competition = $competition_terms[0]->term_id;
                    }
                    $competitions = get_terms( array(
                        'taxonomy'   => 'ddcwwfcsc_competition',
                        'hide_empty' => false,
                        'orderby'    => 'name',
                        'order'      => 'ASC',
                    ) );
                    ?>
                    <select id="ddcwwfcsc_competition" name="ddcwwfcsc_competition" class="regular-text">
                        <option value=""><?php esc_html_e( '— Select Competition —', 'ddcwwfcsc' ); ?></option>
                        <?php if ( ! is_wp_error( $competitions ) ) : ?>
                            <?php foreach ( $competitions as $comp ) : ?>
                                <option value="<?php echo esc_attr( $comp->term_id ); ?>" <?php selected( $current_competition, $comp->term_id ); ?>>
                                    <?php echo esc_html( $comp->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ddcwwfcsc_season"><?php esc_html_e( 'Season', 'ddcwwfcsc' ); ?></label></th>
                <td>
                    <?php
                    $current_season = 0;
                    $season_terms   = get_the_terms( $post->ID, 'ddcwwfcsc_season' );
                    if ( $season_terms && ! is_wp_error( $season_terms ) ) {
                        $current_season = $season_terms[0]->term_id;
                    }
                    $seasons = get_terms( array(
                        'taxonomy'   => 'ddcwwfcsc_season',
                        'hide_empty' => false,
                        'orderby'    => 'name',
                        'order'      => 'DESC',
                    ) );
                    ?>
                    <select id="ddcwwfcsc_season" name="ddcwwfcsc_season" class="regular-text">
                        <option value=""><?php esc_html_e( '— Select Season —', 'ddcwwfcsc' ); ?></option>
                        <?php if ( ! is_wp_error( $seasons ) ) : ?>
                            <?php foreach ( $seasons as $szn ) : ?>
                                <option value="<?php echo esc_attr( $szn->term_id ); ?>" <?php selected( $current_season, $szn->term_id ); ?>>
                                    <?php echo esc_html( $szn->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render the Ticket Settings meta box.
     */
    public static function render_ticket_settings_box( $post ) {
        $default_total = get_option( 'ddcwwfcsc_default_tickets', 8 );
        $default_max   = get_option( 'ddcwwfcsc_default_max_per_person', 2 );

        $total_tickets  = get_post_meta( $post->ID, '_ddcwwfcsc_total_tickets', true );
        $max_per_person = get_post_meta( $post->ID, '_ddcwwfcsc_max_per_person', true );

        // Use defaults for new posts.
        if ( '' === $total_tickets ) {
            $total_tickets = $default_total;
        }
        if ( '' === $max_per_person ) {
            $max_per_person = $default_max;
        }

        // Get current price category.
        $current_price_cat = 0;
        $price_cat_terms   = get_the_terms( $post->ID, 'ddcwwfcsc_price_category' );
        if ( $price_cat_terms && ! is_wp_error( $price_cat_terms ) ) {
            $current_price_cat = $price_cat_terms[0]->term_id;
        }

        $price_categories = get_terms( array(
            'taxonomy'   => 'ddcwwfcsc_price_category',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="ddcwwfcsc_price_category"><?php esc_html_e( 'Price Category', 'ddcwwfcsc' ); ?></label></th>
                <td>
                    <select id="ddcwwfcsc_price_category" name="ddcwwfcsc_price_category" class="regular-text">
                        <option value=""><?php esc_html_e( '— Select Price Category —', 'ddcwwfcsc' ); ?></option>
                        <?php if ( ! is_wp_error( $price_categories ) ) : ?>
                            <?php foreach ( $price_categories as $cat ) :
                                $price = get_term_meta( $cat->term_id, '_ddcwwfcsc_price', true );
                                $label = $cat->name . ' — £' . number_format( (float) $price, 2 );
                            ?>
                                <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $current_price_cat, $cat->term_id ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Manage categories in Settings → DDCWWFCSC.', 'ddcwwfcsc' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ddcwwfcsc_total_tickets"><?php esc_html_e( 'Total Tickets Available', 'ddcwwfcsc' ); ?></label></th>
                <td><input type="number" id="ddcwwfcsc_total_tickets" name="ddcwwfcsc_total_tickets" value="<?php echo esc_attr( $total_tickets ); ?>" min="1" step="1"></td>
            </tr>
            <tr>
                <th><label for="ddcwwfcsc_max_per_person"><?php esc_html_e( 'Max Tickets Per Person', 'ddcwwfcsc' ); ?></label></th>
                <td><input type="number" id="ddcwwfcsc_max_per_person" name="ddcwwfcsc_max_per_person" value="<?php echo esc_attr( $max_per_person ); ?>" min="1" step="1"></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render the On Sale toggle meta box.
     */
    public static function render_on_sale_box( $post ) {
        $on_sale   = get_post_meta( $post->ID, '_ddcwwfcsc_on_sale', true );
        $remaining = get_post_meta( $post->ID, '_ddcwwfcsc_tickets_remaining', true );
        ?>
        <div style="padding: 10px 0;">
            <label>
                <input type="checkbox" name="ddcwwfcsc_on_sale" value="1"
                    <?php checked( $on_sale ); ?>
                >
                <strong><?php esc_html_e( 'Tickets On Sale', 'ddcwwfcsc' ); ?></strong>
            </label>

            <p class="description">
                <?php esc_html_e( 'Tickets will stop being shown to visitors once the match date passes.', 'ddcwwfcsc' ); ?>
            </p>

            <?php if ( $on_sale ) : ?>
                <p class="description">
                    <?php
                    printf(
                        /* translators: %s: number of tickets remaining */
                        esc_html__( 'Tickets remaining: %s', 'ddcwwfcsc' ),
                        '<strong>' . intval( $remaining ) . '</strong>'
                    );
                    ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Save meta box data.
     */
    public static function save_meta_boxes( $post_id, $post ) {
        // Verify nonce.
        if ( ! isset( $_POST['ddcwwfcsc_fixture_nonce'] ) || ! wp_verify_nonce( $_POST['ddcwwfcsc_fixture_nonce'], 'ddcwwfcsc_fixture_meta' ) ) {
            return;
        }

        // Check autosave.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save opponent taxonomy term.
        if ( isset( $_POST['ddcwwfcsc_opponent'] ) ) {
            $opponent_id = absint( $_POST['ddcwwfcsc_opponent'] );
            if ( $opponent_id ) {
                wp_set_object_terms( $post_id, $opponent_id, 'ddcwwfcsc_opponent' );
            } else {
                wp_set_object_terms( $post_id, array(), 'ddcwwfcsc_opponent' );
            }
        }

        // Save competition taxonomy term.
        if ( isset( $_POST['ddcwwfcsc_competition'] ) ) {
            $competition_id = absint( $_POST['ddcwwfcsc_competition'] );
            if ( $competition_id ) {
                wp_set_object_terms( $post_id, $competition_id, 'ddcwwfcsc_competition' );
            } else {
                wp_set_object_terms( $post_id, array(), 'ddcwwfcsc_competition' );
            }
        }

        // Save season taxonomy term.
        if ( isset( $_POST['ddcwwfcsc_season'] ) ) {
            $season_id = absint( $_POST['ddcwwfcsc_season'] );
            if ( $season_id ) {
                wp_set_object_terms( $post_id, $season_id, 'ddcwwfcsc_season' );
            } else {
                wp_set_object_terms( $post_id, array(), 'ddcwwfcsc_season' );
            }
        }

        // Save match details.
        if ( isset( $_POST['ddcwwfcsc_match_date'] ) ) {
            update_post_meta( $post_id, '_ddcwwfcsc_match_date', sanitize_text_field( $_POST['ddcwwfcsc_match_date'] ) );
        }
        // Save venue (home/away).
        if ( isset( $_POST['ddcwwfcsc_venue'] ) ) {
            $venue = in_array( $_POST['ddcwwfcsc_venue'], array( 'home', 'away' ), true ) ? $_POST['ddcwwfcsc_venue'] : '';
            update_post_meta( $post_id, '_ddcwwfcsc_venue', $venue );
        }
        // Save price category taxonomy term.
        if ( isset( $_POST['ddcwwfcsc_price_category'] ) ) {
            $price_cat_id = absint( $_POST['ddcwwfcsc_price_category'] );
            if ( $price_cat_id && term_exists( $price_cat_id, 'ddcwwfcsc_price_category' ) ) {
                wp_set_object_terms( $post_id, $price_cat_id, 'ddcwwfcsc_price_category' );
            } else {
                wp_set_object_terms( $post_id, array(), 'ddcwwfcsc_price_category' );
            }
        }

        // Save ticket settings.
        if ( isset( $_POST['ddcwwfcsc_total_tickets'] ) ) {
            update_post_meta( $post_id, '_ddcwwfcsc_total_tickets', absint( $_POST['ddcwwfcsc_total_tickets'] ) );
        }
        if ( isset( $_POST['ddcwwfcsc_max_per_person'] ) ) {
            update_post_meta( $post_id, '_ddcwwfcsc_max_per_person', absint( $_POST['ddcwwfcsc_max_per_person'] ) );
        }

        // Handle on-sale toggle.
        $was_on_sale = (bool) get_post_meta( $post_id, '_ddcwwfcsc_on_sale', true );
        $now_on_sale = ! empty( $_POST['ddcwwfcsc_on_sale'] );

        // Validate: on-sale requires a price category.
        if ( $now_on_sale ) {
            $price_cat_terms = get_the_terms( $post_id, 'ddcwwfcsc_price_category' );
            if ( ! $price_cat_terms || is_wp_error( $price_cat_terms ) ) {
                $now_on_sale = false;
                add_filter( 'redirect_post_location', function ( $location ) {
                    return add_query_arg( 'ddcwwfcsc_notice', 'no_price_category', $location );
                } );
            }
        }

        if ( $now_on_sale && ! $was_on_sale ) {
            // Set tickets remaining to total when toggling on.
            $total = absint( $_POST['ddcwwfcsc_total_tickets'] ?? get_post_meta( $post_id, '_ddcwwfcsc_total_tickets', true ) );
            if ( ! $total ) {
                $total = (int) get_option( 'ddcwwfcsc_default_tickets', 8 );
            }
            update_post_meta( $post_id, '_ddcwwfcsc_tickets_remaining', $total );
        }

        update_post_meta( $post_id, '_ddcwwfcsc_on_sale', $now_on_sale );

        // Auto-generate the post title.
        self::update_fixture_title( $post_id );
    }

    /**
     * Generate and set the fixture title from opponent and match date.
     */
    public static function update_fixture_title( $post_id ) {
        $opponent_data = DDCWWFCSC_Fixture_CPT::get_opponent( $post_id );
        $match_date    = get_post_meta( $post_id, '_ddcwwfcsc_match_date', true );
        $venue         = get_post_meta( $post_id, '_ddcwwfcsc_venue', true );

        $opponent_name = $opponent_data ? $opponent_data['name'] : '';

        // Home = "Wolves v {Opponent}", Away = "{Opponent} v Wolves".
        if ( $opponent_name ) {
            $vs = ( 'away' === $venue )
                ? $opponent_name . ' v Wolves'
                : 'Wolves v ' . $opponent_name;
        } else {
            $vs = '';
        }

        if ( $vs && $match_date ) {
            $title = $vs . ' — ' . wp_date( 'j M Y', strtotime( $match_date ) );
        } elseif ( $vs ) {
            $title = $vs;
        } elseif ( $match_date ) {
            $title = 'Fixture — ' . wp_date( 'j M Y', strtotime( $match_date ) );
        } else {
            return; // Nothing to generate from yet.
        }

        // Unhook to prevent infinite loop, update, re-hook.
        remove_action( 'save_post_ddcwwfcsc_fixture', array( __CLASS__, 'save_meta_boxes' ), 10 );
        wp_update_post( array(
            'ID'         => $post_id,
            'post_title' => $title,
            'post_name'  => sanitize_title( $title ),
        ) );
        add_action( 'save_post_ddcwwfcsc_fixture', array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
    }

    /**
     * Add custom columns to the fixtures list table.
     */
    public static function add_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
            if ( 'title' === $key ) {
                $new_columns['opponent']   = __( 'Opponent', 'ddcwwfcsc' );
                $new_columns['venue']      = __( 'Venue', 'ddcwwfcsc' );
                $new_columns['match_date'] = __( 'Match Date', 'ddcwwfcsc' );
                $new_columns['on_sale']    = __( 'On Sale', 'ddcwwfcsc' );
                $new_columns['remaining']  = __( 'Tickets Remaining', 'ddcwwfcsc' );
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
                $opp = DDCWWFCSC_Fixture_CPT::get_opponent( $post_id );
                if ( $opp ) {
                    if ( $opp['badge_url'] ) {
                        echo '<img src="' . esc_url( $opp['badge_url'] ) . '" alt="" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;">';
                    }
                    echo esc_html( $opp['name'] );
                } else {
                    echo '—';
                }
                break;

            case 'venue':
                $venue = get_post_meta( $post_id, '_ddcwwfcsc_venue', true );
                if ( 'home' === $venue ) {
                    echo '<span style="color: #2e7d32; font-weight: 600;">&#9679; ' . esc_html__( 'Home', 'ddcwwfcsc' ) . '</span>';
                } elseif ( 'away' === $venue ) {
                    echo '<span style="color: #1565c0; font-weight: 600;">&#9679; ' . esc_html__( 'Away', 'ddcwwfcsc' ) . '</span>';
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;

            case 'match_date':
                $date = get_post_meta( $post_id, '_ddcwwfcsc_match_date', true );
                if ( $date ) {
                    $timestamp = strtotime( $date );
                    echo esc_html( wp_date( 'j M Y, H:i', $timestamp ) );
                } else {
                    echo '—';
                }
                break;

            case 'on_sale':
                $on_sale = get_post_meta( $post_id, '_ddcwwfcsc_on_sale', true );
                if ( $on_sale ) {
                    echo '<span style="color: #00a32a; font-weight: bold;">&#9679; ' . esc_html__( 'On Sale', 'ddcwwfcsc' ) . '</span>';
                } else {
                    echo '<span style="color: #999;">' . esc_html__( 'Not on sale', 'ddcwwfcsc' ) . '</span>';
                }
                break;

            case 'remaining':
                $on_sale = get_post_meta( $post_id, '_ddcwwfcsc_on_sale', true );
                if ( $on_sale ) {
                    $remaining = get_post_meta( $post_id, '_ddcwwfcsc_tickets_remaining', true );
                    $total     = get_post_meta( $post_id, '_ddcwwfcsc_total_tickets', true );
                    echo esc_html( $remaining . ' / ' . $total );
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Define sortable columns.
     */
    public static function sortable_columns( $columns ) {
        $columns['match_date'] = 'match_date';
        return $columns;
    }

    /**
     * Handle sorting by match date in the admin list table.
     */
    public static function apply_admin_sorting( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || 'edit-ddcwwfcsc_fixture' !== $screen->id ) {
            return;
        }

        if ( isset( $_GET['orderby'] ) && 'match_date' === $_GET['orderby'] ) {
            $query->set( 'meta_key', '_ddcwwfcsc_match_date' );
            $query->set( 'orderby', 'meta_value' );
        }
    }

    /**
     * Render the season filter dropdown above the fixture list.
     *
     * WordPress automatically applies the tax_query when the select name matches
     * the taxonomy slug (its registered query_var).
     */
    public static function render_season_filter( $post_type ) {
        if ( 'ddcwwfcsc_fixture' !== $post_type ) {
            return;
        }

        $seasons = get_terms( array(
            'taxonomy'   => 'ddcwwfcsc_season',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'DESC',
        ) );

        if ( is_wp_error( $seasons ) || empty( $seasons ) ) {
            return;
        }

        $selected = isset( $_GET['ddcwwfcsc_season'] ) ? sanitize_text_field( $_GET['ddcwwfcsc_season'] ) : '';
        ?>
        <select name="ddcwwfcsc_season">
            <option value=""><?php esc_html_e( 'All seasons', 'ddcwwfcsc' ); ?></option>
            <?php foreach ( $seasons as $season ) : ?>
                <option value="<?php echo esc_attr( $season->slug ); ?>" <?php selected( $selected, $season->slug ); ?>>
                    <?php echo esc_html( $season->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render the "Tickets on sale" filter dropdown above the fixture list.
     */
    public static function render_on_sale_filter( $post_type ) {
        if ( 'ddcwwfcsc_fixture' !== $post_type ) {
            return;
        }

        $selected = isset( $_GET['ddcwwfcsc_on_sale_filter'] ) ? sanitize_key( $_GET['ddcwwfcsc_on_sale_filter'] ) : '';
        ?>
        <select name="ddcwwfcsc_on_sale_filter">
            <option value=""><?php esc_html_e( 'All fixtures', 'ddcwwfcsc' ); ?></option>
            <option value="1" <?php selected( $selected, '1' ); ?>><?php esc_html_e( 'Tickets on sale', 'ddcwwfcsc' ); ?></option>
            <option value="0" <?php selected( $selected, '0' ); ?>><?php esc_html_e( 'Not on sale', 'ddcwwfcsc' ); ?></option>
        </select>
        <?php
    }

    /**
     * Apply the "Tickets on sale" filter to the admin query.
     */
    public static function apply_on_sale_filter( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || 'edit-ddcwwfcsc_fixture' !== $screen->id ) {
            return;
        }

        if ( ! isset( $_GET['ddcwwfcsc_on_sale_filter'] ) || '' === $_GET['ddcwwfcsc_on_sale_filter'] ) {
            return;
        }

        $value = sanitize_key( $_GET['ddcwwfcsc_on_sale_filter'] );

        if ( '1' === $value ) {
            $query->set( 'meta_query', array(
                array(
                    'key'     => '_ddcwwfcsc_on_sale',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ) );
        } else {
            $query->set( 'meta_query', array(
                'relation' => 'OR',
                array(
                    'key'     => '_ddcwwfcsc_on_sale',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_ddcwwfcsc_on_sale',
                    'value'   => '1',
                    'compare' => '!=',
                ),
            ) );
        }
    }

}
