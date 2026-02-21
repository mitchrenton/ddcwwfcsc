<?php
/**
 * Plugin activation routines.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Activator {

    /**
     * Run on plugin activation.
     */
    public static function activate() {
        self::create_tables();
        self::create_roles();
        self::add_caps_to_admin();
        self::create_default_terms();
        self::create_competition_terms();
        self::create_opponent_terms();
        // Schedule daily fixture sync cron.
        if ( ! wp_next_scheduled( 'ddcwwfcsc_fixture_sync' ) ) {
            wp_schedule_event( time(), 'daily', 'ddcwwfcsc_fixture_sync' );
        }

        // Schedule hourly payment link expiry cron.
        if ( ! wp_next_scheduled( 'ddcwwfcsc_expire_payment_links' ) ) {
            wp_schedule_event( time(), 'hourly', 'ddcwwfcsc_expire_payment_links' );
        }

        flush_rewrite_rules();
    }

    /**
     * Create custom database tables.
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $ticket_table = $wpdb->prefix . 'ddcwwfcsc_ticket_requests';
        $sql_tickets = "CREATE TABLE {$ticket_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            fixture_id BIGINT(20) UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            num_tickets INT(11) NOT NULL DEFAULT 1,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            payment_token VARCHAR(64) DEFAULT NULL,
            stripe_session_id VARCHAR(255) DEFAULT NULL,
            amount DECIMAL(10,2) DEFAULT NULL,
            payment_method VARCHAR(20) DEFAULT NULL,
            approved_at DATETIME DEFAULT NULL,
            paid_at DATETIME DEFAULT NULL,
            payment_expires_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY fixture_id (fixture_id),
            KEY status (status),
            UNIQUE KEY payment_token (payment_token)
        ) {$charset_collate};";

        $motm_table = $wpdb->prefix . 'ddcwwfcsc_motm_votes';
        $sql_motm = "CREATE TABLE {$motm_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            fixture_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            player_name VARCHAR(100) NOT NULL,
            voted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY fixture_user (fixture_id, user_id),
            KEY fixture_id (fixture_id),
            KEY player_name (player_name)
        ) {$charset_collate};";

        $invites_table = $wpdb->prefix . 'ddcwwfcsc_invites';
        $sql_invites = "CREATE TABLE {$invites_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(64) NOT NULL,
            invited_by BIGINT(20) UNSIGNED NOT NULL,
            invited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            accepted_at DATETIME DEFAULT NULL,
            paid_season VARCHAR(20) NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY email (email)
        ) {$charset_collate};";

        $applications_table = $wpdb->prefix . 'ddcwwfcsc_applications';
        $sql_applications = "CREATE TABLE {$applications_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_at DATETIME DEFAULT NULL,
            reviewed_by BIGINT(20) UNSIGNED DEFAULT NULL,
            PRIMARY KEY (id),
            KEY email (email),
            KEY status (status)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_tickets );
        dbDelta( $sql_motm );
        dbDelta( $sql_invites );
        dbDelta( $sql_applications );
    }

    /**
     * Create the President role with Editor capabilities plus custom caps.
     */
    private static function create_roles() {
        $editor = get_role( 'editor' );

        if ( ! $editor ) {
            return;
        }

        $caps = $editor->capabilities;

        // Add custom capabilities.
        $caps['manage_ddcwwfcsc_fixtures']  = true;
        $caps['manage_ddcwwfcsc_tickets']   = true;
        $caps['manage_ddcwwfcsc_settings']  = true;
        $caps['manage_ddcwwfcsc_events']    = true;
        $caps['manage_ddcwwfcsc_bulletins'] = true;
        $caps['manage_ddcwwfcsc_members']   = true;

        // Remove existing role first to update capabilities cleanly.
        remove_role( 'ddcwwfcsc_president' );

        add_role(
            'ddcwwfcsc_president',
            __( 'President', 'ddcwwfcsc' ),
            $caps
        );

        // Member role — basic read access.
        remove_role( 'ddcwwfcsc_member' );
        add_role(
            'ddcwwfcsc_member',
            __( 'Member', 'ddcwwfcsc' ),
            array( 'read' => true )
        );
    }

    /**
     * Grant custom capabilities to the Administrator role.
     */
    private static function add_caps_to_admin() {
        $admin = get_role( 'administrator' );

        if ( ! $admin ) {
            return;
        }

        $admin->add_cap( 'manage_ddcwwfcsc_fixtures' );
        $admin->add_cap( 'manage_ddcwwfcsc_tickets' );
        $admin->add_cap( 'manage_ddcwwfcsc_settings' );
        $admin->add_cap( 'manage_ddcwwfcsc_events' );
        $admin->add_cap( 'manage_ddcwwfcsc_bulletins' );
        $admin->add_cap( 'manage_ddcwwfcsc_members' );
    }

    /**
     * Create default price category terms.
     */
    private static function create_default_terms() {
        // Ensure the taxonomy is registered before inserting terms.
        if ( ! taxonomy_exists( 'ddcwwfcsc_price_category' ) ) {
            DDCWWFCSC_Fixture_CPT::register_taxonomy();
        }

        $defaults = array(
            'Category A' => 30,
            'Category B' => 25,
            'Category C' => 20,
        );

        foreach ( $defaults as $name => $price ) {
            if ( ! term_exists( $name, 'ddcwwfcsc_price_category' ) ) {
                $result = wp_insert_term( $name, 'ddcwwfcsc_price_category' );
                if ( ! is_wp_error( $result ) ) {
                    update_term_meta( $result['term_id'], '_ddcwwfcsc_price', $price );
                }
            }
        }
    }

    /**
     * Create default competition terms.
     */
    private static function create_competition_terms() {
        if ( ! taxonomy_exists( 'ddcwwfcsc_competition' ) ) {
            DDCWWFCSC_Fixture_CPT::register_competition_taxonomy();
        }

        $competitions = array( 'Premier League', 'FA Cup', 'EFL Cup' );

        foreach ( $competitions as $name ) {
            if ( ! term_exists( $name, 'ddcwwfcsc_competition' ) ) {
                wp_insert_term( $name, 'ddcwwfcsc_competition' );
            }
        }
    }

    /**
     * Create or update opponent terms from club badge images.
     *
     * Public so it can be called from admin_init as well.
     */
    public static function create_opponent_terms() {
        if ( ! taxonomy_exists( 'ddcwwfcsc_opponent' ) ) {
            DDCWWFCSC_Fixture_CPT::register_opponent_taxonomy();
        }

        // Map slug to proper display name for special cases.
        $name_overrides = array(
            'qpr'                   => 'QPR',
            'bolton'                => 'Bolton',
            'brighton'              => 'Brighton & Hove Albion',
            'bournemouth'           => 'AFC Bournemouth',
            'milton-keynes-dons'    => 'MK Dons',
            'wolves'                => 'Wolves',
            'west-bromwich-albion'  => 'West Brom',
        );

        $clubs_dir = DDCWWFCSC_PLUGIN_DIR . 'assets/img/clubs/';

        if ( ! is_dir( $clubs_dir ) ) {
            return;
        }

        $files = glob( $clubs_dir . '*.png' );

        if ( ! $files ) {
            return;
        }

        foreach ( $files as $file ) {
            $filename = basename( $file );
            $slug     = pathinfo( $filename, PATHINFO_FILENAME );

            // Generate display name.
            if ( isset( $name_overrides[ $slug ] ) ) {
                $name = $name_overrides[ $slug ];
            } else {
                $name = ucwords( str_replace( '-', ' ', $slug ) );
            }

            $existing = term_exists( $slug, 'ddcwwfcsc_opponent' );

            if ( $existing ) {
                // Ensure badge meta is set on existing terms.
                $term_id = is_array( $existing ) ? $existing['term_id'] : $existing;
                if ( ! get_term_meta( $term_id, '_ddcwwfcsc_badge', true ) ) {
                    update_term_meta( $term_id, '_ddcwwfcsc_badge', $filename );
                }
            } else {
                $result = wp_insert_term( $name, 'ddcwwfcsc_opponent', array( 'slug' => $slug ) );
                if ( ! is_wp_error( $result ) ) {
                    update_term_meta( $result['term_id'], '_ddcwwfcsc_badge', $filename );
                }
            }
        }
    }

    /**
     * Add any missing columns to existing tables without requiring a full
     * deactivate/reactivate cycle. Hooked to admin_init.
     */
    public static function maybe_upgrade_schema() {
        global $wpdb;

        $invites_table = $wpdb->prefix . 'ddcwwfcsc_invites';

        // Add paid_season column (season-based membership, e.g. "2024/25").
        $col = $wpdb->get_var( "SHOW COLUMNS FROM {$invites_table} LIKE 'paid_season'" );
        if ( ! $col ) {
            $wpdb->query( "ALTER TABLE {$invites_table} ADD COLUMN paid_season VARCHAR(20) NULL DEFAULT NULL" );
        }

        // Drop the now-unused paid_until column if it exists (replaced by paid_season).
        $old_col = $wpdb->get_var( "SHOW COLUMNS FROM {$invites_table} LIKE 'paid_until'" );
        if ( $old_col ) {
            $wpdb->query( "ALTER TABLE {$invites_table} DROP COLUMN paid_until" );
        }
    }

    /**
     * Run opponent seeding on admin_init if not yet done for this version.
     */
    public static function maybe_seed_opponents() {
        $seeded_version = get_option( 'ddcwwfcsc_opponents_seeded' );

        if ( $seeded_version === DDCWWFCSC_VERSION ) {
            return;
        }

        self::create_opponent_terms();
        update_option( 'ddcwwfcsc_opponents_seeded', DDCWWFCSC_VERSION );
    }
}
