import { config } from '@vue/test-utils';
import MessageKeys from '@/definitions/MessageKeys';

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

config.mocks!.$messages = {
	KEYS: MessageKeys,
	get: ( key: string ) => `<${key}>`,
};

config.mocks!.$bridgeConfig = {
	usePublish: false,
};
