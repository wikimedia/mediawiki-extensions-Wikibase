import MwMessagesRepository from '@/data-access/MwMessagesRepository';
import { MwMessage } from '@/@types/mediawiki/MwWindow';

describe( 'MwMessagesRepository', () => {
	it( 'get mw.message and the messages keys to build a message collection', () => {
		const message = 'bar';
		const translation = jest.fn( () => message );
		const mwMessages = jest.fn( (): MwMessage => {
			return {
				text: translation,
			};
		} );
		const key = 'foo';
		const repo = new MwMessagesRepository( mwMessages );

		expect( repo.get( key ) ).toBe( message );
		expect( mwMessages ).toHaveBeenLastCalledWith( key );
		expect( translation ).toHaveBeenCalledTimes( 1 );
	} );
} );
