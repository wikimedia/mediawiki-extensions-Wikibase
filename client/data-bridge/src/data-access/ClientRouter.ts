import { MwUtilGetUrl } from '@/@types/mediawiki/MwWindow';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';

export default class ClientRouter implements MediaWikiRouter {

	private readonly getUrl: MwUtilGetUrl;

	public constructor(
		getUrl: MwUtilGetUrl,
	) {
		this.getUrl = getUrl;
	}

	public getPageUrl( title: string, params?: Record<string, unknown> ): string {
		return this.getUrl( title, params );
	}

}
