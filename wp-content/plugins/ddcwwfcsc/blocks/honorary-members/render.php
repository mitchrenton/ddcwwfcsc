<?php
/**
 * Server-side render for the ddcwwfcsc/honorary-members block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$columns = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 3;
$limit   = isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 0;

$columns = max( 2, min( 4, $columns ) );

$query_args = array(
    'post_type'      => 'ddcwwfcsc_honorary',
    'post_status'    => 'publish',
    'posts_per_page' => $limit > 0 ? $limit : -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
);

$members = get_posts( $query_args );

wp_enqueue_style(
    'ddcwwfcsc-honorary-front',
    DDCWWFCSC_PLUGIN_URL . 'assets/css/honorary-front.css',
    array(),
    DDCWWFCSC_VERSION
);
?>

<div <?php echo get_block_wrapper_attributes( array( 'class' => 'ddcwwfcsc-honorary-grid ddcwwfcsc-honorary-grid--cols-' . $columns ) ); ?>>

    <?php if ( empty( $members ) ) : ?>
        <div class="ddcwwfcsc-honorary-none">
            <p><?php esc_html_e( 'No honorary members to display.', 'ddcwwfcsc' ); ?></p>
        </div>
    <?php else : ?>

        <?php foreach ( $members as $member ) : ?>
            <?php echo DDCWWFCSC_Honorary_Front::render_grid_card( $member->ID ); ?>
        <?php endforeach; ?>

    <?php endif; ?>
</div>
