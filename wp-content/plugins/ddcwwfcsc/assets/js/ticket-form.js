( function () {
    'use strict';

    document.addEventListener( 'DOMContentLoaded', function () {
        var forms = document.querySelectorAll( '.ddcwwfcsc-ticket-form' );

        forms.forEach( function ( form ) {
            form.addEventListener( 'submit', function ( e ) {
                e.preventDefault();
                handleSubmit( form );
            } );
        } );

        // Modal open buttons.
        var openBtns = document.querySelectorAll( '[data-open-ticket-modal]' );
        openBtns.forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                var id = btn.getAttribute( 'data-open-ticket-modal' );
                var dialog = document.getElementById( 'ticket-modal-' + id );
                if ( dialog ) {
                    dialog.showModal();
                }
            } );
        } );

        // Modal close buttons.
        var closeBtns = document.querySelectorAll( '.ticket-modal__close' );
        closeBtns.forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                var dialog = btn.closest( 'dialog' );
                if ( dialog ) {
                    dialog.close();
                }
            } );
        } );

        // Backdrop click to close.
        var dialogs = document.querySelectorAll( 'dialog.ticket-modal' );
        dialogs.forEach( function ( dialog ) {
            dialog.addEventListener( 'click', function ( e ) {
                if ( e.target === dialog ) {
                    dialog.close();
                }
            } );
        } );
    } );

    function handleSubmit( form ) {
        var fixtureId  = form.getAttribute( 'data-fixture-id' );
        var submitBtn  = form.querySelector( '.ddcwwfcsc-submit-btn' );
        var messageEl  = form.querySelector( '.ddcwwfcsc-form-message' );
        var name       = form.querySelector( 'input[name="name"]' ).value.trim();
        var email      = form.querySelector( 'input[name="email"]' ).value.trim();
        var numTickets = form.querySelector( 'select[name="num_tickets"]' ).value;

        // Clear previous messages.
        messageEl.textContent = '';
        messageEl.className = 'ddcwwfcsc-form-message';

        // Basic client-side validation.
        if ( ! name || ! email || ! numTickets ) {
            showMessage( messageEl, 'Please fill in all fields.', 'error' );
            return;
        }

        // Disable button.
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting…';

        // Build form data.
        var data = new FormData();
        data.append( 'action', 'ddcwwfcsc_request_tickets' );
        data.append( 'nonce', ddcwwfcsc.nonce );
        data.append( 'fixture_id', fixtureId );
        data.append( 'name', name );
        data.append( 'email', email );
        data.append( 'num_tickets', numTickets );

        fetch( ddcwwfcsc.ajax_url, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
        } )
            .then( function ( response ) {
                return response.json();
            } )
            .then( function ( result ) {
                if ( result.success ) {
                    showMessage( messageEl, result.data.message, 'success' );

                    // Reset form fields to pre-filled values.
                    form.querySelector( 'input[name="name"]' ).value = ddcwwfcsc.user_name || '';
                    form.querySelector( 'input[name="email"]' ).value = ddcwwfcsc.user_email || '';
                    form.querySelector( 'select[name="num_tickets"]' ).selectedIndex = 0;

                    // Update the remaining count display.
                    var remaining = result.data.remaining;
                    var remainingEl = document.querySelector(
                        '.ddcwwfcsc-remaining[data-fixture-id="' + fixtureId + '"]'
                    );
                    if ( remainingEl ) {
                        var totalMatch = remainingEl.textContent.match( /of (\d+)/ );
                        var total = totalMatch ? totalMatch[1] : '?';
                        remainingEl.textContent = remaining + ' of ' + total + ' remaining';
                    }

                    // Update the ticket dropdown options.
                    var select = form.querySelector( 'select[name="num_tickets"]' );
                    var maxPerPerson = select.options.length;
                    var maxSelectable = Math.min( maxPerPerson, remaining );

                    // Rebuild options.
                    select.innerHTML = '';
                    for ( var i = 1; i <= maxSelectable; i++ ) {
                        var option = document.createElement( 'option' );
                        option.value = i;
                        option.textContent = i;
                        select.appendChild( option );
                    }

                    // If no tickets remaining, replace form with sold out.
                    if ( remaining <= 0 ) {
                        var fixture = form.closest( '.ddcwwfcsc-fixture' );
                        form.remove();
                        var soldOut = document.createElement( 'div' );
                        soldOut.className = 'ddcwwfcsc-sold-out';
                        soldOut.innerHTML = '<p>Sold Out</p>';
                        fixture.appendChild( soldOut );
                    }
                } else {
                    showMessage( messageEl, result.data.message, 'error' );
                }
            } )
            .catch( function () {
                showMessage( messageEl, 'An unexpected error occurred. Please try again.', 'error' );
            } )
            .finally( function () {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Request Tickets';
            } );
    }

    function showMessage( el, message, type ) {
        el.textContent = message;
        el.className = 'ddcwwfcsc-form-message ' + type;
    }
} )();
