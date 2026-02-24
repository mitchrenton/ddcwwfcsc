<?php
/**
 * MOTM front-end rendering and AJAX vote handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_MOTM_Front {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'wp_ajax_ddcwwfcsc_motm_vote', array( __CLASS__, 'handle_vote' ) );
        add_action( 'ddcwwfcsc_motm_lineup_ready', array( __CLASS__, 'send_vote_reminder' ) );
        add_filter( 'ddcwwfcsc_bulletin_extra_texts', array( __CLASS__, 'inject_motm_ticker_item' ) );
    }

    /**
     * Find the most recent fixture that currently has voting open.
     *
     * @return int|null Fixture post ID or null if none.
     */
    public static function get_open_vote_fixture() {
        $fixtures = get_posts( array(
            'post_type'      => 'ddcwwfcsc_fixture',
            'post_status'    => 'publish',
            'posts_per_page' => 5,
            'meta_key'       => '_ddcwwfcsc_match_date',
            'orderby'        => 'meta_value',
            'order'          => 'DESC',
            'meta_query'     => array( array(
                'key'   => '_ddcwwfcsc_fd_status',
                'value' => 'FINISHED',
            ) ),
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );

        foreach ( $fixtures as $fixture_id ) {
            if ( self::is_voting_open( $fixture_id ) ) {
                return $fixture_id;
            }
        }

        return null;
    }

    /**
     * Inject a MOTM voting prompt into the bulletin ticker for logged-in members
     * who have not yet voted on the currently open fixture.
     *
     * @param string[] $items Existing extra ticker text items.
     * @return string[]
     */
    public static function inject_motm_ticker_item( $items ) {
        if ( ! is_user_logged_in() ) {
            return $items;
        }

        $fixture_id = self::get_open_vote_fixture();
        if ( ! $fixture_id ) {
            return $items;
        }

        $user_vote = DDCWWFCSC_MOTM_Votes::get_user_vote( $fixture_id, get_current_user_id() );
        if ( $user_vote ) {
            return $items;
        }

        $opponent      = class_exists( 'DDCWWFCSC_Fixture_CPT' ) ? DDCWWFCSC_Fixture_CPT::get_opponent( $fixture_id ) : null;
        $opponent_name = $opponent ? $opponent['name'] : get_the_title( $fixture_id );

        $items[] = sprintf(
            /* translators: %s: opponent name */
            __( 'Man of the Match — vote now for Wolves v %s', 'ddcwwfcsc' ),
            $opponent_name
        );

        return $items;
    }

    /**
     * Enqueue front-end assets on singular fixture pages.
     */
    public static function enqueue_assets() {
        if ( ! is_singular( 'ddcwwfcsc_fixture' ) ) {
            return;
        }

        wp_enqueue_style(
            'ddcwwfcsc-motm-front',
            DDCWWFCSC_PLUGIN_URL . 'assets/css/motm-front.css',
            array(),
            DDCWWFCSC_VERSION
        );

        if ( is_user_logged_in() ) {
            wp_enqueue_script(
                'ddcwwfcsc-motm-vote',
                DDCWWFCSC_PLUGIN_URL . 'assets/js/motm-vote.js',
                array(),
                DDCWWFCSC_VERSION,
                true
            );

            wp_localize_script( 'ddcwwfcsc-motm-vote', 'ddcwwfcsc_motm', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'ddcwwfcsc_motm_vote' ),
            ) );
        }
    }

    /**
     * Check if voting is currently open for a fixture.
     *
     * @param int $post_id Fixture post ID.
     * @return bool
     */
    public static function is_voting_open( $post_id ) {
        // Must be finished.
        $status = get_post_meta( $post_id, '_ddcwwfcsc_fd_status', true );
        if ( 'FINISHED' !== $status ) {
            return false;
        }

        // Must have a lineup.
        $lineup = DDCWWFCSC_MOTM_Lineup::get_lineup( $post_id );
        if ( empty( $lineup ) ) {
            return false;
        }

        // Must be before the next fixture's kick-off.
        $next_kickoff = self::get_next_fixture_kickoff( $post_id );
        if ( $next_kickoff && current_time( 'timestamp' ) >= $next_kickoff ) {
            return false;
        }

        return true;
    }

    /**
     * Get the kick-off timestamp of the next fixture after this one.
     *
     * @param int $post_id Current fixture post ID.
     * @return int|null Unix timestamp or null if no next fixture.
     */
    private static function get_next_fixture_kickoff( $post_id ) {
        $match_date = get_post_meta( $post_id, '_ddcwwfcsc_match_date', true );
        if ( empty( $match_date ) ) {
            return null;
        }

        $query = new WP_Query( array(
            'post_type'      => 'ddcwwfcsc_fixture',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_key'       => '_ddcwwfcsc_match_date',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => '_ddcwwfcsc_match_date',
                    'value'   => $match_date,
                    'compare' => '>',
                    'type'    => 'CHAR',
                ),
            ),
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );

        if ( empty( $query->posts ) ) {
            return null;
        }

        $next_date = get_post_meta( $query->posts[0], '_ddcwwfcsc_match_date', true );
        return $next_date ? strtotime( $next_date ) : null;
    }

    /**
     * Render the voting section for a fixture single page.
     * Called from the theme template.
     *
     * @param int $post_id Fixture post ID.
     * @return string HTML output.
     */
    public static function render_voting_section( $post_id ) {
        $status = get_post_meta( $post_id, '_ddcwwfcsc_fd_status', true );

        // Don't render anything if match isn't finished.
        if ( 'FINISHED' !== $status ) {
            return '';
        }

        $lineup       = DDCWWFCSC_MOTM_Lineup::get_lineup( $post_id );
        $voting_open  = self::is_voting_open( $post_id );
        $is_logged_in = is_user_logged_in();
        $user_vote    = $is_logged_in ? DDCWWFCSC_MOTM_Votes::get_user_vote( $post_id, get_current_user_id() ) : null;

        ob_start();
        ?>
        <section class="ddcwwfcsc-motm-section">
            <h2><?php esc_html_e( 'Man of the Match', 'ddcwwfcsc' ); ?></h2>

            <?php if ( ! $is_logged_in ) : ?>
                <p class="ddcwwfcsc-motm-login-prompt">
                    <?php printf(
                        /* translators: %s: login URL */
                        wp_kses_post( __( '<a href="%s">Log in</a> to vote for Man of the Match.', 'ddcwwfcsc' ) ),
                        esc_url( wp_login_url( get_permalink( $post_id ) ) )
                    ); ?>
                </p>

            <?php elseif ( empty( $lineup ) ) : ?>
                <p class="ddcwwfcsc-motm-pending">
                    <?php esc_html_e( 'Lineup not yet available — voting will open shortly.', 'ddcwwfcsc' ); ?>
                </p>

            <?php elseif ( $voting_open && ! $user_vote ) : ?>
                <?php echo self::render_vote_form( $post_id, $lineup ); ?>

            <?php elseif ( $voting_open && $user_vote ) : ?>
                <p class="ddcwwfcsc-motm-voted">
                    <?php printf(
                        /* translators: %s: player name */
                        esc_html__( 'You voted for %s', 'ddcwwfcsc' ),
                        '<strong>' . esc_html( $user_vote ) . '</strong>'
                    ); ?>
                </p>
                <?php echo self::render_tally( $post_id ); ?>

            <?php else : ?>
                <?php // Voting closed — show results. ?>
                <?php echo self::render_tally( $post_id ); ?>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the vote form with player radio buttons.
     *
     * @param int   $post_id Fixture post ID.
     * @param array $lineup  Player data array.
     * @return string HTML.
     */
    private static function render_vote_form( $post_id, $lineup ) {
        // Separate starters and subs, sort by number.
        $starters = array();
        $subs     = array();

        foreach ( $lineup as $player ) {
            if ( ! empty( $player['starter'] ) ) {
                $starters[] = $player;
            } else {
                $subs[] = $player;
            }
        }

        usort( $starters, function ( $a, $b ) { return $a['number'] - $b['number']; } );
        usort( $subs, function ( $a, $b ) { return $a['number'] - $b['number']; } );

        ob_start();
        ?>
        <form class="ddcwwfcsc-motm-form" data-fixture-id="<?php echo esc_attr( $post_id ); ?>">
            <fieldset>
                <legend><?php esc_html_e( 'Select your Man of the Match:', 'ddcwwfcsc' ); ?></legend>

                <?php if ( ! empty( $starters ) ) : ?>
                    <div class="ddcwwfcsc-motm-group">
                        <span class="ddcwwfcsc-motm-group-label"><?php esc_html_e( 'Starting XI', 'ddcwwfcsc' ); ?></span>
                        <?php foreach ( $starters as $player ) : ?>
                            <label class="ddcwwfcsc-motm-player">
                                <input type="radio" name="motm_player" value="<?php echo esc_attr( $player['name'] ); ?>" required>
                                <span class="ddcwwfcsc-motm-player-number"><?php echo esc_html( $player['number'] ); ?></span>
                                <span class="ddcwwfcsc-motm-player-name"><?php echo esc_html( $player['name'] ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $subs ) ) : ?>
                    <div class="ddcwwfcsc-motm-group">
                        <span class="ddcwwfcsc-motm-group-label"><?php esc_html_e( 'Substitutes', 'ddcwwfcsc' ); ?></span>
                        <?php foreach ( $subs as $player ) : ?>
                            <label class="ddcwwfcsc-motm-player">
                                <input type="radio" name="motm_player" value="<?php echo esc_attr( $player['name'] ); ?>" required>
                                <span class="ddcwwfcsc-motm-player-number"><?php echo esc_html( $player['number'] ); ?></span>
                                <span class="ddcwwfcsc-motm-player-name"><?php echo esc_html( $player['name'] ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </fieldset>

            <button type="submit" class="ddcwwfcsc-motm-submit"><?php esc_html_e( 'Submit Vote', 'ddcwwfcsc' ); ?></button>
            <div class="ddcwwfcsc-motm-message"></div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the vote tally with percentage bars.
     *
     * @param int $post_id Fixture post ID.
     * @return string HTML.
     */
    private static function render_tally( $post_id ) {
        $tally = DDCWWFCSC_MOTM_Votes::get_tally( $post_id );
        $total = DDCWWFCSC_MOTM_Votes::get_total_votes( $post_id );

        if ( empty( $tally ) ) {
            return '<p class="ddcwwfcsc-motm-no-votes">' . esc_html__( 'No votes yet.', 'ddcwwfcsc' ) . '</p>';
        }

        $max_votes = (int) $tally[0]['votes'];

        ob_start();
        ?>
        <div class="ddcwwfcsc-motm-tally">
            <?php foreach ( $tally as $index => $row ) :
                $pct = $total > 0 ? round( ( (int) $row['votes'] / $total ) * 100 ) : 0;
                $is_winner = 0 === $index;
            ?>
                <div class="ddcwwfcsc-motm-tally-row<?php echo $is_winner ? ' ddcwwfcsc-motm-tally-row--winner' : ''; ?>">
                    <div class="ddcwwfcsc-motm-tally-info">
                        <span class="ddcwwfcsc-motm-tally-name">
                            <?php echo esc_html( $row['player_name'] ); ?>
                            <?php if ( $is_winner ) : ?>
                                <span class="ddcwwfcsc-motm-winner-badge"><?php esc_html_e( 'MOTM', 'ddcwwfcsc' ); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="ddcwwfcsc-motm-tally-count"><?php echo esc_html( $row['votes'] ); ?> (<?php echo esc_html( $pct ); ?>%)</span>
                    </div>
                    <div class="ddcwwfcsc-motm-tally-bar">
                        <div class="ddcwwfcsc-motm-tally-fill" style="width:<?php echo esc_attr( $pct ); ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>

            <p class="ddcwwfcsc-motm-total">
                <?php printf( esc_html__( 'Total votes: %d', 'ddcwwfcsc' ), $total ); ?>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Send a vote reminder email to all members when a lineup becomes available.
     * Fires on the ddcwwfcsc_motm_lineup_ready action.
     * Guarded by a post meta flag so it only sends once per fixture.
     *
     * @param int $post_id Fixture post ID.
     */
    public static function send_vote_reminder( $post_id ) {
        // Only send once.
        if ( get_post_meta( $post_id, '_ddcwwfcsc_motm_vote_reminder_sent', true ) ) {
            return;
        }

        $members = get_users( array( 'role' => 'ddcwwfcsc_member' ) );
        if ( empty( $members ) ) {
            return;
        }

        $fixture_title = get_the_title( $post_id );
        $fixture_url   = get_permalink( $post_id );

        $subject = sprintf(
            /* translators: %s: fixture title */
            __( 'Vote for Man of the Match — %s', 'ddcwwfcsc' ),
            $fixture_title
        );

        $template_path = DDCWWFCSC_PLUGIN_DIR . 'templates/emails/motm-vote-reminder.php';

        foreach ( $members as $member ) {
            ob_start();
            if ( file_exists( $template_path ) ) {
                include $template_path;
            }
            $body = ob_get_clean();

            if ( $body ) {
                wp_mail(
                    $member->user_email,
                    $subject,
                    $body,
                    array( 'Content-Type: text/html; charset=UTF-8' )
                );
            }
        }

        update_post_meta( $post_id, '_ddcwwfcsc_motm_vote_reminder_sent', true );

        error_log( sprintf(
            '[DDCWWFCSC MOTM] Vote reminder sent for fixture %d to %d members.',
            $post_id,
            count( $members )
        ) );
    }

    /**
     * Handle the MOTM vote AJAX request.
     */
    public static function handle_vote() {
        check_ajax_referer( 'ddcwwfcsc_motm_vote', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in to vote.', 'ddcwwfcsc' ) ) );
        }

        $fixture_id = isset( $_POST['fixture_id'] ) ? absint( $_POST['fixture_id'] ) : 0;
        $player     = isset( $_POST['player'] ) ? sanitize_text_field( $_POST['player'] ) : '';

        if ( ! $fixture_id || 'ddcwwfcsc_fixture' !== get_post_type( $fixture_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid fixture.', 'ddcwwfcsc' ) ) );
        }

        if ( empty( $player ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select a player.', 'ddcwwfcsc' ) ) );
        }

        if ( ! self::is_voting_open( $fixture_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Voting is closed for this fixture.', 'ddcwwfcsc' ) ) );
        }

        // Validate player is in the lineup.
        $lineup = DDCWWFCSC_MOTM_Lineup::get_lineup( $fixture_id );
        $valid  = false;
        foreach ( $lineup as $p ) {
            if ( $p['name'] === $player ) {
                $valid = true;
                break;
            }
        }

        if ( ! $valid ) {
            wp_send_json_error( array( 'message' => __( 'Invalid player selection.', 'ddcwwfcsc' ) ) );
        }

        $user_id = get_current_user_id();
        $result  = DDCWWFCSC_MOTM_Votes::cast_vote( $fixture_id, $user_id, $player );

        if ( ! $result ) {
            wp_send_json_error( array( 'message' => __( 'You have already voted for this fixture.', 'ddcwwfcsc' ) ) );
        }

        // Return the updated tally HTML.
        $tally_html = self::render_tally( $fixture_id );

        wp_send_json_success( array(
            'message'    => sprintf(
                /* translators: %s: player name */
                __( 'You voted for %s', 'ddcwwfcsc' ),
                $player
            ),
            'tally_html' => $tally_html,
            'player'     => $player,
        ) );
    }
}
