<?php
/**
 * Fixture sync from football-data.org API.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Fixture_Sync {

    /**
     * API base URL.
     */
    const API_BASE = 'https://api.football-data.org/v4';

    /**
     * Known competition codes → taxonomy term names.
     * Used to map API codes to friendly labels; any unknown code falls back
     * to the name string returned by the API itself.
     */
    const COMPETITIONS = array(
        'PL'  => 'Premier League',
        'FAC' => 'FA Cup',
        'ELC' => 'EFL Cup',
        'CL'  => 'Champions League',
        'EL'  => 'Europa League',
        'ECL' => 'Conference League',
    );

    /**
     * Transient TTL in seconds (12 hours).
     */
    const CACHE_TTL = 43200;

    /**
     * Hardcoded overrides for API team names → taxonomy slugs.
     */
    const OPPONENT_OVERRIDES = array(
        'Brighton & Hove Albion'    => 'brighton',
        'Brighton and Hove Albion'  => 'brighton',
        'AFC Bournemouth'           => 'bournemouth',
        'Wolverhampton Wanderers'   => 'wolves',
        'Wolverhampton Wanderers FC' => 'wolves',
        'Queens Park Rangers'       => 'qpr',
        'MK Dons'                   => 'milton-keynes-dons',
        'West Bromwich Albion'      => 'west-bromwich-albion',
        'Nottingham Forest'         => 'nottingham-forest',
        'Tottenham Hotspur'         => 'tottenham',
        'Leicester City'            => 'leicester',
        'Manchester United'         => 'manchester-united',
        'Manchester City'           => 'manchester-city',
        'Newcastle United'          => 'newcastle',
        'West Ham United'           => 'west-ham',
    );

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'ddcwwfcsc_fixture_sync', array( __CLASS__, 'run_sync' ) );
        add_action( 'init', array( __CLASS__, 'register_meta' ) );
    }

    /**
     * Register sync-specific post meta.
     */
    public static function register_meta() {
        $meta_fields = array(
            '_ddcwwfcsc_fd_match_id'    => array( 'type' => 'integer', 'default' => 0 ),
            '_ddcwwfcsc_fd_status'      => array( 'type' => 'string',  'default' => '' ),
            '_ddcwwfcsc_fd_competition' => array( 'type' => 'string',  'default' => '' ),
            '_ddcwwfcsc_fd_synced_at'   => array( 'type' => 'string',  'default' => '' ),
        );

        foreach ( $meta_fields as $key => $config ) {
            $sanitize = 'string' === $config['type'] ? 'sanitize_text_field' : 'absint';

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

    /**
     * Main sync entry point — called by WP-Cron or manually.
     *
     * @return array{created: int, updated: int, skipped: int, error: string}|null Null if no API key.
     */
    public static function run_sync() {
        $api_key = get_option( 'ddcwwfcsc_fd_api_key', '' );
        if ( empty( $api_key ) ) {
            error_log( '[DDCWWFCSC Sync] No API key configured — skipping sync.' );
            return null;
        }

        $team_id = (int) get_option( 'ddcwwfcsc_fd_team_id', 76 );

        $created     = 0;
        $updated     = 0;
        $skipped     = 0;
        $by_comp     = array(); // competition label => count for the sync log.

        // Fetch all of the team's matches in one request (no competition filter).
        // This avoids 403s on the free tier that occur when explicitly requesting
        // cup competition endpoints — the team endpoint returns all competitions.
        $matches = self::fetch_all_matches( $api_key, $team_id );

        if ( null === $matches ) {
            error_log( '[DDCWWFCSC Sync] Aborting sync — could not fetch matches.' );
            update_option( 'ddcwwfcsc_last_sync_log', array(
                'time'    => gmdate( 'c' ),
                'error'   => 'fetch_failed',
                'by_comp' => array(),
            ) );
            return array( 'created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'error' => 'fetch_failed' );
        }

        foreach ( $matches as $match ) {
            // Tally raw competition counts before processing.
            $comp_label = $match['competition']['name'] ?? ( $match['competition']['code'] ?? 'Unknown' );
            $by_comp[ $comp_label ] = ( $by_comp[ $comp_label ] ?? 0 ) + 1;

            $result = self::process_match( $match, $team_id );
            if ( 'created' === $result ) {
                $created++;
            } elseif ( 'updated' === $result ) {
                $updated++;
            } else {
                $skipped++;
            }
        }

        error_log( sprintf(
            '[DDCWWFCSC Sync] Complete — %d created, %d updated, %d skipped. Competitions: %s',
            $created,
            $updated,
            $skipped,
            implode( ', ', array_map(
                function ( $name, $count ) { return $name . ' (' . $count . ')'; },
                array_keys( $by_comp ),
                $by_comp
            ) )
        ) );

        update_option( 'ddcwwfcsc_last_sync_log', array(
            'time'    => gmdate( 'c' ),
            'error'   => '',
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'by_comp' => $by_comp,
        ) );

        return array( 'created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'error' => '' );
    }

    /**
     * Fetch matches for a competition from the API (with transient cache).
     *
     * @param string $code    Competition code (PL, FAC, ELC).
     * @param string $api_key API token.
     * @param int    $team_id Football-data.org team ID.
     * @return array|null Array of matches, empty array if unavailable, null on hard error.
     */
    public static function fetch_matches( $code, $api_key, $team_id ) {
        $transient_key = 'ddcwwfcsc_fd_' . strtolower( $code ) . '_' . $team_id;

        // Temporarily bypass cache for full-season import.
        delete_transient( $transient_key );

        $date_from = '2025-08-01';
        $date_to   = '2026-07-31';

        $url = sprintf(
            '%s/teams/%d/matches?competitions=%s&status=SCHEDULED,POSTPONED,FINISHED&dateFrom=%s&dateTo=%s',
            self::API_BASE,
            $team_id,
            $code,
            $date_from,
            $date_to
        );

        $response = wp_remote_get( $url, array(
            'headers' => array(
                'X-Auth-Token' => $api_key,
            ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( '[DDCWWFCSC Sync] HTTP error for ' . $code . ': ' . $response->get_error_message() );
            return null;
        }

        $status_code = wp_remote_retrieve_response_code( $response );

        if ( 403 === $status_code ) {
            error_log( '[DDCWWFCSC Sync] ' . $code . ' not available on free tier — skipping.' );
            set_transient( $transient_key, array(), self::CACHE_TTL );
            return array();
        }

        if ( 429 === $status_code ) {
            error_log( '[DDCWWFCSC Sync] Rate limited (429) on ' . $code . '.' );
            return null;
        }

        if ( 200 !== $status_code ) {
            error_log( '[DDCWWFCSC Sync] Unexpected status ' . $status_code . ' for ' . $code . '.' );
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_array( $body ) || ! isset( $body['matches'] ) ) {
            error_log( '[DDCWWFCSC Sync] Invalid response body for ' . $code . '.' );
            return null;
        }

        $matches = $body['matches'];
        set_transient( $transient_key, $matches, self::CACHE_TTL );

        return $matches;
    }

    /**
     * Fetch all matches for the team across every competition (no competition filter).
     *
     * Using the unfiltered endpoint lets the free tier return cup fixtures that
     * would 403 if requested by competition code directly.
     *
     * @param string $api_key API token.
     * @param int    $team_id Football-data.org team ID.
     * @return array|null Array of matches, or null on hard error.
     */
    public static function fetch_all_matches( $api_key, $team_id ) {
        $url = sprintf(
            '%s/teams/%d/matches?status=SCHEDULED,POSTPONED,FINISHED&dateFrom=%s&dateTo=%s',
            self::API_BASE,
            $team_id,
            '2025-08-01',
            '2026-07-31'
        );

        $response = wp_remote_get( $url, array(
            'headers' => array( 'X-Auth-Token' => $api_key ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( '[DDCWWFCSC Sync] HTTP error fetching all matches: ' . $response->get_error_message() );
            return null;
        }

        $status_code = wp_remote_retrieve_response_code( $response );

        if ( 429 === $status_code ) {
            error_log( '[DDCWWFCSC Sync] Rate limited (429) fetching all matches.' );
            return null;
        }

        if ( 200 !== $status_code ) {
            error_log( '[DDCWWFCSC Sync] Unexpected status ' . $status_code . ' fetching all matches.' );
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_array( $body ) || ! isset( $body['matches'] ) ) {
            error_log( '[DDCWWFCSC Sync] Invalid response body fetching all matches.' );
            return null;
        }

        return $body['matches'];
    }

    /**
     * Process a single match — create or update a fixture post.
     *
     * @param array $match   Match data from the API.
     * @param int   $team_id Our team's football-data.org ID.
     * @return string 'created', 'updated', or 'skipped'.
     */
    public static function process_match( $match, $team_id ) {
        $status = $match['status'] ?? '';

        // Skip cancelled matches.
        if ( 'CANCELLED' === $status ) {
            return 'skipped';
        }

        $fd_id = (int) ( $match['id'] ?? 0 );
        if ( ! $fd_id ) {
            return 'skipped';
        }

        // Extract competition from match data.
        $code          = $match['competition']['code'] ?? '';
        $comp_api_name = $match['competition']['name'] ?? '';

        // Determine venue.
        $home_id = (int) ( $match['homeTeam']['id'] ?? 0 );
        $venue   = ( $home_id === $team_id ) ? 'home' : 'away';

        // Determine opponent.
        $opponent_data = ( 'home' === $venue ) ? $match['awayTeam'] : $match['homeTeam'];
        $opponent_name       = $opponent_data['name'] ?? '';
        $opponent_short_name = $opponent_data['shortName'] ?? $opponent_name;
        $opponent_crest      = $opponent_data['crest'] ?? '';

        // Convert UTC date to local.
        $utc_date   = $match['utcDate'] ?? '';
        $local_date = self::convert_utc_to_local( $utc_date );

        // Resolve opponent taxonomy term.
        $term_id = self::resolve_opponent( $opponent_name, $opponent_short_name );

        // Save the API crest URL on the opponent term.
        if ( $term_id && $opponent_crest ) {
            update_term_meta( $term_id, '_ddcwwfcsc_crest_url', esc_url_raw( $opponent_crest ) );
        }

        // Check for existing fixture.
        $existing_id = self::find_fixture_by_fd_id( $fd_id );
        $now_iso     = gmdate( 'c' );

        if ( $existing_id ) {
            // Update — only sync-safe fields.
            update_post_meta( $existing_id, '_ddcwwfcsc_match_date', $local_date );
            update_post_meta( $existing_id, '_ddcwwfcsc_venue', $venue );
            update_post_meta( $existing_id, '_ddcwwfcsc_fd_status', $status );
            update_post_meta( $existing_id, '_ddcwwfcsc_fd_synced_at', $now_iso );
            update_post_meta( $existing_id, '_ddcwwfcsc_fd_competition', $code );

            if ( $term_id ) {
                wp_set_object_terms( $existing_id, $term_id, 'ddcwwfcsc_opponent' );
            }

            // Sync scores for finished matches.
            self::sync_scores( $existing_id, $match );

            // Fetch MOTM lineup for finished matches.
            if ( 'FINISHED' === $status && class_exists( 'DDCWWFCSC_MOTM_Lineup' ) ) {
                DDCWWFCSC_MOTM_Lineup::maybe_fetch_lineup( $existing_id );
            }

            // Assign competition and season terms.
            self::assign_competition_term( $existing_id, $code, $comp_api_name );
            self::assign_season_term( $existing_id, $local_date );

            // Regenerate title.
            DDCWWFCSC_Fixture_Admin::update_fixture_title( $existing_id );

            return 'updated';
        }

        // Create new fixture.
        $default_tickets = (int) get_option( 'ddcwwfcsc_default_tickets', 8 );
        $default_max     = (int) get_option( 'ddcwwfcsc_default_max_per_person', 2 );

        $post_id = wp_insert_post( array(
            'post_type'   => 'ddcwwfcsc_fixture',
            'post_status' => 'publish',
            'post_title'  => 'Fixture', // Temporary — regenerated below.
        ), true );

        if ( is_wp_error( $post_id ) ) {
            error_log( '[DDCWWFCSC Sync] Failed to create fixture for match ' . $fd_id . ': ' . $post_id->get_error_message() );
            return 'skipped';
        }

        // Set meta.
        update_post_meta( $post_id, '_ddcwwfcsc_match_date', $local_date );
        update_post_meta( $post_id, '_ddcwwfcsc_venue', $venue );
        update_post_meta( $post_id, '_ddcwwfcsc_total_tickets', $default_tickets );
        update_post_meta( $post_id, '_ddcwwfcsc_tickets_remaining', $default_tickets );
        update_post_meta( $post_id, '_ddcwwfcsc_max_per_person', $default_max );
        update_post_meta( $post_id, '_ddcwwfcsc_on_sale', false );
        update_post_meta( $post_id, '_ddcwwfcsc_fd_match_id', $fd_id );
        update_post_meta( $post_id, '_ddcwwfcsc_fd_status', $status );
        update_post_meta( $post_id, '_ddcwwfcsc_fd_competition', $code );
        update_post_meta( $post_id, '_ddcwwfcsc_fd_synced_at', $now_iso );

        if ( $term_id ) {
            wp_set_object_terms( $post_id, $term_id, 'ddcwwfcsc_opponent' );
        }

        // Sync scores for finished matches.
        self::sync_scores( $post_id, $match );

        // Fetch MOTM lineup for finished matches.
        if ( 'FINISHED' === $status && class_exists( 'DDCWWFCSC_MOTM_Lineup' ) ) {
            DDCWWFCSC_MOTM_Lineup::maybe_fetch_lineup( $post_id );
        }

        // Assign competition and season terms.
        self::assign_competition_term( $post_id, $code, $comp_api_name );
        self::assign_season_term( $post_id, $local_date );

        // Generate proper title.
        DDCWWFCSC_Fixture_Admin::update_fixture_title( $post_id );

        return 'created';
    }

    /**
     * Sync full-time scores from a match onto a fixture post.
     *
     * @param int   $post_id Fixture post ID.
     * @param array $match   Match data from the API.
     */
    public static function sync_scores( $post_id, $match ) {
        $status = $match['status'] ?? '';

        if ( 'FINISHED' !== $status ) {
            return;
        }

        $home_score = $match['score']['fullTime']['home'] ?? null;
        $away_score = $match['score']['fullTime']['away'] ?? null;

        if ( null !== $home_score && null !== $away_score ) {
            update_post_meta( $post_id, '_ddcwwfcsc_home_score', (int) $home_score );
            update_post_meta( $post_id, '_ddcwwfcsc_away_score', (int) $away_score );
        }
    }

    /**
     * Find an existing fixture post by football-data.org match ID.
     *
     * @param int $fd_id Match ID.
     * @return int|null Post ID or null.
     */
    public static function find_fixture_by_fd_id( $fd_id ) {
        $query = new WP_Query( array(
            'post_type'      => 'ddcwwfcsc_fixture',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'   => '_ddcwwfcsc_fd_match_id',
                    'value' => $fd_id,
                    'type'  => 'NUMERIC',
                ),
            ),
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );

        return $query->posts ? (int) $query->posts[0] : null;
    }

    /**
     * Resolve an API team name to an opponent taxonomy term ID.
     *
     * @param string $name       Full team name from API.
     * @param string $short_name Short team name from API.
     * @return int|null Term ID or null.
     */
    public static function resolve_opponent( $name, $short_name ) {
        // 1. Hardcoded overrides.
        if ( isset( self::OPPONENT_OVERRIDES[ $name ] ) ) {
            $slug = self::OPPONENT_OVERRIDES[ $name ];
            $term = get_term_by( 'slug', $slug, 'ddcwwfcsc_opponent' );
            if ( $term ) {
                return (int) $term->term_id;
            }
        }

        // 2. Try sanitized short name.
        $slug = sanitize_title( $short_name );
        $term = get_term_by( 'slug', $slug, 'ddcwwfcsc_opponent' );
        if ( $term ) {
            return (int) $term->term_id;
        }

        // 3. Try sanitized full name, stripping trailing -fc / -afc.
        $slug = sanitize_title( $name );
        $slug = preg_replace( '/-(fc|afc)$/', '', $slug );
        $term = get_term_by( 'slug', $slug, 'ddcwwfcsc_opponent' );
        if ( $term ) {
            return (int) $term->term_id;
        }

        // 4. Create new term.
        $result = wp_insert_term( $short_name, 'ddcwwfcsc_opponent' );
        if ( is_wp_error( $result ) ) {
            error_log( '[DDCWWFCSC Sync] Failed to create opponent term "' . $short_name . '": ' . $result->get_error_message() );
            return null;
        }

        error_log( '[DDCWWFCSC Sync] Created new opponent term "' . $short_name . '" — add a badge image.' );
        return (int) $result['term_id'];
    }

    /**
     * Assign a competition taxonomy term to a fixture.
     *
     * @param int    $post_id      Fixture post ID.
     * @param string $code         Competition code (PL, FAC, ELC, etc.).
     * @param string $api_name     Competition name from the API — used as label
     *                             for any code not in the COMPETITIONS map.
     */
    public static function assign_competition_term( $post_id, $code, $api_name = '' ) {
        $name = self::COMPETITIONS[ $code ] ?? ( $api_name ?: $code );

        $term = get_term_by( 'name', $name, 'ddcwwfcsc_competition' );
        if ( ! $term ) {
            $result = wp_insert_term( $name, 'ddcwwfcsc_competition' );
            if ( is_wp_error( $result ) ) {
                return;
            }
            $term_id = (int) $result['term_id'];
        } else {
            $term_id = (int) $term->term_id;
        }

        wp_set_object_terms( $post_id, $term_id, 'ddcwwfcsc_competition' );
    }

    /**
     * Assign a season taxonomy term to a fixture based on match date.
     *
     * Football seasons run Aug–Jul: a match in Jan 2026 = "2025/26",
     * a match in Sep 2025 = "2025/26".
     *
     * @param int    $post_id    Fixture post ID.
     * @param string $local_date Local date in Y-m-d\TH:i format.
     */
    public static function assign_season_term( $post_id, $local_date ) {
        if ( empty( $local_date ) ) {
            return;
        }

        $timestamp = strtotime( $local_date );
        if ( ! $timestamp ) {
            return;
        }

        $year  = (int) gmdate( 'Y', $timestamp );
        $month = (int) gmdate( 'n', $timestamp );

        // Aug (8) onwards = start of new season, before Aug = end of previous season.
        if ( $month >= 8 ) {
            $start_year = $year;
        } else {
            $start_year = $year - 1;
        }

        $end_year_short = substr( (string) ( $start_year + 1 ), -2 );
        $season_name    = $start_year . '/' . $end_year_short;

        $term = get_term_by( 'name', $season_name, 'ddcwwfcsc_season' );
        if ( ! $term ) {
            $result = wp_insert_term( $season_name, 'ddcwwfcsc_season' );
            if ( is_wp_error( $result ) ) {
                return;
            }
            $term_id = (int) $result['term_id'];
        } else {
            $term_id = (int) $term->term_id;
        }

        wp_set_object_terms( $post_id, $term_id, 'ddcwwfcsc_season' );
    }

    /**
     * Convert a UTC ISO 8601 date to a local datetime-local string.
     *
     * @param string $utc UTC date string.
     * @return string Local date in Y-m-d\TH:i format.
     */
    public static function convert_utc_to_local( $utc ) {
        if ( empty( $utc ) ) {
            return '';
        }

        try {
            $dt = new DateTime( $utc, new DateTimeZone( 'UTC' ) );
            $dt->setTimezone( wp_timezone() );
            return $dt->format( 'Y-m-d\TH:i' );
        } catch ( Exception $e ) {
            error_log( '[DDCWWFCSC Sync] Date conversion error: ' . $e->getMessage() );
            return '';
        }
    }
}
