( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {

		// Initialise all ticket forms on the page.
		document.querySelectorAll( '.ddcwwfcsc-ticket-form' ).forEach( initForm );

		// Modal open buttons.
		document.querySelectorAll( '[data-open-ticket-modal]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var dialog = document.getElementById( 'ticket-modal-' + btn.getAttribute( 'data-open-ticket-modal' ) );
				if ( dialog ) {
					dialog.showModal();
				}
			} );
		} );

		// Modal close buttons.
		document.querySelectorAll( '.ticket-modal__close' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var dialog = btn.closest( 'dialog' );
				if ( dialog ) {
					dialog.close();
				}
			} );
		} );

		// Backdrop click to close.
		document.querySelectorAll( 'dialog.ticket-modal' ).forEach( function ( dialog ) {
			dialog.addEventListener( 'click', function ( e ) {
				if ( e.target === dialog ) {
					dialog.close();
				}
			} );
		} );
	} );

	function initForm( form ) {
		var stepperInput = form.querySelector( '.ddcwwfcsc-stepper-input' );
		var decBtn       = form.querySelector( '.ddcwwfcsc-stepper-dec' );
		var incBtn       = form.querySelector( '.ddcwwfcsc-stepper-inc' );

		if ( ! stepperInput ) {
			return;
		}

		function updateUI() {
			var count = parseInt( stepperInput.value, 10 ) || 1;
			var min   = parseInt( stepperInput.min, 10 ) || 1;
			var max   = parseInt( stepperInput.max, 10 ) || 1;

			count = Math.max( min, Math.min( max, count ) );
			stepperInput.value = count;

			decBtn.disabled = count <= min;
			incBtn.disabled = count >= max;

			updateButtonLabel( form, count );
			updateTotalPrice( form, count );
		}

		decBtn.addEventListener( 'click', function () {
			stepperInput.value = ( parseInt( stepperInput.value, 10 ) || 1 ) - 1;
			updateUI();
		} );

		incBtn.addEventListener( 'click', function () {
			stepperInput.value = ( parseInt( stepperInput.value, 10 ) || 1 ) + 1;
			updateUI();
		} );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			handleSubmit( form );
		} );

		// Set initial state.
		updateUI();
	}

	function updateButtonLabel( form, count ) {
		var btn = form.querySelector( '.ddcwwfcsc-submit-btn' );
		if ( btn && ! btn.disabled ) {
			btn.textContent = 'Request ' + count + ( count === 1 ? ' ticket' : ' tickets' );
		}
	}

	function updateTotalPrice( form, count ) {
		var totalEl   = form.querySelector( '.ddcwwfcsc-ticket-total' );
		var unitPrice = parseFloat( form.getAttribute( 'data-price' ) ) || 0;
		if ( totalEl && unitPrice > 0 ) {
			totalEl.textContent = 'Total: £' + ( unitPrice * count ).toFixed( 2 );
		}
	}

	function handleSubmit( form ) {
		var fixtureId    = form.getAttribute( 'data-fixture-id' );
		var submitBtn    = form.querySelector( '.ddcwwfcsc-submit-btn' );
		var messageEl    = form.querySelector( '.ddcwwfcsc-form-message' );
		var stepperInput = form.querySelector( '.ddcwwfcsc-stepper-input' );
		var name         = form.querySelector( 'input[name="name"]' ).value.trim();
		var email        = form.querySelector( 'input[name="email"]' ).value.trim();
		var numTickets   = parseInt( stepperInput.value, 10 ) || 1;
		var originalLabel = submitBtn.textContent;

		// Clear previous messages.
		messageEl.textContent = '';
		messageEl.className   = 'ddcwwfcsc-form-message';

		submitBtn.disabled    = true;
		submitBtn.textContent = 'Submitting\u2026';

		var data = new FormData();
		data.append( 'action',      'ddcwwfcsc_request_tickets' );
		data.append( 'nonce',       ddcwwfcsc.nonce );
		data.append( 'fixture_id',  fixtureId );
		data.append( 'name',        name );
		data.append( 'email',       email );
		data.append( 'num_tickets', numTickets );

		fetch( ddcwwfcsc.ajax_url, {
			method:      'POST',
			body:        data,
			credentials: 'same-origin',
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( result ) {
				submitBtn.disabled = false;

				if ( result.success ) {
					showMessage( messageEl, result.data.message, 'success' );

					window.dataLayer = window.dataLayer || [];
					window.dataLayer.push( {
						event:       'ticket_request',
						fixture_id:  parseInt( fixtureId, 10 ),
						num_tickets: numTickets,
						value:       parseFloat( form.getAttribute( 'data-price' ) || '0' ) * numTickets,
						currency:    'GBP',
					} );

					var remaining    = result.data.remaining;
					var maxPerPerson = parseInt( form.getAttribute( 'data-max-per-person' ), 10 ) || 1;
					var newMax       = Math.min( maxPerPerson, remaining );

					// Update all remaining-count displays for this fixture.
					document.querySelectorAll( '.ddcwwfcsc-remaining[data-fixture-id="' + fixtureId + '"]' ).forEach( function ( el ) {
						el.textContent = remaining + ( remaining === 1 ? ' ticket available' : ' tickets available' );
					} );

					if ( remaining <= 0 ) {
						// Replace the form with a sold-out notice.
						var fixture = form.closest( '.ddcwwfcsc-fixture' );
						form.remove();
						if ( fixture ) {
							var soldOut = document.createElement( 'div' );
							soldOut.className = 'ddcwwfcsc-sold-out';
							soldOut.innerHTML = '<p>Sold Out</p>';
							fixture.appendChild( soldOut );
						}
					} else {
						// Reset stepper to 1, update constraints, refresh UI.
						stepperInput.setAttribute( 'max', newMax );
						stepperInput.value = 1;

						var decBtn = form.querySelector( '.ddcwwfcsc-stepper-dec' );
						var incBtn = form.querySelector( '.ddcwwfcsc-stepper-inc' );
						decBtn.disabled = true;
						incBtn.disabled = newMax <= 1;

						submitBtn.textContent = 'Request 1 ticket';
						updateTotalPrice( form, 1 );
					}
				} else {
					showMessage( messageEl, result.data.message, 'error' );
					submitBtn.textContent = originalLabel;
				}
			} )
			.catch( function () {
				submitBtn.disabled    = false;
				submitBtn.textContent = originalLabel;
				showMessage( messageEl, 'An unexpected error occurred. Please try again.', 'error' );
			} );
	}

	function showMessage( el, message, type ) {
		el.textContent = message;
		el.className   = 'ddcwwfcsc-form-message ' + type;
	}
} )();
