import {
	ApiError,
	ApiResponse,
} from '@/definitions/data-access/Api';

export interface ApiQueryResponsePage {
	title: string;
	missing?: true;
	invalid?: true;
}

export interface ApiQueryResponseBody {
	normalized?: readonly {
		fromencoded: boolean;
		from: string;
		to: string;
	}[];
	pages?: readonly ApiQueryResponsePage[];
}

export interface ApiQueryResponse extends ApiResponse {
	query: ApiQueryResponseBody;
}
export interface ApiQueryInfoTestResponsePage extends ApiQueryResponsePage {
	actions: {
		[action: string]: readonly ApiError[];
	};
}

export interface ApiQueryRestrictionsResponseBody extends ApiQueryResponseBody {
	restrictions: {
		types: readonly string[];
		levels: readonly string[];
		cascadinglevels: readonly string[];
		semiprotectedlevels: readonly string[];
	};
}
