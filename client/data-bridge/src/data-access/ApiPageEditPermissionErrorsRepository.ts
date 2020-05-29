import {
	getApiQueryResponsePage,
	assertIsInfoTestPage,
	assertIsRestrictionsBody,
} from '@/data-access/ApiQuery';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import TitleInvalid from '@/data-access/error/TitleInvalid';
import { ApiBlockedError, ApiError, ReadingApi } from '@/definitions/data-access/Api';
import PageEditPermissionErrorsRepository, {
	PermissionError,
	PermissionErrorCascadeProtectedPage,
	PermissionErrorBlockedUser,
	PermissionErrorProtectedPage,
	PermissionErrorType,
	PermissionErrorUnknown,
} from '@/definitions/data-access/PageEditPermissionErrorsRepository';

export interface ApiErrorRawErrorformat extends ApiError {
	key: string;
	params: readonly ( string|number )[];
}

function assertIsApiErrorRawErrorformat(
	error: ApiError,
): asserts error is ApiErrorRawErrorformat {
	if ( !( 'key' in error && 'params' in error ) ) {
		throw new TechnicalProblem( 'API returned wrong error format.' );
	}
}

export default class ApiPageEditPermissionErrorsRepository implements PageEditPermissionErrorsRepository {

	private readonly api: ReadingApi;

	public constructor( api: ReadingApi ) {
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
		assertIsInfoTestPage( page );
		assertIsRestrictionsBody( queryBody );
		const semiProtectedLevels = queryBody.restrictions.semiprotectedlevels
			.map( this.rewriteCompatibilityRight );
		return page.actions.edit.map(
			( error ) => this.apiErrorToPermissionError( error, semiProtectedLevels ),
		);
	}

	private apiErrorToPermissionError( error: ApiError, semiProtectedLevels: readonly string[] ): PermissionError {
		assertIsApiErrorRawErrorformat( error );
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
			case 'cascadeprotected': {
				const pages = this.parseWikitextPagesList( error.params[ 1 ] as string );
				if ( pages.length !== error.params[ 0 ] ) {
					throw new TechnicalProblem(
						`API reported ${error.params[ 0 ]} cascade-protected pages but we parsed ${pages.length}.`,
					);
				}
				const permissionError: PermissionErrorCascadeProtectedPage = {
					type: PermissionErrorType.CASCADE_PROTECTED_PAGE,
					pages,
				};
				return permissionError;
			}
			case 'blocked': {
				const permissionError: PermissionErrorBlockedUser = {
					type: PermissionErrorType.BLOCKED,
					blockinfo: ( error as ApiBlockedError ).data.blockinfo,
					// ToDo: current IP missing because of T240565
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

	/**
	 * Parse a list of pages from (very limited) wikitext.
	 * See PermissionManager::checkCascadingSourcesRestrictions()
	 * for the PHP code generating the list.
	 */
	private parseWikitextPagesList( wikitext: string ): string[] {
		const lines = wikitext.split( '\n' );
		const trailingLine = lines.pop();
		if ( trailingLine !== '' ) {
			throw new TechnicalProblem( `Wikitext did not end in blank line: ${trailingLine}` );
		}
		return lines.map( ( line ) => {
			if ( !line.startsWith( '*' ) ) {
				throw new TechnicalProblem( `Line does not look like a list item: ${line}` );
			}
			let listItem = line.slice( 1 );
			if ( listItem.startsWith( ' ' ) ) {
				listItem = listItem.slice( 1 );
			}
			if ( !listItem.startsWith( '[[' ) || !listItem.endsWith( ']]' ) ) {
				throw new TechnicalProblem( `List item does not look like a wikilink: ${listItem}` );
			}
			let title = listItem.slice( 2, -2 );
			if ( title.startsWith( ':' ) ) {
				title = title.slice( 1 );
			}
			return title;
		} );
	}

}
