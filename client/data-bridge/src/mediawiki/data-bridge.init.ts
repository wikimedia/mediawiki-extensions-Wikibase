if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', countLinks );
} else {
	countLinks();
}

function countLinks(): void {
	const linkRegexp = /^https:\/\/www\.wikidata\.org\/wiki\/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)/;
	const validLinks = Array.from( document.querySelectorAll( 'a[href]' ) )
		.filter( function ( element: Element ): boolean {
			return !!( element as HTMLAnchorElement ).href.match( linkRegexp );
		} );
	// eslint-disable-next-line no-console
	console.log( `Number of links potentially usable for data bridge: ${ validLinks.length }` );
}
