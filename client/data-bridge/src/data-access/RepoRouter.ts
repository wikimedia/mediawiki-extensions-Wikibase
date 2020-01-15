import WbRepo from '@/@types/wikibase/WbRepo';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';
import { MwUtilWikiUrlencode } from '@/@types/mediawiki/MwWindow';

type QuerySerializer = ( query: Record<string, unknown> ) => string;

export default class RepoRouter implements MediaWikiRouter {
	private readonly repoConfig: WbRepo;
	private readonly wikiUrlencode: MwUtilWikiUrlencode;
	private readonly querySerializer: QuerySerializer;

	public constructor(
		repoConfig: WbRepo,
		wikiUrlencode: MwUtilWikiUrlencode,
		querySerializer: QuerySerializer,
	) {
		this.repoConfig = repoConfig;
		this.wikiUrlencode = wikiUrlencode;
		this.querySerializer = querySerializer;
	}

	public getPageUrl( title: string, params?: Record<string, unknown> ): string {
		let url, query;

		if ( params ) {
			query = this.querySerializer( params );
		}

		if ( query ) {
			url = this.wikiScript() + '?title=' + this.wikiUrlencode( title ) + '&' + query;
		} else {
			url = this.repoConfig.url + this.repoConfig.articlePath.replace(
				'$1',
				this.wikiUrlencode( title )
					.replace( /\$/g, '$$$$' ),
			);
		}

		return url;
	}

	private wikiScript( script = 'index.php' ): string {
		return this.repoConfig.url + this.repoConfig.scriptPath + '/' + script;
	}
}
