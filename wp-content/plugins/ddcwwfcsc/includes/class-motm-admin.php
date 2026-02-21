<?php
/**
 * MOTM admin — meta boxes and standings page.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_MOTM_Admin {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_ddcwwfcsc_fixture', array( __CLASS__, 'save_lineup_override' ) );
        add_action( 'admin_menu', array( __CLASS__, 'add_standings_page' ) );
        add_action( 'admin_post_ddcwwfcsc_fetch_lineup', array( __CLASS__, 'handle_fetch_lineup' ) );
    }

    /**
     * Register meta boxes on fixture edit screen.
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'ddcwwfcsc_motm_lineup',
            __( 'MOTM Lineup', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_lineup_meta_box' ),
            'ddcwwfcsc_fixture',
            'normal',
            'default'
        );

        add_meta_box(
            'ddcwwfcsc_motm_votes',
            __( 'MOTM Votes', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_votes_meta_box' ),
            'ddcwwfcsc_fixture',
            'side',
            'default'
        );
    }

    /**
     * Render the lineup meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_lineup_meta_box( $post ) {
        wp_nonce_field( 'ddcwwfcsc_motm_lineup', 'ddcwwfcsc_motm_lineup_nonce' );

        // Show fetch result notice.
        if ( isset( $_GET['motm_fetch'] ) ) {
            if ( 'ok' === $_GET['motm_fetch'] ) {
                echo '<div class="notice notice-success inline" style="margin:0 0 1em;"><p>' . esc_html__( 'Lineup fetched successfully from TheSportsDB.', 'ddcwwfcsc' ) . '</p></div>';
            } else {
                echo '<div class="notice notice-error inline" style="margin:0 0 1em;"><p>' . esc_html__( 'Lineup fetch failed. Check the error log for details, or enter the lineup manually below.', 'ddcwwfcsc' ) . '</p></div>';
            }
        }

        $api_lineup = get_post_meta( $post->ID, '_ddcwwfcsc_motm_lineup', true );
        $override   = get_post_meta( $post->ID, '_ddcwwfcsc_motm_lineup_override', true );
        $fetched    = get_post_meta( $post->ID, '_ddcwwfcsc_motm_lineup_fetched', true );
        $status     = get_post_meta( $post->ID, '_ddcwwfcsc_fd_status', true );

        // Show API lineup if available.
        if ( ! empty( $api_lineup ) && is_array( $api_lineup ) ) {
            echo '<h4 style="margin-top:0;">' . esc_html__( 'API Lineup', 'ddcwwfcsc' ) . '</h4>';
            echo '<table class="widefat fixed striped" style="margin-bottom:1em;">';
            echo '<thead><tr><th style="width:60px;">#</th><th>' . esc_html__( 'Player', 'ddcwwfcsc' ) . '</th><th style="width:80px;">' . esc_html__( 'Type', 'ddcwwfcsc' ) . '</th></tr></thead><tbody>';
            foreach ( $api_lineup as $player ) {
                printf(
                    '<tr><td>%d</td><td>%s</td><td>%s</td></tr>',
                    (int) $player['number'],
                    esc_html( $player['name'] ),
                    ! empty( $player['starter'] ) ? esc_html__( 'Starter', 'ddcwwfcsc' ) : esc_html__( 'Sub', 'ddcwwfcsc' )
                );
            }
            echo '</tbody></table>';
        } elseif ( 'FINISHED' === $status ) {
            if ( $fetched ) {
                echo '<p>' . esc_html__( 'API lineup fetch attempted but returned no data.', 'ddcwwfcsc' ) . '</p>';
            } else {
                echo '<p>' . esc_html__( 'Lineup not yet fetched from API. It will be fetched automatically, or enter manually below.', 'ddcwwfcsc' ) . '</p>';
            }
        } else {
            echo '<p>' . esc_html__( 'Lineup will be fetched automatically when this match finishes.', 'ddcwwfcsc' ) . '</p>';
        }

        // Fetch Lineup button — available for any FINISHED match (TheSportsDB needs no API key).
        if ( 'FINISHED' === $status ) {
            $fetch_url = wp_nonce_url(
                add_query_arg(
                    array(
                        'action'  => 'ddcwwfcsc_fetch_lineup',
                        'post_id' => $post->ID,
                    ),
                    admin_url( 'admin-post.php' )
                ),
                'ddcwwfcsc_fetch_lineup_' . $post->ID
            );
            printf(
                '<p><a href="%s" class="button button-secondary">%s</a></p>',
                esc_url( $fetch_url ),
                $fetched
                    ? esc_html__( 'Re-fetch Lineup from TheSportsDB', 'ddcwwfcsc' )
                    : esc_html__( 'Fetch Lineup Now', 'ddcwwfcsc' )
            );
        }

        // Manual override textarea.
        $override_text = '';
        if ( ! empty( $override ) && is_array( $override ) ) {
            $lines = array();
            foreach ( $override as $player ) {
                $lines[] = $player['number'] . ' ' . $player['name'];
            }
            $override_text = implode( "\n", $lines );
        }

        echo '<h4>' . esc_html__( 'Manual Override', 'ddcwwfcsc' ) . '</h4>';
        echo '<p class="description">' . esc_html__( 'One player per line: "{number} {name}". If filled, this overrides the API lineup for voting.', 'ddcwwfcsc' ) . '</p>';
        printf(
            '<textarea name="ddcwwfcsc_motm_lineup_override" rows="12" class="large-text" style="font-family:monospace;">%s</textarea>',
            esc_textarea( $override_text )
        );
    }

    /**
     * Save the manual lineup override.
     *
     * @param int $post_id Post ID.
     */
    public static function save_lineup_override( $post_id ) {
        if ( ! isset( $_POST['ddcwwfcsc_motm_lineup_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['ddcwwfcsc_motm_lineup_nonce'], 'ddcwwfcsc_motm_lineup' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $raw = isset( $_POST['ddcwwfcsc_motm_lineup_override'] ) ? sanitize_textarea_field( $_POST['ddcwwfcsc_motm_lineup_override'] ) : '';

        if ( empty( trim( $raw ) ) ) {
            delete_post_meta( $post_id, '_ddcwwfcsc_motm_lineup_override' );
        } else {
            $players = DDCWWFCSC_MOTM_Lineup::parse_manual_lineup( $raw );
            if ( ! empty( $players ) ) {
                $had_lineup = (bool) get_post_meta( $post_id, '_ddcwwfcsc_motm_lineup_override', true );
                update_post_meta( $post_id, '_ddcwwfcsc_motm_lineup_override', $players );

                // Fire the lineup-ready action if this is the first time a lineup
                // has been set for a finished match (so the vote reminder goes out).
                if ( ! $had_lineup && 'FINISHED' === get_post_meta( $post_id, '_ddcwwfcsc_fd_status', true ) ) {
                    do_action( 'ddcwwfcsc_motm_lineup_ready', $post_id );
                }
            }
        }
    }

    /**
     * Render the votes meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_votes_meta_box( $post ) {
        $tally = DDCWWFCSC_MOTM_Votes::get_tally( $post->ID );
        $total = DDCWWFCSC_MOTM_Votes::get_total_votes( $post->ID );

        if ( empty( $tally ) ) {
            echo '<p>' . esc_html__( 'No votes yet.', 'ddcwwfcsc' ) . '</p>';
            return;
        }

        // Voting status.
        if ( class_exists( 'DDCWWFCSC_MOTM_Front' ) && DDCWWFCSC_MOTM_Front::is_voting_open( $post->ID ) ) {
            echo '<p><strong>' . esc_html__( 'Voting is open', 'ddcwwfcsc' ) . '</strong></p>';
        } else {
            echo '<p><strong>' . esc_html__( 'Voting is closed', 'ddcwwfcsc' ) . '</strong></p>';
        }

        printf( '<p>' . esc_html__( 'Total votes: %d', 'ddcwwfcsc' ) . '</p>', $total );

        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>' . esc_html__( 'Player', 'ddcwwfcsc' ) . '</th><th style="width:60px;">' . esc_html__( 'Votes', 'ddcwwfcsc' ) . '</th></tr></thead><tbody>';
        foreach ( $tally as $row ) {
            printf(
                '<tr><td>%s</td><td>%d</td></tr>',
                esc_html( $row['player_name'] ),
                (int) $row['votes']
            );
        }
        echo '</tbody></table>';
    }

    /**
     * Handle the "Fetch Lineup Now" admin POST action.
     */
    public static function handle_fetch_lineup() {
        $post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( __( 'You do not have permission to do this.', 'ddcwwfcsc' ) );
        }

        check_admin_referer( 'ddcwwfcsc_fetch_lineup_' . $post_id );

        // Clear the fetched flag so it can re-fetch.
        delete_post_meta( $post_id, '_ddcwwfcsc_motm_lineup_fetched' );

        $success = DDCWWFCSC_MOTM_Lineup::fetch_and_store_lineup( $post_id );

        $redirect = add_query_arg(
            array( 'motm_fetch' => $success ? 'ok' : 'fail' ),
            get_edit_post_link( $post_id, 'raw' )
        );

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Add the MOTM Standings submenu page under Fixtures.
     */
    public static function add_standings_page() {
        add_submenu_page(
            'edit.php?post_type=ddcwwfcsc_fixture',
            __( 'MOTM Standings', 'ddcwwfcsc' ),
            __( 'MOTM Standings', 'ddcwwfcsc' ),
            'manage_ddcwwfcsc_fixtures',
            'ddcwwfcsc-motm-standings',
            array( __CLASS__, 'render_standings_page' )
        );
    }

    /**
     * Render the MOTM Standings admin page.
     */
    public static function render_standings_page() {
        $seasons = get_terms( array(
            'taxonomy'   => 'ddcwwfcsc_season',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'DESC',
        ) );

        if ( is_wp_error( $seasons ) ) {
            $seasons = array();
        }

        // Determine selected season.
        $selected_season = isset( $_GET['season'] ) ? absint( $_GET['season'] ) : 0;
        if ( ! $selected_season && ! empty( $seasons ) ) {
            $selected_season = $seasons[0]->term_id;
        }

        $standings = array();
        if ( $selected_season ) {
            $standings = DDCWWFCSC_MOTM_Votes::get_season_standings( $selected_season );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Man of the Match Standings', 'ddcwwfcsc' ); ?></h1>

            <?php if ( ! empty( $seasons ) ) : ?>
                <form method="get" style="margin-bottom:1em;">
                    <input type="hidden" name="post_type" value="ddcwwfcsc_fixture">
                    <input type="hidden" name="page" value="ddcwwfcsc-motm-standings">
                    <label for="season"><strong><?php esc_html_e( 'Season:', 'ddcwwfcsc' ); ?></strong></label>
                    <select name="season" id="season" onchange="this.form.submit()">
                        <?php foreach ( $seasons as $season ) : ?>
                            <option value="<?php echo esc_attr( $season->term_id ); ?>" <?php selected( $selected_season, $season->term_id ); ?>>
                                <?php echo esc_html( $season->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php else : ?>
                <p><?php esc_html_e( 'No seasons found.', 'ddcwwfcsc' ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $standings ) ) : ?>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width:50px;"><?php esc_html_e( '#', 'ddcwwfcsc' ); ?></th>
                            <th><?php esc_html_e( 'Player', 'ddcwwfcsc' ); ?></th>
                            <th style="width:100px;"><?php esc_html_e( 'MOTM Votes', 'ddcwwfcsc' ); ?></th>
                            <th style="width:120px;"><?php esc_html_e( 'Fixtures Voted', 'ddcwwfcsc' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rank = 1;
                        foreach ( $standings as $row ) :
                        ?>
                            <tr>
                                <td><?php echo esc_html( $rank++ ); ?></td>
                                <td><?php echo esc_html( $row['player_name'] ); ?></td>
                                <td><?php echo esc_html( $row['total_votes'] ); ?></td>
                                <td><?php echo esc_html( $row['fixtures_voted'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ( $selected_season ) : ?>
                <p><?php esc_html_e( 'No MOTM votes recorded for this season yet.', 'ddcwwfcsc' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}
