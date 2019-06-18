$( function () {
	var $validLinks = $( 'a' ).filter( function ( index, element ) {
		return element.href.match( /^https:\/\/www\.wikidata\.org\/wiki\/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)/ );
	} );
	console.log( 'Number of links potentially usable for wikidata bridge: ' + $validLinks.length );

	if ( $validLinks.length > 0 ) {
		// somehow load our data-bridge js and css now
	}
} );
