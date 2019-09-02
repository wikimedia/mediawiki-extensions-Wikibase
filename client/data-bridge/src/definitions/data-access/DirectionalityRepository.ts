export default interface DirectionalityRepository {
	resolve( languageCode: string ): 'ltr'|'rtl'|'auto';
}
