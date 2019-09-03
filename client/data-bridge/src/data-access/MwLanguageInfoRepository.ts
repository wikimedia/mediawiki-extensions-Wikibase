import {
	UlsData,
	MwLanguage,
} from '@/@types/mediawiki/MwWindow';
import Language from '@/datamodel/Language';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';

export default class MwLanguageInfoRepository implements LanguageInfoRepository {
	private directionalityResolver: ( languageCode: string ) => 'ltr'|'rtl';
	private bcp47Resolver: ( languageCode: string ) => string;

	public constructor(
		mwLanguage: MwLanguage,
		ulsDirectionality: UlsData,
	) {
		this.directionalityResolver = ulsDirectionality.getDir;
		this.bcp47Resolver = mwLanguage.bcp47;
	}

	public resolve( languageCode: string ): Language {
		return {
			code: this.bcp47Resolver( languageCode ),
			directionality: this.directionalityResolver( languageCode ),
		};
	}
}
