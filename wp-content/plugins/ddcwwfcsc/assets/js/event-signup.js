( function () {
    'use strict';

    document.addEventListener( 'DOMContentLoaded', function () {
        var forms = document.querySelectorAll( '.ddcwwfcsc-event-signup-form' );

        forms.forEach( function ( form ) {
            form.addEventListener( 'submit', function ( e ) {
                e.preventDefault();
                handleSubmit( form );
            } );
        } );
    } );

    function handleSubmit( form ) {
        var eventId   = form.getAttribute( 'data-event-id' );
        var submitBtn = form.querySelector( '.ddcwwfcsc-event-signup-btn' );
        var messageEl = form.querySelector( '.ddcwwfcsc-event-signup-message' );
        var name      = form.querySelector( 'input[name="name"]' ).value.trim();
        var email     = form.querySelector( 'input[name="email"]' ).value.trim();

        // Clear previous messages.
        messageEl.textContent = '';
        messageEl.className = 'ddcwwfcsc-event-signup-message';

        // Basic client-side validation.
        if ( ! name || ! email ) {
            showMessage( messageEl, 'Please fill in all fields.', 'error' );
            return;
        }

        // Disable button.
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting…';

        // Build form data.
        var data = new FormData();
        data.append( 'action', 'ddcwwfcsc_event_signup' );
        data.append( 'nonce', ddcwwfcsc_event_signup.nonce );
        data.append( 'post_id', eventId );
        data.append( 'name', name );
        data.append( 'email', email );

        fetch( ddcwwfcsc_event_signup.ajax_url, {
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
                    form.querySelector( 'input[name="name"]' ).value = ddcwwfcsc_event_signup.user_name || '';
                    form.querySelector( 'input[name="email"]' ).value = ddcwwfcsc_event_signup.user_email || '';

                    // Update the attendee count.
                    var countEl = document.querySelector( '.ddcwwfcsc-event-count' );
                    if ( countEl ) {
                        countEl.textContent = result.data.count;
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
                submitBtn.textContent = 'Sign Up';
            } );
    }

    function showMessage( el, message, type ) {
        el.textContent = message;
        el.className = 'ddcwwfcsc-event-signup-message ' + type;
    }
} )();
