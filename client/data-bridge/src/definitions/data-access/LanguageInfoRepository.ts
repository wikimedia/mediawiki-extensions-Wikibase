import Language from '@/datamodel/Language';

export default interface LanguageInfoRepository {
	/**
	 * @param languageCode a Mediawiki language code
	 */
	resolve( languageCode: string ): Language;
}
