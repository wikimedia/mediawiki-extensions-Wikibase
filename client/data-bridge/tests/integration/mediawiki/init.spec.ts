import ApiCore from '@/data-access/ApiCore';
import ApiWritingRepository from '@/data-access/ApiWritingRepository';
import BatchingApi from '@/data-access/BatchingApi';
import ClientRouter from '@/data-access/ClientRouter';
import TrimmingWritingRepository from '@/data-access/TrimmingWritingRepository';
import EditFlow from '@/definitions/EditFlow';
import init from '@/mediawiki/init';
import ServiceContainer from '@/services/ServiceContainer';
import ApiReadingEntityRepository from '@/data-access/ApiReadingEntityRepository';
import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';
import {
	mockMwForeignApiConstructor,
	mockMwConfig,
	mockMwEnv,
	mockMwApiConstructor,
} from '../../util/mocks';
import MwMessagesRepository from '@/data-access/MwMessagesRepository';
import ApiEntityLabelRepository from '@/data-access/ApiEntityLabelRepository';
import ApiRepoConfigRepository from '@/data-access/ApiRepoConfigRepository';
import DataBridgeTrackerService from '@/data-access/DataBridgeTrackerService';
import EventTracker from '@/mediawiki/facades/EventTracker';
import ApiPropertyDataTypeRepository from '@/data-access/ApiPropertyDataTypeRepository';
import createServices from '@/services/createServices';
import { budge } from '../../util/timer';
import CombiningPermissionsRepository from '@/data-access/CombiningPermissionsRepository';
import ApiPageEditPermissionErrorsRepository from '@/data-access/ApiPageEditPermissionErrorsRepository';
import RepoRouter from '@/data-access/RepoRouter';
import ApiPurge from '@/data-access/ApiPurge';
import PrefixingEventTracker from '@/tracking/PrefixingEventTracker';
import ApiRenderReferencesRepository from '@/data-access/ApiRenderReferencesRepository';

const manager = jest.fn();
const dialog = {
	getManager: jest.fn().mockReturnValue( manager ),
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
				launch: jest.fn().mockReturnValue( emitter ),
				createServices,
			},
			editTags = [ 'a tag' ],
			usePublish = true,
			issueReportingLink = 'https://bugs.example/new?body=<body>',
			pageTitle = 'Client_page',
			contentLanguage = 'fr',
			userName = 'Test user',
			config = mockMwConfig( {
				editTags,
				usePublish,
				issueReportingLink,
				wgPageName: pageTitle,
				wgPageContentLanguage: contentLanguage,
				wgUserName: userName,
			} ),
			wbRepoConfig = config.get( 'wbRepo' ),
			foreignApiUrl = wbRepoConfig.url + wbRepoConfig.scriptPath + '/api.php',
			MwForeignApiConstructor = mockMwForeignApiConstructor( {
				expectedUrl: foreignApiUrl,
			} ),
			repoMwApi = new MwForeignApiConstructor( foreignApiUrl ),
			repoApiCore = new ApiCore( repoMwApi ),
			repoApi = new BatchingApi( repoApiCore ),
			MwApiConstructor = mockMwApiConstructor( {} ),
			clientMwApi = new MwApiConstructor(),
			clientApi = new ApiCore( clientMwApi );
		const require = jest.fn().mockReturnValueOnce( app );
		const createApp = jest.fn();
		const vue = { createMwApp: createApp };
		require.mockReturnValueOnce( vue );
		const using = jest.fn().mockResolvedValue( require );

		mockMwEnv(
			using,
			config,
			undefined,
			MwForeignApiConstructor,
			MwApiConstructor,
		);
		const expectedServices = new ServiceContainer();
		expectedServices.set( 'readingEntityRepository', new ApiReadingEntityRepository(
			repoApi,
		) );
		expectedServices.set( 'writingEntityRepository', new TrimmingWritingRepository( new ApiWritingRepository(
			repoApiCore,
			editTags,
		) ) );
		expectedServices.set( 'languageInfoRepository', new MwLanguageInfoRepository(
			window.mw.language,
			window.$.uls!.data,
		) );
		expectedServices.set( 'entityLabelRepository', new ApiEntityLabelRepository(
			contentLanguage,
			repoApi,
		) );
		expectedServices.set( 'propertyDatatypeRepository', new ApiPropertyDataTypeRepository(
			repoApi,
		) );
		expectedServices.set( 'messagesRepository', new MwMessagesRepository(
			window.mw.message,
		) );
		expectedServices.set( 'wikibaseRepoConfigRepository', new ApiRepoConfigRepository( repoApi ) );
		expectedServices.set( 'tracker', new DataBridgeTrackerService(
			new PrefixingEventTracker(
				new EventTracker( window.mw.track ),
				'MediaWiki.wikibase.client.databridge',
			),
		) );
		expectedServices.set( 'editAuthorizationChecker', new CombiningPermissionsRepository(
			new ApiPageEditPermissionErrorsRepository( repoApi ),
			new ApiPageEditPermissionErrorsRepository( clientApi ),
		) );
		expectedServices.set( 'referencesRenderingRepository', new ApiRenderReferencesRepository(
			clientApi,
			contentLanguage,
		) );
		expectedServices.set( 'repoRouter', new RepoRouter(
			wbRepoConfig,
			window.mw.util.wikiUrlencode,
			$.param,
		) );
		expectedServices.set( 'clientRouter', new ClientRouter(
			window.mw.util.getUrl,
		) );

		expectedServices.set( 'purgeTitles', new ApiPurge( clientMwApi ) );

		const entityId = 'Q5';
		const propertyId = 'P4711';
		const entityTitle = entityId; // main namespace
		const editFlow = EditFlow.SINGLE_BEST_VALUE;
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
				createApp,
				{
					containerSelector: '#data-bridge-container',
				},
				{
					pageTitle,
					entityId,
					propertyId,
					entityTitle,
					editFlow,
					client: { usePublish, issueReportingLink },
					originalHref: testLinkHref,
					pageUrl: 'https://data-bridge.test/jest', // configured in jest.config.js
					userName,
				},
				expectedServices,
			);

			expect( mockSubscribeToEvents ).toHaveBeenCalledWith( emitter, manager );
		} );
	} );
} );
