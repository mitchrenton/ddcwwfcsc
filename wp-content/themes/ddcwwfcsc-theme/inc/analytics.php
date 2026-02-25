<?php
/**
 * Google Tag Manager integration.
 *
 * Outputs the GTM head snippet via wp_head and the noscript fallback via
 * wp_body_open. Environment-specific auth tokens are selected automatically
 * based on the site URL; GTM is suppressed on unrecognised (local) domains.
 *
 * Container: GTM-TDBNVN8
 * Environments:
 *   env-2  (live)    ddcwwfcsc.co.uk
 *   env-11 (staging) test.ddcwwfcsc.co.uk
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the GTM environment config for the current site.
 *
 * @return array|null  Assoc array with keys 'id', 'auth', 'preview', or null
 *                     if the domain is not recognised (suppresses GTM).
 */
function ddcwwfcsc_gtm_config(): ?array {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );

	if ( 'test.ddcwwfcsc.co.uk' === $host ) {
		return array(
			'id'      => 'GTM-TDBNVN8',
			'auth'    => 'kNc-ycD-ojchvJOhaIhsNw',
			'preview' => 'env-11',
		);
	}

	if ( 'ddcwwfcsc.co.uk' === $host ) {
		return array(
			'id'      => 'GTM-TDBNVN8',
			'auth'    => 'P5A_zz93CXt0fy8D8XKbiA',
			'preview' => 'env-2',
		);
	}

	return null; // Local / unrecognised domain — suppress GTM.
}

/**
 * Output the GTM <script> snippet in <head>.
 * Hooked at priority 1 so it fires before other wp_head output.
 */
function ddcwwfcsc_gtm_head(): void {
	$config = ddcwwfcsc_gtm_config();
	if ( ! $config ) {
		return;
	}

	$id      = esc_js( $config['id'] );
	$auth    = esc_attr( $config['auth'] );
	$preview = esc_attr( $config['preview'] );
	?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl+'&gtm_auth=<?php echo $auth; ?>&gtm_preview=<?php echo $preview; ?>&gtm_cookies_win=x';
f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo $id; ?>');</script>
<!-- End Google Tag Manager -->
	<?php
}
add_action( 'wp_head', 'ddcwwfcsc_gtm_head', 1 );

/**
 * Output the GTM <noscript> fallback immediately after <body>.
 */
function ddcwwfcsc_gtm_body(): void {
	$config = ddcwwfcsc_gtm_config();
	if ( ! $config ) {
		return;
	}

	$id      = esc_attr( $config['id'] );
	$auth    = esc_attr( $config['auth'] );
	$preview = esc_attr( $config['preview'] );
	?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $id; ?>&gtm_auth=<?php echo $auth; ?>&gtm_preview=<?php echo $preview; ?>&gtm_cookies_win=x"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
	<?php
}
add_action( 'wp_body_open', 'ddcwwfcsc_gtm_body', 1 );

/**
 * Handle server-side analytics events passed via the `ae` query parameter,
 * and register the outbound link click listener.
 *
 * The `ae` param is appended to redirect URLs by the plugin on login/register
 * success so the event fires on the landing page after navigation completes.
 * history.replaceState() cleans the param from the visible URL immediately.
 */
function ddcwwfcsc_analytics_footer(): void {
	if ( ! ddcwwfcsc_gtm_config() ) {
		return; // GTM not active on this domain — skip.
	}

	$allowed = array( 'login', 'register' );
	$ae      = sanitize_key( $_GET['ae'] ?? '' );
	$event   = in_array( $ae, $allowed, true ) ? $ae : '';
	?>
<script>
(function () {
	window.dataLayer = window.dataLayer || [];

	<?php if ( $event ) : ?>
	window.dataLayer.push( { event: <?php echo wp_json_encode( $event ); ?> } );
	if ( history.replaceState ) {
		var _u = new URL( window.location.href );
		_u.searchParams.delete( 'ae' );
		history.replaceState( null, '', _u.toString() );
	}
	<?php endif; ?>

	// Outbound link click tracking.
	document.addEventListener( 'click', function ( e ) {
		var a = e.target.closest( 'a[href]' );
		if ( ! a ) return;
		try {
			var dest = new URL( a.href );
			if ( dest.hostname && dest.hostname !== window.location.hostname ) {
				window.dataLayer.push( {
					event:       'outbound_click',
					link_url:    a.href,
					link_domain: dest.hostname,
					link_text:   ( a.textContent || '' ).trim().slice( 0, 100 ),
				} );
			}
		} catch ( err ) {}
	} );
})();
</script>
	<?php
}
add_action( 'wp_footer', 'ddcwwfcsc_analytics_footer', 99 );
