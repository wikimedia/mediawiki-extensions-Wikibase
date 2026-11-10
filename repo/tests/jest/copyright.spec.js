const cookieKey = 'wikibase.acknowledgedcopyrightversion';
const optionsKey = 'wb-acknowledgedcopyrightversion';

const copyrightVersion = 'wikibase-1';
const mockConfig = {
	wbCopyright: {
		version: copyrightVersion,
		messageHtml: 'agreement text with <a>link</a>'
	}
};

mw.cookie = {
	get: jest.fn(),
	set: jest.fn()
};

mw.config = {
	get: jest.fn( ( key ) => mockConfig[ key ] ),
	exists: jest.fn( ( key ) => key in mockConfig )
};

const { api } = require( '../../resources/wikibase.wbui2025/api/api.js' );
const copyright = require( '../../resources/wikibase.wbui2025/copyright.js' );

describe( 'isAccepted', () => {
	it( 'is true if the cookie for accepted version matches current', () => {
		mw.cookie.get.mockReturnValueOnce( copyrightVersion );

		expect( copyright.isAccepted() ).toBe( true );
		expect( mw.cookie.get ).toHaveBeenCalledWith( cookieKey );
	} );

	it( 'is true if the accepted version in user option matches current', () => {
		mw.cookie.get.mockReturnValueOnce( null );
		mw.user.options.set( optionsKey, copyrightVersion );

		expect( copyright.isAccepted() ).toBe( true );
	} );

	it( 'is false if neither the cookie nor user option matches current', () => {
		mw.cookie.get.mockReturnValueOnce( 'some-other-version' );
		mw.user.options.set( optionsKey, 'not-this-either' );

		expect( copyright.isAccepted() ).toBe( false );
	} );
} );

describe( 'persistAcceptance', () => {
	it( 'sets the cookie it the user is not named (is temp or anon)', () => {
		mw.user.isNamed.mockReturnValueOnce( false );
		copyright.persistAcceptance();
		expect( mw.cookie.set )
			.toHaveBeenCalledWith( cookieKey, copyrightVersion, { expires: 94608000, path: '/' } );
	} );

	describe( 'when the user is named (not anon or temp)', () => {
		beforeEach( () => {
			mw.user.isNamed.mockReturnValueOnce( true );

			const done = jest.fn().mockReturnValue( { fail: jest.fn() } );
			api.saveOption = jest.fn().mockReturnValue( { done } );
		} );

		it( 'makes an api call to save the acceptance', () => {
			copyright.persistAcceptance();

			expect( api.saveOption ).toHaveBeenCalledWith( optionsKey, copyrightVersion );
		} );
	} );
} );
