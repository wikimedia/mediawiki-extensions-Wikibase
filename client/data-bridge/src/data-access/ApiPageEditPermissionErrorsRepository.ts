import {
	getApiQueryResponsePage,
	isInfoTestPage,
	isRestrictionsBody,
} from '@/data-access/ApiQuery';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import TitleInvalid from '@/data-access/error/TitleInvalid';
import Api, { ApiError } from '@/definitions/data-access/Api';
import PageEditPermissionErrorsRepository, {
	PermissionError,
	PermissionErrorProtectedPage,
	PermissionErrorType,
	PermissionErrorUnknown,
} from '@/definitions/data-access/PageEditPermissionErrorsRepository';

interface ApiErrorRawErrorformat extends ApiError {
	key: string;
	params: ( string|number )[];
}

function isApiErrorRawErrorformat( error: ApiError ): error is ApiErrorRawErrorformat {
	return 'key' in error && 'params' in error;
}

export default class ApiPageEditPermissionErrorsRepository implements PageEditPermissionErrorsRepository {

	private readonly api: Api;

	public constructor( api: Api ) {
		this.api = api;
	}

	public async getPermissionErrors( title: string ): Promise<PermissionError[]> {
		const response = await this.api.get( {
			action: 'query',
			titles: new Set( [ title ] ),
			prop: new Set( [ 'info' ] ),
			meta: new Set( [ 'siteinfo' ] ),
			intestactions: new Set( [ 'edit' ] ),
			intestactionsdetail: 'full',
			siprop: new Set( [ 'restrictions' ] ),
			errorformat: 'raw',
			formatversion: 2,
		} );
		const queryBody = response.query;
		const page = getApiQueryResponsePage( queryBody, title );
		if ( page === null ) {
			throw new TechnicalProblem( `API did not return information for page '${title}'.` );
		}
		if ( page.invalid ) { // no need to check .missing, intestactions still works in that case
			throw new TitleInvalid( title );
		}
		if ( !isInfoTestPage( page ) ) {
			throw new TechnicalProblem( 'API info did not return test actions.' );
		}
		if ( !isRestrictionsBody( queryBody ) ) {
			throw new TechnicalProblem( 'API siteinfo did not return restrictions.' );
		}
		const semiProtectedLevels = queryBody.restrictions.semiprotectedlevels
			.map( this.rewriteCompatibilityRight );
		return page.actions.edit.map(
			( error ) => this.apiErrorToPermissionError( error, semiProtectedLevels ),
		);
	}

	private apiErrorToPermissionError( error: ApiError, semiProtectedLevels: string[] ): PermissionError {
		if ( !isApiErrorRawErrorformat( error ) ) {
			throw new TechnicalProblem( 'API returned wrong error format.' );
		}
		switch ( error.code ) {
			case 'protectedpage': {
				const right = error.params[ 0 ] as string;
				const permissionError: PermissionErrorProtectedPage = {
					type: PermissionErrorType.PROTECTED_PAGE,
					right,
					semiProtected: semiProtectedLevels.includes( right ),
				};
				return permissionError;
			}
			default: {
				const permissionError: PermissionErrorUnknown = {
					type: PermissionErrorType.UNKNOWN,
					code: error.code,
					messageKey: error.key,
					messageParams: error.params,
				};
				return permissionError;
			}
		}
	}

	/**
	 * Account for MediaWiki backwards compatibility –
	 * a protection level can be not only a right,
	 * but also the 'sysop' group (rewritten to 'editprotected' right)
	 * or the 'autoconfirmed' group (rewritten to 'editsemiprotected' right).
	 * API errors always use the rewritten right,
	 * but the $wgSemiprotectedRestrictionLevels setting may contain a group.
	 */
	private rewriteCompatibilityRight( rightOrGroup: string ): string {
		switch ( rightOrGroup ) {
			case 'sysop': return 'editprotected';
			case 'autoconfirmed': return 'editsemiprotected';
			default: return rightOrGroup;
		}
	}

}
