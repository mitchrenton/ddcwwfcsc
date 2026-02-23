<?php
/**
 * Plugin settings page.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Settings {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_post_ddcwwfcsc_sync_now', array( __CLASS__, 'handle_sync_now' ) );
        add_action( 'admin_post_ddcwwfcsc_save_price_categories', array( __CLASS__, 'handle_save_price_categories' ) );
    }

    /**
     * Add the settings page under Settings.
     */
    public static function add_settings_page() {
        add_options_page(
            __( 'DDCWWFCSC Settings', 'ddcwwfcsc' ),
            __( 'DDCWWFCSC', 'ddcwwfcsc' ),
            'manage_ddcwwfcsc_settings',
            'ddcwwfcsc-settings',
            array( __CLASS__, 'render_settings_page' )
        );
    }

    /**
     * Register settings and sections.
     */
    public static function register_settings() {
        // Google Maps API key.
        register_setting( 'ddcwwfcsc_settings', 'ddcwwfcsc_google_maps_api_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );

        add_settings_section(
            'ddcwwfcsc_google_maps_section',
            __( 'Google Maps', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_google_maps_section' ),
            'ddcwwfcsc-settings'
        );

        add_settings_field(
            'ddcwwfcsc_google_maps_api_key',
            __( 'API Key', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_google_maps_api_key_field' ),
            'ddcwwfcsc-settings',
            'ddcwwfcsc_google_maps_section'
        );

        // Stripe Payments settings.
        register_setting( 'ddcwwfcsc_settings', 'ddcwwfcsc_stripe_publishable_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );

        register_setting( 'ddcwwfcsc_settings', 'ddcwwfcsc_stripe_secret_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );

        register_setting( 'ddcwwfcsc_settings', 'ddcwwfcsc_stripe_webhook_secret', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );

        add_settings_section(
            'ddcwwfcsc_stripe_section',
            __( 'Stripe Payments', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_stripe_section' ),
            'ddcwwfcsc-settings'
        );

        add_settings_field(
            'ddcwwfcsc_stripe_publishable_key',
            __( 'Publishable Key', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_stripe_publishable_key_field' ),
            'ddcwwfcsc-settings',
            'ddcwwfcsc_stripe_section'
        );

        add_settings_field(
            'ddcwwfcsc_stripe_secret_key',
            __( 'Secret Key', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_stripe_secret_key_field' ),
            'ddcwwfcsc-settings',
            'ddcwwfcsc_stripe_section'
        );

        add_settings_field(
            'ddcwwfcsc_stripe_webhook_secret',
            __( 'Webhook Secret', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_stripe_webhook_secret_field' ),
            'ddcwwfcsc-settings',
            'ddcwwfcsc_stripe_section'
        );

        // Football Data API settings.
        register_setting( 'ddcwwfcsc_settings', 'ddcwwfcsc_fd_api_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );

        register_setting( 'ddcwwfcsc_settings', 'ddcwwfcsc_fd_team_id', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 76,
        ) );

        add_settings_section(
            'ddcwwfcsc_fd_section',
            __( 'Football Data API', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_fd_section' ),
            'ddcwwfcsc-settings'
        );

        add_settings_field(
            'ddcwwfcsc_fd_api_key',
            __( 'API Key', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_fd_api_key_field' ),
            'ddcwwfcsc-settings',
            'ddcwwfcsc_fd_section'
        );

        add_settings_field(
            'ddcwwfcsc_fd_team_id',
            __( 'Team ID', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_fd_team_id_field' ),
            'ddcwwfcsc-settings',
            'ddcwwfcsc_fd_section'
        );

        // Membership settings.
        register_setting( 'ddcwwfcsc_settings', 'ddcwwfcsc_current_season', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );

        add_settings_section(
            'ddcwwfcsc_membership_section',
            __( 'Membership', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_membership_section' ),
            'ddcwwfcsc-settings'
        );

        add_settings_field(
            'ddcwwfcsc_current_season',
            __( 'Current Season', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_current_season_field' ),
            'ddcwwfcsc-settings',
            'ddcwwfcsc_membership_section'
        );

        // TheSportsDB settings (for MOTM lineups).
        register_setting( 'ddcwwfcsc_settings', 'ddcwwfcsc_tsdb_team_name', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'Wolverhampton Wanderers',
        ) );

        add_settings_section(
            'ddcwwfcsc_tsdb_section',
            __( 'TheSportsDB (MOTM Lineups)', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_tsdb_section' ),
            'ddcwwfcsc-settings'
        );

        add_settings_field(
            'ddcwwfcsc_tsdb_team_name',
            __( 'Team Name', 'ddcwwfcsc' ),
            array( __CLASS__, 'render_tsdb_team_name_field' ),
            'ddcwwfcsc-settings',
            'ddcwwfcsc_tsdb_section'
        );
    }

    /**
     * Render the settings page.
     */
    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'DDCWWFCSC Settings', 'ddcwwfcsc' ); ?></h1>

            <?php self::render_sync_notice(); ?>

            <?php self::render_ticket_settings_section(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'ddcwwfcsc_settings' );
                do_settings_sections( 'ddcwwfcsc-settings' );
                submit_button();
                ?>
            </form>

            <?php if ( get_option( 'ddcwwfcsc_fd_api_key', '' ) ) : ?>
                <hr>
                <h2><?php esc_html_e( 'Manual Fixture Sync', 'ddcwwfcsc' ); ?></h2>
                <p><?php esc_html_e( 'Run the fixture sync immediately instead of waiting for the daily cron job. Existing transient caches are respected — to force a fresh fetch, wait 12 hours or clear transients.', 'ddcwwfcsc' ); ?></p>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="ddcwwfcsc_sync_now">
                    <?php wp_nonce_field( 'ddcwwfcsc_sync_now', 'ddcwwfcsc_sync_nonce' ); ?>
                    <?php submit_button( __( 'Sync Now', 'ddcwwfcsc' ), 'secondary', 'submit', false ); ?>
                </form>
                <?php self::render_sync_log(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handle the "Sync Now" admin POST action.
     */
    public static function handle_sync_now() {
        if ( ! current_user_can( 'manage_ddcwwfcsc_settings' ) ) {
            wp_die( __( 'You do not have permission to do this.', 'ddcwwfcsc' ) );
        }

        check_admin_referer( 'ddcwwfcsc_sync_now', 'ddcwwfcsc_sync_nonce' );

        $result = DDCWWFCSC_Fixture_Sync::run_sync();

        $query_args = array( 'page' => 'ddcwwfcsc-settings' );

        if ( null === $result ) {
            $query_args['sync_error'] = 'no_key';
        } elseif ( ! empty( $result['error'] ) ) {
            $query_args['sync_error']   = 'api';
            $query_args['sync_created'] = $result['created'];
            $query_args['sync_updated'] = $result['updated'];
        } else {
            $query_args['sync_done']    = '1';
            $query_args['sync_created'] = $result['created'];
            $query_args['sync_updated'] = $result['updated'];
            $query_args['sync_skipped'] = $result['skipped'];
        }

        wp_safe_redirect( add_query_arg( $query_args, admin_url( 'options-general.php' ) ) );
        exit;
    }

    /**
     * Render sync result admin notices on the settings page.
     */
    private static function render_sync_notice() {
        if ( isset( $_GET['sync_done'] ) ) {
            $created = (int) ( $_GET['sync_created'] ?? 0 );
            $updated = (int) ( $_GET['sync_updated'] ?? 0 );
            $skipped = (int) ( $_GET['sync_skipped'] ?? 0 );
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html( sprintf(
                    __( 'Fixture sync complete — %d created, %d updated, %d skipped.', 'ddcwwfcsc' ),
                    $created,
                    $updated,
                    $skipped
                ) )
            );
        } elseif ( isset( $_GET['sync_error'] ) ) {
            if ( 'no_key' === $_GET['sync_error'] ) {
                printf(
                    '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                    esc_html__( 'Fixture sync failed — no API key configured.', 'ddcwwfcsc' )
                );
            } else {
                $created = (int) ( $_GET['sync_created'] ?? 0 );
                $updated = (int) ( $_GET['sync_updated'] ?? 0 );
                printf(
                    '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
                    esc_html( sprintf(
                        __( 'Fixture sync aborted due to an API error. Before the error: %d created, %d updated. Check the error log for details.', 'ddcwwfcsc' ),
                        $created,
                        $updated
                    ) )
                );
            }
        }
    }

    /**
     * Render the combined Ticket Settings section (defaults + price categories).
     */
    public static function render_ticket_settings_section() {
        $default_tickets = get_option( 'ddcwwfcsc_default_tickets', 8 );
        $default_max     = get_option( 'ddcwwfcsc_default_max_per_person', 2 );

        $categories = get_terms( array(
            'taxonomy'   => 'ddcwwfcsc_price_category',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );

        if ( is_wp_error( $categories ) ) {
            $categories = array();
        }

        // Show admin notices from price category save redirects.
        if ( isset( $_GET['pc_updated'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Ticket settings updated.', 'ddcwwfcsc' ) . '</p></div>';
        }
        if ( isset( $_GET['pc_error'] ) ) {
            $error = sanitize_text_field( $_GET['pc_error'] );
            $msg   = __( 'An error occurred.', 'ddcwwfcsc' );
            if ( 'in_use' === $error ) {
                $msg = __( 'Cannot delete a price category that is assigned to fixtures.', 'ddcwwfcsc' );
            } elseif ( 'missing_name' === $error ) {
                $msg = __( 'Category name is required.', 'ddcwwfcsc' );
            } elseif ( 'duplicate' === $error ) {
                $msg = __( 'A category with that name already exists.', 'ddcwwfcsc' );
            }
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
        }
        ?>
        <h2><?php esc_html_e( 'Ticket Settings', 'ddcwwfcsc' ); ?></h2>
        <p><?php esc_html_e( 'Configure default ticket quantities and manage price categories for the season.', 'ddcwwfcsc' ); ?></p>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="ddcwwfcsc_save_price_categories">
            <?php wp_nonce_field( 'ddcwwfcsc_save_price_categories', 'ddcwwfcsc_pc_nonce' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ddcwwfcsc_default_tickets"><?php esc_html_e( 'Default Tickets Per Fixture', 'ddcwwfcsc' ); ?></label></th>
                    <td>
                        <input type="number" id="ddcwwfcsc_default_tickets" name="ddcwwfcsc_default_tickets" value="<?php echo esc_attr( $default_tickets ); ?>" min="1" step="1" class="small-text">
                        <p class="description"><?php esc_html_e( 'The default number of tickets available for each new fixture.', 'ddcwwfcsc' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ddcwwfcsc_default_max_per_person"><?php esc_html_e( 'Default Max Per Person', 'ddcwwfcsc' ); ?></label></th>
                    <td>
                        <input type="number" id="ddcwwfcsc_default_max_per_person" name="ddcwwfcsc_default_max_per_person" value="<?php echo esc_attr( $default_max ); ?>" min="1" step="1" class="small-text">
                        <p class="description"><?php esc_html_e( 'The default maximum number of tickets one person can request per fixture.', 'ddcwwfcsc' ); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Price Categories', 'ddcwwfcsc' ); ?></h3>

            <table class="widefat" style="max-width: 600px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Category Name', 'ddcwwfcsc' ); ?></th>
                        <th style="width: 120px;"><?php esc_html_e( 'Price (£)', 'ddcwwfcsc' ); ?></th>
                        <th style="width: 80px;"><?php esc_html_e( 'Delete', 'ddcwwfcsc' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $categories ) ) : ?>
                        <tr>
                            <td colspan="3"><?php esc_html_e( 'No price categories yet.', 'ddcwwfcsc' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $categories as $term ) :
                            $price = get_term_meta( $term->term_id, '_ddcwwfcsc_price', true );
                        ?>
                            <tr>
                                <td>
                                    <input type="text" name="categories[<?php echo esc_attr( $term->term_id ); ?>][name]" value="<?php echo esc_attr( $term->name ); ?>" class="regular-text" required>
                                </td>
                                <td>
                                    <input type="number" name="categories[<?php echo esc_attr( $term->term_id ); ?>][price]" value="<?php echo esc_attr( $price ); ?>" step="0.01" min="0" class="small-text">
                                </td>
                                <td>
                                    <label>
                                        <input type="checkbox" name="delete_categories[]" value="<?php echo esc_attr( $term->term_id ); ?>">
                                        <?php esc_html_e( 'Delete', 'ddcwwfcsc' ); ?>
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr style="background: #f9f9f9;">
                        <td>
                            <input type="text" name="new_category[name]" value="" class="regular-text" placeholder="<?php esc_attr_e( 'New category name', 'ddcwwfcsc' ); ?>">
                        </td>
                        <td>
                            <input type="number" name="new_category[price]" value="" step="0.01" min="0" class="small-text" placeholder="0.00">
                        </td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button( __( 'Save Ticket Settings', 'ddcwwfcsc' ) ); ?>
        </form>
        <hr>
        <?php
    }

    /**
     * Render the Membership section description.
     */
    public static function render_membership_section() {
        echo '<p>' . esc_html__( 'Set the active season so the system knows which season membership fees apply to.', 'ddcwwfcsc' ) . '</p>';
    }

    /**
     * Render the Current Season field.
     * Shows a dropdown of existing season taxonomy terms.
     */
    public static function render_current_season_field() {
        $current = get_option( 'ddcwwfcsc_current_season', '' );
        $seasons = get_terms( array(
            'taxonomy'   => 'ddcwwfcsc_season',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'DESC',
        ) );

        if ( is_wp_error( $seasons ) || empty( $seasons ) ) {
            printf(
                '<input type="text" name="ddcwwfcsc_current_season" value="%s" class="regular-text" placeholder="e.g. 2024/25">',
                esc_attr( $current )
            );
            echo '<p class="description">' . esc_html__( 'No season terms found. Create seasons by adding fixtures, or enter the season name manually.', 'ddcwwfcsc' ) . '</p>';
            return;
        }
        ?>
        <select name="ddcwwfcsc_current_season">
            <option value=""><?php esc_html_e( '— Select a season —', 'ddcwwfcsc' ); ?></option>
            <?php foreach ( $seasons as $season ) : ?>
                <option value="<?php echo esc_attr( $season->name ); ?>" <?php selected( $current, $season->name ); ?>>
                    <?php echo esc_html( $season->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e( 'Members are considered paid if their recorded season matches this value.', 'ddcwwfcsc' ); ?></p>
        <?php
    }

    /**
     * Render the Google Maps section description.
     */
    public static function render_google_maps_section() {
        echo '<p>' . esc_html__( 'Enter your Google Maps JavaScript API key to display interactive maps on Beerwolf pub guide pages and event venue locations.', 'ddcwwfcsc' ) . '</p>';
    }

    /**
     * Render the Google Maps API key field.
     */
    public static function render_google_maps_api_key_field() {
        $value = get_option( 'ddcwwfcsc_google_maps_api_key', '' );
        ?>
        <input type="text" name="ddcwwfcsc_google_maps_api_key" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
        <p class="description"><?php esc_html_e( 'Required for Beerwolf map embeds and event venue maps. Must have the Maps JavaScript API and Places API enabled.', 'ddcwwfcsc' ); ?></p>
        <?php
    }

    /**
     * Render the Stripe Payments section description.
     */
    public static function render_stripe_section() {
        echo '<p>' . esc_html__( 'Enter your Stripe API keys to enable online payments for ticket requests. Use test-mode keys (pk_test_..., sk_test_...) for development.', 'ddcwwfcsc' ) . '</p>';
    }

    /**
     * Render the Stripe publishable key field.
     */
    public static function render_stripe_publishable_key_field() {
        $value = get_option( 'ddcwwfcsc_stripe_publishable_key', '' );
        ?>
        <input type="text" name="ddcwwfcsc_stripe_publishable_key" value="<?php echo esc_attr( $value ); ?>" class="regular-text" autocomplete="off">
        <p class="description"><?php esc_html_e( 'Starts with pk_test_ or pk_live_.', 'ddcwwfcsc' ); ?></p>
        <?php
    }

    /**
     * Render the Stripe secret key field.
     */
    public static function render_stripe_secret_key_field() {
        $value = get_option( 'ddcwwfcsc_stripe_secret_key', '' );
        ?>
        <input type="password" name="ddcwwfcsc_stripe_secret_key" value="<?php echo esc_attr( $value ); ?>" class="regular-text" autocomplete="off">
        <p class="description"><?php esc_html_e( 'Starts with sk_test_ or sk_live_. Keep this secret.', 'ddcwwfcsc' ); ?></p>
        <?php
    }

    /**
     * Render the Stripe webhook secret field.
     */
    public static function render_stripe_webhook_secret_field() {
        $value = get_option( 'ddcwwfcsc_stripe_webhook_secret', '' );
        ?>
        <input type="password" name="ddcwwfcsc_stripe_webhook_secret" value="<?php echo esc_attr( $value ); ?>" class="regular-text" autocomplete="off">
        <p class="description"><?php esc_html_e( 'Starts with whsec_. Found in your Stripe Dashboard under Webhooks.', 'ddcwwfcsc' ); ?></p>
        <?php
    }

    /**
     * Render the Football Data section description.
     */
    public static function render_fd_section() {
        echo '<p>' . esc_html__( 'Connect to football-data.org to automatically sync upcoming Wolves fixtures. A free-tier account provides Premier League coverage; cup competitions are attempted and gracefully skipped if unavailable.', 'ddcwwfcsc' ) . '</p>';
    }

    /**
     * Render the Football Data API key field.
     */
    public static function render_fd_api_key_field() {
        $value = get_option( 'ddcwwfcsc_fd_api_key', '' );
        ?>
        <input type="password" name="ddcwwfcsc_fd_api_key" value="<?php echo esc_attr( $value ); ?>" class="regular-text" autocomplete="off">
        <p class="description"><?php esc_html_e( 'Your football-data.org API token. Sign up at football-data.org for a free account.', 'ddcwwfcsc' ); ?></p>
        <?php
    }

    /**
     * Render the Football Data team ID field.
     */
    public static function render_fd_team_id_field() {
        $value = get_option( 'ddcwwfcsc_fd_team_id', 76 );
        ?>
        <input type="number" name="ddcwwfcsc_fd_team_id" value="<?php echo esc_attr( $value ); ?>" min="1" step="1" class="small-text">
        <p class="description"><?php esc_html_e( 'Wolves = 76. Only change this if you know what you are doing.', 'ddcwwfcsc' ); ?></p>
        <?php
    }

    /**
     * Render the TheSportsDB section description.
     */
    public static function render_tsdb_section() {
        echo '<p>' . esc_html__( 'TheSportsDB is used to fetch match lineups for Man of the Match voting. No API key required — data is free and community-contributed.', 'ddcwwfcsc' ) . '</p>';
    }

    /**
     * Render the TheSportsDB team name field.
     */
    public static function render_tsdb_team_name_field() {
        $value = get_option( 'ddcwwfcsc_tsdb_team_name', 'Wolverhampton Wanderers' );
        ?>
        <input type="text" name="ddcwwfcsc_tsdb_team_name" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
        <p class="description"><?php esc_html_e( 'Must match the team name as it appears on TheSportsDB. Default: Wolverhampton Wanderers.', 'ddcwwfcsc' ); ?></p>
        <?php
    }

    /**
     * Render the last fixture sync log beneath the Sync Now button.
     */
    private static function render_sync_log() {
        $log = get_option( 'ddcwwfcsc_last_sync_log', null );
        if ( ! $log ) {
            return;
        }

        $time    = isset( $log['time'] ) ? get_date_from_gmt( $log['time'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '—';
        $error   = $log['error'] ?? '';
        $by_comp = $log['by_comp'] ?? array();
        ?>
        <h3><?php esc_html_e( 'Last Sync Log', 'ddcwwfcsc' ); ?></h3>
        <p>
            <strong><?php esc_html_e( 'Run at:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $time ); ?>&emsp;
            <strong><?php esc_html_e( 'Created:', 'ddcwwfcsc' ); ?></strong> <?php echo (int) ( $log['created'] ?? 0 ); ?>&emsp;
            <strong><?php esc_html_e( 'Updated:', 'ddcwwfcsc' ); ?></strong> <?php echo (int) ( $log['updated'] ?? 0 ); ?>&emsp;
            <strong><?php esc_html_e( 'Skipped:', 'ddcwwfcsc' ); ?></strong> <?php echo (int) ( $log['skipped'] ?? 0 ); ?>
        </p>
        <?php if ( $error ) : ?>
            <p style="color:#b32d2e;"><strong><?php esc_html_e( 'Error:', 'ddcwwfcsc' ); ?></strong> <?php echo esc_html( $error ); ?></p>
        <?php endif; ?>
        <?php if ( ! empty( $by_comp ) ) : ?>
            <p><strong><?php esc_html_e( 'Matches by competition found in API response:', 'ddcwwfcsc' ); ?></strong></p>
            <ul style="list-style:disc;padding-left:1.5em;margin-top:0;">
                <?php foreach ( $by_comp as $comp => $count ) : ?>
                    <li><?php echo esc_html( $comp ) . ': ' . (int) $count; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p><?php esc_html_e( 'No competitions found in the last sync — the API returned no matches.', 'ddcwwfcsc' ); ?></p>
        <?php endif; ?>
        <?php
    }

    /**
     * Handle saving price categories (update, add, delete).
     */
    public static function handle_save_price_categories() {
        if ( ! current_user_can( 'manage_ddcwwfcsc_settings' ) ) {
            wp_die( __( 'You do not have permission to do this.', 'ddcwwfcsc' ) );
        }

        check_admin_referer( 'ddcwwfcsc_save_price_categories', 'ddcwwfcsc_pc_nonce' );

        $redirect_args = array( 'page' => 'ddcwwfcsc-settings' );

        // Save ticket default options.
        if ( isset( $_POST['ddcwwfcsc_default_tickets'] ) ) {
            update_option( 'ddcwwfcsc_default_tickets', absint( $_POST['ddcwwfcsc_default_tickets'] ) );
        }
        if ( isset( $_POST['ddcwwfcsc_default_max_per_person'] ) ) {
            update_option( 'ddcwwfcsc_default_max_per_person', absint( $_POST['ddcwwfcsc_default_max_per_person'] ) );
        }

        // Handle deletions first.
        if ( ! empty( $_POST['delete_categories'] ) && is_array( $_POST['delete_categories'] ) ) {
            foreach ( $_POST['delete_categories'] as $term_id ) {
                $term_id = absint( $term_id );
                if ( ! $term_id ) {
                    continue;
                }

                // Check if any fixtures use this term.
                $posts = get_posts( array(
                    'post_type'      => 'ddcwwfcsc_fixture',
                    'tax_query'      => array( array(
                        'taxonomy' => 'ddcwwfcsc_price_category',
                        'terms'    => $term_id,
                    ) ),
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                ) );

                if ( ! empty( $posts ) ) {
                    wp_safe_redirect( add_query_arg( array_merge( $redirect_args, array( 'pc_error' => 'in_use' ) ), admin_url( 'options-general.php' ) ) );
                    exit;
                }

                wp_delete_term( $term_id, 'ddcwwfcsc_price_category' );
            }
        }

        // Update existing categories.
        if ( ! empty( $_POST['categories'] ) && is_array( $_POST['categories'] ) ) {
            foreach ( $_POST['categories'] as $term_id => $data ) {
                $term_id = absint( $term_id );
                if ( ! $term_id ) {
                    continue;
                }

                // Skip if marked for deletion.
                if ( ! empty( $_POST['delete_categories'] ) && in_array( (string) $term_id, $_POST['delete_categories'], true ) ) {
                    continue;
                }

                $name  = sanitize_text_field( $data['name'] ?? '' );
                $price = floatval( $data['price'] ?? 0 );

                if ( $name ) {
                    wp_update_term( $term_id, 'ddcwwfcsc_price_category', array( 'name' => $name ) );
                }
                update_term_meta( $term_id, '_ddcwwfcsc_price', $price );
            }
        }

        // Add new category.
        if ( ! empty( $_POST['new_category']['name'] ) ) {
            $new_name  = sanitize_text_field( $_POST['new_category']['name'] );
            $new_price = floatval( $_POST['new_category']['price'] ?? 0 );

            if ( ! $new_name ) {
                wp_safe_redirect( add_query_arg( array_merge( $redirect_args, array( 'pc_error' => 'missing_name' ) ), admin_url( 'options-general.php' ) ) );
                exit;
            }

            if ( term_exists( $new_name, 'ddcwwfcsc_price_category' ) ) {
                wp_safe_redirect( add_query_arg( array_merge( $redirect_args, array( 'pc_error' => 'duplicate' ) ), admin_url( 'options-general.php' ) ) );
                exit;
            }

            $result = wp_insert_term( $new_name, 'ddcwwfcsc_price_category' );
            if ( ! is_wp_error( $result ) ) {
                update_term_meta( $result['term_id'], '_ddcwwfcsc_price', $new_price );
            }
        }

        wp_safe_redirect( add_query_arg( array_merge( $redirect_args, array( 'pc_updated' => '1' ) ), admin_url( 'options-general.php' ) ) );
        exit;
    }

}
