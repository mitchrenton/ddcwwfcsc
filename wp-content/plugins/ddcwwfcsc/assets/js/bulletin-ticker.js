( function () {
    'use strict';

    document.addEventListener( 'DOMContentLoaded', function () {
        var tickers = document.querySelectorAll( '.ddcwwfcsc-bulletin-ticker' );

        tickers.forEach( function ( ticker ) {
            var track = ticker.querySelector( '.ddcwwfcsc-bulletin-ticker-track' );
            if ( ! track ) {
                return;
            }

            var speed = parseInt( ticker.getAttribute( 'data-speed' ), 10 ) || 30;
            var trackWidth = track.scrollWidth / 2; // Content is doubled.
            var duration = trackWidth / speed;

            track.style.animationDuration = duration + 's';
        } );
    } );
} )();
