<?php
/**
 * Plugin deactivation routines.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Deactivator {

    /**
     * Run on plugin deactivation.
     */
    public static function deactivate() {
        // Remove custom role.
        remove_role( 'ddcwwfcsc_president' );

        // Clear fixture sync cron.
        wp_clear_scheduled_hook( 'ddcwwfcsc_fixture_sync' );

        // Clear payment link expiry cron.
        wp_clear_scheduled_hook( 'ddcwwfcsc_expire_payment_links' );

        // Remove custom capabilities from admin.
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->remove_cap( 'manage_ddcwwfcsc_fixtures' );
            $admin->remove_cap( 'manage_ddcwwfcsc_tickets' );
            $admin->remove_cap( 'manage_ddcwwfcsc_settings' );
            $admin->remove_cap( 'manage_ddcwwfcsc_events' );
            $admin->remove_cap( 'manage_ddcwwfcsc_bulletins' );
        }

        flush_rewrite_rules();
    }
}
