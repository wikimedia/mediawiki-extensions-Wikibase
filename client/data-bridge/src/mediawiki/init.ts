import MwWindow from '@/@types/mediawiki/MwWindow';
import BridgeDomElementsSelector from '@/mediawiki/BridgeDomElementsSelector';
import { SelectedElement } from '@/mediawiki/SelectedElement';
import Dispatcher from '@/mediawiki/Dispatcher';
import EventTracker from '@/mediawiki/facades/EventTracker';
import MwInitTracker from '@/mediawiki/MwInitTracker';
import PrefixingEventTracker from '@/tracking/PrefixingEventTracker';

const APP_MODULE = 'wikibase.client.data-bridge.app';
const VUE_MODULE = 'vue';
const WBREPO_MODULE = 'mw.config.values.wbRepo';
const FOREIGNAPI_MODULE = 'mediawiki.ForeignApi';
const API_MODULE = 'mediawiki.api';
const ULS_MODULE = 'jquery.uls.data';
const MWLANGUAGE_MODULE = 'mediawiki.language';

function stopNativeClickHandling( event: Event ): void {
	event.preventDefault();
	event.stopPropagation();
}

export default async (): Promise<void> => {
	const mwWindow: MwWindow = window,
		dataBridgeConfig = mwWindow.mw.config.get( 'wbDataBridgeConfig' );
	if ( dataBridgeConfig.hrefRegExp === null ) {
		mwWindow.mw.log.warn(
			'data bridge config incomplete: dataBridgeHrefRegExp missing',
		);
		return;
	}
	const bridgeElementSelector = new BridgeDomElementsSelector( dataBridgeConfig.hrefRegExp );
	const linksToOverload: readonly SelectedElement[] = bridgeElementSelector.selectElementsToOverload();
	if ( linksToOverload.length > 0 ) {
		const eventTracker = new PrefixingEventTracker(
			new EventTracker( mwWindow.mw.track ),
			'MediaWiki.wikibase.client.databridge',
		);
		const dispatcherPromise = mwWindow.mw.loader.using( [
			APP_MODULE,
			VUE_MODULE,
			WBREPO_MODULE,
			FOREIGNAPI_MODULE,
			API_MODULE,
			ULS_MODULE,
			MWLANGUAGE_MODULE,
		] ).then( async ( require ) => {
			const app = await require( APP_MODULE );
			const vue = await require( VUE_MODULE );
			return new Dispatcher( mwWindow, vue, app, dataBridgeConfig, eventTracker );
		} );
		const initTracker = new MwInitTracker( eventTracker, window.performance, window.document );

		linksToOverload.forEach( ( selectedElement: SelectedElement ) => {
			let isOpening = false;
			selectedElement.link.setAttribute( 'aria-haspopup', 'dialog' );
			selectedElement.link.addEventListener( 'click', async ( event: MouseEvent ) => {
				if ( event.altKey || event.ctrlKey || event.shiftKey || event.metaKey ) {
					return;
				}

				stopNativeClickHandling( event );
				if ( isOpening ) {
					return; // user clicked link again while we were awaiting dispatcherPromise, ignore
				}
				isOpening = true;

				const finishTracking = initTracker.startClickDelayTracker();
				const dispatcher = await dispatcherPromise;
				dispatcher.dispatch( selectedElement );
				finishTracking();
				isOpening = false;
			} );
		} );

		initTracker.recordTimeToLinkListenersAttached();
		await dispatcherPromise; // tests need to know when they can expect the click listeners to work
	}
};
