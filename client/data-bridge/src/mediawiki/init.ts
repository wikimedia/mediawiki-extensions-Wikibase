import MwWindow from '@/@types/mediawiki/MwWindow';
import AppInformation from '@/definitions/AppInformation';
import BridgeDomElementsSelector from '@/mediawiki/BridgeDomElementsSelector';
import { SelectedElement } from '@/mediawiki/SelectedElement';

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
	const linksToOverload: SelectedElement[] = bridgeElementSelector.selectElementsToOverload();
	if ( linksToOverload.length > 0 ) {
		const require = await ( window as MwWindow ).mw.loader.using( APP_MODULE ),
			app = require( APP_MODULE );
		linksToOverload.map( ( selectedElement: SelectedElement ) => {
			selectedElement.link.addEventListener( 'click', ( event: Event ) => {
				event.preventDefault();
				event.stopPropagation();
				const information: AppInformation = {
					entityID: selectedElement.entityID,
					propertyID: selectedElement.propertyID,
					editFlow: selectedElement.editFlow,
				};
				app.launch( information );
			} );
		} );
	}
};
