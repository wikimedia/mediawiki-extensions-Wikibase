( function () {
	'use strict';

	$( 'form#mw-newentity-form1' ).on( 'submit', function () {
		$( this ).find( 'button[type=submit]' ).prop( 'disabled', true );
	} );

}() );
