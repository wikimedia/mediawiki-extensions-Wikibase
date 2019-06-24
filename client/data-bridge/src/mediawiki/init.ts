import MwWindow from '@/@types/mediawiki/MwWindow';
import { selectLinks, filterLinksByHref } from './selectLinks';

export default (): void => {
	if ( filterLinksByHref( selectLinks() ).length > 0 ) {
		( window as MwWindow ).mw.loader.using( 'wikibase.client.data-bridge.app' ).then( () => {
			/* nothing to do yet */
		} );
	}
};
