import MwWindow from '@/@types/mediawiki/MwWindow';
import MWHookHandler from '@/MWHookHandler';
import TaintedChecker from '@/TaintedChecker';
import StatementTracker from '@/StatementTracker';
import ReferenceListChangeCounter from '@/ReferenceListChangeCounter';

const RL_COMMON_MODULE_NAME = 'wikibase.tainted-ref';
export default async (): Promise<void> => {
	const mwWindow: MwWindow = window;
	function messageToTextFunction( key: string ): string {
		return mwWindow.mw.message( key ).text();
	}

	if ( mwWindow.mw.config.get( 'wbTaintedReferencesEnabled' ) ) {
		const require = await mwWindow.mw.loader.using( RL_COMMON_MODULE_NAME );
		const app = require( RL_COMMON_MODULE_NAME );

		const statementTracker = new StatementTracker( mwWindow.mw.track, new ReferenceListChangeCounter() );
		const hookHandler = new MWHookHandler( mwWindow.mw.hook, new TaintedChecker(), statementTracker );

		mwWindow.mw.hook( 'wikibase.entityPage.entityView.rendered' ).add(
			() => {
				const helpLink = mwWindow.mw.util.getUrl( 'Special:MyLanguage/Help:Sources' );
				app.launch( hookHandler, helpLink, messageToTextFunction, mwWindow.mw.track );
			},
		);
	}
};
