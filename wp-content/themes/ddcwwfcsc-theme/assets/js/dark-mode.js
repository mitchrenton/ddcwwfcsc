/**
 * Dark mode toggle behaviour.
 */
( function () {
	'use strict';

	var btn = document.querySelector( '.dark-mode-toggle' );
	if ( ! btn ) return;

	btn.addEventListener( 'click', function () {
		var isDark = document.documentElement.getAttribute( 'data-theme' ) === 'dark';
		if ( isDark ) {
			document.documentElement.removeAttribute( 'data-theme' );
			localStorage.setItem( 'ddcwwfcsc_theme', 'light' );
		} else {
			document.documentElement.setAttribute( 'data-theme', 'dark' );
			localStorage.setItem( 'ddcwwfcsc_theme', 'dark' );
		}
	} );
} )();
