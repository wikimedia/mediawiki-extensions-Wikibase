import init from '@/mediawiki/init';
import BridgeDomElementsSelector from '@/mediawiki/BridgeDomElementsSelector';
import MwWindow from '@/@types/mediawiki/MwWindow';

function mockMwEnv( using: () => Promise<any>, get: () => any, warn: () => void ): void {
	( window as MwWindow ).mw = {
		loader: {
			using,
		},
		config: {
			get,
		},
		log: {
			deprecate: jest.fn(),
			error: jest.fn(),
			warn,
		},
	};
}

jest.mock( '@/mediawiki/BridgeDomElementsSelector', function () {
	return jest.fn().mockImplementation( () => {} );
} );

describe( 'init', () => {

	it( 'loads `wikibase.client.data-bridge.app` and adds click handler', () => {
		const require = jest.fn(),
			using = jest.fn( () => {
				return new Promise( ( resolve ) => resolve( require ) );
			} );
		const get = (): any => ( {
			hrefRegExp: 'https://www\\.wikidata\\.org/wiki/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)',
		} );
		mockMwEnv( using, get, jest.fn() );

		const link = {
			addEventListener: jest.fn(),
		};
		( BridgeDomElementsSelector as jest.Mock ).mockImplementation( () => ( {
			selectElementsToOverload: () => [ { link } ],
		} ) );

		return init().then( () => {
			expect( using ).toBeCalledTimes( 1 );
			expect( using ).toBeCalledWith( 'wikibase.client.data-bridge.app' );
			expect( require ).toBeCalledWith( 'wikibase.client.data-bridge.app' );
			expect( link.addEventListener ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	it( 'loads does nothing if no supported links are found', () => {
		const using = jest.fn();
		const get = (): any => ( {
			hrefRegExp: 'https://www\\.wikidata\\.org/wiki/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)',
		} );
		mockMwEnv( using, get, jest.fn() );

		( BridgeDomElementsSelector as jest.Mock ).mockImplementation( () => {
			return {
				selectElementsToOverload: () => [],
			};
		} );

		init();
		expect( using ).toBeCalledTimes( 0 );

	} );

	it( 'warns on missing hrefRegExp', () => {
		const using = jest.fn();
		const get = (): any => ( {
			hrefRegExp: null,
		} );
		const warn = jest.fn();
		mockMwEnv( using, get, warn );

		init();
		expect( using ).toBeCalledTimes( 0 );
		expect( warn ).toBeCalledTimes( 1 );
	} );
} );
