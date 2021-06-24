import MediaWikiPurge from '@/definitions/MediaWikiPurge';
import { MwApi } from '@/@types/mediawiki/MwWindow';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';

export default class ApiPurge implements MediaWikiPurge {
	private readonly api: MwApi;
	private readonly TITLES_LIMIT = 50;

	public constructor( api: MwApi ) {
		this.api = api;
	}

	public purge( titles: readonly string[] ): Promise<void> {
		if ( titles.length > this.TITLES_LIMIT ) {
			throw new TechnicalProblem( `You cannot purge more than ${this.TITLES_LIMIT} titles` );
		}
		return Promise.resolve(
			this.api.post( {
				action: 'purge',
				titles,
				forcelinkupdate: true,
				errorformat: 'raw',
				formatversion: 2,
			} ),
		);
	}
}
