/**
 * @license GPL-2.0-or-later
 */
( function ( _wb ) {
	'use strict';

	const Vue = require( 'vue' );
	const Pinia = require( 'pinia' );
	const App = require( './wikibase.wbui2025/wikibase.wbui2025.statementView.vue' );
	const { useServerRenderedHtml } = require( './wikibase.wbui2025/store/serverRenderedHtml.js' );

	const wbui2025StatementList = document.getElementById( 'wikibase-wbui2025-statementgrouplistview' );

	if ( wbui2025StatementList !== undefined ) {
		const pinia = Pinia.createPinia();

		// This initialization code runs when the Resource Loader loads the module. Other modules are
		// also loaded around the same time. If any of those (most notably, the Kartographer extension's
		// frontend code) run before this one and modify the DOM, what's being imported may not actually
		// be the untouched, server-rendered HTML.
		useServerRenderedHtml( pinia ).importFromElement( wbui2025StatementList );

		mw.log( 'Loading MobileUi Statement View...' );
		mw.hook( 'wikibase.entityPage.entityLoaded' ).add( ( data ) => {
			const statements = data.claims;
			const propertyIds = Object.keys( statements );

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
