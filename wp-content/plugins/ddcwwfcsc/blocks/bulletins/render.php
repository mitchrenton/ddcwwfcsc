<?php
/**
 * Server-side render for the ddcwwfcsc/bulletins block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$limit = isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 10;
$speed = isset( $attributes['speed'] ) ? (int) $attributes['speed'] : 30;

$speed = max( 5, min( 120, $speed ) );

$bulletins   = DDCWWFCSC_Bulletin_CPT::get_active_bulletins( $limit );
$extra_texts = apply_filters( 'ddcwwfcsc_bulletin_extra_texts', array() );

if ( empty( $bulletins ) && empty( $extra_texts ) ) {
    return;
}

wp_enqueue_style(
    'ddcwwfcsc-bulletin-front',
    DDCWWFCSC_PLUGIN_URL . 'assets/css/bulletin-front.css',
    array(),
    DDCWWFCSC_VERSION
);

wp_enqueue_script(
    'ddcwwfcsc-bulletin-ticker',
    DDCWWFCSC_PLUGIN_URL . 'assets/js/bulletin-ticker.js',
    array(),
    DDCWWFCSC_VERSION,
    true
);
?>

<div <?php echo get_block_wrapper_attributes(); ?>>
    <?php echo DDCWWFCSC_Bulletin_Front::render_ticker( $bulletins, $speed ); ?>
</div>
