module.exports = (function ( $, mw ) {
	var deferred = $.Deferred();
	if ( mw.config.exists( 'wbEntity' ) ) {
		// FIXME: Might be a problem if someone will start modifying the object. Should we consider passing JSON as string?
		deferred.resolve( JSON.parse( mw.config.get( 'wbEntity' ) ) );
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
