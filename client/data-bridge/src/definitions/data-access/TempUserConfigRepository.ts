export interface TempUserConfiguration {
	enabled: boolean;
}

/**
 * Repository to get the configuration of TempUser account from the Wikibase
 * instance where the data is going to be saved.
 */
export default interface TempUserConfigRepository {
	getTempUserConfiguration(): Promise<TempUserConfiguration>;
}
