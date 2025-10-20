import 'cypress-axe';

// eslint-disable-next-line @typescript-eslint/no-namespace
declare namespace Cypress {
	interface Chainable {
		visitTitle( args: string|object, qsDefaults: object ): Chainable<Window>;
		visitTitleMobile( args: string|object ): Chainable<void>;
	}
}

Cypress.Commands.add( 'visitTitle', ( args, qsDefaults = {} ) => {
	let options = null;
	let title = null;
	if ( typeof args === 'string' ) {
		title = args;
		options = {
			qs: Object.assign( qsDefaults, {
				title: args,
			} ),
		};
	} else {
		options = args;
		title = options.title;
		if ( options.qs !== undefined ) {
			options.qs = Object.assign( qsDefaults, options.qs, { title } );
		} else {
			options.qs = Object.assign( qsDefaults, {
				title,
			} );
		}
	}
	cy.visit( Object.assign( options, { url: 'index.php' } ) );
	cy.injectAxe();
	return cy.window();
} );

Cypress.Commands.add( 'visitTitleMobile', ( args ) => cy.visitTitle( args, { mobileaction: 'toggle_view_mobile' } ) );
