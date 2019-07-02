import MwWindow from '@/@types/mediawiki/MwWindow';

export function selectLinks(): HTMLAnchorElement[] {
	return Array.from( document.querySelectorAll( 'a[href]' ) );
}

export function filterLinksByHref( selectedLinks: HTMLAnchorElement[] ): HTMLAnchorElement[] {
	const dataBridgeConfig = ( window as MwWindow ).mw.config.get( 'wbDataBridgeConfig' );
	if ( dataBridgeConfig.hrefRegExp === null ) {
		( window as MwWindow ).mw.log.warn(
			'data bridge config incomplete: dataBridgeHrefRegExp missing',
		);
		return [];
	}

	const linkRegexp = new RegExp( dataBridgeConfig.hrefRegExp );
	return selectedLinks.filter( function ( element: HTMLAnchorElement ): boolean {
		return !!element.href.match( linkRegexp );
	} );
}
