/**
 * Gallery lightbox — vanilla JS, no dependencies.
 * Groups images by their parent .ddcwwfcsc-gallery element and supports
 * prev/next navigation, keyboard arrows, and Escape to close.
 */
( function () {
	'use strict';

	var lightbox    = null;
	var lbImg       = null;
	var lbCaption   = null;
	var lbPrev      = null;
	var lbNext      = null;
	var lbCounter   = null;

	var currentGallery = [];
	var currentIndex   = 0;

	/**
	 * Build the lightbox dialog and append it to the body (once).
	 */
	function buildLightbox() {
		if ( document.getElementById( 'ddcwwfcsc-lightbox' ) ) {
			return;
		}

		var dialog = document.createElement( 'dialog' );
		dialog.id        = 'ddcwwfcsc-lightbox';
		dialog.className = 'ddcwwfcsc-lightbox';
		dialog.setAttribute( 'aria-modal', 'true' );
		dialog.setAttribute( 'aria-label', 'Image viewer' );

		dialog.innerHTML =
			'<div class="ddcwwfcsc-lightbox__inner">' +
				'<img class="ddcwwfcsc-lightbox__img" src="" alt="">' +
				'<p class="ddcwwfcsc-lightbox__caption"></p>' +
				'<span class="ddcwwfcsc-lightbox__counter"></span>' +
			'</div>' +
			'<button class="ddcwwfcsc-lightbox__close" aria-label="Close">' +
				'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
			'</button>' +
			'<button class="ddcwwfcsc-lightbox__nav ddcwwfcsc-lightbox__prev" aria-label="Previous image">' +
				'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15,18 9,12 15,6"/></svg>' +
			'</button>' +
			'<button class="ddcwwfcsc-lightbox__nav ddcwwfcsc-lightbox__next" aria-label="Next image">' +
				'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9,18 15,12 9,6"/></svg>' +
			'</button>';

		document.body.appendChild( dialog );

		lightbox  = dialog;
		lbImg     = dialog.querySelector( '.ddcwwfcsc-lightbox__img' );
		lbCaption = dialog.querySelector( '.ddcwwfcsc-lightbox__caption' );
		lbCounter = dialog.querySelector( '.ddcwwfcsc-lightbox__counter' );
		lbPrev    = dialog.querySelector( '.ddcwwfcsc-lightbox__prev' );
		lbNext    = dialog.querySelector( '.ddcwwfcsc-lightbox__next' );

		// Close button.
		dialog.querySelector( '.ddcwwfcsc-lightbox__close' ).addEventListener( 'click', closeLightbox );

		// Click on backdrop (the dialog element itself, outside inner content).
		dialog.addEventListener( 'click', function ( e ) {
			if ( e.target === dialog ) {
				closeLightbox();
			}
		} );

		// Navigation.
		lbPrev.addEventListener( 'click', function ( e ) { e.stopPropagation(); navigate( -1 ); } );
		lbNext.addEventListener( 'click', function ( e ) { e.stopPropagation(); navigate( 1 ); } );

		// Keyboard navigation.
		document.addEventListener( 'keydown', function ( e ) {
			if ( ! lightbox || ! lightbox.open ) return;
			if ( e.key === 'ArrowLeft' )  { e.preventDefault(); navigate( -1 ); }
			if ( e.key === 'ArrowRight' ) { e.preventDefault(); navigate( 1 ); }
		} );
	}

	function openLightbox( gallery, index ) {
		currentGallery = gallery;
		currentIndex   = index;
		showImage( index );
		lightbox.showModal();
	}

	function closeLightbox() {
		lightbox.close();
	}

	function navigate( dir ) {
		currentIndex = ( currentIndex + dir + currentGallery.length ) % currentGallery.length;
		showImage( currentIndex );
	}

	function showImage( index ) {
		var item    = currentGallery[ index ];
		var hasMany = currentGallery.length > 1;

		lbImg.src        = item.src;
		lbImg.alt        = item.alt || '';
		lbCaption.textContent = item.caption || '';
		lbCaption.hidden  = ! item.caption;
		lbCounter.textContent = hasMany ? ( index + 1 ) + ' / ' + currentGallery.length : '';
		lbCounter.hidden  = ! hasMany;
		lbPrev.hidden     = ! hasMany;
		lbNext.hidden     = ! hasMany;
	}

	/**
	 * Find all galleries on the page and bind click handlers.
	 */
	function init() {
		buildLightbox();

		var galleries = document.querySelectorAll( '.ddcwwfcsc-gallery' );

		galleries.forEach( function ( galleryEl ) {
			var links = galleryEl.querySelectorAll( '.ddcwwfcsc-gallery__link' );
			if ( ! links.length ) return;

			var images = Array.prototype.map.call( links, function ( link ) {
				return {
					src:     link.href,
					alt:     link.dataset.alt     || '',
					caption: link.dataset.caption || '',
				};
			} );

			links.forEach( function ( link, idx ) {
				link.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					openLightbox( images, idx );
				} );
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

} )();
