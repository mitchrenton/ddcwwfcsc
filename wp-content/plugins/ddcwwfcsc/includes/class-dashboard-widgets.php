<?php
/**
 * Dashboard widgets for the President role.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Dashboard_Widgets {

    /**
     * Hook into WordPress.
     */
    public static function init() {
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widgets' ) );
    }

    /**
     * Register dashboard widgets based on user capabilities.
     */
    public static function register_widgets() {
        if ( current_user_can( 'manage_ddcwwfcsc_tickets' ) ) {
            wp_add_dashboard_widget(
                'ddcwwfcsc_pending_tickets',
                esc_html__( 'Pending Ticket Requests', 'ddcwwfcsc' ),
                array( __CLASS__, 'render_ticket_widget' )
            );
        }

        if ( current_user_can( 'manage_ddcwwfcsc_fixtures' ) ) {
            wp_add_dashboard_widget(
                'ddcwwfcsc_motm_standings',
                esc_html__( 'MOTM Standings', 'ddcwwfcsc' ),
                array( __CLASS__, 'render_motm_widget' )
            );
        }
    }

    /**
     * Render the Pending Ticket Requests widget.
     */
    public static function render_ticket_widget() {
        global $wpdb;

        $table = $wpdb->prefix . 'ddcwwfcsc_ticket_requests';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $pending_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'"
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results(
            "SELECT fixture_id, name, num_tickets, created_at
             FROM {$table}
             WHERE status = 'pending'
             ORDER BY created_at DESC
             LIMIT 10"
        );

        if ( empty( $rows ) ) {
            echo '<p>' . esc_html__( 'No pending ticket requests.', 'ddcwwfcsc' ) . '</p>';
            return;
        }

        printf(
            '<p><strong>%d %s</strong></p>',
            $pending_count,
            esc_html( _n( 'Pending', 'Pending', $pending_count, 'ddcwwfcsc' ) )
        );

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Name', 'ddcwwfcsc' ) . '</th>';
        echo '<th>' . esc_html__( 'Fixture', 'ddcwwfcsc' ) . '</th>';
        echo '<th>' . esc_html__( 'Tickets', 'ddcwwfcsc' ) . '</th>';
        echo '<th>' . esc_html__( 'Date', 'ddcwwfcsc' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $rows as $row ) {
            echo '<tr>';
            echo '<td>' . esc_html( $row->name ) . '</td>';
            echo '<td>' . esc_html( get_the_title( $row->fixture_id ) ) . '</td>';
            echo '<td>' . esc_html( $row->num_tickets ) . '</td>';
            echo '<td>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $row->created_at ) ) ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        printf(
            '<p class="textright"><a href="%s">%s</a></p>',
            esc_url( admin_url( 'admin.php?page=ddcwwfcsc-ticket-requests' ) ),
            esc_html__( 'View All Requests', 'ddcwwfcsc' )
        );
    }

    /**
     * Render the MOTM Standings widget.
     */
    public static function render_motm_widget() {
        $year  = (int) gmdate( 'Y' );
        $month = (int) gmdate( 'n' );

        $start_year  = $month >= 8 ? $year : $year - 1;
        $season_name = $start_year . '/' . substr( $start_year + 1, 2 );

        $term = get_term_by( 'name', $season_name, 'ddcwwfcsc_season' );

        if ( ! $term ) {
            echo '<p>' . esc_html__( 'No MOTM votes recorded this season.', 'ddcwwfcsc' ) . '</p>';
            return;
        }

        $standings = DDCWWFCSC_MOTM_Votes::get_season_standings( $term->term_id );

        if ( empty( $standings ) ) {
            echo '<p>' . esc_html__( 'No MOTM votes recorded this season.', 'ddcwwfcsc' ) . '</p>';
            return;
        }

        printf(
            '<p><strong>%s %s</strong></p>',
            esc_html__( 'Season', 'ddcwwfcsc' ),
            esc_html( $season_name )
        );

        $top_5 = array_slice( $standings, 0, 5 );

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Rank', 'ddcwwfcsc' ) . '</th>';
        echo '<th>' . esc_html__( 'Player', 'ddcwwfcsc' ) . '</th>';
        echo '<th>' . esc_html__( 'Votes', 'ddcwwfcsc' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $top_5 as $index => $player ) {
            echo '<tr>';
            echo '<td>' . esc_html( $index + 1 ) . '</td>';
            echo '<td>' . esc_html( $player['player_name'] ) . '</td>';
            echo '<td>' . esc_html( $player['total_votes'] ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        printf(
            '<p class="textright"><a href="%s">%s</a></p>',
            esc_url( admin_url( 'edit.php?post_type=ddcwwfcsc_fixture&page=ddcwwfcsc-motm-standings' ) ),
            esc_html__( 'View Full Standings', 'ddcwwfcsc' )
        );
    }
}
