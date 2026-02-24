<?php
/**
 * Ticket request handling — AJAX endpoints and admin list page.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDCWWFCSC_Ticket_Requests {

    /**
     * Initialize hooks.
     */
    public static function init() {
        // AJAX handler (logged-in users only).
        add_action( 'wp_ajax_ddcwwfcsc_request_tickets', array( __CLASS__, 'handle_request' ) );

        // Admin menu.
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_page' ) );

        // Handle bulk actions.
        add_action( 'admin_init', array( __CLASS__, 'handle_bulk_actions' ) );

        // Admin add-request form handler.
        add_action( 'admin_post_ddcwwfcsc_add_request', array( __CLASS__, 'handle_admin_add_request' ) );
    }

    /**
     * Handle a ticket request via AJAX.
     */
    public static function handle_request() {
        // Require login (defence-in-depth).
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in to request tickets.', 'ddcwwfcsc' ) ) );
        }

        // Verify nonce.
        if ( ! check_ajax_referer( 'ddcwwfcsc_ticket_request', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page and try again.', 'ddcwwfcsc' ) ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ddcwwfcsc_ticket_requests';

        // Sanitize inputs.
        $fixture_id  = absint( $_POST['fixture_id'] ?? 0 );
        $name        = sanitize_text_field( $_POST['name'] ?? '' );
        $email       = sanitize_email( $_POST['email'] ?? '' );
        $num_tickets = absint( $_POST['num_tickets'] ?? 0 );

        // Validate inputs.
        if ( ! $fixture_id || ! $name || ! $email || ! $num_tickets ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in all fields.', 'ddcwwfcsc' ) ) );
        }

        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'ddcwwfcsc' ) ) );
        }

        // Verify fixture exists and is on sale.
        $fixture = get_post( $fixture_id );
        if ( ! $fixture || 'ddcwwfcsc_fixture' !== $fixture->post_type ) {
            wp_send_json_error( array( 'message' => __( 'Invalid fixture.', 'ddcwwfcsc' ) ) );
        }

        $on_sale = get_post_meta( $fixture_id, '_ddcwwfcsc_on_sale', true );
        if ( ! $on_sale ) {
            wp_send_json_error( array( 'message' => __( 'Tickets are not currently on sale for this fixture.', 'ddcwwfcsc' ) ) );
        }

        // Reject requests for fixtures whose date has passed.
        $match_date = get_post_meta( $fixture_id, '_ddcwwfcsc_match_date', true );
        if ( $match_date && strtotime( $match_date ) < current_time( 'timestamp' ) ) {
            wp_send_json_error( array( 'message' => __( 'Tickets are no longer available for this fixture.', 'ddcwwfcsc' ) ) );
        }

        // Check per-member limit across all existing requests for this fixture + email.
        $max_per_person = (int) get_post_meta( $fixture_id, '_ddcwwfcsc_max_per_person', true );
        if ( $max_per_person ) {
            $already_requested = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COALESCE(SUM(num_tickets), 0) FROM {$table}
                  WHERE fixture_id = %d AND email = %s AND status != 'cancelled'",
                $fixture_id,
                $email
            ) );

            if ( $already_requested + $num_tickets > $max_per_person ) {
                $remaining_allowance = max( 0, $max_per_person - $already_requested );
                if ( $remaining_allowance <= 0 ) {
                    wp_send_json_error( array(
                        'message' => __( 'You have already reached the maximum ticket allocation for this fixture.', 'ddcwwfcsc' ),
                    ) );
                } else {
                    wp_send_json_error( array(
                        'message' => sprintf(
                            /* translators: %d: remaining ticket allowance for this member */
                            __( 'You can only request %d more ticket(s) for this fixture.', 'ddcwwfcsc' ),
                            $remaining_allowance
                        ),
                    ) );
                }
            }
        }

        // Check availability — use a lock to prevent race conditions.
        $remaining = (int) get_post_meta( $fixture_id, '_ddcwwfcsc_tickets_remaining', true );

        if ( $remaining < 1 ) {
            wp_send_json_error( array( 'message' => __( 'Sorry, all tickets have been claimed for this fixture.', 'ddcwwfcsc' ) ) );
        }

        if ( $num_tickets > $remaining ) {
            wp_send_json_error( array(
                'message' => sprintf(
                    /* translators: %d: number of tickets remaining */
                    __( 'Only %d ticket(s) remaining. Please reduce your request.', 'ddcwwfcsc' ),
                    $remaining
                ),
            ) );
        }

        // Decrement the remaining count atomically.
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->postmeta}
                 SET meta_value = meta_value - %d
                 WHERE post_id = %d
                   AND meta_key = '_ddcwwfcsc_tickets_remaining'
                   AND CAST(meta_value AS SIGNED) >= %d",
                $num_tickets,
                $fixture_id,
                $num_tickets
            )
        );

        if ( ! $updated ) {
            wp_send_json_error( array( 'message' => __( 'Sorry, those tickets are no longer available. Please try again.', 'ddcwwfcsc' ) ) );
        }

        // Clean the meta cache so subsequent reads are accurate.
        wp_cache_delete( $fixture_id, 'post_meta' );

        // Calculate amount from the fixture's price category.
        $amount      = null;
        $price_terms = get_the_terms( $fixture_id, 'ddcwwfcsc_price_category' );
        if ( $price_terms && ! is_wp_error( $price_terms ) ) {
            $price_val = get_term_meta( $price_terms[0]->term_id, '_ddcwwfcsc_price', true );
            if ( $price_val ) {
                $amount = round( (float) $price_val * $num_tickets, 2 );
            }
        }

        // Insert the request record.
        $insert_data   = array(
            'fixture_id'  => $fixture_id,
            'name'        => $name,
            'email'       => $email,
            'num_tickets' => $num_tickets,
            'status'      => 'pending',
            'created_at'  => current_time( 'mysql' ),
        );
        $insert_format = array( '%d', '%s', '%s', '%d', '%s', '%s' );

        if ( null !== $amount ) {
            $insert_data['amount'] = $amount;
            $insert_format[]       = '%f';
        }

        $wpdb->insert( $table, $insert_data, $insert_format );

        $request_id = $wpdb->insert_id;

        // Send notification emails.
        $new_remaining = (int) get_post_meta( $fixture_id, '_ddcwwfcsc_tickets_remaining', true );

        DDCWWFCSC_Notifications::send_confirmation( $request_id, $fixture_id, $name, $email, $num_tickets );
        DDCWWFCSC_Notifications::send_president_notification( $request_id, $fixture_id, $name, $email, $num_tickets, $new_remaining );

        wp_send_json_success( array(
            'message'   => __( 'Your ticket request has been submitted successfully! You will receive a confirmation email shortly.', 'ddcwwfcsc' ),
            'remaining' => $new_remaining,
        ) );
    }

    /**
     * Add the Ticket Requests submenu page.
     */
    public static function add_admin_page() {
        add_submenu_page(
            'edit.php?post_type=ddcwwfcsc_fixture',
            __( 'Ticket Requests', 'ddcwwfcsc' ),
            __( 'Ticket Requests', 'ddcwwfcsc' ),
            'manage_ddcwwfcsc_tickets',
            'ddcwwfcsc-ticket-requests',
            array( __CLASS__, 'render_admin_page' )
        );
    }

    /**
     * Render the admin page.
     */
    public static function render_admin_page() {
        // Show the add-request form when requested.
        if ( isset( $_GET['action'] ) && 'add' === $_GET['action'] ) {
            self::render_add_form();
            return;
        }

        $list_table = new DDCWWFCSC_Ticket_Requests_List_Table();
        $list_table->prepare_items();

        $add_url = add_query_arg(
            array(
                'post_type' => 'ddcwwfcsc_fixture',
                'page'      => 'ddcwwfcsc-ticket-requests',
                'action'    => 'add',
            ),
            admin_url( 'edit.php' )
        );
        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e( 'Ticket Requests', 'ddcwwfcsc' ); ?>
                <a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'ddcwwfcsc' ); ?></a>
            </h1>

            <?php if ( isset( $_GET['bulk_updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php
                        $count = absint( $_GET['bulk_updated'] );
                        printf(
                            /* translators: %d: number of requests updated */
                            esc_html( _n( '%d request updated.', '%d requests updated.', $count, 'ddcwwfcsc' ) ),
                            $count
                        );
                    ?></p>
                </div>
            <?php endif; ?>

            <?php if ( isset( $_GET['request_added'] ) && '1' === $_GET['request_added'] ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'Ticket request created successfully.', 'ddcwwfcsc' ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( isset( $_GET['add_error'] ) ) : ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['add_error'] ) ) ); ?></p>
                </div>
            <?php endif; ?>

            <form method="get">
                <input type="hidden" name="post_type" value="ddcwwfcsc_fixture">
                <input type="hidden" name="page" value="ddcwwfcsc-ticket-requests">
                <?php
                $list_table->search_box( __( 'Search Requests', 'ddcwwfcsc' ), 'ddcwwfcsc-search' );
                $list_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the "Add New Request" form.
     */
    private static function render_add_form() {
        // Get on-sale fixtures with remaining tickets.
        $fixtures = get_posts( array(
            'post_type'      => 'ddcwwfcsc_fixture',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_ddcwwfcsc_on_sale',
                    'value' => '1',
                ),
                array(
                    'key'     => '_ddcwwfcsc_tickets_remaining',
                    'value'   => '0',
                    'compare' => '>',
                    'type'    => 'NUMERIC',
                ),
            ),
            'orderby'        => 'meta_value',
            'meta_key'       => '_ddcwwfcsc_match_date',
            'order'          => 'ASC',
        ) );

        $back_url = add_query_arg(
            array(
                'post_type' => 'ddcwwfcsc_fixture',
                'page'      => 'ddcwwfcsc-ticket-requests',
            ),
            admin_url( 'edit.php' )
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Add New Ticket Request', 'ddcwwfcsc' ); ?></h1>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="ddcwwfcsc_add_request">
                <?php wp_nonce_field( 'ddcwwfcsc_add_request', '_wpnonce_add_request' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="fixture_id"><?php esc_html_e( 'Fixture', 'ddcwwfcsc' ); ?></label></th>
                        <td>
                            <?php if ( empty( $fixtures ) ) : ?>
                                <p class="description"><?php esc_html_e( 'No fixtures currently on sale with available tickets.', 'ddcwwfcsc' ); ?></p>
                            <?php else : ?>
                                <select name="fixture_id" id="fixture_id" required>
                                    <option value=""><?php esc_html_e( '— Select Fixture —', 'ddcwwfcsc' ); ?></option>
                                    <?php foreach ( $fixtures as $fixture ) :
                                        $remaining     = (int) get_post_meta( $fixture->ID, '_ddcwwfcsc_tickets_remaining', true );
                                        $opponent_data = DDCWWFCSC_Fixture_CPT::get_opponent( $fixture->ID );
                                        $match_date    = get_post_meta( $fixture->ID, '_ddcwwfcsc_match_date', true );
                                        $label         = $opponent_data ? $opponent_data['name'] : $fixture->post_title;
                                        if ( $match_date ) {
                                            $label .= ' — ' . wp_date( 'j M Y', strtotime( $match_date ) );
                                        }
                                        $label .= sprintf( ' (%d remaining)', $remaining );
                                    ?>
                                        <option value="<?php echo esc_attr( $fixture->ID ); ?>"><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="name"><?php esc_html_e( 'Name', 'ddcwwfcsc' ); ?></label></th>
                        <td><input type="text" name="name" id="name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email"><?php esc_html_e( 'Email', 'ddcwwfcsc' ); ?></label></th>
                        <td><input type="email" name="email" id="email" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="num_tickets"><?php esc_html_e( 'Number of Tickets', 'ddcwwfcsc' ); ?></label></th>
                        <td><input type="number" name="num_tickets" id="num_tickets" min="1" value="1" class="small-text" required></td>
                    </tr>
                </table>

                <?php if ( ! empty( $fixtures ) ) : ?>
                    <?php submit_button( __( 'Create Request', 'ddcwwfcsc' ) ); ?>
                <?php endif; ?>
            </form>

            <p><a href="<?php echo esc_url( $back_url ); ?>"><?php esc_html_e( '&larr; Back to Ticket Requests', 'ddcwwfcsc' ); ?></a></p>
        </div>
        <?php
    }

    /**
     * Handle the admin "Add New Request" form submission.
     */
    public static function handle_admin_add_request() {
        // Verify nonce and capability.
        if ( ! isset( $_POST['_wpnonce_add_request'] ) || ! wp_verify_nonce( $_POST['_wpnonce_add_request'], 'ddcwwfcsc_add_request' ) ) {
            wp_die( __( 'Security check failed.', 'ddcwwfcsc' ) );
        }

        if ( ! current_user_can( 'manage_ddcwwfcsc_tickets' ) ) {
            wp_die( __( 'You do not have permission to do this.', 'ddcwwfcsc' ) );
        }

        $redirect_base = add_query_arg(
            array(
                'post_type' => 'ddcwwfcsc_fixture',
                'page'      => 'ddcwwfcsc-ticket-requests',
            ),
            admin_url( 'edit.php' )
        );

        global $wpdb;
        $table = $wpdb->prefix . 'ddcwwfcsc_ticket_requests';

        // Sanitize inputs.
        $fixture_id  = absint( $_POST['fixture_id'] ?? 0 );
        $name        = sanitize_text_field( $_POST['name'] ?? '' );
        $email       = sanitize_email( $_POST['email'] ?? '' );
        $num_tickets = absint( $_POST['num_tickets'] ?? 0 );

        // Validate inputs.
        if ( ! $fixture_id || ! $name || ! $email || ! $num_tickets ) {
            wp_safe_redirect( add_query_arg( 'add_error', urlencode( __( 'Please fill in all fields.', 'ddcwwfcsc' ) ), $redirect_base ) );
            exit;
        }

        if ( ! is_email( $email ) ) {
            wp_safe_redirect( add_query_arg( 'add_error', urlencode( __( 'Please enter a valid email address.', 'ddcwwfcsc' ) ), $redirect_base ) );
            exit;
        }

        // Verify fixture exists and is on sale.
        $fixture = get_post( $fixture_id );
        if ( ! $fixture || 'ddcwwfcsc_fixture' !== $fixture->post_type ) {
            wp_safe_redirect( add_query_arg( 'add_error', urlencode( __( 'Invalid fixture.', 'ddcwwfcsc' ) ), $redirect_base ) );
            exit;
        }

        $on_sale = get_post_meta( $fixture_id, '_ddcwwfcsc_on_sale', true );
        if ( ! $on_sale ) {
            wp_safe_redirect( add_query_arg( 'add_error', urlencode( __( 'Tickets are not currently on sale for this fixture.', 'ddcwwfcsc' ) ), $redirect_base ) );
            exit;
        }

        // Check availability.
        $remaining = (int) get_post_meta( $fixture_id, '_ddcwwfcsc_tickets_remaining', true );
        if ( $remaining < 1 || $num_tickets > $remaining ) {
            wp_safe_redirect( add_query_arg( 'add_error', urlencode( __( 'Not enough tickets remaining.', 'ddcwwfcsc' ) ), $redirect_base ) );
            exit;
        }

        // Decrement the remaining count atomically.
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->postmeta}
                 SET meta_value = meta_value - %d
                 WHERE post_id = %d
                   AND meta_key = '_ddcwwfcsc_tickets_remaining'
                   AND CAST(meta_value AS SIGNED) >= %d",
                $num_tickets,
                $fixture_id,
                $num_tickets
            )
        );

        if ( ! $updated ) {
            wp_safe_redirect( add_query_arg( 'add_error', urlencode( __( 'Those tickets are no longer available. Please try again.', 'ddcwwfcsc' ) ), $redirect_base ) );
            exit;
        }

        // Clean the meta cache.
        wp_cache_delete( $fixture_id, 'post_meta' );

        // Calculate amount from the fixture's price category.
        $amount      = null;
        $price_terms = get_the_terms( $fixture_id, 'ddcwwfcsc_price_category' );
        if ( $price_terms && ! is_wp_error( $price_terms ) ) {
            $price_val = get_term_meta( $price_terms[0]->term_id, '_ddcwwfcsc_price', true );
            if ( $price_val ) {
                $amount = round( (float) $price_val * $num_tickets, 2 );
            }
        }

        // Insert the request record.
        $insert_data   = array(
            'fixture_id'  => $fixture_id,
            'name'        => $name,
            'email'       => $email,
            'num_tickets' => $num_tickets,
            'status'      => 'pending',
            'created_at'  => current_time( 'mysql' ),
        );
        $insert_format = array( '%d', '%s', '%s', '%d', '%s', '%s' );

        if ( null !== $amount ) {
            $insert_data['amount'] = $amount;
            $insert_format[]       = '%f';
        }

        $wpdb->insert( $table, $insert_data, $insert_format );

        $request_id = $wpdb->insert_id;

        // Immediately approve so the requester receives the payment link email right away.
        DDCWWFCSC_Payments::approve_request( $request_id );

        wp_safe_redirect( add_query_arg( 'request_added', '1', $redirect_base ) );
        exit;
    }

    /**
     * Handle bulk status changes.
     */
    public static function handle_bulk_actions() {
        if ( ! isset( $_GET['page'] ) || 'ddcwwfcsc-ticket-requests' !== $_GET['page'] ) {
            return;
        }

        // Detect single row action or bulk action.
        $action      = '';
        $request_ids = array();

        if ( isset( $_GET['row_action'] ) && isset( $_GET['request_id'] ) ) {
            // Single row action.
            $action      = sanitize_text_field( $_GET['row_action'] );
            $request_id  = absint( $_GET['request_id'] );

            if ( ! in_array( $action, array( 'approve', 'mark_paid', 'cancel' ), true ) ) {
                return;
            }
            if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ddcwwfcsc_row_' . $action . '_' . $request_id ) ) {
                return;
            }

            $request_ids = array( $request_id );
        } else {
            // Bulk action.
            if ( isset( $_GET['action'] ) && '-1' !== $_GET['action'] ) {
                $action = sanitize_text_field( $_GET['action'] );
            } elseif ( isset( $_GET['action2'] ) && '-1' !== $_GET['action2'] ) {
                $action = sanitize_text_field( $_GET['action2'] );
            }

            if ( ! in_array( $action, array( 'approve', 'mark_paid', 'cancel' ), true ) ) {
                return;
            }
            if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-ticket-requests' ) ) {
                return;
            }

            $request_ids = array_map( 'absint', $_GET['request'] ?? array() );
        }

        if ( ! current_user_can( 'manage_ddcwwfcsc_tickets' ) ) {
            return;
        }

        if ( empty( $request_ids ) ) {
            return;
        }

        global $wpdb;
        $table   = $wpdb->prefix . 'ddcwwfcsc_ticket_requests';
        $updated = 0;

        foreach ( $request_ids as $request_id ) {
            $request = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $request_id ) );
            if ( ! $request ) {
                continue;
            }

            if ( 'approve' === $action ) {
                // Only approve pending requests.
                if ( 'pending' !== $request->status ) {
                    continue;
                }
                if ( DDCWWFCSC_Payments::approve_request( $request_id ) ) {
                    $updated++;
                }
            } elseif ( 'mark_paid' === $action ) {
                // Mark as paid from pending or approved.
                if ( ! in_array( $request->status, array( 'pending', 'approved' ), true ) ) {
                    continue;
                }
                if ( DDCWWFCSC_Payments::mark_as_paid( $request_id, 'manual' ) ) {
                    $updated++;
                }
            } elseif ( 'cancel' === $action ) {
                // Cancel from pending or approved (not from paid).
                if ( ! in_array( $request->status, array( 'pending', 'approved' ), true ) ) {
                    continue;
                }

                // Return tickets to the pool.
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->postmeta}
                         SET meta_value = meta_value + %d
                         WHERE post_id = %d AND meta_key = '_ddcwwfcsc_tickets_remaining'",
                        $request->num_tickets,
                        $request->fixture_id
                    )
                );
                wp_cache_delete( $request->fixture_id, 'post_meta' );

                $wpdb->update(
                    $table,
                    array( 'status' => 'cancelled' ),
                    array( 'id' => $request_id ),
                    array( '%s' ),
                    array( '%d' )
                );
                $updated++;
            }
        }

        wp_safe_redirect( add_query_arg(
            array(
                'post_type'    => 'ddcwwfcsc_fixture',
                'page'         => 'ddcwwfcsc-ticket-requests',
                'bulk_updated' => $updated,
            ),
            admin_url( 'edit.php' )
        ) );
        exit;
    }
}

