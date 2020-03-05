import init from '@/mediawiki/init';
import BridgeDomElementsSelector from '@/mediawiki/BridgeDomElementsSelector';
import {
	mockMwConfig,
	mockMwEnv,
} from '../../util/mocks';
import EditFlow from '@/definitions/EditFlow';
import Dispatcher from '@/mediawiki/Dispatcher';
import { budge } from '../../util/timer';

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
			using = jest.fn().mockResolvedValue( require );
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
				'mediawiki.api',
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
			using = jest.fn().mockResolvedValue( require ),
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

		return init().then( async () => {
			await selectedElement.link.addEventListener.mock.calls[ 0 ][ 1 ]( event );
			expect( event.preventDefault ).toHaveBeenCalled();
			expect( event.stopPropagation ).toHaveBeenCalled();
			expect( Dispatcher ).toHaveBeenCalledWith( window, app, dataBridgeConfig );
			expect( mockDispatcher.dispatch ).toHaveBeenCalledWith( selectedElement );
		} );
	} );

	it( 'doesn\'t handle clicks while notorious keys are pressed', async () => {
		const app = {},
			require = jest.fn().mockReturnValue( app ),
			using = jest.fn().mockResolvedValue( require ),
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

		await init();
		const notoriousKeys = [ 'altKey', 'ctrlKey', 'shiftKey', 'metaKey' ];
		const assertionPromises = await notoriousKeys.map(
			async ( key ) => {
				await selectedElement.link.addEventListener.mock.calls[ 0 ][ 1 ]( {
					...event,
					[ key ]: true,
				} );
				expect( event.preventDefault ).not.toHaveBeenCalled();
				expect( event.stopPropagation ).not.toHaveBeenCalled();
				expect( mockDispatcher.dispatch ).not.toHaveBeenCalled();
				return;
			},
		);
		return Promise.all( assertionPromises );
	} );

	it( 'does not dispatch app twice if clicked a second time before app loads', async () => {
		const app = {},
			require = jest.fn().mockReturnValue( app );
		let resolveUsing: ( require: Function ) => void;
		const using = jest.fn(
				() => new Promise( ( resolve ) => {
					resolveUsing = resolve;
				} ),
			),
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
		init();

		selectedElement.link.addEventListener.mock.calls[ 0 ][ 1 ]( event );
		await budge();
		expect( event.preventDefault ).toHaveBeenCalled();
		event.preventDefault.mockClear();
		expect( event.stopPropagation ).toHaveBeenCalled();
		event.stopPropagation.mockClear();
		expect( Dispatcher ).not.toHaveBeenCalled();
		expect( mockDispatcher.dispatch ).not.toHaveBeenCalled();

		selectedElement.link.addEventListener.mock.calls[ 0 ][ 1 ]( event );
		await budge();
		expect( event.preventDefault ).toHaveBeenCalled();
		expect( event.stopPropagation ).toHaveBeenCalled();
		resolveUsing!( require );
		await budge();
		expect( Dispatcher ).toHaveBeenCalledWith( window, app, dataBridgeConfig );
		expect( Dispatcher ).toHaveBeenCalledTimes( 1 );
		expect( mockDispatcher.dispatch ).toHaveBeenCalledWith( selectedElement );
		expect( mockDispatcher.dispatch ).toHaveBeenCalledTimes( 1 );
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
