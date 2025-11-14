/**
 * @license GPL-2.0-or-later
 */
( function ( wikibase ) {
	'use strict';

	const Vue = require( 'vue' );
	const Pinia = require( 'pinia' );
	const wbui2025 = require( 'wikibase.wbui2025.lib' );

	const wbui2025StatementList = document.getElementById( 'wikibase-wbui2025-statementgrouplistview' );

	if ( wbui2025StatementList !== undefined ) {
		const pinia = Pinia.createPinia();

		// This initialization code runs when the Resource Loader loads the module. Other modules are
		// also loaded around the same time. If any of those (most notably, the Kartographer extension's
		// frontend code) run before this one and modify the DOM, what's being imported may not actually
		// be the untouched, server-rendered HTML.
		wbui2025.store.useServerRenderedHtml( pinia ).importFromElement( wbui2025StatementList );
		wbui2025.store.useMessageStore( pinia );

		const AddStatementButton = require( './components/addStatementButton.vue' );
		const StatusMessage = require( './components/statusMessage.vue' );
		const StatementGroupView = require( './components/statementGroupView.vue' );

		mw.log( 'Loading MobileUi Statement View...' );
		mw.hook( 'wikibase.entityPage.entityLoaded' ).add( ( data ) => {
			const savedStatementStore = wbui2025.store.useSavedStatementsStore( pinia );
			savedStatementStore.populateWithClaims( data.claims );
			const parsedValueStore = wbui2025.store.useParsedValueStore( pinia );
			parsedValueStore.populateWithStatements( data.claims );

			for ( const propertyId of wbui2025.store.getPropertyIds() ) {
				const rootProps = {
					propertyId,
					entityId: data.id
				};
				const rootContainer = wbui2025StatementList.querySelector( `#wikibase-wbui2025-statementwrapper-${ propertyId }` );
				Vue.createMwApp( StatementGroupView, rootProps )
					.use( pinia )
					.mount( rootContainer );
			}

			for ( const addContainer of wbui2025StatementList.getElementsByClassName( 'wikibase-wbui2025-statement-section-add-wrapper' ) ) {
				Vue.createMwApp( AddStatementButton, {
					entityId: data.id
				} )
					.use( pinia )
					.mount( addContainer );
			}
			const statusMessageContainer = document.getElementById( 'wikibase-wbui2025-status-message-mount-point' );
			Vue.createMwApp( StatusMessage, {} )
				.use( pinia )
				.mount( statusMessageContainer );
		} );
	} else {
		mw.error( 'Unable to find statement list placeholder element to mount mobile statement view' );
	}
}(
	wikibase
) );
