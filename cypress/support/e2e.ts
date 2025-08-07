Cypress.Commands.add( 'visitTitle', ( args ) => {
	let options = null;
	let title = null;
	if ( typeof args === 'string' ) {
		title = args;
		options = {
			qs: {
				title: args
			}
		};
	} else {
		options = args;
		title = options.title;
		if ( options.qs !== undefined ) {
			options.qs.title = title;
		} else {
			options.qs = {
				title
			};
		}
	}
	return cy.visit( Object.assign( options, { url: 'index.php' } ) );
} );
