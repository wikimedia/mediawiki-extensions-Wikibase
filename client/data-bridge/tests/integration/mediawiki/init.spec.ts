import ApiCore from '@/data-access/ApiCore';
import ApiWritingRepository from '@/data-access/ApiWritingRepository';
import BatchingApi from '@/data-access/BatchingApi';
import EditFlow from '@/definitions/EditFlow';
import init from '@/mediawiki/init';
import MwWindow from '@/@types/mediawiki/MwWindow';
import ServiceContainer from '@/services/ServiceContainer';
import SpecialPageReadingEntityRepository from '@/data-access/SpecialPageReadingEntityRepository';
import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';
import {
	mockMwForeignApiConstructor,
	mockMwConfig,
	mockMwEnv,
} from '../../util/mocks';
import MwMessagesRepository from '@/data-access/MwMessagesRepository';
import ApiEntityLabelRepository from '@/data-access/ApiEntityLabelRepository';
import ApiRepoConfigRepository from '@/data-access/ApiRepoConfigRepository';
import DataBridgeTrackerService from '@/data-access/DataBridgeTrackerService';
import EventTracker from '@/mediawiki/facades/EventTracker';
import ApiPropertyDataTypeRepository from '@/data-access/ApiPropertyDataTypeRepository';
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
			MwForeignApiConstructor = mockMwForeignApiConstructor( { expectedUrl: 'http://localhost/w/api.php' } ),
			mwApi = new MwForeignApiConstructor( 'http://localhost/w/api.php' ),
			api = new BatchingApi( new ApiCore( mwApi ) ),
			editTags = [ 'a tag' ],
			usePublish = true,
			pageTitle = 'Client_page';
		mockMwEnv(
			using,
			mockMwConfig( {
				editTags,
				usePublish,
				wgPageName: pageTitle,
			} ),
			undefined,
			MwForeignApiConstructor,
		);
		const expectedServices = new ServiceContainer();
		expectedServices.set( 'readingEntityRepository', new SpecialPageReadingEntityRepository(
			( window as MwWindow ).$,
			'http://localhost/wiki/Special:EntityData',
		) );
		expectedServices.set( 'writingEntityRepository', new ApiWritingRepository(
			mwApi,
			'Test User',
			editTags,
		) );
		expectedServices.set( 'languageInfoRepository', new MwLanguageInfoRepository(
			( window as MwWindow ).mw.language,
			( window as MwWindow ).$.uls!.data,
		) );
		expectedServices.set( 'entityLabelRepository', new ApiEntityLabelRepository(
			'en',
			api,
		) );
		expectedServices.set( 'propertyDatatypeRepository', new ApiPropertyDataTypeRepository(
			api,
		) );
		expectedServices.set( 'messagesRepository', new MwMessagesRepository(
			( window as MwWindow ).mw.message,
		) );
		expectedServices.set( 'wikibaseRepoConfigRepository', new ApiRepoConfigRepository( api ) );
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
					pageTitle,
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
