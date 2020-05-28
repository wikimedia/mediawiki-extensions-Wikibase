export default interface MediaWikiPurge {
	purge( titles: readonly string[] ): Promise<void>;
}
