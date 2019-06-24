export default function selectLinks(): HTMLAnchorElement[] {
	// Todo: create some configuration for this link --> T226494
	const linkRegexp = /^https:\/\/www\.wikidata\.org\/wiki\/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)/;
	const validLinks = Array.from( document.querySelectorAll( 'a[href]' ) )
		.filter( ( element: Element ): boolean => {
			return !!( element as HTMLAnchorElement ).href.match( linkRegexp );
		} );
	return ( validLinks as HTMLAnchorElement[] );
}
