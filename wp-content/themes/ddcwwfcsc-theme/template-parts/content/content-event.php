<?php
/**
 * Template part for displaying an event card in archives.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

$post_id       = get_the_ID();
$event_date    = ddcwwfcsc_event_date( $post_id );
$location      = get_post_meta( $post_id, '_ddcwwfcsc_event_location', true );
$price_member  = get_post_meta( $post_id, '_ddcwwfcsc_event_price_member', true );
$price_non     = get_post_meta( $post_id, '_ddcwwfcsc_event_price_non_member', true );
$signups       = class_exists( 'DDCWWFCSC_Event_CPT' ) ? DDCWWFCSC_Event_CPT::get_signups( $post_id ) : array();
$signup_count  = is_array( $signups ) ? count( $signups ) : 0;
?>
<div class="event-card">
	<?php if ( $event_date ) : ?>
		<div class="event-card__date"><?php echo esc_html( $event_date ); ?></div>
	<?php endif; ?>

	<h3 class="event-card__title">
		<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
	</h3>

	<?php if ( $location ) : ?>
		<div class="event-card__location"><?php echo esc_html( $location ); ?></div>
	<?php endif; ?>

	<?php if ( $price_member || $price_non ) : ?>
		<div class="event-card__price">
			<?php if ( $price_member ) : ?>
				<?php printf( '%s: &pound;%s', esc_html__( 'Members', 'ddcwwfcsc-theme' ), esc_html( $price_member ) ); ?>
			<?php endif; ?>
			<?php if ( $price_member && $price_non ) : ?>
				&nbsp;&middot;&nbsp;
			<?php endif; ?>
			<?php if ( $price_non ) : ?>
				<?php printf( '%s: &pound;%s', esc_html__( 'Non-members', 'ddcwwfcsc-theme' ), esc_html( $price_non ) ); ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $signup_count > 0 ) : ?>
		<div class="event-card__signups">
			<?php printf( esc_html( _n( '%d person signed up', '%d people signed up', $signup_count, 'ddcwwfcsc-theme' ) ), $signup_count ); ?>
		</div>
	<?php endif; ?>
</div>
