const { setActivePinia, createPinia } = require( 'pinia' );
const { useMessageStore } = require( '../../../resources/wikibase.wbui2025/store/messageStore.js' );

describe( 'Message Store', () => {
	beforeEach( () => {
		setActivePinia( createPinia() );
	} );

	it( 'store starts empty', () => {
		const messageStore = useMessageStore();
		expect( messageStore.messages.size ).toBe( 0 );
	} );

	// We expect there only ever to be one message in the store (T429153). When we
	// implement that change fully (T439118), this test should also be updated.
	it( 'adds messages to the store', () => {
		const messageStore = useMessageStore();
		let messageId = messageStore.addStatusMessage( { text: 'something' } );
		expect( messageId ).toBe( 1 );
		expect( messageStore.messages.size ).toBe( 1 );
		messageId = messageStore.addStatusMessage( { text: 'something else' } );
		expect( messageId ).toBe( 2 );
		expect( messageStore.messages.size ).toBe( 1 );
	} );

	it( 'can remove a message from the store', () => {
		const messageStore = useMessageStore();
		const message = { text: 'something' };
		const messageId = messageStore.addStatusMessage( message );
		expect( messageId ).toBe( 1 );
		expect( messageStore.messages.size ).toBe( 1 );
		messageStore.clearStatusMessage( 1 );
		expect( messageStore.messages.size ).toBe( 0 );
	} );

	it( 'throws an exception if there is no matching messageId', () => {
		const messageStore = useMessageStore();
		const removeUnknownMessage = () => {
			messageStore.clearStatusMessage( 5 );
		};
		expect( removeUnknownMessage ).toThrowError( 'No such message ID: 5' );
	} );
} );
