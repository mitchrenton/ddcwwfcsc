<?php
/**
 * Server-side render for the ddcwwfcsc/events block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Query upcoming published events.
$events = get_posts( array(
    'post_type'      => 'ddcwwfcsc_event',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'meta_key'       => '_ddcwwfcsc_event_date',
    'meta_query'     => array(
        array(
            'key'     => '_ddcwwfcsc_event_date',
            'value'   => current_time( 'Y-m-d\TH:i' ),
            'compare' => '>=',
            'type'    => 'CHAR',
        ),
    ),
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
) );

// Enqueue the sign-up form script and styles.
wp_enqueue_style(
    'ddcwwfcsc-event-front',
    DDCWWFCSC_PLUGIN_URL . 'assets/css/event-front.css',
    array(),
    DDCWWFCSC_VERSION
);

wp_enqueue_script(
    'ddcwwfcsc-event-signup',
    DDCWWFCSC_PLUGIN_URL . 'assets/js/event-signup.js',
    array(),
    DDCWWFCSC_VERSION,
    true
);

wp_localize_script( 'ddcwwfcsc-event-signup', 'ddcwwfcsc_event_signup', array(
    'ajax_url' => admin_url( 'admin-ajax.php' ),
    'nonce'    => wp_create_nonce( 'ddcwwfcsc_event_signup' ),
) );
?>

<div <?php echo get_block_wrapper_attributes( array( 'class' => 'ddcwwfcsc-events' ) ); ?>>

    <?php if ( empty( $events ) ) : ?>
        <div class="ddcwwfcsc-no-events">
            <p><?php esc_html_e( 'No upcoming events. Check back soon!', 'ddcwwfcsc' ); ?></p>
        </div>
    <?php else : ?>

        <?php foreach ( $events as $event ) : ?>
            <div class="ddcwwfcsc-event-card">
                <h3 class="ddcwwfcsc-event-card-title"><?php echo esc_html( $event->post_title ); ?></h3>
                <?php echo DDCWWFCSC_Event_Front::render_event_html( $event->ID ); ?>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>
