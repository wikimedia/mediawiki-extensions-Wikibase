import { MwApi } from '@/@types/mediawiki/MwWindow';
import ApiErrors from '@/data-access/error/ApiErrors';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import ApiCore from '@/data-access/ApiCore';
import {
	ApiAction,
	ApiParams,
} from '@/definitions/data-access/Api';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import { mockApi } from '../../util/mocks';
import $ from 'jquery';
import jqXHR = JQuery.jqXHR;

describe( 'ApiCore', () => {

	it( 'resolves with unmodified API response on success', async () => {
		const expectedResponse = { some: 'result' };
		const mwApi = mockApi( expectedResponse );
		const api = new ApiCore( mwApi );

		const actualResponse = await api.get( { action: 'unknown' } );

		expect( actualResponse ).toBe( expectedResponse );
	} );

	it( 'maps sets to arrays and passes through all other parameter types', async () => {
		const mwApi: MwApi = {
			get: jest.fn( () => Promise.resolve( {} ) ),
		} as unknown as MwApi;
		const api = new ApiCore( mwApi );

		await api.get( {
			string: 's',
			int: 1,
			boolean: true,
			array: [ 'array element', 123 ],
			set: new Set( [ 'set element', 456 ] ),
		} as unknown as ApiParams<ApiAction> );

		expect( mwApi.get ).toHaveBeenCalledWith( {
			string: 's',
			int: 1,
			boolean: true,
			array: [ 'array element', 123 ],
			set: [ 'set element', 456 ],
		} );
	} );

	function mockRejectingMwApi( ...args: any[] ): MwApi {
		return { get() {
			return $.Deferred().reject( ...args ).promise();
		} } as unknown as MwApi;
	}

	it( 'rejects with jQuery error on AJAX failure', () => {
		const xhr = {} as jqXHR;
		const textStatus = 'error';
		const exception = 'Not Found';
		const mwApi = mockRejectingMwApi( 'http', { xhr, textStatus, exception } );
		const api = new ApiCore( mwApi );

		return expect( api.get( { action: 'unknown' } ) )
			.rejects
			.toStrictEqual( new JQueryTechnicalError( xhr ) );
	} );

	it( 'rejects with technical problem on empty response', () => {
		const message = 'OK response but empty result (check HTTP headers?)';
		const mwApi = mockRejectingMwApi(
			'ok-but-empty',
			message,
			undefined,
			{} as jqXHR,
		);
		const api = new ApiCore( mwApi );

		return expect( api.get( { action: 'unknown' } ) )
			.rejects
			.toStrictEqual( new TechnicalProblem( message ) );
	} );

	it( 'rejects with ApiErrors on errorformat=bc response', () => {
		const error = { code: 'unknown_action' };
		const result = { error };
		const mwApi = mockRejectingMwApi( error.code, result, result, {} as jqXHR );
		const api = new ApiCore( mwApi );

		return expect( api.get( { action: 'unknown' } ) )
			.rejects
			.toStrictEqual( new ApiErrors( [ error ] ) );
	} );

	it( 'rejects with ApiErrors on errorformat=none response', () => {
		const unknownActionError = { code: 'unknown_action' };
		const assertUserError = { code: 'assertuserfailed' };
		const result = { errors: [ unknownActionError, assertUserError ] };
		const mwApi = mockRejectingMwApi( unknownActionError.code, result, result, {} as jqXHR );
		const api = new ApiCore( mwApi );

		return expect( api.get( { action: 'unknown', assert: 'user', errorformat: 'none' } ) )
			.rejects
			.toStrictEqual( new ApiErrors( [ unknownActionError, assertUserError ] ) );
	} );

} );
