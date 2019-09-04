import ForeignApiWritingRepository from '@/data-access/ForeignApiWritingRepository';
import ServiceRepositories from '@/services/ServiceRepositories';
import SpecialPageEntityRepository from '@/data-access/SpecialPageEntityRepository';
import MwWindow from '@/@types/mediawiki/MwWindow';

export default function createServices( mwWindow: MwWindow ): ServiceRepositories {
	const services = new ServiceRepositories();

	const repoConfig = mwWindow.mw.config.get( 'wbRepo' ),
		specialEntityDataUrl = repoConfig.url + repoConfig.articlePath.replace(
			'$1',
			'Special:EntityData',
		);

	services.setEntityRepository( new SpecialPageEntityRepository(
		mwWindow.$,
		specialEntityDataUrl,
	) );

	if ( mwWindow.mw.ForeignApi === undefined ) {
		throw new Error( 'mw.ForeignApi was not loaded!' );
	}

	services.setWritingEntityRepository( new ForeignApiWritingRepository(
		new mwWindow.mw.ForeignApi(
			`${repoConfig.url}${repoConfig.scriptPath}/api.php`,
		),
		mwWindow.mw.config.get( 'wgUserName' ),
		// TODO tags from some config
	) );

	return services;
}
