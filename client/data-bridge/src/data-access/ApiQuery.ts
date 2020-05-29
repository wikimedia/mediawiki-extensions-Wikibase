import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import {
	ApiQueryInfoTestResponsePage,
	ApiQueryResponseBody,
	ApiQueryResponsePage,
	ApiQueryRestrictionsResponseBody,
} from '@/definitions/data-access/ApiQuery';

export function getApiQueryResponsePage( response: ApiQueryResponseBody, title: string ): ApiQueryResponsePage|null {
	for ( const normalized of ( response.normalized || [] ) ) {
		if ( normalized.from === title ) {
			title = normalized.to;
			break;
		}
	}
	for ( const page of ( response.pages || [] ) ) {
		if ( page.title === title ) {
			return page;
		}
	}
	return null;
}

export function assertIsInfoTestPage(
	page: ApiQueryResponsePage,
): asserts page is ApiQueryInfoTestResponsePage {
	if ( !( 'actions' in page ) ) {
		throw new TechnicalProblem( 'API info did not return test actions.' );
	}
}

export function assertIsRestrictionsBody(
	body: ApiQueryResponseBody,
): asserts body is ApiQueryRestrictionsResponseBody {
	if ( !( 'restrictions' in body ) ) {
		throw new TechnicalProblem( 'API siteinfo did not return restrictions.' );
	}
}
