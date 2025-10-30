( function () {
	'use strict';

	if ( !mw.config.get('wbFederatedValuesEnabled') ) {
		return;
	}

	const ENTITY_SEARCH_CONTEXT = require( './entitySearchContext.json' );

	mw.hook( 'wikibase.entityselector.search.api-parameters.searchcontext.value' )
		.add( function ( data ) {
			data.searchcontext = ENTITY_SEARCH_CONTEXT.VALUE;
		} );
}() );
