import MwWindow from '@/@types/mediawiki/MwWindow';
import BridgeDomElementsSelector from '@/mediawiki/BridgeDomElementsSelector';
import { SelectedElement } from '@/mediawiki/SelectedElement';
import prepareContainer from '@/mediawiki/prepareContainer';
import ServiceRepositories from '@/services/ServiceRepositories';
import SpecialPageEntityRepository from '@/data-access/SpecialPageEntityRepository';

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

				const services = new ServiceRepositories();
				services.setEntityRepository( new SpecialPageEntityRepository(
					mwWindow.$,
					specialEntityDataUrl,
				) );

				app.launch(
					{ containerSelector: `#${APP_DOM_CONTAINER_ID}` },
					{
						entityId: selectedElement.entityId,
						propertyId: selectedElement.propertyId,
						editFlow: selectedElement.editFlow,
					},
					services,
				);
			} );
		} );
	}
};
