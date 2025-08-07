Cypress.Commands.add( 'visitTitle', ( args, qsDefaults = {} ) => {
	let options = null;
	let title = null;
	if ( typeof args === 'string' ) {
		title = args;
		options = {
			qs: Object.assign( qsDefaults, {
				title: args
			} )
		};
	} else {
		options = args;
		title = options.title;
		if ( options.qs !== undefined ) {
			options.qs = Object.assign( qsDefaults, options.qs, { title } );
		} else {
			options.qs = Object.assign( qsDefaults, {
				title
			} );
		}
	}
	return cy.visit( Object.assign( options, { url: 'index.php' } ) );
} );

Cypress.Commands.add( 'visitTitleMobile', ( args ) => {
	return cy.visitTitle( args, { mobileaction: 'toggle_view_mobile' } );
} );
