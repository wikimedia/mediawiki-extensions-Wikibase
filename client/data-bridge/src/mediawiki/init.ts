import MwWindow from '@/@types/mediawiki/MwWindow';
import { selectLinks, filterLinksByHref } from './selectLinks';

const APP_MODULE = 'wikibase.client.data-bridge.app';

export default async (): Promise<void> => {
	if ( filterLinksByHref( selectLinks() ).length > 0 ) {
		const require = await ( window as MwWindow ).mw.loader.using( APP_MODULE ),
			app = require( APP_MODULE );
		app.launch( {
			'greeting': 'Hello from wikidata-data-bridge!',
		} );
	}
};
