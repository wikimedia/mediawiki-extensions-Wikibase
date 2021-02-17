import MessagesRepository from '@/definitions/data-access/MessagesRepository';
import Messages from '@/presentation/plugins/MessagesPlugin/Messages';
import MessagesEnum from '@/definitions/MessageKeys';

describe( 'Messages', () => {
	it( 'includes the message keys', () => {
		const messages = new Messages( {} as MessagesRepository );

		expect( messages.KEYS ).toBe( MessagesEnum );
	} );

	it( 'forwards to the MessagesRepository', () => {
		const messagesRepository: MessagesRepository = {
			get: jest.fn( ( key ) => `test ${key}` ),
			getText: jest.fn( ( key ) => `test text ${key}` ),
		};
		const messages = new Messages( messagesRepository );

		const message = messages.get( 'key' );
		const messageText = messages.getText( 'key' );

		expect( messagesRepository.get ).toHaveBeenCalledTimes( 1 );
		expect( messagesRepository.get ).toHaveBeenCalledWith( 'key' );
		expect( message ).toBe( 'test key' );
		expect( messageText ).toBe( 'test text key' );

		const parameter = 'something';
		messages.get( 'key', parameter );
		expect( messagesRepository.get ).toHaveBeenCalledWith( 'key', parameter );
	} );
} );
