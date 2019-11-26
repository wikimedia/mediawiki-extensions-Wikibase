import ApiWritingRepository from '@/data-access/ApiWritingRepository';
import EditFlow from '@/definitions/EditFlow';
import init from '@/mediawiki/init';
import MwWindow from '@/@types/mediawiki/MwWindow';
import ServiceContainer from '@/services/ServiceContainer';
import SpecialPageReadingEntityRepository from '@/data-access/SpecialPageReadingEntityRepository';
import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';
import {
	mockForeignApiConstructor,
	mockMwConfig,
	mockMwEnv,
} from '../../util/mocks';
import MwMessagesRepository from '@/data-access/MwMessagesRepository';
import DispatchingEntityLabelRepository from '@/data-access/DispatchingEntityLabelRepository';
import ApiEntityInfoDispatcher from '@/data-access/ApiEntityInfoDispatcher';
import ApiRepoConfigRepository from '@/data-access/ApiRepoConfigRepository';
import DataBridgeTrackerService from '@/data-access/DataBridgeTrackerService';
import EventTracker from '@/mediawiki/facades/EventTracker';
import DispatchingPropertyDataTypeRepository from '@/data-access/DispatchingPropertyDataTypeRepository';
import createServices from '@/services/createServices';
import { budge } from '../../util/timer';

const manager = jest.fn();
const dialog = {
	getManager: jest.fn( () => manager ),
};

const mockPrepareContainer = jest.fn( ( _x?: any, _y?: any, _z?: any ) => {
	return dialog;
} );
jest.mock( '@/mediawiki/prepareContainer', () => ( {
	__esModule: true, // this property makes it work
	default: ( oo: any, $: any ) => mockPrepareContainer( oo, $ ),
} ) );

const mockSubscribeToEvents = jest.fn();
jest.mock( '@/mediawiki/subscribeToEvents', () => ( {
	__esModule: true,
	default: ( emitter: any, windowManager: any ) => mockSubscribeToEvents( emitter, windowManager ),
} ) );

describe( 'init', () => {
	it( 'loads `wikibase.client.data-bridge.app` and launches it on click', () => {
		const emitter = jest.fn(),
			app = {
				launch: jest.fn( () => {
					return emitter;
				} ),
				createServices,
			},
			require = jest.fn( () => app ),
			using = jest.fn( () => {
				return new Promise( ( resolve ) => resolve( require ) );
			} ),
			ForeignApiConstructor = mockForeignApiConstructor( { expectedUrl: 'http://localhost/w/api.php' } ),
			ForeignApi = new ForeignApiConstructor( 'http://localhost/w/api.php' ),
			editTags = [ 'a tag' ],
			usePublish = true;
		mockMwEnv(
			using,
			mockMwConfig( {
				editTags,
				usePublish,
			} ),
			undefined,
			ForeignApiConstructor,
		);
		const expectedServices = new ServiceContainer();
		expectedServices.set( 'readingEntityRepository', new SpecialPageReadingEntityRepository(
			( window as MwWindow ).$,
			'http://localhost/wiki/Special:EntityData',
		) );
		expectedServices.set( 'writingEntityRepository', new ApiWritingRepository(
			ForeignApi,
			'Test User',
			editTags,
		) );
		expectedServices.set( 'languageInfoRepository', new MwLanguageInfoRepository(
			( window as MwWindow ).mw.language,
			( window as MwWindow ).$.uls!.data,
		) );
		const entityInfoDispatcher = new ApiEntityInfoDispatcher( ForeignApi, [ 'labels', 'datatype' ] );
		expectedServices.set( 'entityLabelRepository', new DispatchingEntityLabelRepository(
			'en',
			entityInfoDispatcher,
		) );
		expectedServices.set( 'propertyDatatypeRepository', new DispatchingPropertyDataTypeRepository(
			entityInfoDispatcher,
		) );
		expectedServices.set( 'messagesRepository', new MwMessagesRepository(
			( window as MwWindow ).mw.message,
		) );
		expectedServices.set( 'wikibaseRepoConfigRepository', new ApiRepoConfigRepository( ForeignApi ) );
		expectedServices.set( 'tracker', new DataBridgeTrackerService(
			new EventTracker( ( window as MwWindow ).mw.track ),
		) );

		const entityId = 'Q5';
		const propertyId = 'P4711';
		const entityTitle = entityId; // main namespace
		const editFlow = EditFlow.OVERWRITE;
		const testLinkHref = `https://www.wikidata.org/wiki/${entityId}?uselang=en#${propertyId}`;
		document.body.innerHTML = `
<span data-bridge-edit-flow="${editFlow}">
	<a rel="nofollow" class="external text" href="${testLinkHref}">a link to be selected</a>
</span>`;
		const testLink = document.querySelector( 'a' );

		return init().then( async () => {
			testLink!.click();
			await budge();

			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
			expect( app.launch ).toHaveBeenCalledWith(
				{
					containerSelector: '#data-bridge-container',
				},
				{
					entityId,
					propertyId,
					entityTitle,
					editFlow,
					client: { usePublish },
				},
				expectedServices,
			);

			expect( mockSubscribeToEvents ).toHaveBeenCalledWith( emitter, manager );
		} );
	} );
} );
