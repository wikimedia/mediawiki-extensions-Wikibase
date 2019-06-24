import init from '@/mediawiki/init';
import * as linker from '@/mediawiki/selectLinks';
import MwWindow from '@/@types/mediawiki/MwWindow';

function mockMwEnv( using: () => Promise<any> ): void {
	( window as MwWindow ).mw = {
		loader: {
			using,
		},
	};
}

describe( 'init', () => {
	it( 'loads `wikibase.client.data-bridge.app`, if it found supported links', async () => {
		const using = jest.fn( () => {
			return new Promise<void>( ( resolve ) => resolve() );
		} );

		mockMwEnv( using );
		const mock = jest.spyOn( linker, 'filterLinksByHref' ); // spy on otherFn
		mock.mockReturnValue( [
			{ href: ' https://www.wikidata.org/wiki/Q123#P321' },
		] as any );

		await init();
		expect( using ).toBeCalledTimes( 1 );
		expect( using ).toBeCalledWith( 'wikibase.client.data-bridge.app' );

	} );

	it( 'loads does nothing if no supported links are found', async () => {
		const using = jest.fn();
		mockMwEnv( using );
		const mock = jest.spyOn( linker, 'filterLinksByHref' );
		mock.mockReturnValue( [] );

		await init();
		expect( using ).toBeCalledTimes( 0 );

	} );
} );
