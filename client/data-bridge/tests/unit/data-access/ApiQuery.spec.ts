import { getApiQueryResponsePage } from '@/data-access/ApiQuery';
import {
	ApiQueryResponseBody,
	ApiQueryResponsePage,
} from '@/definitions/data-access/ApiQuery';

describe( 'getApiQueryResponsePage', () => {

	it( 'finds page of same title', () => {
		const title = 'Title';
		const page: ApiQueryResponsePage = { title };
		const response: ApiQueryResponseBody = { pages: [ page ] };

		expect( getApiQueryResponsePage( response, title ) ).toBe( page );
	} );

	it( 'follows normalized title', () => {
		const unnormalizedTitle = 'title';
		const normalizedTitle = 'Title';
		const page: ApiQueryResponsePage = { title: normalizedTitle };
		const response: ApiQueryResponseBody = {
			normalized: [ {
				fromencoded: false,
				from: unnormalizedTitle,
				to: normalizedTitle,
			} ],
			pages: [ page ],
		};

		expect( getApiQueryResponsePage( response, unnormalizedTitle ) ).toBe( page );
	} );

	it( 'returns null for missing page', () => {
		const absentTitle = 'Absent title';
		const presentTitle = 'Present title';
		const presentPage: ApiQueryResponsePage = { title: presentTitle };
		const response: ApiQueryResponseBody = { pages: [ presentPage ] };

		expect( getApiQueryResponsePage( response, absentTitle ) ).toBeNull();
	} );

	it( 'returns null for missing pages', () => {
		const absentTitle = 'Absent title';
		const response: ApiQueryResponseBody = {};

		expect( getApiQueryResponsePage( response, absentTitle ) ).toBeNull();
	} );

} );
