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
				Vue.createMwApp( App, {
					statement: statements[ propertyId ][ 0 ]
				} ).mount( mexStatementList.querySelector( `#wikibase-mex-statementwrapper-${ propertyId }` ) );
			}
		} );
	} else {
		mw.error( 'Unable to find statement list placeholder element to mount mobile statement view' );
	}
}(
	wikibase
) );
