import init from '@/mediawiki/init';
import BridgeDomElementsSelector from '@/mediawiki/BridgeDomElementsSelector';
import {
	mockMwConfig,
	mockMwEnv,
} from '../../util/mocks';
import EditFlow from '@/definitions/EditFlow';
import Dispatcher from '@/mediawiki/Dispatcher';
import { budge } from '../../util/timer';
import MwInitTracker from '@/mediawiki/MwInitTracker';
import EventTracker from '@/mediawiki/facades/EventTracker';
import Tracker from '@/tracking/Tracker';
import PrefixingEventTracker from '@/tracking/PrefixingEventTracker';

jest.mock( '@/mediawiki/BridgeDomElementsSelector', function () {
	return jest.fn().mockImplementation( () => {} );
} );

const mockEventTracker = {};
jest.mock( '@/mediawiki/facades/EventTracker', () => {
	return jest.fn().mockImplementation( () => mockEventTracker );
} );

const mockPrefixingEventTracker = {};
jest.mock( '@/tracking/PrefixingEventTracker', function () {
	return jest.fn().mockImplementation( () => mockPrefixingEventTracker );
} );

const mwInitTrackerClickDelayCallback = jest.fn();
const mockMwInitTracker: Partial<MwInitTracker> = {
	recordTimeToLinkListenersAttached: jest.fn(),
	startClickDelayTracker: jest.fn().mockReturnValue( mwInitTrackerClickDelayCallback ),
};
jest.mock( '@/mediawiki/MwInitTracker', () => {
	return jest.fn().mockImplementation( () => mockMwInitTracker );
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
				'vue',
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
			vue = {},
			require = jest.fn().mockReturnValueOnce( app ),
			using = jest.fn().mockResolvedValue( require ),
			entityId = 'Q5',
			propertyId = 'P4711',
			editFlow = EditFlow.SINGLE_BEST_VALUE,
			dataBridgeConfig = {
				hrefRegExp: '',
				editTags: [],
				usePublish: false,
				issueReportingLink: 'https://bugs.example/new?body=<body>',
			};
		require.mockReturnValueOnce( vue );
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

		const initPromise = init();

		expect( EventTracker ).toHaveBeenCalledWith( window.mw.track );
		expect( PrefixingEventTracker ).toHaveBeenCalledWith(
			mockEventTracker,
			'MediaWiki.wikibase.client.databridge',
		);
		expect( MwInitTracker ).toHaveBeenCalledWith(
			mockPrefixingEventTracker,
			window.performance,
			window.document,
		);
		expect( mockMwInitTracker.recordTimeToLinkListenersAttached ).toHaveBeenCalled();

		return initPromise.then( async () => {
			// simulate the actual 'click' normally done by the user
			await selectedElement.link.addEventListener.mock.calls[ 0 ][ 1 ]( event );

			expect( event.preventDefault ).toHaveBeenCalled();
			expect( event.stopPropagation ).toHaveBeenCalled();

			expect( mockMwInitTracker.startClickDelayTracker ).toHaveBeenCalled();
			expect( mwInitTrackerClickDelayCallback ).toHaveBeenCalled();

			expect( Dispatcher ).toHaveBeenCalledWith( window, {}, app, dataBridgeConfig, mockPrefixingEventTracker );
			expect( mockDispatcher.dispatch ).toHaveBeenCalledWith( selectedElement );
		} );
	} );

	it( 'doesn\'t handle clicks while notorious keys are pressed', async () => {
		const app = {},
			require = jest.fn().mockReturnValue( app ),
			using = jest.fn().mockResolvedValue( require ),
			entityId = 'Q5',
			propertyId = 'P4711',
			editFlow = EditFlow.SINGLE_BEST_VALUE;
		mockMwEnv( using );

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
		const app = {};
		const vue = {};
		const require = jest.fn().mockReturnValueOnce( app );
		require.mockReturnValueOnce( vue );
		let resolveUsing: ( require: Function ) => void;
		const using = jest.fn(
				() => new Promise( ( resolve ) => {
					resolveUsing = resolve;
				} ),
			),
			entityId = 'Q5',
			propertyId = 'P4711',
			editFlow = EditFlow.SINGLE_BEST_VALUE,
			dataBridgeConfig = {
				hrefRegExp: '',
				editTags: [],
				usePublish: false,
				issueReportingLink: 'https://bugs.example/new?body=<body>',
			},
			tracker = {} as Tracker;
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
		expect( Dispatcher ).toHaveBeenCalledWith( window, vue, app, dataBridgeConfig, tracker );
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
