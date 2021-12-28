import Bcp47Language from '@/datamodel/Bcp47Language';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';

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

export function testInLanguage( mwLangCode: string ): { lang: string; dir: string;} {
	const language = testLanguageInfoRepository.resolve( mwLangCode );
	return {
		lang: language.code,
		dir: language.directionality,
	};
}
