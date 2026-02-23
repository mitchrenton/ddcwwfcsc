<?php
/**
 * MOTM admin — meta boxes and standings page.
 *
 * @package DDCWWFCSC
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_MOTM_Admin {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'add_meta_boxes',               array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_ddcwwfcsc_fixture',  array( __CLASS__, 'save_lineup' ) );
        add_action( 'admin_menu',                   array( __CLASS__, 'add_standings_page' ) );
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
     * Render the squad picker lineup meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_lineup_meta_box( $post ) {
        wp_nonce_field( 'ddcwwfcsc_motm_lineup', 'ddcwwfcsc_motm_lineup_nonce' );

        $status     = get_post_meta( $post->ID, '_ddcwwfcsc_fd_status', true );
        $lineup_ids = get_post_meta( $post->ID, '_ddcwwfcsc_motm_lineup_ids', true );
        $lineup_ids = is_array( $lineup_ids ) ? $lineup_ids : array();

        // Build a keyed lookup: player_id => 'starter' | 'sub'.
        $selection = array();
        foreach ( $lineup_ids as $entry ) {
            $pid = absint( $entry['player_id'] ?? 0 );
            if ( $pid ) {
                $selection[ $pid ] = (bool) ( $entry['starter'] ?? false ) ? 'starter' : 'sub';
            }
        }

        $squad = DDCWWFCSC_Player_CPT::get_squad();

        if ( empty( $squad ) ) {
            echo '<p>' . esc_html__( 'No players in the squad yet. Add players under Fixtures → Squad.', 'ddcwwfcsc' ) . '</p>';
            return;
        }

        if ( 'FINISHED' !== $status ) {
            echo '<p class="description">' . esc_html__( 'You can pre-select the lineup before the match, or fill it in once the match is finished.', 'ddcwwfcsc' ) . '</p>';
        }
        ?>
        <p class="description" style="margin-bottom:0.75em;">
            <?php esc_html_e( 'Select Starter or Sub for each player who appeared. Leave blank for players who did not play. You can include more than 11 to cover all substitutes used.', 'ddcwwfcsc' ); ?>
        </p>

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:40px;"><?php esc_html_e( '#', 'ddcwwfcsc' ); ?></th>
                    <th><?php esc_html_e( 'Player', 'ddcwwfcsc' ); ?></th>
                    <th style="width:50px;"><?php esc_html_e( 'Pos', 'ddcwwfcsc' ); ?></th>
                    <th style="width:60px; text-align:center;">—</th>
                    <th style="width:70px; text-align:center;"><?php esc_html_e( 'Starter', 'ddcwwfcsc' ); ?></th>
                    <th style="width:60px; text-align:center;"><?php esc_html_e( 'Sub', 'ddcwwfcsc' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $squad as $player ) :
                    $pid      = $player->ID;
                    $data     = DDCWWFCSC_Player_CPT::get_player_data( $pid );
                    $selected = $selection[ $pid ] ?? '';
                ?>
                    <tr>
                        <td><?php echo esc_html( $data['number'] ?: '—' ); ?></td>
                        <td><?php echo esc_html( $data['name'] ); ?></td>
                        <td><?php echo esc_html( $data['position'] ); ?></td>
                        <td style="text-align:center;">
                            <input type="radio"
                                   name="ddcwwfcsc_lineup[<?php echo esc_attr( $pid ); ?>]"
                                   value=""
                                   <?php checked( $selected, '' ); ?>>
                        </td>
                        <td style="text-align:center;">
                            <input type="radio"
                                   name="ddcwwfcsc_lineup[<?php echo esc_attr( $pid ); ?>]"
                                   value="starter"
                                   <?php checked( $selected, 'starter' ); ?>>
                        </td>
                        <td style="text-align:center;">
                            <input type="radio"
                                   name="ddcwwfcsc_lineup[<?php echo esc_attr( $pid ); ?>]"
                                   value="sub"
                                   <?php checked( $selected, 'sub' ); ?>>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Save the squad-based lineup selection.
     *
     * @param int $post_id Post ID.
     */
    public static function save_lineup( $post_id ) {
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

        $raw     = isset( $_POST['ddcwwfcsc_lineup'] ) ? (array) $_POST['ddcwwfcsc_lineup'] : array();
        $entries = array();

        foreach ( $raw as $player_id => $role ) {
            $player_id = absint( $player_id );
            $role      = sanitize_key( $role ); // 'starter', 'sub', or ''

            if ( ! $player_id || ! in_array( $role, array( 'starter', 'sub' ), true ) ) {
                continue;
            }

            // Confirm it's actually a player post.
            if ( 'ddcwwfcsc_player' !== get_post_type( $player_id ) ) {
                continue;
            }

            $entries[] = array(
                'player_id' => $player_id,
                'starter'   => ( 'starter' === $role ),
            );
        }

        $had_lineup = ! empty( get_post_meta( $post_id, '_ddcwwfcsc_motm_lineup_ids', true ) );

        if ( empty( $entries ) ) {
            delete_post_meta( $post_id, '_ddcwwfcsc_motm_lineup_ids' );
        } else {
            update_post_meta( $post_id, '_ddcwwfcsc_motm_lineup_ids', $entries );
        }

        // Fire vote reminder on first lineup save for a finished fixture.
        if (
            ! empty( $entries ) &&
            ! $had_lineup &&
            'FINISHED' === get_post_meta( $post_id, '_ddcwwfcsc_fd_status', true )
        ) {
            do_action( 'ddcwwfcsc_motm_lineup_ready', $post_id );
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
