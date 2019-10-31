import ForeignApiWritingRepository from '@/data-access/ForeignApiWritingRepository';
import ServiceRepositories from '@/services/ServiceRepositories';
import SpecialPageReadingEntityRepository from '@/data-access/SpecialPageReadingEntityRepository';
import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';
import MwWindow from '@/@types/mediawiki/MwWindow';
import MwMessagesRepository from '@/data-access/MwMessagesRepository';
import DispatchingEntityLabelRepository from '@/data-access/DispatchingEntityLabelRepository';
import ForeignApiEntityInfoDispatcher from '@/data-access/ForeignApiEntityInfoDispatcher';
import ForeignApiRepoConfigRepository from '@/data-access/ForeignApiRepoConfigRepository';
import DataBridgeTrackerService from '@/data-access/DataBridgeTrackerService';
import EventTracker from '@/mediawiki/facades/EventTracker';
import DispatchingPropertyDataTypeRepository from '@/data-access/DispatchingPropertyDataTypeRepository';

export default function createServices( mwWindow: MwWindow, editTags: string[] ): ServiceRepositories {
	const services = new ServiceRepositories();

	const repoConfig = mwWindow.mw.config.get( 'wbRepo' ),
		specialEntityDataUrl = repoConfig.url + repoConfig.articlePath.replace(
			'$1',
			'Special:EntityData',
		);

	services.setReadingEntityRepository( new SpecialPageReadingEntityRepository(
		mwWindow.$,
		specialEntityDataUrl,
	) );

	if ( mwWindow.mw.ForeignApi === undefined ) {
		throw new Error( 'mw.ForeignApi was not loaded!' );
	}

	const repoForeignApi = new mwWindow.mw.ForeignApi(
		`${repoConfig.url}${repoConfig.scriptPath}/api.php`,
	);

	services.setWritingEntityRepository( new ForeignApiWritingRepository(
		repoForeignApi,
		mwWindow.mw.config.get( 'wgUserName' ),
		editTags.length === 0 ? undefined : editTags,
	) );

	const foreignApiEntityInfoDispatcher = new ForeignApiEntityInfoDispatcher(
		repoForeignApi,
		[ 'labels', 'datatype' ],
	);

	services.setEntityLabelRepository(
		new DispatchingEntityLabelRepository(
			mwWindow.mw.config.get( 'wgPageContentLanguage' ),
			foreignApiEntityInfoDispatcher,
		),
	);

	services.setPropertyDatatypeRepository(
		new DispatchingPropertyDataTypeRepository(
			foreignApiEntityInfoDispatcher,
		),
	);

	if ( mwWindow.$.uls === undefined ) {
		throw new Error( '$.uls was not loaded!' );
	}

	services.setLanguageInfoRepository( new MwLanguageInfoRepository(
		mwWindow.mw.language,
		mwWindow.$.uls.data,
	) );

	services.setMessagesRepository( new MwMessagesRepository( mwWindow.mw.message ) );

	services.setWikibaseRepoConfigRepository( new ForeignApiRepoConfigRepository(
		repoForeignApi,
	) );

	services.setTracker(
		new DataBridgeTrackerService(
			new EventTracker( mwWindow.mw.track ),
		),
	);

	return services;
}
