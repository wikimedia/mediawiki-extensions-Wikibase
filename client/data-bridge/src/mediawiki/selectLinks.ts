export function selectLinks(): HTMLAnchorElement[] {
	return Array.from( document.querySelectorAll( 'a[href]' ) );
}

export function filterLinksByHref( selectedLinks: HTMLAnchorElement[] ): HTMLAnchorElement[] {
	// Todo: create some configuration for this link --> T226494
	const linkRegexp = /^https:\/\/www\.wikidata\.org\/wiki\/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)/;
	return selectedLinks.filter( ( element: HTMLAnchorElement ): boolean => {
		return !!element.href.match( linkRegexp );
	} );
}
