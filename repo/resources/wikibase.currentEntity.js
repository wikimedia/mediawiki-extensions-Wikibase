module.exports = (function ( $, mw ) {
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


	var result = (typeof wbEntity === 'undefined') ? createPendingPromise() : createResolvedPromise( wbEntity );

	return result
		.then( JSON.parse )
		.then( deepFreeze )
		.then( deepSeal );

	function nativePromisesSupported() {
		return typeof Promise !== "undefined" && Promise.toString().indexOf( "[native code]" ) !== -1;
	}

	function createResolvedPromise( value ) {
		if ( nativePromisesSupported() ) {
			return Promise.resolve( value );
		} else {
			return $.Deferred().resolve( value ).promise();
		}
	}

	function createPendingPromise() {
		if ( nativePromisesSupported() ) {
			return new Promise( function () {} );
		} else {
			return $.Deferred().promise();
		}
	}

	/**
	 * Copied from https://developer.mozilla.org/en/docs/Web/JavaScript/Reference/Global_Objects/Object/freeze
	 * @param obj
	 * @return {Object}
	 */
	function deepFreeze( obj ) {
		// Retrieve the property names defined on obj
		var propNames = Object.getOwnPropertyNames( obj );

		// Freeze properties before freezing self
		propNames.forEach( function ( name ) {
			var prop = obj[ name ];

			// Freeze prop if it is an object
			if ( typeof prop === 'object' && prop !== null )
				deepFreeze( prop );
		} );

		// Freeze self (no-op if already frozen)
		return Object.freeze( obj );
	}

	/**
	 * @param obj
	 * @return {Object}
	 */
	function deepSeal( obj ) {
		// Retrieve the property names defined on obj
		var propNames = Object.getOwnPropertyNames( obj );

		// Seal properties before freezing self
		propNames.forEach( function ( name ) {
			var prop = obj[ name ];

			// Seal prop if it is an object
			if ( typeof prop === 'object' && prop !== null )
				deepSeal( prop );
		} );

		// Seal self (no-op if already frozen)
		return Object.seal( obj );
	}

})( $, mw );

/**
Usage example:

mw.loader.using( [ 'wikibase.currentEntity' ] ).then( function ( require ) {
	'use strict';
	// This code will be fired only if and when 'wikibase.currentEntity' is loaded
	require( 'wikibase.currentEntity' ).then( function ( entity ) {
			console.log( entity );
			return entity;
		}
	);
} );

*/
