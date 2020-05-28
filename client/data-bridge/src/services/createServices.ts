import ApiCore from '@/data-access/ApiCore';
import ApiReadingEntityRepository from '@/data-access/ApiReadingEntityRepository';
import ApiWritingRepository from '@/data-access/ApiWritingRepository';
import BatchingApi from '@/data-access/BatchingApi';
import ClientRouter from '@/data-access/ClientRouter';
import TrimmingWritingRepository from '@/data-access/TrimmingWritingRepository';
import ServiceContainer from '@/services/ServiceContainer';
import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';
import MwWindow from '@/@types/mediawiki/MwWindow';
import RepoRouter from '@/data-access/RepoRouter';
import MwMessagesRepository from '@/data-access/MwMessagesRepository';
import ApiRepoConfigRepository from '@/data-access/ApiRepoConfigRepository';
import DataBridgeTrackerService from '@/data-access/DataBridgeTrackerService';
import ApiPropertyDataTypeRepository from '@/data-access/ApiPropertyDataTypeRepository';
import ApiEntityLabelRepository from '@/data-access/ApiEntityLabelRepository';
import CombiningPermissionsRepository from '@/data-access/CombiningPermissionsRepository';
import ApiPageEditPermissionErrorsRepository from '@/data-access/ApiPageEditPermissionErrorsRepository';
import ApiPurge from '@/data-access/ApiPurge';
import Tracker from '@/tracking/Tracker';
import ApiRenderReferencesRepository from '@/data-access/ApiRenderReferencesRepository';

export default function createServices(
	mwWindow: MwWindow,
	editTags: readonly string[],
	eventTracker: Tracker,
): ServiceContainer {
	const services = new ServiceContainer();

	const
		repoConfig = mwWindow.mw.config.get( 'wbRepo' ),
		repoRouter = new RepoRouter(
			repoConfig,
			mwWindow.mw.util.wikiUrlencode,
			mwWindow.$.param,
		);

	const clientMwApi = new mwWindow.mw.Api();

	if ( mwWindow.mw.ForeignApi === undefined ) {
		throw new Error( 'mw.ForeignApi was not loaded!' );
	}

	const repoMwApi = new mwWindow.mw.ForeignApi( // TODO use repoRouter with a getScript() method maybe
		`${repoConfig.url}${repoConfig.scriptPath}/api.php`,
	);
	const repoApiCore = new ApiCore( repoMwApi );
	const repoApi = new BatchingApi( repoApiCore );

	services.set( 'readingEntityRepository', new ApiReadingEntityRepository(
		repoApi,
	) );

	services.set( 'writingEntityRepository', new TrimmingWritingRepository( new ApiWritingRepository(
		repoApiCore,
		editTags.length === 0 ? undefined : editTags,
	) ) );

	const pageContentLanguage = mwWindow.mw.config.get( 'wgPageContentLanguage' );
	services.set( 'entityLabelRepository', new ApiEntityLabelRepository(
		pageContentLanguage,
		repoApi,
	) );

	services.set( 'propertyDatatypeRepository', new ApiPropertyDataTypeRepository(
		repoApi,
	) );

	if ( mwWindow.$.uls === undefined ) {
		throw new Error( '$.uls was not loaded!' );
	}

	services.set( 'languageInfoRepository', new MwLanguageInfoRepository(
		mwWindow.mw.language,
		mwWindow.$.uls.data,
	) );

	services.set( 'messagesRepository', new MwMessagesRepository( mwWindow.mw.message ) );

	services.set( 'wikibaseRepoConfigRepository', new ApiRepoConfigRepository(
		repoApi,
	) );

	services.set( 'tracker', new DataBridgeTrackerService( eventTracker ) );

	const clientApi = new ApiCore( clientMwApi );

	services.set( 'editAuthorizationChecker', new CombiningPermissionsRepository(
		new ApiPageEditPermissionErrorsRepository( repoApi ),
		new ApiPageEditPermissionErrorsRepository( clientApi ),
	) );

	services.set( 'referencesRenderingRepository', new ApiRenderReferencesRepository(
		clientApi,
		pageContentLanguage,
	) );

	services.set( 'repoRouter', repoRouter );

	services.set( 'clientRouter', new ClientRouter( mwWindow.mw.util.getUrl ) );

	const purge = new ApiPurge( clientMwApi );
	services.set( 'purgeTitles', purge );

	return services;
}
