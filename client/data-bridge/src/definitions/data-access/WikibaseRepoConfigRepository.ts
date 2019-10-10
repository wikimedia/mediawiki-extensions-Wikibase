export interface WikibaseRepoConfiguration {
	dataTypeLimits: {
		string: {
			maxLength: number;
		};
	};
}

/**
 * Repository to get the configuration from the Wikibase repo instance where the data is going to be saved.
 */
export default interface WikibaseRepoConfigRepository {
	getRepoConfiguration(): Promise<WikibaseRepoConfiguration>;
}
