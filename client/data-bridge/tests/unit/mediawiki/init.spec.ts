import init from '@/mediawiki/init';
import BridgeDomElementsSelector from '@/mediawiki/BridgeDomElementsSelector';
import {
	mockMwConfig,
	mockMwEnv,
} from '../../util/mocks';
import EditFlow from '@/definitions/EditFlow';
import Dispatcher from '@/mediawiki/Dispatcher';

jest.mock( '@/mediawiki/BridgeDomElementsSelector', function () {
	return jest.fn().mockImplementation( () => {} );
} );

const mockDispatcher = {
	dispatch: jest.fn(),
};
jest.mock( '@/mediawiki/Dispatcher', () => {
	return jest.fn().mockImplementation( () => {
		return mockDispatcher;
	} );
} );

describe( 'init', () => {
	it( 'loads `wikibase.client.data-bridge.app` and adds click handler', () => {
		const require = jest.fn(),
			using = jest.fn( () => Promise.resolve( require ) );
		mockMwEnv( using );

		const link = {
			addEventListener: jest.fn(),
			setAttribute: jest.fn(),
		};
		( BridgeDomElementsSelector as jest.Mock ).mockImplementation( () => ( {
			selectElementsToOverload: () => [ { link } ],
		} ) );

		return init().then( () => {
			expect( using ).toHaveBeenCalledTimes( 1 );
			expect( using ).toHaveBeenCalledWith( [
				'wikibase.client.data-bridge.app',
				'mw.config.values.wbRepo',
				'mediawiki.ForeignApi',
				'jquery.uls.data',
				'mediawiki.language',
			] );
			expect( require ).toHaveBeenCalledWith( 'wikibase.client.data-bridge.app' );
			expect( link.setAttribute ).toHaveBeenCalledTimes( 1 );
			expect( link.setAttribute ).toHaveBeenCalledWith( 'aria-haspopup', 'dialog' );
			expect( link.addEventListener ).toHaveBeenCalledTimes( 1 );
			expect( link.addEventListener.mock.calls[ 0 ][ 0 ] ).toBe( 'click' );
			expect( typeof link.addEventListener.mock.calls[ 0 ][ 1 ] ).toBe( 'function' );
		} );
	} );

	it( 'loads `wikibase.client.data-bridge.app` and dispatches it on click', () => {
		const app = {},
			require = jest.fn().mockReturnValue( app ),
			using = jest.fn( () => Promise.resolve( require ) ),
			entityId = 'Q5',
			propertyId = 'P4711',
			editFlow = EditFlow.OVERWRITE,
			dataBridgeConfig = {
				hrefRegExp: '',
				editTags: [],
				usePublish: false,
			};
		mockMwEnv( using, mockMwConfig( dataBridgeConfig ) );

		const selectedElement = {
			link: {
				addEventListener: jest.fn(),
				setAttribute: jest.fn(),
			},
			entityId,
			propertyId,
			editFlow,
		};
		const event = {
			preventDefault: jest.fn(),
			stopPropagation: jest.fn(),
		};
		( BridgeDomElementsSelector as jest.Mock ).mockImplementation( () => ( {
			selectElementsToOverload: () => [ selectedElement ],
		} ) );

		return init().then( () => {
			selectedElement.link.addEventListener.mock.calls[ 0 ][ 1 ]( event );
			expect( event.preventDefault ).toHaveBeenCalled();
			expect( event.stopPropagation ).toHaveBeenCalled();
			expect( Dispatcher ).toHaveBeenCalledWith( window, app, dataBridgeConfig );
			expect( mockDispatcher.dispatch ).toHaveBeenCalledWith( selectedElement );
		} );
	} );

	it( 'loads does nothing if no supported links are found', () => {
		const using = jest.fn();
		mockMwEnv( using );

		( BridgeDomElementsSelector as jest.Mock ).mockImplementation( () => ( {
			selectElementsToOverload: () => [],
		} ) );

		init();

		expect( using ).toHaveBeenCalledTimes( 0 );
	} );

	it( 'warns on missing hrefRegExp', () => {
		const using = jest.fn();
		const warn = jest.fn();
		mockMwEnv( using, mockMwConfig( { hrefRegExp: null } ), warn );

		init();
		expect( using ).toHaveBeenCalledTimes( 0 );
		expect( warn ).toHaveBeenCalledTimes( 1 );
	} );
} );
