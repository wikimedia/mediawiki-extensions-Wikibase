( function () {
	'use strict';

	$( 'form#wb-mergeitems-form1' ).on( 'submit', function () {
		$( this ).find( 'button[type=submit]' ).prop( 'disabled', true );
	} );

}() );
