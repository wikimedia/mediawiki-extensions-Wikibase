import { MwApi } from '@/@types/mediawiki/MwWindow';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import TitleInvalid from '@/data-access/error/TitleInvalid';
import PageEditPermissionErrorsRepository, {
	PermissionError,
	PermissionErrorProtectedPage,
	PermissionErrorType,
	PermissionErrorUnknown,
} from '@/definitions/data-access/PageEditPermissionErrorsRepository';

interface QueryResponse {
	query: QueryResponseBody;
}

interface QueryResponseBody {
	normalized?: {
		fromencoded: boolean;
		from: string;
		to: string;
	}[];
	pages: QueryResponsePage[];
}

interface QueryResponsePage {
	title: string;
	missing?: true;
	invalid?: true;
}

interface QueryResponsePageInfoTest extends QueryResponsePage {
	actions: {
		[ action: string ]: ApiError[];
	};
}

function isQueryResponsePageInfoTest( page: QueryResponsePage ): page is QueryResponsePageInfoTest {
	return 'actions' in page;
}

interface RestrictionsQueryResponseBody extends QueryResponseBody {
	restrictions: {
		types: string[];
		levels: string[];
		cascadinglevels: string[];
		semiprotectedlevels: string[];
	};
}

function isRestrictionsQueryResponseBody( body: QueryResponseBody ): body is RestrictionsQueryResponseBody {
	return 'restrictions' in body;
}

interface ApiError {
	code: string;
	data?: object;
}

interface ApiErrorRawErrorformat extends ApiError {
	key: string;
	params: string[];
}

function isApiErrorRawErrorformat( error: ApiError ): error is ApiErrorRawErrorformat {
	return 'key' in error && 'params' in error;
}

export default class ApiPageEditPermissionErrorsRepository implements PageEditPermissionErrorsRepository {

	private readonly api: MwApi;

	public constructor( api: MwApi ) {
		this.api = api;
	}

	public async getPermissionErrors( title: string ): Promise<PermissionError[]> {
		let response;
		try {
			response = await this.api.get( {
				action: 'query',
				titles: [ title ],
				prop: [ 'info' ],
				meta: [ 'siteinfo' ],
				intestactions: [ 'edit' ],
				intestactionsdetail: 'full',
				siprop: [ 'restrictions' ],
				errorformat: 'raw',
				formatversion: 2,
			} );
		} catch ( error ) {
			throw new JQueryTechnicalError( error );
		}
		const page = this.queryResponsePage( response, title );
		if ( page === null ) {
			throw new TechnicalProblem( `API did not return information for page '${title}'.` );
		}
		if ( page.invalid ) { // no need to check .missing, intestactions still works in that case
			throw new TitleInvalid( title );
		}
		if ( !isQueryResponsePageInfoTest( page ) ) {
			throw new TechnicalProblem( 'API info did not return test actions.' );
		}
		const queryBody = response.query;
		if ( !isRestrictionsQueryResponseBody( queryBody ) ) {
			throw new TechnicalProblem( 'API siteinfo did not return restrictions.' );
		}
		const semiProtectedLevels = queryBody.restrictions.semiprotectedlevels
			.map( this.rewriteCompatibilityRight );
		return page.actions.edit.map(
			( error ) => this.apiErrorToPermissionError( error, semiProtectedLevels ),
		);
	}

	private queryResponsePage( response: QueryResponse, title: string ): QueryResponsePage|null {
		for ( const normalized of ( response.query.normalized || [] ) ) {
			if ( normalized.from === title ) {
				title = normalized.to;
				break;
			}
		}
		for ( const page of response.query.pages ) {
			if ( page.title === title ) {
				return page;
			}
		}
		return null;
	}

	private apiErrorToPermissionError( error: ApiError, semiProtectedLevels: string[] ): PermissionError {
		if ( !isApiErrorRawErrorformat( error ) ) {
			throw new TechnicalProblem( 'API returned wrong error format.' );
		}
		switch ( error.code ) {
			case 'protectedpage': {
				const right = error.params[ 0 ];
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
