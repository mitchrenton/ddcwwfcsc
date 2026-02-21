/**
 * Google Places Autocomplete for the event venue field.
 *
 * Called as the Google Maps API callback via &callback=ddcwwfcscInitEventAutocomplete.
 */
function ddcwwfcscInitEventAutocomplete() {
    var input = document.querySelector( '.ddcwwfcsc-event-location' );
    if ( ! input ) {
        return;
    }

    var latField = document.getElementById( 'ddcwwfcsc_event_lat' );
    var lngField = document.getElementById( 'ddcwwfcsc_event_lng' );

    var autocomplete = new google.maps.places.Autocomplete( input, {
        fields: [ 'formatted_address', 'geometry', 'name' ],
    } );

    autocomplete.addListener( 'place_changed', function() {
        var place = autocomplete.getPlace();

        if ( ! place.geometry || ! place.geometry.location ) {
            return;
        }

        latField.value = place.geometry.location.lat();
        lngField.value = place.geometry.location.lng();

        if ( place.formatted_address ) {
            input.value = place.formatted_address;
        }
    } );
}
