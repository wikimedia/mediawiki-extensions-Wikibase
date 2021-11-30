import Dispatcher from '@/mediawiki/Dispatcher';
import MwWindow from '@/@types/mediawiki/MwWindow';
import AppBridge from '@/definitions/AppBridge';
import EditFlow from '@/definitions/EditFlow';
import DataBridgeConfig from '@/@types/wikibase/DataBridgeConfig';
import { mockMwConfig } from '../../util/mocks';
import Tracker from '@/tracking/Tracker';

const manager = jest.fn();
const dialog = {
	getManager: jest.fn().mockReturnValue( manager ),
};
const mockPrepareContainer = jest.fn( ( _x?: any, _y?: any, _z?: any ) => {
	return dialog;
} );
jest.mock( '@/mediawiki/prepareContainer', () => ( {
	__esModule: true,
	default: ( oo: any, $: any, id: any ) => mockPrepareContainer( oo, $, id ),
} ) );

const mockSubscribeToEvents = jest.fn();
jest.mock( '@/mediawiki/subscribeToEvents', () => ( {
	__esModule: true,
	default: ( emitter: any, windowManager: any ) => mockSubscribeToEvents( emitter, windowManager ),
} ) );

describe( 'Dispatcher', () => {
	it( 'can be constructed with mwWindow and app definition', () => {
		const dispatcher = new Dispatcher(
			{} as MwWindow,
			{} as unknown,
			{} as AppBridge,
			{} as DataBridgeConfig,
			{} as Tracker,
		);
		expect( dispatcher ).toBeInstanceOf( Dispatcher );
	} );

	describe( 'dispatch', () => {
		it( 'prepares the DOM container', () => {
			const OO = new ( jest.fn() )();
			const $ = new ( jest.fn() )();

			const dispatcher = new Dispatcher(
				{
					OO,
					$,
					mw: { config: mockMwConfig() },
					location: { href: '' },
				} as MwWindow,
				{ createMwApp: jest.fn() },
				{
					launch: jest.fn(),
					createServices: jest.fn(),
				},
				{ usePublish: false } as DataBridgeConfig,
				{} as Tracker,
			);

			dispatcher.dispatch( { link: { href: '' } } as any );

			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
			expect( mockPrepareContainer.mock.calls[ 0 ][ 0 ] ).toBe( OO );
			expect( mockPrepareContainer.mock.calls[ 0 ][ 1 ] ).toBe( $ );
			expect( mockPrepareContainer.mock.calls[ 0 ][ 2 ] ).toBe( Dispatcher.APP_DOM_CONTAINER_ID );
		} );

		it( 'triggers service creation and launches app', () => {
			const usePublish = true;
			const editTags: readonly string[] = [ 'my tag' ];
			const pageTitle = 'Client_page';
			const pageUrl = 'https://client.example/wiki/Client_page';
			const userName = 'Test user';
			const mwWindow = {
				mw: { config: mockMwConfig( { wgPageName: pageTitle, wgUserName: userName } ) },
				location: { href: pageUrl },
			};
			const emitter = jest.fn();
			const mockServices = {};
			const app = {
				launch: jest.fn().mockReturnValue( emitter ),
				createServices: jest.fn().mockReturnValue( mockServices ),
			};
			const entityId = 'Q4711';
			const propertyId = 'P815';
			const entityTitle = entityId;
			const editFlow = EditFlow.SINGLE_BEST_VALUE;
			const originalHref = 'https://example.com/index.php?title=Item:Q42&uselang=en#P31';
			const tracker = {} as Tracker;
			const createApp = jest.fn();

			const dispatcher = new Dispatcher(
				mwWindow as MwWindow,
				{ createMwApp: createApp },
				app as any,
				{ usePublish, editTags } as DataBridgeConfig,
				tracker,
			);

			dispatcher.dispatch( {
				link: { href: originalHref } as HTMLAnchorElement,
				entityId,
				propertyId,
				entityTitle,
				editFlow,
			} );

			expect( app.createServices ).toHaveBeenCalledWith( mwWindow, editTags, tracker );
			expect( app.launch ).toHaveBeenCalledWith(
				createApp,
				{
					containerSelector: `#${Dispatcher.APP_DOM_CONTAINER_ID}`,
				},
				{
					pageTitle,
					entityId,
					propertyId,
					entityTitle,
					editFlow,
					client: {
						usePublish,
					},
					originalHref,
					pageUrl,
					userName,
				},
				mockServices,
			);

			expect( mockSubscribeToEvents ).toHaveBeenCalledWith( emitter, manager );
		} );
	} );
} );
