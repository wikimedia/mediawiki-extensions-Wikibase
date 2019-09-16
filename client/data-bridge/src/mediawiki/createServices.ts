import ForeignApiWritingRepository from '@/data-access/ForeignApiWritingRepository';
import ServiceRepositories from '@/services/ServiceRepositories';
import SpecialPageReadingEntityRepository from '@/data-access/SpecialPageReadingEntityRepository';
import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';
import MwWindow from '@/@types/mediawiki/MwWindow';
import ForeignApiEntityLabelRepository from '@/data-access/ForeignApiEntityLabelRepository';

export default function createServices( mwWindow: MwWindow ): ServiceRepositories {
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

	const editTags = mwWindow.mw.config.get( 'wbDataBridgeConfig' ).editTags;
	services.setWritingEntityRepository( new ForeignApiWritingRepository(
		repoForeignApi,
		mwWindow.mw.config.get( 'wgUserName' ),
		editTags.length === 0 ? undefined : editTags,
	) );

	services.setEntityLabelRepository(
		new ForeignApiEntityLabelRepository(
			mwWindow.mw.config.get( 'wgPageContentLanguage' ),
			repoForeignApi,
		),
	);

	if ( mwWindow.$.uls === undefined ) {
		throw new Error( '$.uls was not loaded!' );
	}

	services.setLanguageInfoRepository( new MwLanguageInfoRepository(
		mwWindow.mw.language,
		mwWindow.$.uls.data,
	) );
	return services;
}
