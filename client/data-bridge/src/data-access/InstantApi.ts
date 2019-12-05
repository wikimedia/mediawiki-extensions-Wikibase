import { MwApi } from '@/@types/mediawiki/MwWindow';
import Api, {
	ApiAction,
	ApiParams,
	ApiResponses,
} from '@/definitions/data-access/Api';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';

/**
 * A service to make API requests immediately,
 * wrapping an instance of the MediaWiki API class.
 * It turns sets into arrays and maps request errors
 * to JQueryTechnicalError.
 *
 * (The name InstantApi was chosen to contrast BatchingApi.)
 */
export default class InstantApi implements Api {

	private readonly api: MwApi;

	public constructor( api: MwApi ) {
		this.api = api;
	}

	public async get<action extends ApiAction>( params: ApiParams<action> ): Promise<ApiResponses[action]> {
		for ( const name of Object.keys( params ) ) {
			const param = params[ name ];
			if ( param instanceof Set ) {
				params[ name ] = [ ...param ];
			}
		}
		try {
			return await this.api.get( params );
		} catch ( xhr ) {
			throw new JQueryTechnicalError( xhr );
		}
	}

}
