import {
	ApiAction,
	ApiParams,
	ApiResponse,
	ApiResponsesMap,
	ReadingApi,
} from '@/definitions/data-access/Api';

/**
 * A service to batch API requests.
 * Compatible requests made within the same call stack
 * (i.e., synchronously) are merged into one.
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
 * Whether two requests are compatible depends on their parameters.
 * Parameters that only occur in one of two requests,
 * being either missing or set to `undefined` in the other,
 * have no effect on compatibility and are always added to the resulting request.
 * If the same parameter name occurs in both requests,
 * the result depends on the type of the value:
 *
 * - If the values on both sides are sets (instances of Set),
 *   the sets are merged into one for the resulting request,
 *   and sent to the underlying API as an array of values.
 *   (Integer values are converted to strings.)
 * - If the value on either side is an array (instance of Array),
 *   the requests are incompatible.
 *   This only makes sense for parameters where duplicates are significant;
 *   for most parameters, you should use sets instead.
 * - If the values on both sides are booleans,
 *   the requests are incompatible if the values are different.
 *   Otherwise, they are sent unmodified to the underlying API.
 * - If the values on both sides are `undefined`,
 *   they are sent unmodified to the underlying API.
 * - Otherwise, the values on both sides are converted to strings.
 *   If the resulting strings are different, the requests are incompatible;
 *   otherwise, they are added to the resulting request.
 *   (The string conversion ensures that, for example,
 *   formatversion: 2 and formatversion: '2' are compatible.)
 *
 * Callers should specify all the parameters that they rely on,
 * even where this means specifying the default value, so that
 * conflicts with requests specifying non-default values can be detected.
 * Using formatversion: 2 is strongly encouraged.
 */
export default class BatchingApi implements ReadingApi {

	private readonly api: ReadingApi;

	private readonly requests: {
		params: ApiParams<ApiAction>;
		promise: Promise<ApiResponse>;
		resolve: ( response: ApiResponse ) => void;
		reject: ( reason: unknown ) => void;
	}[];

	/**
	 * Create a new service for requests to the given (local or foreign) API.
	 * @param api Underlying implementation responsible for
	 * making the merged API calls (usually an {@link ApiCore}).
	 */
	public constructor( api: ReadingApi ) {
		this.api = api;
		this.requests = [];
	}

	public get<action extends ApiAction>( params: ApiParams<action> ): Promise<ApiResponsesMap[ action ]> {
		for ( const request of this.requests ) {
			const mergedParams = this.mergeParams( request.params, params );
			if ( mergedParams !== false ) {
				// add to existing request
				request.params = mergedParams;
				return request.promise;
			}
		}
		// no matching existing request, add new request
		let resolve: ( response: ApiResponse ) => void = undefined as never,
			reject: ( reason: unknown ) => void = undefined as never;
		const promise = new Promise<ApiResponse>( ( resolve_, reject_ ) => {
			resolve = resolve_;
			reject = reject_;
		} );
		this.requests.push( { params, promise, resolve, reject } );
		if ( this.requests.length === 1 ) {
			Promise.resolve().then( () => this.flush() );
		}
		return promise;
	}

	/**
	 * Flush the queue of pending requests,
	 * sending them all to the underlying API
	 * and resolving or rejecting their promises as needed.
	 */
	private flush(): void {
		for ( const { params, resolve, reject } of this.requests ) {
			this.api.get( params ).then( resolve, reject );
		}
		this.requests.length = 0; // truncate
	}

	/**
	 * Merge two sets of parameters into one,
	 * or return `false` if they are incompatible.
	 * The original objects are never modified.
	 */
	private mergeParams<action1 extends ApiAction, action2 extends ApiAction>(
		params1: ApiParams<action1>,
		params2: ApiParams<action2>,
	): ApiParams<action1&action2>|false {
		const paramNames = new Set( [
			...Object.getOwnPropertyNames( params1 ),
			...Object.getOwnPropertyNames( params2 ),
		] );
		const mergedParams: ApiParams<action1&action2> = {} as ApiParams<action1&action2>;
		for ( const paramName of paramNames ) {
			const inParams1 = params1[ paramName ] !== undefined,
				inParams2 = params2[ paramName ] !== undefined;
			if ( !( inParams1 && inParams2 ) ) {
				mergedParams[ paramName ] = ( inParams1 ? params1 : params2 )[ paramName ];
				continue;
			}
			const value1 = params1[ paramName ],
				value2 = params2[ paramName ];
			if ( value1 instanceof Set && value2 instanceof Set ) {
				const mergedSet = new Set<string>();
				mergedParams[ paramName ] = mergedSet;
				for ( const valueSet of [ value1, value2 ] ) {
					for ( const member of valueSet ) {
						mergedSet.add( String( member ) );
					}
				}
				continue;
			}
			if ( value1 instanceof Array || value2 instanceof Array ) {
				return false;
			}
			if ( typeof value1 === 'boolean' && typeof value2 === 'boolean' ) {
				if ( value1 === value2 ) {
					mergedParams[ paramName ] = value1;
					continue;
				} else {
					return false;
				}
			}
			const string1 = String( value1 ),
				string2 = String( value2 );
			if ( string1 === string2 ) {
				mergedParams[ paramName ] = string1;
				continue;
			} else {
				return false;
			}
		}
		return mergedParams;
	}
}
