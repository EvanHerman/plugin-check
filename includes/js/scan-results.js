( function() {

    jQuery( document ).ready( function( $ ) {
        wp.codeEditor.initialize( $('.scan-results' ), scan_result_settings );
        wp.codeEditor.initialize( $('.phpcs-results' ), scan_result_settings );
    } );

} )( jQuery );