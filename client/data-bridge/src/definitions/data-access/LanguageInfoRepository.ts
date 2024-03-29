import Bcp47Language from '@/datamodel/Bcp47Language';

export default interface LanguageInfoRepository {
	/**
	 * @param languageCode a MediaWiki language code
	 */
	resolve( languageCode: string ): Bcp47Language;
}
