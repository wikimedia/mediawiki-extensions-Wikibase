import { ApiQueryResponse } from '@/definitions/data-access/ApiQuery';
import { ApiWbgetentitiesResponse } from '@/definitions/data-access/ApiWbgetentities';

export type ApiResponse = {
	// there are no members common to all API modules
};

export interface ApiResponsesMap {
	query: ApiQueryResponse;
	wbgetentities: ApiWbgetentitiesResponse;
	[ action: string ]: ApiResponse;
}
export type ApiAction = keyof ApiResponsesMap & string;

export interface ApiParams<action extends ApiAction> {
	action: action;
	[ name: string ]: string | number | boolean | undefined | readonly ( string|number )[] | Set<string|number>;
}

export interface ReadingApi {
	/**
	 * Send a GET request with at least the given parameters.
	 * The resulting response may include data from other requests
	 * which were combined with this one.
	 */
	get<action extends ApiAction>( params: ApiParams<action> ): Promise<ApiResponsesMap[ action ]>;
}

export interface WritingApi {
	/**
	 * Send a POST request with the given parameters and with a valid edit token
	 */
	postWithEditToken( params: ApiParams<string> ): Promise<unknown>;

	/**
	 * Add parameters to assert that the current local browser tab login status is also true at the server.
	 *
	 * Otherwise the same as postWithEditToken
	 */
	postWithEditTokenAndAssertUser( params: ApiParams<string> ): Promise<unknown>;
}

/**
 * An interface for MediaWiki API requests.
 * Some implementations may merge compatible requests for efficiency.
 *
 * Usage example:
 *
 * ```
 * api.get( {
 *     action: 'query',
 *     prop: new Set( [ 'info' ] ),
 *     meta: new Set( [ 'siteinfo' ] ),
 *     titles: new Set( [ 'Help:Contents', 'Project:Main Page' ] ),
 *     redirects: true,
 *     inprop: new Set( [ 'url' ] ),
 *     siprop: new Set( [ 'usergroups' ] ),
 *     formatversion: 2,
 * } );
 * ```
 *
 * Each parameter value may be:
 *
 * - A string or integer, for a plain parameter that must be exactly this value.
 * - A boolean, which is completely omitted if false.
 * - A Set of strings or integers, which may be merged with other sets from compatible requests.
 *   (Integers are interchangeable with their string representations.)
 *   This should be used for most multi-value parameters,
 *   replacing most uses of arrays with MediaWiki’s API class.
 * - An Array of strings or integers, which will never be merged with other requests.
 *   This is mainly useful for parameters that allow duplicate values,
 *   or where the order is significant.
 * - `undefined`, which is completely omitted (just like `false`).
 *
 * Callers should specify all the parameters that they rely on,
 * even where this means specifying the default value, so that
 * conflicts with requests specifying non-default values can be detected.
 * Using formatversion: 2 is strongly encouraged.
 */
export default interface Api extends ReadingApi, WritingApi {}

export interface ApiError {
	code: string;
	data?: object;
}

export interface ApiBlockedErrorBlockInfo {
	blockid: number;
	blockedby: string;
	blockedbyid: number;
	blockreason: string;
	blockedtimestamp: string;
	blockexpiry: string;
	blockpartial: boolean;
}

export interface ApiBlockedError extends ApiError {
	code: 'blocked';
	data: {
		blockinfo: ApiBlockedErrorBlockInfo;
	};
}

export interface ApiBadtokenError extends ApiError {
	code: 'badtoken';
	params: string[];
}
