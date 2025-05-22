/**
 * @license GPL-2.0-or-later
 */
( function ( _wb ) {
	'use strict';

	const Vue = require( 'vue' );
	const App = require( './wikibase.mobileUi/wikibase.mobileUi.statementView.vue' );

	const placeholderElement = document.getElementById( 'mobile-ui-statements-view-placeholder' );

	if ( placeholderElement !== undefined ) {
		mw.log( 'Loading MobileUi Statement View...' );
		Vue.createMwApp( App ).mount( placeholderElement );
	} else {
		mw.error( 'Unable to find statement list placeholder element to mount mobile statement view' );
	}
}(
	wikibase
) );
