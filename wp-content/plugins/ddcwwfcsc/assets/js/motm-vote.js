( function () {
    'use strict';

    document.addEventListener( 'DOMContentLoaded', function () {
        var form = document.querySelector( '.ddcwwfcsc-motm-form' );
        if ( ! form ) {
            return;
        }

        form.addEventListener( 'submit', function ( e ) {
            e.preventDefault();
            handleVote( form );
        } );
    } );

    function handleVote( form ) {
        var fixtureId = form.getAttribute( 'data-fixture-id' );
        var selected  = form.querySelector( 'input[name="motm_player"]:checked' );
        var submitBtn = form.querySelector( '.ddcwwfcsc-motm-submit' );
        var messageEl = form.querySelector( '.ddcwwfcsc-motm-message' );

        // Clear previous messages.
        messageEl.textContent = '';
        messageEl.className = 'ddcwwfcsc-motm-message';

        if ( ! selected ) {
            showMessage( messageEl, 'Please select a player.', 'error' );
            return;
        }

        // Disable button.
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting…';

        // Build form data.
        var data = new FormData();
        data.append( 'action', 'ddcwwfcsc_motm_vote' );
        data.append( 'nonce', ddcwwfcsc_motm.nonce );
        data.append( 'fixture_id', fixtureId );
        data.append( 'player', selected.value );

        fetch( ddcwwfcsc_motm.ajax_url, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
        } )
            .then( function ( response ) {
                return response.json();
            } )
            .then( function ( result ) {
                if ( result.success ) {
                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push( {
                        event:      'motm_vote',
                        fixture_id: parseInt( fixtureId, 10 ),
                        player:     selected.value,
                    } );

                    // Replace the form with the voted message + tally.
                    var section = form.closest( '.ddcwwfcsc-motm-section' );
                    if ( section ) {
                        var heading = section.querySelector( 'h2' );
                        var html = '';
                        if ( heading ) {
                            html += heading.outerHTML;
                        }
                        html += '<p class="ddcwwfcsc-motm-voted">';
                        html += result.data.message.replace(
                            result.data.player,
                            '<strong>' + escapeHtml( result.data.player ) + '</strong>'
                        );
                        html += '</p>';
                        html += result.data.tally_html;
                        section.innerHTML = html;
                    }
                } else {
                    showMessage( messageEl, result.data.message, 'error' );
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Vote';
                }
            } )
            .catch( function () {
                showMessage( messageEl, 'An unexpected error occurred. Please try again.', 'error' );
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Vote';
            } );
    }

    function showMessage( el, message, type ) {
        el.textContent = message;
        el.className = 'ddcwwfcsc-motm-message ' + type;
    }

    function escapeHtml( text ) {
        var div = document.createElement( 'div' );
        div.appendChild( document.createTextNode( text ) );
        return div.innerHTML;
    }
} )();
