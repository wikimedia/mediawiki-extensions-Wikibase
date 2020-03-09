export interface WikibaseRepoConfiguration {
	dataTypeLimits: {
		string: {
			maxLength: number;
		};
	};
	dataRightsUrl: string;
	dataRightsText: string;
	termsOfUseUrl: string;
}

/**
 * Repository to get the configuration from the Wikibase repo instance where the data is going to be saved.
 */
export default interface WikibaseRepoConfigRepository {
	getRepoConfiguration(): Promise<WikibaseRepoConfiguration>;
}
