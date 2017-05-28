( function ( $ ) {
	'use strict';

	var form = $( 'form#wb-mergeitems-form1' );

	form.submit( function () {
		$( this ).find( 'button[type="submit"]' ).prop( 'disabled', true );
	} );

}( jQuery ) );
