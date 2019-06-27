declare var mw: any;

export function selectLinks(): HTMLAnchorElement[] {
	return Array.from( document.querySelectorAll( 'a[href]' ) );
}

export function filterLinksByHref( selectedLinks: HTMLAnchorElement[] ): HTMLAnchorElement[] {
	const dataBridgeConfig = mw.config.get( 'wbDataBridgeConfig' ),
		linkRegexp = new RegExp( dataBridgeConfig.hrefRegExp );
	return selectedLinks.filter( ( element: HTMLAnchorElement ): boolean => {
		return !!element.href.match( linkRegexp );
	} );
}
