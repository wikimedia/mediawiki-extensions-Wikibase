import ForeignApiWritingRepository from '@/data-access/ForeignApiWritingRepository';
import EditFlow from '@/definitions/EditFlow';
import init from '@/mediawiki/init';
import MwWindow from '@/@types/mediawiki/MwWindow';
import ServiceRepositories from '@/services/ServiceRepositories';
import SpecialPageReadingEntityRepository from '@/data-access/SpecialPageReadingEntityRepository';
import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';
import {
	mockForeignApiConstructor,
	mockMwConfig,
	mockMwEnv,
} from '../../util/mocks';
import ForeignApiEntityLabelRepository from '@/data-access/ForeignApiEntityLabelRepository';
import MwMessagesRepository from '@/data-access/MwMessagesRepository';

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
		const expectedServices = new ServiceRepositories();
		expectedServices.setReadingEntityRepository(
			new SpecialPageReadingEntityRepository(
				( window as MwWindow ).$,
				'http://localhost/wiki/Special:EntityData',
			),
		);
		expectedServices.setWritingEntityRepository(
			new ForeignApiWritingRepository( ForeignApi, 'Test User', editTags ),
		);
		expectedServices.setLanguageInfoRepository(
			new MwLanguageInfoRepository(
				( window as MwWindow ).mw.language,
				( window as MwWindow ).$.uls!.data,
			),
		);
		expectedServices.setEntityLabelRepository(
			new ForeignApiEntityLabelRepository( 'en', ForeignApi ),
		);
		expectedServices.setMessagesRepository(
			new MwMessagesRepository( ( window as MwWindow ).mw.message ),
		);
		const entityId = 'Q5';
		const propertyId = 'P4711';
		const editFlow = EditFlow.OVERWRITE;
		const testLinkHref = `https://www.wikidata.org/wiki/${entityId}?uselang=en#${propertyId}`;
		document.body.innerHTML = `
<span data-bridge-edit-flow="${editFlow}">
	<a rel="nofollow" class="external text" href="${testLinkHref}">a link to be selected</a>
</span>`;
		const testLink = document.querySelector( 'a' );

		return init().then( () => {
			testLink!.click();

			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
			expect( app.launch ).toHaveBeenCalledWith(
				{
					containerSelector: '#data-bridge-container',
					usePublish,
				},
				{
					entityId,
					propertyId,
					editFlow,
				},
				expectedServices,
			);

			expect( mockSubscribeToEvents ).toHaveBeenCalledWith( emitter, manager );
		} );
	} );
} );
