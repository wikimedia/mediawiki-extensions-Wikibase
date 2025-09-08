'use strict';

beforeEach( () => {
	// Create the teleport target
	const element = document.createElement( 'div' );
	element.id = 'mw-teleport-target';
	document.body.appendChild( element );
} );

afterEach( () => {
	const element = document.getElementById( 'mw-teleport-target' );
	if ( element ) {
		document.body.removeChild( element );
	}
} );
