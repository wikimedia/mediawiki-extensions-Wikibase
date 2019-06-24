export default function selectLinks(): HTMLAnchorElement[] {
	const linkRegexp = /^https:\/\/www\.wikidata\.org\/wiki\/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)/;
	return ( Array.from( 
		document.querySelectorAll( 'a[href]' ) 
	).filter( ( element: Element ): boolean => {
			return !!( element as HTMLAnchorElement ).href.match( linkRegexp );
	} 
	) as HTMLAnchorElement[] );
}

