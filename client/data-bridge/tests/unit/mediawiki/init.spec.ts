import init from '@/mediawiki/init';
import BridgeDomElementsSelector from '@/mediawiki/BridgeDomElementsSelector';
import {
	mockMwConfig,
	mockMwEnv,
} from '../../util/mocks';

jest.mock( '@/mediawiki/BridgeDomElementsSelector', function () {
	return jest.fn().mockImplementation( () => {} );
} );

describe( 'init', () => {
	it( 'loads `wikibase.client.data-bridge.app` and adds click handler', () => {
		const require = jest.fn(),
			using = jest.fn( () => Promise.resolve( require ) );
		mockMwEnv( using );

		const link = {
			addEventListener: jest.fn(),
		};
		( BridgeDomElementsSelector as jest.Mock ).mockImplementation( () => ( {
			selectElementsToOverload: () => [ { link } ],
		} ) );

		return init().then( () => {
			expect( using ).toBeCalledTimes( 1 );
			expect( using ).toBeCalledWith( [ 'wikibase.client.data-bridge.app', 'mw.config.values.wbRepo' ] );
			expect( require ).toBeCalledWith( 'wikibase.client.data-bridge.app' );
			expect( link.addEventListener ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	it( 'loads does nothing if no supported links are found', () => {
		const using = jest.fn();
		mockMwEnv( using );

		( BridgeDomElementsSelector as jest.Mock ).mockImplementation( () => ( {
			selectElementsToOverload: () => [],
		} ) );

		init();

		expect( using ).toBeCalledTimes( 0 );
	} );

	it( 'warns on missing hrefRegExp', () => {
		const using = jest.fn();
		const warn = jest.fn();
		mockMwEnv( using, mockMwConfig( { hrefRegExp: null } ), warn );

		init();
		expect( using ).toBeCalledTimes( 0 );
		expect( warn ).toBeCalledTimes( 1 );
	} );
} );
