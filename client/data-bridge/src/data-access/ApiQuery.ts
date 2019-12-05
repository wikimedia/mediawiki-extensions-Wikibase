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

export function isInfoTestPage( page: ApiQueryResponsePage ): page is ApiQueryInfoTestResponsePage {
	return 'actions' in page;
}

export function isRestrictionsBody( body: ApiQueryResponseBody ): body is ApiQueryRestrictionsResponseBody {
	return 'restrictions' in body;
}
