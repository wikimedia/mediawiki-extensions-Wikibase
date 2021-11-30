import { config } from '@vue/test-utils';
import MessageKeys from '@/definitions/MessageKeys';
import Messages from '@/presentation/plugins/MessagesPlugin/Messages';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';
import { testInLanguage } from '../util/language';

// Break on unhandled promise rejection (default warning might be overlooked)
// https://github.com/facebook/jest/issues/3251#issuecomment-299183885
// However...
// https://stackoverflow.com/questions/51957531/jest-how-to-throw-an-error-inside-a-unhandledrejection-handler
if ( typeof process.env.LISTENING_TO_UNHANDLED_REJECTION === 'undefined' ) {
	process.on( 'unhandledRejection', ( unhandledRejectionWarning ) => {
		throw unhandledRejectionWarning; // see stack trace for test at fault
	} );
	// Avoid memory leak by adding too many listeners
	process.env.LISTENING_TO_UNHANDLED_REJECTION = 'yes';
}

beforeEach( () => {
	expect.hasAssertions();
} );

config.global.mocks = {
	...config.global.mocks,
	...{
		$messages: {
			KEYS: MessageKeys,
			get: ( key: string ) => `⧼${key}⧽`,
			getText: ( key: string ) => `⧼${key}⧽`,
		},
		$repoRouter: {
			getPageUrl: ( title, _params? ) => title,
		},
		$inLanguage: testInLanguage,
	} as {
		$messages: Messages;
		$repoRouter: MediaWikiRouter;
		$inLanguage: ( mwLangCode: string ) => { lang: string; dir: string;},
	},
};
