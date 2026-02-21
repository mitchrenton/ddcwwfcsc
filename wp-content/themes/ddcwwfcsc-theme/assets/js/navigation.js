/**
 * Mobile navigation toggle.
 */
( function () {
	'use strict';

	var toggle = document.querySelector( '.menu-toggle' );
	var nav    = document.getElementById( 'site-navigation' );
	if ( ! toggle || ! nav ) return;

	toggle.addEventListener( 'click', function () {
		var expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
		toggle.setAttribute( 'aria-expanded', String( ! expanded ) );
		nav.classList.toggle( 'toggled' );
	} );

	// Close on Escape.
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && nav.classList.contains( 'toggled' ) ) {
			nav.classList.remove( 'toggled' );
			toggle.setAttribute( 'aria-expanded', 'false' );
			toggle.focus();
		}
	} );
} )();
