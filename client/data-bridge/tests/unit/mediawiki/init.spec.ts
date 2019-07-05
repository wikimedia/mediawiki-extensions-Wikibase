import init from '@/mediawiki/init';
import * as linker from '@/mediawiki/selectLinks';
import MwWindow from '@/@types/mediawiki/MwWindow';

function mockMwEnv( using: () => Promise<any> ): void {
	( window as MwWindow ).mw = {
		loader: {
			using,
		},
		config: {
			get: jest.fn(),
		},
		log: {
			deprecate: jest.fn(),
			error: jest.fn(),
			warn: jest.fn(),
		},
	};
}

describe( 'init', () => {
	it( 'loads `wikibase.client.data-bridge.app`, if it found supported links', () => {
		const app = { launch: jest.fn() },
			require = jest.fn( () => app ),
			using = jest.fn( () => {
				return new Promise( ( resolve ) => resolve( require ) );
			} );

		mockMwEnv( using );
		const mock = jest.spyOn( linker, 'filterLinksByHref' );
		mock.mockReturnValue( [
			{ href: 'https://www.wikidata.org/wiki/Q123#P321' },
		] as any );

		return init().then( () => {
			expect( using ).toBeCalledTimes( 1 );
			expect( using ).toBeCalledWith( 'wikibase.client.data-bridge.app' );
			expect( require ).toBeCalledWith( 'wikibase.client.data-bridge.app' );
			expect( app.launch ).toBeCalledTimes( 1 );
		} );
	} );

	it( 'loads does nothing if no supported links are found', () => {
		const using = jest.fn();
		mockMwEnv( using );
		const mock = jest.spyOn( linker, 'filterLinksByHref' );
		mock.mockReturnValue( [] );

		init();
		expect( using ).toBeCalledTimes( 0 );

	} );
} );
