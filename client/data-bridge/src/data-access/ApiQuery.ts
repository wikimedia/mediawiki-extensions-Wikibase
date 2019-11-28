import {
	ApiError,
	ApiQueryResponseBody,
	ApiQueryResponsePage,
} from '@/definitions/data-access/Api';

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

export interface ApiQueryInfoTestResponsePage extends ApiQueryResponsePage {
	actions: {
		[ action: string ]: ApiError[];
	};
}

export function isInfoTestPage( page: ApiQueryResponsePage ): page is ApiQueryInfoTestResponsePage {
	return 'actions' in page;
}

export interface ApiQueryRestrictionsResponseBody extends ApiQueryResponseBody {
	restrictions: {
		types: string[];
		levels: string[];
		cascadinglevels: string[];
		semiprotectedlevels: string[];
	};
}

export function isRestrictionsBody( body: ApiQueryResponseBody ): body is ApiQueryRestrictionsResponseBody {
	return 'restrictions' in body;
}
