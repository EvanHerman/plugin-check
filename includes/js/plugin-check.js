( function( $ ) {

    $( document ).on( 'submit', 'form.scan-plugin', function() {
        $( 'form.scan-plugin input[type="submit"]' ).attr( 'disabled', 'disabled' );
        $( 'form.scan-plugin .spinner' ).addClass( 'is-active' );
    } );

} )( jQuery );