/**
 * @license GPL-2.0-or-later
 */
( function ( _wb ) {
	'use strict';

	const Vue = require( 'vue' );
	const Pinia = require( 'pinia' );
	const { useServerRenderedHtml } = require( './store/serverRenderedHtml.js' );
	const { useMessageStore } = require( './store/messageStore.js' );
	const { useSavedStatementsStore, getPropertyIds } = require( './store/savedStatementsStore.js' );
	const { useParsedValueStore } = require( './store/parsedValueStore.js' );

	const wbui2025StatementList = document.getElementById( 'wikibase-wbui2025-statementgrouplistview' );

	if ( wbui2025StatementList !== undefined ) {
		const pinia = Pinia.createPinia();

		// This initialization code runs when the Resource Loader loads the module. Other modules are
		// also loaded around the same time. If any of those (most notably, the Kartographer extension's
		// frontend code) run before this one and modify the DOM, what's being imported may not actually
		// be the untouched, server-rendered HTML.
		useServerRenderedHtml( pinia ).importFromElement( wbui2025StatementList );
		useMessageStore( pinia );

		const AddStatementButton = require( './wikibase.wbui2025.addStatementButton.vue' );
		const StatusMessage = require( './wikibase.wbui2025.statusMessage.vue' );
		const StatementGroupView = require( './wikibase.wbui2025.statementGroupView.vue' );

		mw.log( 'Loading MobileUi Statement View...' );
		mw.hook( 'wikibase.entityPage.entityLoaded' ).add( ( data ) => {
			const savedStatementStore = useSavedStatementsStore( pinia );
			savedStatementStore.populateWithClaims( data.claims );
			const parsedValueStore = useParsedValueStore( pinia );
			parsedValueStore.populateWithStatements( data.claims );

			for ( const propertyId of getPropertyIds() ) {
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
				Vue.createMwApp( AddStatementButton, {} )
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
