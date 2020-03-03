export default interface MediaWikiPurge {
	purge( titles: string[] ): Promise<void>;
}
