import {
	MwApi,
	MwApiParameters,
} from '@/@types/mediawiki/MwWindow';
import ApiErrors from '@/data-access/error/ApiErrors';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import Api, {
	ApiAction,
	ApiError,
	ApiParams,
	ApiResponsesMap,
} from '@/definitions/data-access/Api';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import jqXHR = JQuery.jqXHR;

/**
 * Basic implementation of Api using MwApi.
 * This turns set parameters into arrays
 * (the other parameter types MwApi can handle itself)
 * and maps rejections to appropriate error classes.
 * Other Api implementations often wrap this one.
 */
export default class ApiCore implements Api {

	private readonly api: MwApi;

	public constructor( api: MwApi ) {
		this.api = api;
	}

	public postWithEditTokenAndAssertUser<action extends ApiAction>(
		params: ApiParams<action>,
	): Promise<unknown> {
		return Promise.resolve( // turn jQuery promise into native one
			this.api.postWithEditToken(
				this.api.assertCurrentUser( this.mapParameters( params ) ),
			).catch( this.mwApiRejectionToError ),
		);
	}

	public postWithEditToken<action extends ApiAction>(
		params: ApiParams<action>,
	): Promise<unknown> {
		return Promise.resolve( // turn jQuery promise into native one
			this.api.postWithEditToken( this.mapParameters( params ) )
				.catch( this.mwApiRejectionToError ),
		);
	}

	public get<action extends ApiAction>( params: ApiParams<action> ): Promise<ApiResponsesMap[action]> {
		return Promise.resolve( // turn jQuery promise into native one
			this.api.get( this.mapParameters( params ) )
				.catch( this.mwApiRejectionToError ),
		);
	}

	private mapParameters( params: ApiParams<ApiAction> ): MwApiParameters {
		const parameters: MwApiParameters = {};
		for ( const name of Object.keys( params ) ) {
			const param = params[ name ];
			if ( param instanceof Set ) {
				parameters[ name ] = [ ...param ];
			} else {
				parameters[ name ] = param;
			}
		}
		return parameters;
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
					errors?: readonly ApiError[]; // errorformat ≠ 'bc'
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
