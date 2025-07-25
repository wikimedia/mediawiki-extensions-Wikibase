/**
 * @license GPL-2.0-or-later
 */
( function ( _wb ) {
	'use strict';

	const Vue = require( 'vue' );
	const Pinia = require( 'pinia' );
	const App = require( './wikibase.mobileUi/wikibase.mobileUi.statementView.vue' );
	const { useServerRenderedHtml } = require( './wikibase.mobileUi/store/serverRenderedHtml.js' );

	const wbui2025StatementList = document.getElementById( 'wikibase-wbui2025-statementgrouplistview' );

	if ( wbui2025StatementList !== undefined ) {
		mw.log( 'Loading MobileUi Statement View...' );
		mw.hook( 'wikibase.entityPage.entityLoaded' ).add( ( data ) => {
			const statements = data.claims;
			const propertyIds = Object.keys( statements );
			const pinia = Pinia.createPinia();
			useServerRenderedHtml( pinia ).importFromElement( wbui2025StatementList );

			// As a proof of concept of passing real data into the Vue component, mount a vue component
			// for the first statement associated with each property
			for ( const propertyId of propertyIds ) {
				const rootProps = {
					statements: statements[ propertyId ],
					propertyId
				};
				const rootContainer = wbui2025StatementList.querySelector( `#wikibase-wbui2025-statementwrapper-${ propertyId }` );
				Vue.createMwApp( App, rootProps )
					.use( pinia )
					.mount( rootContainer );
			}
		} );
	} else {
		mw.error( 'Unable to find statement list placeholder element to mount mobile statement view' );
	}
}(
	wikibase
) );
