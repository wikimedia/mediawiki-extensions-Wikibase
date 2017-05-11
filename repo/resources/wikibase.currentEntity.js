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

	var nativePromisesSupported = typeof Promise !== "undefined" && Promise.toString().indexOf( "[native code]" ) !== -1;

	function createResolvedPromise( value ) {
		if ( nativePromisesSupported ) {
			return Promise.resolve( value );
		} else {
			return $.Deferred().resolve( value ).promise();
		}
	}

	function createPendingPromise() {
		if ( nativePromisesSupported ) {
			return new Promise( function () {} );
		} else {
			return $.Deferred().promise();
		}
	}

	return {
		then: function ( onFulfilled, onRejected ) {
			if ( typeof wbEntity === 'undefined' ) {
				return createPendingPromise();
			}

			return createResolvedPromise( wbEntity ).then( function ( wbEntity ) {
				return JSON.parse( wbEntity );
			} ).then( onFulfilled, onRejected );
		},
		catch: function ( onRejected ) {
			return this.then( undefined, onRejected );
		}
	};
})( $, mw );

/**
Usage example:

mw.loader.using( [ 'wikibase.currentEntity' ] ).then( function ( require ) {
	// This code will be fired only if and when 'wikibase.currentEntity' is loaded
	require( 'wikibase.currentEntity' ).then( function ( entity ) {
			console.log( entity );
			return entity;
		}
	);
} );

*/
