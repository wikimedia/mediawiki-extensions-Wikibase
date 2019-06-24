module.exports = function selectLinks(): HTMLAnchorElement[] {
	const linkRegexp = /^https:\/\/www\.wikidata\.org\/wiki\/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)/;
	const validLinks = Array.from( document.querySelectorAll( 'a[href]' ) )
		.filter( function ( element: Element ): boolean {
			return !!( element as HTMLAnchorElement ).href.match( linkRegexp );
		} );
	return ( validLinks as HTMLAnchorElement[] );
};
