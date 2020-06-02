import MwMessagesRepository from '@/data-access/MwMessagesRepository';
import { MwMessage } from '@/@types/mediawiki/MwWindow';

describe( 'MwMessagesRepository', () => {
	it( 'get mw.message and the messages keys to build a message collection', () => {
		const message = 'bar';
		const translation = jest.fn().mockReturnValue( message );
		const mwMessages = jest.fn( (): MwMessage => {
			return {
				text: jest.fn(),
				parse: translation,
			};
		} );
		const key = 'foo';
		const repo = new MwMessagesRepository( mwMessages );

		expect( repo.get( key ) ).toBe( message );
		expect( mwMessages ).toHaveBeenLastCalledWith( key );
		expect( translation ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'get mw.message, the messages keys and parameters to build a message collection', () => {
		const mwMessages = jest.fn( ( _key: string, ...parameter: readonly ( string|HTMLElement )[] ): MwMessage => {
			return {
				parse: jest.fn().mockReturnValueOnce( parameter[ 0 ] ),
				text: jest.fn(),
			};
		} );

		const key = 'foo';
		const parameter = 'bar';
		const repo = new MwMessagesRepository( mwMessages );

		expect( repo.get( key, parameter ) ).toBe( parameter );
	} );

} );
