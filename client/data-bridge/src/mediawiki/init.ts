import MwWindow from '@/@types/mediawiki/MwWindow';
import AppInformation from '@/definitions/AppInformation';
import BridgeDomElementsSelector from '@/mediawiki/BridgeDomElementsSelector';
import { SelectedElement } from '@/mediawiki/SelectedElement';
import prepareContainer from '@/mediawiki/prepareContainer';
import ApplicationConfig from '@/definitions/ApplicationConfig';

const APP_MODULE = 'wikibase.client.data-bridge.app';
const WBREPO_MODULE = 'mw.config.values.wbRepo';
const APP_DOM_CONTAINER_ID = 'data-bridge-container';

export default async (): Promise<void> => {
	const mwWindow = window as MwWindow,
		dataBridgeConfig = mwWindow.mw.config.get( 'wbDataBridgeConfig' );
	if ( dataBridgeConfig.hrefRegExp === null ) {
		mwWindow.mw.log.warn(
			'data bridge config incomplete: dataBridgeHrefRegExp missing',
		);
		return;
	}
	const bridgeElementSelector = new BridgeDomElementsSelector( dataBridgeConfig.hrefRegExp );
	const linksToOverload: SelectedElement[] = bridgeElementSelector.selectElementsToOverload();
	if ( linksToOverload.length > 0 ) {
		const require = await mwWindow.mw.loader.using( [ APP_MODULE, WBREPO_MODULE ] ),
			app = require( APP_MODULE ),
			repoConfig = mwWindow.mw.config.get( 'wbRepo' ),
			specialEntityDataUrl = repoConfig.url + repoConfig.articlePath.replace(
				'$1',
				'Special:EntityData',
			);

		linksToOverload.map( ( selectedElement: SelectedElement ) => {
			selectedElement.link.addEventListener( 'click', ( event: Event ) => {
				event.preventDefault();
				event.stopPropagation();

				prepareContainer( mwWindow.OO, mwWindow.$, APP_DOM_CONTAINER_ID );

				const configuration: ApplicationConfig = {
					containerSelector: `#${APP_DOM_CONTAINER_ID}`,
					specialEntityDataUrl,
				};
				const information: AppInformation = {
					entityId: selectedElement.entityId,
					propertyId: selectedElement.propertyId,
					editFlow: selectedElement.editFlow,
				};
				app.launch( configuration, information );
			} );
		} );
	}
};
