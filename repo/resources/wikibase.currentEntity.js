/* eslint no-console: ["error", { allow: ["warn"] }] */

module.exports = ( function ( $, mw ) {
	'use strict';

	var wbEntity;
	if ( mw.config.exists( 'wbEntity' ) ) {
		wbEntity = mw.config.get( 'wbEntity' );
		if ( mw.config.values && mw.config.values.wbEntity ) {
			Object.defineProperty( mw.config.values, 'wbEntity', {
				enumerable: true,
				get: function () {
					console.warn(
						'Configuration variable "wbEntity" is deprecated. ' +
						'Use module "wikibase.currentEntity" instead.'
					);
					return wbEntity;
				}
			} );
		}
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
		mw.hook( 'wikibase.entity' ).fire( deepFreeze( JSON.parse( wbEntity ) ) );
	}

} )( jQuery, mediaWiki );

/**
 Usage example:
	 mw.hook( 'wikibase.entity' ).add( function ( entity ) {
		'use strict';
		console.log( entity );
	 } );

 Can be easily converted to promise.

 Native:
	 new Promise( function ( resolve ) {
			mw.hook( 'wikibase.entity' ).add( function ( entity ) {
				resolve( entity );
			} );
		} ).then( function ( entity ) {
			console.log( entity );
		} );

 jQuery:
	 $.Deferred( function ( deferred ) {
			mw.hook( 'wikibase.entity' ).add( function ( entity ) {
				deferred.resolve( entity );
			} );
		} ).then( function ( entity ) {
			console.log( entity );
		} );
 */
