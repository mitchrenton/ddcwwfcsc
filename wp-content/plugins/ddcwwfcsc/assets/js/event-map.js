/**
 * Event venue Google Maps initialisation.
 *
 * Called as the Google Maps API callback via &callback=ddcwwfcscInitEventMaps.
 */
function ddcwwfcscInitEventMaps() {
    var containers = document.querySelectorAll( '.ddcwwfcsc-event-map' );

    containers.forEach( function( container ) {
        var lat  = parseFloat( container.getAttribute( 'data-lat' ) );
        var lng  = parseFloat( container.getAttribute( 'data-lng' ) );
        var name = container.getAttribute( 'data-name' ) || '';

        if ( isNaN( lat ) || isNaN( lng ) ) {
            return;
        }

        var position = { lat: lat, lng: lng };

        var map = new google.maps.Map( container, {
            center: position,
            zoom: 15,
            mapTypeControl: false,
            streetViewControl: false,
        } );

        var marker = new google.maps.Marker( {
            position: position,
            map: map,
            title: name,
        } );

        if ( name ) {
            var infoWindow = new google.maps.InfoWindow( {
                content: '<strong>' + name + '</strong>',
            } );

            marker.addListener( 'click', function() {
                infoWindow.open( map, marker );
            } );
        }
    } );
}
