import Track from '@/vue-plugins/Track';
import Message from '@/vue-plugins/Message';
import MwWindow from '@/@types/mediawiki/MwWindow';
import MWHookHandler from '@/MWHookHandler';
import TaintedChecker from '@/TaintedChecker';

const RL_COMMON_MODULE_NAME = 'wikibase.tainted-ref';
export default async (): Promise<void> => {
	const mwWindow = window as MwWindow;
	function messageToTextFunction( key: string ): string {
		return mwWindow.mw.message( key ).text();
	}

	if ( mwWindow.mw.config.get( 'wbTaintedReferencesEnabled' ) ) {
		const require = await mwWindow.mw.loader.using( RL_COMMON_MODULE_NAME );
		const app = require( RL_COMMON_MODULE_NAME );
		const Vue = require( 'vue2' );
		const hookHandler = new MWHookHandler( mwWindow.mw.hook, new TaintedChecker() );

		Vue.use( Track, { trackingFunction: mwWindow.mw.track } );
		Vue.use( Message, { messageToTextFunction } );
		mwWindow.mw.hook( 'wikibase.entityPage.entityView.rendered' ).add(
			() => {
				const helpLink = mwWindow.mw.util.getUrl( 'Special:MyLanguage/Help:Sources' );
				app.launch( hookHandler, helpLink );
			},
		);
	}
};
