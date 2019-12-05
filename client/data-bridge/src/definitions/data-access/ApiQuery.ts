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
	normalized?: {
		fromencoded: boolean;
		from: string;
		to: string;
	}[];
	pages?: ApiQueryResponsePage[];
}

export interface ApiQueryResponse extends ApiResponse {
	query: ApiQueryResponseBody;
}
export interface ApiQueryInfoTestResponsePage extends ApiQueryResponsePage {
	actions: {
		[action: string]: ApiError[];
	};
}

export interface ApiQueryRestrictionsResponseBody extends ApiQueryResponseBody {
	restrictions: {
		types: string[];
		levels: string[];
		cascadinglevels: string[];
		semiprotectedlevels: string[];
	};
}
