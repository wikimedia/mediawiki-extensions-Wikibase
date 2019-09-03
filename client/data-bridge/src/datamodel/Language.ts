interface Language {
	/**
	 *@var string a language in BCP 47 standard (not a Mediawiki language code)
	 */
	code: string;
	directionality: 'auto'|'ltr'|'rtl';
}
export default Language;
