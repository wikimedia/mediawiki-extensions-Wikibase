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

	return services;
}
