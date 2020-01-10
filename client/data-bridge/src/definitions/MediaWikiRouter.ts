export default interface MediaWikiRouter {
	getPageUrl( title: string, params?: Record<string, unknown> ): string;
}
