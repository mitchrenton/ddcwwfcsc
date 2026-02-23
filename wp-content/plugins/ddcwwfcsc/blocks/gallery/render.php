<?php
/**
 * Server-side render for the ddcwwfcsc/gallery block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$images  = isset( $attributes['images'] ) ? (array) $attributes['images'] : array();
$columns = isset( $attributes['columns'] ) ? max( 2, min( 4, (int) $attributes['columns'] ) ) : 3;

if ( empty( $images ) ) {
	return;
}

wp_enqueue_style(
	'ddcwwfcsc-gallery',
	DDCWWFCSC_PLUGIN_URL . 'assets/css/gallery.css',
	array(),
	DDCWWFCSC_VERSION
);

wp_enqueue_script(
	'ddcwwfcsc-gallery-lightbox',
	DDCWWFCSC_PLUGIN_URL . 'assets/js/gallery-lightbox.js',
	array(),
	DDCWWFCSC_VERSION,
	true
);
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'ddcwwfcsc-gallery ddcwwfcsc-gallery--cols-' . $columns ) ); ?>>

	<?php foreach ( $images as $image ) :
		$image_id = absint( $image['id'] ?? 0 );
		$alt      = sanitize_text_field( $image['alt'] ?? '' );
		$caption  = sanitize_text_field( $image['caption'] ?? '' );

		if ( $image_id ) {
			$thumb_url = wp_get_attachment_image_url( $image_id, 'large' ) ?: wp_get_attachment_image_url( $image_id, 'full' );
			$full_url  = wp_get_attachment_image_url( $image_id, 'full' );
		} else {
			$thumb_url = esc_url( $image['url'] ?? '' );
			$full_url  = esc_url( $image['fullUrl'] ?? $image['url'] ?? '' );
		}

		if ( ! $thumb_url ) {
			continue;
		}

		if ( ! $full_url ) {
			$full_url = $thumb_url;
		}
	?>
		<figure class="ddcwwfcsc-gallery__item">
			<a class="ddcwwfcsc-gallery__link"
			   href="<?php echo esc_url( $full_url ); ?>"
			   data-alt="<?php echo esc_attr( $alt ); ?>"
			   data-caption="<?php echo esc_attr( $caption ); ?>">
				<img class="ddcwwfcsc-gallery__img"
				     src="<?php echo esc_url( $thumb_url ); ?>"
				     alt="<?php echo esc_attr( $alt ); ?>"
				     loading="lazy">
			</a>
			<?php if ( $caption ) : ?>
				<figcaption class="ddcwwfcsc-gallery__caption"><?php echo esc_html( $caption ); ?></figcaption>
			<?php endif; ?>
		</figure>

	<?php endforeach; ?>

</div>