/**
 * WP_List_Table for ticket requests.
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class DDCWWFCSC_Ticket_Requests_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => 'ticket-request',
            'plural'   => 'ticket-requests',
            'ajax'     => false,
        ) );
    }

    public function get_columns() {
        return array(
            'cb'          => '<input type="checkbox" />',
            'name'        => __( 'Name', 'ddcwwfcsc' ),
            'email'       => __( 'Email', 'ddcwwfcsc' ),
            'num_tickets' => __( 'Tickets', 'ddcwwfcsc' ),
            'amount'      => __( 'Amount', 'ddcwwfcsc' ),
            'fixture'     => __( 'Fixture', 'ddcwwfcsc' ),
            'status'      => __( 'Status', 'ddcwwfcsc' ),
            'created_at'  => __( 'Date', 'ddcwwfcsc' ),
        );
    }

    public function get_sortable_columns() {
        return array(
            'name'       => array( 'name', false ),
            'created_at' => array( 'created_at', true ),
            'status'     => array( 'status', false ),
        );
    }

    public function prepare_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'ddcwwfcsc_ticket_requests';

        $per_page     = 20;
        $current_page = $this->get_pagenum();
        $orderby      = sanitize_sql_orderby( $_GET['orderby'] ?? 'created_at' ) ?: 'created_at';
        $order        = ( isset( $_GET['order'] ) && 'asc' === strtolower( $_GET['order'] ) ) ? 'ASC' : 'DESC';

        // Whitelist columns to prevent SQL injection.
        $allowed_orderby = array( 'name', 'created_at', 'status', 'email', 'num_tickets' );
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'created_at';
        }

        $where = '1=1';
        $params = array();

        // Filter by fixture.
        if ( ! empty( $_GET['fixture_id'] ) ) {
            $where   .= ' AND fixture_id = %d';
            $params[] = absint( $_GET['fixture_id'] );
        }

        // Filter by status.
        if ( ! empty( $_GET['status'] ) && in_array( $_GET['status'], array( 'pending', 'approved', 'paid', 'cancelled' ), true ) ) {
            $where   .= ' AND status = %s';
            $params[] = sanitize_text_field( $_GET['status'] );
        }

        // Search.
        if ( ! empty( $_GET['s'] ) ) {
            $search   = '%' . $wpdb->esc_like( sanitize_text_field( $_GET['s'] ) ) . '%';
            $where   .= ' AND (name LIKE %s OR email LIKE %s)';
            $params[] = $search;
            $params[] = $search;
        }

        // Get total items.
        $count_query = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        if ( ! empty( $params ) ) {
            $count_query = $wpdb->prepare( $count_query, $params );
        }
        $total_items = (int) $wpdb->get_var( $count_query );

        // Get items.
        $offset = ( $current_page - 1 ) * $per_page;
        $query  = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $query_params = array_merge( $params, array( $per_page, $offset ) );
        $this->items = $wpdb->get_results( $wpdb->prepare( $query, $query_params ) );

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ) );

        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );
    }

    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="request[]" value="%d" />', $item->id );
    }

    public function column_name( $item ) {
        $base_url = add_query_arg(
            array(
                'post_type'  => 'ddcwwfcsc_fixture',
                'page'       => 'ddcwwfcsc-ticket-requests',
                'request_id' => $item->id,
            ),
            admin_url( 'edit.php' )
        );

        $actions = array();

        if ( 'pending' === $item->status ) {
            $actions['approve'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url( wp_nonce_url( add_query_arg( 'row_action', 'approve', $base_url ), 'ddcwwfcsc_row_approve_' . $item->id ) ),
                esc_html__( 'Approve', 'ddcwwfcsc' )
            );
        }

        if ( in_array( $item->status, array( 'pending', 'approved' ), true ) ) {
            $actions['mark_paid'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url( wp_nonce_url( add_query_arg( 'row_action', 'mark_paid', $base_url ), 'ddcwwfcsc_row_mark_paid_' . $item->id ) ),
                esc_html__( 'Mark Paid', 'ddcwwfcsc' )
            );
            $actions['cancel'] = sprintf(
                '<a href="%s" style="color: #b32d2e;">%s</a>',
                esc_url( wp_nonce_url( add_query_arg( 'row_action', 'cancel', $base_url ), 'ddcwwfcsc_row_cancel_' . $item->id ) ),
                esc_html__( 'Cancel', 'ddcwwfcsc' )
            );
        }

        return esc_html( $item->name ) . $this->row_actions( $actions );
    }

    public function column_email( $item ) {
        return '<a href="mailto:' . esc_attr( $item->email ) . '">' . esc_html( $item->email ) . '</a>';
    }

    public function column_num_tickets( $item ) {
        return absint( $item->num_tickets );
    }

    public function column_fixture( $item ) {
        $fixture = get_post( $item->fixture_id );
        if ( ! $fixture ) {
            return '—';
        }
        $opponent_data = DDCWWFCSC_Fixture_CPT::get_opponent( $item->fixture_id );
        $label = $opponent_data ? $opponent_data['name'] : $fixture->post_title;
        return '<a href="' . esc_url( get_edit_post_link( $item->fixture_id ) ) . '">' . esc_html( $label ) . '</a>';
    }

    public function column_amount( $item ) {
        if ( null === $item->amount || '' === $item->amount ) {
            return '—';
        }
        return '&pound;' . esc_html( number_format( (float) $item->amount, 2 ) );
    }

    public function column_status( $item ) {
        $badge = '';
        if ( 'paid' === $item->status && ! empty( $item->payment_method ) ) {
            $method_label = 'stripe' === $item->payment_method ? 'Stripe' : 'Manual';
            $badge = ' <span style="font-size: 11px; color: #666;">(' . esc_html( $method_label ) . ')</span>';
        }

        $statuses = array(
            'pending'   => '<span style="color: #dba617;">&#9679; ' . esc_html__( 'Pending', 'ddcwwfcsc' ) . '</span>',
            'approved'  => '<span style="color: #2271b1;">&#9679; ' . esc_html__( 'Awaiting Payment', 'ddcwwfcsc' ) . '</span>',
            'paid'      => '<span style="color: #00a32a;">&#9679; ' . esc_html__( 'Paid', 'ddcwwfcsc' ) . '</span>' . $badge,
            'cancelled' => '<span style="color: #d63638;">&#9679; ' . esc_html__( 'Cancelled', 'ddcwwfcsc' ) . '</span>',
        );
        return $statuses[ $item->status ] ?? esc_html( $item->status );
    }

    public function column_created_at( $item ) {
        return esc_html( wp_date( 'j M Y, H:i', strtotime( $item->created_at ) ) );
    }

    public function column_default( $item, $column_name ) {
        return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '—';
    }

    public function get_bulk_actions() {
        return array(
            'approve'   => __( 'Approve & Send Payment Link', 'ddcwwfcsc' ),
            'mark_paid' => __( 'Mark as Paid (Manual)', 'ddcwwfcsc' ),
            'cancel'    => __( 'Cancel', 'ddcwwfcsc' ),
        );
    }

    /**
     * Extra table navigation — fixture filter dropdown.
     */
    protected function extra_tablenav( $which ) {
        if ( 'top' !== $which ) {
            return;
        }

        $fixtures = get_posts( array(
            'post_type'      => 'ddcwwfcsc_fixture',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        $current_fixture = absint( $_GET['fixture_id'] ?? 0 );
        $current_status  = sanitize_text_field( $_GET['status'] ?? '' );
        ?>
        <div class="alignleft actions">
            <select name="fixture_id">
                <option value=""><?php esc_html_e( 'All Fixtures', 'ddcwwfcsc' ); ?></option>
                <?php foreach ( $fixtures as $fixture ) : ?>
                    <option value="<?php echo esc_attr( $fixture->ID ); ?>" <?php selected( $current_fixture, $fixture->ID ); ?>>
                        <?php echo esc_html( $fixture->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status">
                <option value=""><?php esc_html_e( 'All Statuses', 'ddcwwfcsc' ); ?></option>
                <option value="pending" <?php selected( $current_status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'ddcwwfcsc' ); ?></option>
                <option value="approved" <?php selected( $current_status, 'approved' ); ?>><?php esc_html_e( 'Awaiting Payment', 'ddcwwfcsc' ); ?></option>
                <option value="paid" <?php selected( $current_status, 'paid' ); ?>><?php esc_html_e( 'Paid', 'ddcwwfcsc' ); ?></option>
                <option value="cancelled" <?php selected( $current_status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'ddcwwfcsc' ); ?></option>
            </select>

            <?php submit_button( __( 'Filter', 'ddcwwfcsc' ), '', 'filter_action', false ); ?>
        </div>
        <?php
    }
}
