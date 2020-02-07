import Bcp47Language from '@/datamodel/Bcp47Language';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';
import inlanguage from '@/presentation/directives/inlanguage';

export const testLanguageInfoRepository: LanguageInfoRepository = {
	resolve( languageCode: string ): Bcp47Language {
		switch ( languageCode ) {
			case 'en':
				return { code: 'en', directionality: 'ltr' };
			case 'he':
				return { code: 'he', directionality: 'rtl' };
			default:
				return { code: languageCode, directionality: 'auto' };
		}
	},
};

export const testDirectivesInLanguage = {
	inlanguage: inlanguage( testLanguageInfoRepository ),
};
