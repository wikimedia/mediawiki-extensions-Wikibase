/**
 * @module wikibase.entityPage.entityLoaded
 * @fires "wikibase.entityPage.entityLoaded"
 *
 * This module fires a MediaWiki hook named "wikibase.entityPage.entityLoaded" as soon as the JSON
 * representing the entity stored on the current entity page is loaded. Listener callbacks should
 * expect the entity as a native JavaScript object (the parsed JSON serialization) passed as the
 * first argument.
 *
 * Note: The entity object is completely frozen (read-only) to avoid the case when one of clients
 *       accidentally change it and break other clients.
 *
 * @example <caption>Basic usage</caption>
 * mw.hook( 'wikibase.entityPage.entityLoaded' ).add( function ( entity ) {
 *     'use strict';
 *     // Your code goes here
 *     console.log( entity );
 * } );
 *
 * @example <caption>Convert to jQuery promise</caption>
 * const entityPromise = $.Deferred( function ( deferred ) {
 *     mw.hook( 'wikibase.entityPage.entityLoaded' ).add( function ( entity ) {
 *         deferred.resolve( entity );
 *     } );
 * } ).promise();
 *
 * @example <caption>Convert to native promise</caption>
 * const entityPromise = new Promise( function ( resolve ) {
 *     mw.hook( 'wikibase.entityPage.entityLoaded' ).add( function ( entity ) {
 *         resolve( entity );
 *     } );
 * } );
 */
( function ( mwConfig ) {
	'use strict';

	/**
	 * Copied from https://developer.mozilla.org/en/docs/Web/JavaScript/Reference/Global_Objects/Object/freeze
	 *
	 * @param {Object} obj
	 * @return {Object}
	 */
	function deepFreeze( obj ) {
		// Retrieve the property names defined on obj
		const propNames = Object.getOwnPropertyNames( obj );

		// Freeze properties before freezing self
		propNames.forEach( function ( name ) {
			const prop = obj[ name ];

			// Freeze prop if it is an object
			if ( typeof prop === 'object' && prop !== null ) {
				deepFreeze( prop );
			}
		} );

		// Freeze self (no-op if already frozen)
		return Object.freeze( obj );
	}

	const entityId = mwConfig.get( 'wbEntityId' );

	if ( entityId === null ) {
		mw.log.error(
			'wikibase.entityPage.entityLoaded should only be loaded ' +
			'in conjunction with the right JS config (e.g. ParserOutputJsConfigBuilder)!'
		);
		return;
	}

	// Load from Special:EntityData because it gets cached in several layers
	const specialEntityDataPath = mwConfig.get( 'wgArticlePath' ).replace(
		/\$1/g, 'Special:EntityData/' + entityId + '.json'
	);
	const url = new URL( specialEntityDataPath, location.href );
	url.searchParams.set( 'revision', mwConfig.get( 'wgRevisionId' ) );

	$.getJSON( url.toString(), ( data ) => {
		if ( !data || !data.entities || !data.entities[ entityId ] ) {
			return;
		}

		const wbEntity = data.entities[ entityId ];

		if ( wbEntity ) {
			// Note this assumes "wbEntity" contains valid JSON, and will throw an error otherwise.
			mw.hook( 'wikibase.entityPage.entityLoaded' ).fire( deepFreeze( wbEntity ) );
		}
	} );

}( mw.config ) );
