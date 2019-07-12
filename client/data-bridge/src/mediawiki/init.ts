import MwWindow from '@/@types/mediawiki/MwWindow';
import BridgeDomElementsSelector from '@/mediawiki/BridgeDomElementsSelector';

const APP_MODULE = 'wikibase.client.data-bridge.app';

export default async (): Promise<void> => {
	const dataBridgeConfig = ( window as MwWindow ).mw.config.get( 'wbDataBridgeConfig' );
	if ( dataBridgeConfig.hrefRegExp === null ) {
		( window as MwWindow ).mw.log.warn(
			'data bridge config incomplete: dataBridgeHrefRegExp missing',
		);
		return;
	}
	const bridgeElementSelector = new BridgeDomElementsSelector( dataBridgeConfig.hrefRegExp );
	if ( bridgeElementSelector.selectElementsToOverload().length > 0 ) {
		const require = await ( window as MwWindow ).mw.loader.using( APP_MODULE ),
			app = require( APP_MODULE );
		app.launch( {
			'greeting': 'Hello from wikidata-data-bridge!',
		} );
	}
};
