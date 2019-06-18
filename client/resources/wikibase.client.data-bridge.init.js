$( function () {
	var $validLinks = $( 'a' ).filter( function ( index, element ) {
		return element.href.match( /^https:\/\/www\.wikidata\.org\/wiki\/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)/ );
	} );
	// eslint-disable-next-line no-console
	console.log( 'Number of links potentially usable for wikidata bridge: ' + $validLinks.length );

	if ( $validLinks.length > 0 ) {
		mw.loader.using( [ 'wikibase.client.data-bridge.app' ] ).then( function () {
			/* FIXME */
		} );
	}
} );
