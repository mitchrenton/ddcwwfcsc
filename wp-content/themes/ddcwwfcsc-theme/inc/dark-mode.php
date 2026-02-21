<?php
/**
 * Dark mode: inline script to prevent FOUC + toggle button output.
 *
 * @package DDCWWFCSC_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Outputs an inline <script> in <head> that reads localStorage
 * and applies data-theme="dark" before first paint.
 */
function ddcwwfcsc_dark_mode_inline_script() {
	?>
	<script>
	(function(){
		var t = localStorage.getItem('ddcwwfcsc_theme');
		if ( t === 'dark' || ( ! t && window.matchMedia('(prefers-color-scheme: dark)').matches ) ) {
			document.documentElement.setAttribute('data-theme','dark');
		}
	})();
	</script>
	<?php
}

/**
 * Outputs the dark mode toggle button (placed inside the nav).
 */
function ddcwwfcsc_dark_mode_toggle() {
	?>
	<button class="dark-mode-toggle" type="button" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'ddcwwfcsc-theme' ); ?>">
		<span class="dark-mode-toggle__icon dark-mode-toggle__icon--light" aria-hidden="true">&#9788;</span>
		<span class="dark-mode-toggle__icon dark-mode-toggle__icon--dark" aria-hidden="true">&#9790;</span>
	</button>
	<?php
}
