import MwWindow from '@/@types/mediawiki/MwWindow';
import BridgeDomElementsSelector from '@/mediawiki/BridgeDomElementsSelector';
import { SelectedElement } from '@/mediawiki/SelectedElement';
import Dispatcher from '@/mediawiki/Dispatcher';

const APP_MODULE = 'wikibase.client.data-bridge.app';
const WBREPO_MODULE = 'mw.config.values.wbRepo';
const FOREIGNAPI_MODULE = 'mediawiki.ForeignApi';
const ULS_MODULE = 'jquery.uls.data';
const MWLANGUAGE_MODULE = 'mediawiki.language';

function stopNativeClickHandling( event: Event ): void {
	event.preventDefault();
	event.stopPropagation();
}

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
		const require = await mwWindow.mw.loader.using( [
				APP_MODULE,
				WBREPO_MODULE,
				FOREIGNAPI_MODULE,
				ULS_MODULE,
				MWLANGUAGE_MODULE,
			] ),
			app = require( APP_MODULE ),
			dispatcher = new Dispatcher( mwWindow, app, dataBridgeConfig );

		linksToOverload.map( ( selectedElement: SelectedElement ) => {
			selectedElement.link.setAttribute( 'aria-haspopup', 'dialog' );
			selectedElement.link.addEventListener( 'click', ( event: Event ) => {
				stopNativeClickHandling( event );
				dispatcher.dispatch( selectedElement );
			} );
		} );
	}
};
