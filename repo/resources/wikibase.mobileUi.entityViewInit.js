/**
 * @license GPL-2.0-or-later
 */
( function ( _wb ) {
	'use strict';

	const Vue = require( 'vue' );
	const App = require( './wikibase.mobileUi/wikibase.mobileUi.statementView.vue' );

	const mexStatementList = document.getElementById( 'wikibase-mex-statementgrouplistview' );

	if ( mexStatementList !== undefined ) {
		mw.log( 'Loading MobileUi Statement View...' );
		mw.hook( 'wikibase.entityPage.entityLoaded' ).add( ( data ) => {
			const statements = data.claims;
			const propertyIds = Object.keys( statements );

			// As a proof of concept of passing real data into the Vue component, mount a vue component
			// for the first statement associated with each property
			for ( const propertyId of propertyIds ) {
				let statement = statements[ propertyId ][ 0 ];
				// get an unfrozen copy so we can add .html
				statement = JSON.parse( JSON.stringify( statement ) );

				// Pull the statement html from the server side rendering
				// XXX: Will this work with somevalue / novalue Snaks?
				statement.mainsnak.html = $( '#wikibase-mex-statementwrapper-' + propertyId ).first()
					.find( '.wikibase-mex-snak-value' ).html();

				Vue.createMwApp( App, {
					statement: statement
				} ).mount( mexStatementList.querySelector( `#wikibase-mex-statementwrapper-${ propertyId }` ) );
			}
		} );
	} else {
		mw.error( 'Unable to find statement list placeholder element to mount mobile statement view' );
	}
}(
	wikibase
) );
