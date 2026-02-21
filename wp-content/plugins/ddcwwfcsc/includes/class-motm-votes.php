<?php
/**
 * MOTM votes data layer — pure query helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_MOTM_Votes {

    /**
     * Get the votes table name.
     *
     * @return string
     */
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'ddcwwfcsc_motm_votes';
    }

    /**
     * Cast a vote. Uses INSERT IGNORE so the unique constraint silently rejects duplicates.
     *
     * @param int    $fixture_id Post ID.
     * @param int    $user_id    WP user ID.
     * @param string $player     Player name.
     * @return bool True if inserted, false if duplicate or error.
     */
    public static function cast_vote( $fixture_id, $user_id, $player ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->query( $wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->prefix}ddcwwfcsc_motm_votes
                (fixture_id, user_id, player_name, voted_at)
             VALUES (%d, %d, %s, %s)",
            $fixture_id,
            $user_id,
            $player,
            current_time( 'mysql', true )
        ) );

        return 1 === $result;
    }

    /**
     * Get the current user's vote for a fixture.
     *
     * @param int $fixture_id Post ID.
     * @param int $user_id    WP user ID.
     * @return string|null Player name or null.
     */
    public static function get_user_vote( $fixture_id, $user_id ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_var( $wpdb->prepare(
            "SELECT player_name FROM {$wpdb->prefix}ddcwwfcsc_motm_votes
             WHERE fixture_id = %d AND user_id = %d",
            $fixture_id,
            $user_id
        ) );
    }

    /**
     * Get vote tally for a fixture, ordered by count descending.
     *
     * @param int $fixture_id Post ID.
     * @return array [ [ 'player_name' => string, 'votes' => int ], ... ]
     */
    public static function get_tally( $fixture_id ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT player_name, COUNT(*) AS votes
             FROM {$wpdb->prefix}ddcwwfcsc_motm_votes
             WHERE fixture_id = %d
             GROUP BY player_name
             ORDER BY votes DESC, player_name ASC",
            $fixture_id
        ), ARRAY_A );
    }

    /**
     * Get total vote count for a fixture.
     *
     * @param int $fixture_id Post ID.
     * @return int
     */
    public static function get_total_votes( $fixture_id ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ddcwwfcsc_motm_votes
             WHERE fixture_id = %d",
            $fixture_id
        ) );
    }

    /**
     * Get season standings — total MOTM votes per player across all fixtures in a season.
     *
     * @param int $season_term_id Season taxonomy term ID.
     * @return array [ [ 'player_name' => string, 'total_votes' => int, 'fixtures_voted' => int ], ... ]
     */
    public static function get_season_standings( $season_term_id ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT v.player_name,
                    COUNT(*) AS total_votes,
                    COUNT(DISTINCT v.fixture_id) AS fixtures_voted
             FROM {$wpdb->prefix}ddcwwfcsc_motm_votes v
             INNER JOIN {$wpdb->term_relationships} tr
                ON tr.object_id = v.fixture_id
             WHERE tr.term_taxonomy_id = (
                SELECT tt.term_taxonomy_id
                FROM {$wpdb->term_taxonomy} tt
                WHERE tt.term_id = %d AND tt.taxonomy = 'ddcwwfcsc_season'
             )
             GROUP BY v.player_name
             ORDER BY total_votes DESC, v.player_name ASC",
            $season_term_id
        ), ARRAY_A );
    }
}
