document.addEventListener( 'DOMContentLoaded', function () {
	const linkRegexp = /^https:\/\/www\.wikidata\.org\/wiki\/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)/;
	const validLinks = Array.from( document.querySelectorAll( 'a[href]' ) )
		.filter( function ( element: Element ) {
			return ( element as HTMLAnchorElement ).href.match( linkRegexp );
		} );
	// eslint-disable-next-line no-console
	console.log( 'Number of links potentially usable for wikidata bridge: ' + validLinks.length );
} );
