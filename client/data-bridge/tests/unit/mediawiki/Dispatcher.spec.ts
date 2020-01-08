import Dispatcher from '@/mediawiki/Dispatcher';
import MwWindow from '@/@types/mediawiki/MwWindow';
import AppBridge from '@/definitions/AppBridge';
import EditFlow from '@/definitions/EditFlow';
import DataBridgeConfig from '@/@types/wikibase/DataBridgeConfig';
import { mockMwConfig } from '../../util/mocks';

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
			{} as AppBridge,
			{} as DataBridgeConfig,
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
				} as MwWindow,
				{
					launch: jest.fn(),
					createServices: jest.fn(),
				},
				{ usePublish: false } as DataBridgeConfig,
			);

			dispatcher.dispatch( new ( jest.fn() )() );

			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
			expect( mockPrepareContainer.mock.calls[ 0 ][ 0 ] ).toBe( OO );
			expect( mockPrepareContainer.mock.calls[ 0 ][ 1 ] ).toBe( $ );
			expect( mockPrepareContainer.mock.calls[ 0 ][ 2 ] ).toBe( Dispatcher.APP_DOM_CONTAINER_ID );
		} );

		it( 'triggers service creation and launches app', () => {
			const usePublish = true;
			const editTags = [ 'my tag' ];
			const pageTitle = 'Client_page';
			const mwWindow = { mw: { config: mockMwConfig( { wgPageName: pageTitle } ) } };
			const emitter = jest.fn();
			const mockServices = {};
			const app = {
				launch: jest.fn().mockReturnValue( emitter ),
				createServices: jest.fn().mockReturnValue( mockServices ),
			};
			const entityId = 'Q4711';
			const propertyId = 'P815';
			const entityTitle = entityId;
			const editFlow = EditFlow.OVERWRITE;

			const dispatcher = new Dispatcher(
				mwWindow as MwWindow,
				app as any,
				{ usePublish, editTags } as DataBridgeConfig,
			);

			dispatcher.dispatch( {
				link: new ( jest.fn() )(),
				entityId,
				propertyId,
				entityTitle,
				editFlow,
			} );

			expect( app.createServices ).toHaveBeenCalledWith( mwWindow, editTags );
			expect( app.launch ).toHaveBeenCalledWith(
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
				},
				mockServices,
			);

			expect( mockSubscribeToEvents ).toHaveBeenCalledWith( emitter, manager );
		} );
	} );
} );
