import { MwApi } from '@/@types/mediawiki/MwWindow';
import ApiErrors from '@/data-access/error/ApiErrors';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import Api, {
	ApiAction,
	ApiError,
	ApiParams,
	ApiResponses,
} from '@/definitions/data-access/Api';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import jqXHR = JQuery.jqXHR;

/**
 * A service to make API requests immediately,
 * wrapping an instance of the MediaWiki API class.
 * It turns set parameters into arrays
 * and maps rejections to appropriate error classes.
 *
 * (The name InstantApi was chosen to contrast BatchingApi.)
 */
export default class InstantApi implements Api {

	private readonly api: MwApi;

	public constructor( api: MwApi ) {
		this.api = api;
	}

	public get<action extends ApiAction>( params: ApiParams<action> ): Promise<ApiResponses[action]> {
		for ( const name of Object.keys( params ) ) {
			const param = params[ name ];
			if ( param instanceof Set ) {
				params[ name ] = [ ...param ];
			}
		}
		return Promise.resolve( // turn jQuery promise into native one
			this.api.get( params )
				.catch( this.mwApiRejectionToError ),
		);
	}

	/**
	 * Translate a rejection from mw.Api into a single error.
	 * Since mw.Api uses jQuery Deferreds, there can be up to four arguments.
	 * (See mw.Api’s ajax method for the code generating the rejections.)
	 */
	private mwApiRejectionToError( code: string, arg2: unknown, _arg3: unknown, _arg4: unknown ): never {
		switch ( code ) {
			case 'http': { // jQuery AJAX failure
				const detail = arg2 as {
					xhr: jqXHR;
					textStatus: string;
					exception: string;
				}; // arg3 and arg4 are not defined
				throw new JQueryTechnicalError( detail.xhr );
			}
			case 'ok-but-empty': { // HTTP 200, empty response body, should never happen™
				const message = arg2 as string; // arg3 is result, arg4 is jqXHR
				throw new TechnicalProblem( message );
			}
			default: { // API error(s)
				const result = arg2 as {
					error?: ApiError; // errorformat = 'bc' (default)
					errors?: ApiError[]; // errorformat ≠ 'bc'
				}; // arg3 is also result, arg4 is jqXHR
				if ( result.error ) {
					throw new ApiErrors( [ result.error ] );
				} else if ( result.errors ) {
					throw new ApiErrors( result.errors );
				} else {
					throw new TechnicalProblem( 'mw.Api rejected but result does not contain error(s)' );
				}
			}
		}
	}
}
