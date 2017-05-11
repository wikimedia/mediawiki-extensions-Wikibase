module.exports = (function ( $, mw ) {
	var deferred = $.Deferred();
	if ( mw.config.exists( 'wbEntity' ) ) {
		// FIXME: Might be a problem if someone will start modifying the object. Should we consider passing JSON as string?
		//        Or may be hack around so each call of `then` would pass a different object?
		var wbEntity = mw.config.get( 'wbEntity' );
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
		deferred.resolve( JSON.parse( wbEntity ) );
	}
	var jqueryPromise = deferred.promise();

	var nativePromisesSupported = typeof Promise !== "undefined" && Promise.toString().indexOf("[native code]") !== -1;

	if (nativePromisesSupported ) {
		return Promise.resolve(jqueryPromise)
	} else {
		return jqueryPromise;
	}
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
