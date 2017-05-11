/**
 * @module wikibase.entityPage.entityLoaded
 * @fires "wikibase.entityPage.entityLoaded"
 *
 * Module fires "wikibase.entityPage.entityLoaded" mediawiki hook as soon as entity JSON is loaded.
 * Listener callback should expect entity object (parsed entity serialization)
 * passed as a first argument.
 *
 * Note: Entity object is completely frozen (readonly) to avoid the case when one of clients
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
 * var entityPromise = $.Deferred( function ( deferred ) {
 *     mw.hook( 'wikibase.entityPage.entityLoaded' ).add( function ( entity ) {
 *         deferred.resolve( entity );
 *     } );
 * } ).promise();
 *
 * @example <caption>Convert to native promise</caption>
 * var entityPromise = new Promise( function ( resolve ) {
 *     mw.hook( 'wikibase.entityPage.entityLoaded' ).add( function ( entity ) {
 *         resolve( entity );
 *     } );
 * } );
 */
( function ( mw ) {
	'use strict';

	var wbEntity;
	if ( mw.config.exists( 'wbEntity' ) ) {
		wbEntity = mw.config.get( 'wbEntity' );
		// TODO Add deprecation warning
	}

	/**
	 * Copied from https://developer.mozilla.org/en/docs/Web/JavaScript/Reference/Global_Objects/Object/freeze
	 *
	 * @param {Object} obj
	 * @return {Object}
	 */
	function deepFreeze( obj ) {
		// Retrieve the property names defined on obj
		var propNames = Object.getOwnPropertyNames( obj );

		// Freeze properties before freezing self
		propNames.forEach( function ( name ) {
			var prop = obj[ name ];

			// Freeze prop if it is an object
			if ( typeof prop === 'object' && prop !== null ) {
				deepFreeze( prop );
			}
		} );

		// Freeze self (no-op if already frozen)
		return Object.freeze( obj );
	}

	if ( wbEntity ) {
		mw.hook( 'wikibase.entityPage.entityLoaded' ).fire( deepFreeze( JSON.parse( wbEntity ) ) );
	}

}( mediaWiki ) );
