jQuery( function ( $ ) {
    "use strict";

    $( 'body' ).bind( 'updated_checkout', function () {
        $('.location_confirmation input' ).on( 'change', function() {
            $( 'body' ).trigger( 'update_checkout' );
        });
    } );

} );