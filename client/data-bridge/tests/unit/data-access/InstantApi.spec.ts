import { MwApi } from '@/@types/mediawiki/MwWindow';
import InstantApi from '@/data-access/InstantApi';
import {
	ApiAction,
	ApiParams,
} from '@/definitions/data-access/Api';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import { mockMwApi } from '../../util/mocks';
import { expectError } from '../../util/promise';
import jqXHR = JQuery.jqXHR;

describe( 'InstantApi', () => {

	it( 'resolves with unmodified API response on success', async () => {
		const expectedResponse = { some: 'result' };
		const mwApi = mockMwApi( expectedResponse );
		const api = new InstantApi( mwApi );

		const actualResponse = await api.get( { action: 'unknown' } );

		expect( actualResponse ).toBe( expectedResponse );
	} );

	it( 'rejects with jQuery error on failure', async () => {
		const xhr = {} as jqXHR;
		const mwApi = mockMwApi( undefined, xhr );
		const api = new InstantApi( mwApi );

		const error = await expectError( api.get( { action: 'unknown' } ) );

		expect( error ).toStrictEqual( new JQueryTechnicalError( xhr ) );
	} );

	it( 'maps sets to arrays and passes through all other parameter types', async () => {
		const mwApi: MwApi = {
			get: jest.fn( () => Promise.resolve( {} ) ),
		} as unknown as MwApi;
		const api = new InstantApi( mwApi );

		await api.get( {
			string: 's',
			int: 1,
			boolean: true,
			array: [ 'array element' ],
			set: new Set( [ 'set element' ] ),
		} as unknown as ApiParams<ApiAction> );

		expect( mwApi.get ).toHaveBeenCalledWith( {
			string: 's',
			int: 1,
			boolean: true,
			array: [ 'array element' ],
			set: [ 'set element' ],
		} );
	} );

} );
