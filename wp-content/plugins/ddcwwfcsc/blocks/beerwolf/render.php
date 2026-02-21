<?php
/**
 * Server-side render for the ddcwwfcsc/beerwolf block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$opponent_id = isset( $attributes['opponentId'] ) ? (int) $attributes['opponentId'] : 0;

if ( ! $opponent_id ) {
    return;
}

$beerwolf_post = DDCWWFCSC_Beerwolf_CPT::get_beerwolf_for_opponent( $opponent_id );

if ( ! $beerwolf_post ) {
    return;
}

// Enqueue the shared front-end styles.
wp_enqueue_style(
    'ddcwwfcsc-beerwolf-front',
    DDCWWFCSC_PLUGIN_URL . 'blocks/beerwolf/style.css',
    array(),
    DDCWWFCSC_VERSION
);
?>

<div <?php echo get_block_wrapper_attributes( array( 'class' => 'ddcwwfcsc-beerwolf' ) ); ?>>
    <?php if ( $beerwolf_post->post_content ) : ?>
        <div class="ddcwwfcsc-beerwolf-intro">
            <?php echo apply_filters( 'the_content', $beerwolf_post->post_content ); ?>
        </div>
    <?php endif; ?>

    <?php echo DDCWWFCSC_Beerwolf_Front::render_beerwolf_html( $beerwolf_post->ID ); ?>
</div>
