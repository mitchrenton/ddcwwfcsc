<?php
/**
 * TheSportsDB integration for MOTM lineups.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_MOTM_Lineup {

    /**
     * TheSportsDB free API base (key '123' is the documented free key).
     */
    const API_BASE = 'https://www.thesportsdb.com/api/v1/json/123';

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'ddcwwfcsc_fetch_motm_lineup', array( __CLASS__, 'handle_scheduled_fetch' ) );
    }

    /**
     * Schedule a delayed lineup fetch for a fixture.
     * Called from fixture sync after a match becomes FINISHED.
     *
     * @param int $post_id Fixture post ID.
     */
    public static function maybe_fetch_lineup( $post_id ) {
        $already_fetched = get_post_meta( $post_id, '_ddcwwfcsc_motm_lineup_fetched', true );
        if ( $already_fetched ) {
            return;
        }

        // TheSportsDB data is community-sourced — only attempt within 7 days
        // of the match (older matches are unlikely to get community entries).
        $match_date = get_post_meta( $post_id, '_ddcwwfcsc_match_date', true );
        if ( $match_date ) {
            $match_ts = strtotime( $match_date );
            if ( $match_ts && $match_ts < strtotime( '-7 days' ) ) {
                update_post_meta( $post_id, '_ddcwwfcsc_motm_lineup_fetched', 'skipped' );
                return;
            }
        }

        // Schedule a single event 30 seconds from now to avoid blocking the sync.
        if ( ! wp_next_scheduled( 'ddcwwfcsc_fetch_motm_lineup', array( $post_id ) ) ) {
            wp_schedule_single_event( time() + 30, 'ddcwwfcsc_fetch_motm_lineup', array( $post_id ) );
        }
    }

    /**
     * Handle the scheduled lineup fetch.
     *
     * @param int $post_id Fixture post ID.
     */
    public static function handle_scheduled_fetch( $post_id ) {
        $post_id = absint( $post_id );
        if ( ! $post_id || 'ddcwwfcsc_fixture' !== get_post_type( $post_id ) ) {
            return;
        }

        // Don't re-fetch.
        if ( get_post_meta( $post_id, '_ddcwwfcsc_motm_lineup_fetched', true ) ) {
            return;
        }

        self::fetch_and_store_lineup( $post_id );
    }

    /**
     * Fetch lineup from TheSportsDB and store as post meta.
     *
     * @param int $post_id Fixture post ID.
     * @return bool True on success.
     */
    public static function fetch_and_store_lineup( $post_id ) {
        // Step 1: Get the TheSportsDB event ID for this fixture.
        $tsdb_event_id = self::resolve_tsdb_event_id( $post_id );
        if ( ! $tsdb_event_id ) {
            return false;
        }

        // Step 2: Fetch lineups.
        $lineup = self::fetch_lineup( $tsdb_event_id );
        if ( empty( $lineup ) ) {
            return false;
        }

        // Store lineup and mark as fetched.
        update_post_meta( $post_id, '_ddcwwfcsc_motm_lineup', $lineup );
        update_post_meta( $post_id, '_ddcwwfcsc_motm_lineup_fetched', true );

        error_log( sprintf(
            '[DDCWWFCSC MOTM] Lineup fetched for fixture %d — %d players.',
            $post_id,
            count( $lineup )
        ) );

        do_action( 'ddcwwfcsc_motm_lineup_ready', $post_id );

        return true;
    }

    /**
     * Resolve the TheSportsDB event ID for a fixture by matching date + team name.
     *
     * @param int $post_id Fixture post ID.
     * @return int|null TheSportsDB event ID, or null if not found.
     */
    private static function resolve_tsdb_event_id( $post_id ) {
        // Return cached value if already resolved.
        $stored = get_post_meta( $post_id, '_ddcwwfcsc_tsdb_event_id', true );
        if ( $stored ) {
            return (int) $stored;
        }

        $match_date = get_post_meta( $post_id, '_ddcwwfcsc_match_date', true );
        if ( empty( $match_date ) ) {
            error_log( '[DDCWWFCSC MOTM] No match date for fixture ' . $post_id );
            return null;
        }

        $date = substr( $match_date, 0, 10 );
        $url  = self::API_BASE . '/eventsday.php?d=' . rawurlencode( $date ) . '&l=English+Premier+League';

        error_log( '[DDCWWFCSC MOTM] TheSportsDB event lookup: ' . $url );

        $response = self::tsdb_request( $url );
        if ( null === $response ) {
            return null;
        }

        $events = $response['events'] ?? null;
        if ( empty( $events ) ) {
            error_log( sprintf( '[DDCWWFCSC MOTM] No Premier League events found on %s.', $date ) );
            return null;
        }

        // Find the Wolves match by team name.
        $team_name = get_option( 'ddcwwfcsc_tsdb_team_name', 'Wolverhampton Wanderers' );
        $event_id  = null;

        foreach ( $events as $event ) {
            $home = $event['strHomeTeam'] ?? '';
            $away = $event['strAwayTeam'] ?? '';

            if (
                stripos( $home, $team_name ) !== false ||
                stripos( $away, $team_name ) !== false ||
                stripos( $home, 'Wolves' ) !== false ||
                stripos( $away, 'Wolves' ) !== false
            ) {
                $event_id = (int) ( $event['idEvent'] ?? 0 );
                break;
            }
        }

        if ( ! $event_id ) {
            error_log( sprintf( '[DDCWWFCSC MOTM] Wolves not found in Premier League events for %s.', $date ) );
            return null;
        }

        // Cache the resolved event ID on the fixture for future fetches.
        update_post_meta( $post_id, '_ddcwwfcsc_tsdb_event_id', $event_id );

        error_log( '[DDCWWFCSC MOTM] Resolved TheSportsDB event ID: ' . $event_id );

        return $event_id;
    }

    /**
     * Fetch the Wolves lineup for a given TheSportsDB event.
     *
     * @param int $tsdb_event_id TheSportsDB event ID.
     * @return array Array of [ name, number, starter ] entries, or empty.
     */
    private static function fetch_lineup( $tsdb_event_id ) {
        $url = self::API_BASE . '/lookuplineup.php?id=' . absint( $tsdb_event_id );

        $response = self::tsdb_request( $url );
        if ( null === $response ) {
            return array();
        }

        $entries = $response['lineup'] ?? null;
        if ( empty( $entries ) ) {
            error_log( '[DDCWWFCSC MOTM] No lineup data in TheSportsDB for event ' . $tsdb_event_id . '. Community data may not have been entered yet.' );
            return array();
        }

        error_log( '[DDCWWFCSC MOTM] Raw lineup entries: ' . count( $entries ) );

        // Identify the exact team name TheSportsDB uses for Wolves by inspecting
        // all distinct non-empty strTeam values in this lineup response.
        $team_name_setting = get_option( 'ddcwwfcsc_tsdb_team_name', 'Wolverhampton Wanderers' );
        $distinct_teams    = array_values( array_unique( array_filter( array_column( $entries, 'strTeam' ) ) ) );

        error_log( '[DDCWWFCSC MOTM] Teams found in lineup: ' . implode( ' | ', $distinct_teams ) );

        $wolves_team = null;
        foreach ( $distinct_teams as $tn ) {
            if (
                stripos( $tn, $team_name_setting ) !== false ||
                stripos( $tn, 'Wolverhampton' ) !== false ||
                stripos( $tn, 'Wolves' ) !== false
            ) {
                $wolves_team = $tn;
                break;
            }
        }

        if ( ! $wolves_team ) {
            error_log( '[DDCWWFCSC MOTM] Could not identify Wolves in lineup — teams: ' . implode( ', ', $distinct_teams ) );
            return array();
        }

        $players = array();

        foreach ( $entries as $entry ) {
            if ( ( $entry['strTeam'] ?? '' ) !== $wolves_team ) {
                continue;
            }

            $name    = sanitize_text_field( $entry['strPlayer'] ?? '' );
            $number  = absint( $entry['intSquadNumber'] ?? 0 );
            $starter = 'Yes' !== ( $entry['strSubstitute'] ?? 'No' );

            if ( $name ) {
                $players[] = array(
                    'name'    => $name,
                    'number'  => $number,
                    'starter' => $starter,
                );
            }
        }

        return $players;
    }

    /**
     * Make a TheSportsDB API request.
     * No authentication required — free tier uses key '123' in the URL path.
     *
     * @param string $url Full API URL.
     * @return array|null Decoded response body, or null on error.
     */
    private static function tsdb_request( $url ) {
        $response = wp_remote_get( $url, array( 'timeout' => 15 ) );

        if ( is_wp_error( $response ) ) {
            error_log( '[DDCWWFCSC MOTM] HTTP error: ' . $response->get_error_message() );
            return null;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $raw_body    = wp_remote_retrieve_body( $response );

        if ( 200 !== $status_code ) {
            error_log( '[DDCWWFCSC MOTM] TheSportsDB returned HTTP ' . $status_code );
            return null;
        }

        $body = json_decode( $raw_body, true );
        if ( ! is_array( $body ) ) {
            error_log( '[DDCWWFCSC MOTM] Invalid JSON from TheSportsDB: ' . substr( $raw_body, 0, 200 ) );
            return null;
        }

        return $body;
    }

    /**
     * Get the lineup for a fixture (API-fetched or manual override).
     *
     * @param int $post_id Fixture post ID.
     * @return array Array of player data, or empty.
     */
    public static function get_lineup( $post_id ) {
        // Manual override takes priority.
        $override = get_post_meta( $post_id, '_ddcwwfcsc_motm_lineup_override', true );
        if ( ! empty( $override ) && is_array( $override ) ) {
            return $override;
        }

        $lineup = get_post_meta( $post_id, '_ddcwwfcsc_motm_lineup', true );
        return is_array( $lineup ) ? $lineup : array();
    }

    /**
     * Parse a manual lineup string into the standard player array format.
     * Format: one player per line, "{number} {name}".
     *
     * @param string $text Raw textarea content.
     * @return array Array of [ name, number, starter ] entries.
     */
    public static function parse_manual_lineup( $text ) {
        $lines   = array_filter( array_map( 'trim', explode( "\n", $text ) ) );
        $players = array();

        foreach ( $lines as $line ) {
            if ( preg_match( '/^(\d+)\s+(.+)$/', $line, $matches ) ) {
                $players[] = array(
                    'name'    => sanitize_text_field( $matches[2] ),
                    'number'  => (int) $matches[1],
                    'starter' => true,
                );
            }
        }

        return $players;
    }
}
