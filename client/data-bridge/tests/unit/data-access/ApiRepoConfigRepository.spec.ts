import ApiRepoConfigRepository from '@/data-access/ApiRepoConfigRepository';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import { mockApi } from '../../util/mocks';
import jqXHR = JQuery.jqXHR;

describe( 'ApiRepoConfigRepository', () => {

	const wbdatabridgeconfig = {
		dataTypeLimits: {
			string: {
				maxLength: 400,
			},
		},
	};
	const api = mockApi( {
		query: {
			wbdatabridgeconfig,
		},
	} );

	it( 'calls the api with the correct parameters', () => {
		jest.spyOn( api, 'get' );

		const configurationRepository = new ApiRepoConfigRepository( api );

		return configurationRepository.getRepoConfiguration().then( () => {
			expect( api.get ).toHaveBeenCalledTimes( 1 );
			expect( api.get ).toHaveBeenCalledWith( {
				action: 'query',
				meta: new Set( [ 'wbdatabridgeconfig' ] ),
				errorformat: 'raw',
				formatversion: 2,
			} );
		} );
	} );

	it( 'returns the configuration from a well-formed response', () => {
		const configurationRepository = new ApiRepoConfigRepository( api );

		return configurationRepository.getRepoConfiguration().then( ( configuration: WikibaseRepoConfiguration ) => {
			expect( configuration ).toStrictEqual( wbdatabridgeconfig );
		} );
	} );

	it( 'rejects if the response does not match the agreed-upon format', () => {
		const api = mockApi( {
			query: {
				wbdatabridgeconfig: {
					foobar: 'yes',
				},
			},
		} );

		const configurationRepository = new ApiRepoConfigRepository( api );

		return expect( configurationRepository.getRepoConfiguration() )
			.rejects
			.toStrictEqual( new TechnicalProblem( 'Result not well formed.' ) );
	} );

	it( 'rejects if the response indicates revelant API endpoint is disabled in repo', () => {
		const api = mockApi( {
			warnings: [
				{
					code: 'unrecognizedvalues',
					module: 'query',
				},
			],
		} );

		const configurationRepository = new ApiRepoConfigRepository( api );

		return expect( configurationRepository.getRepoConfiguration() )
			.rejects
			.toStrictEqual( new TechnicalProblem( 'Result indicates repo API is disabled (see dataBridgeEnabled).' ) );
	} );

	it( 'passes through rejection from underlying API', () => {
		const rejection = new JQueryTechnicalError( {} as jqXHR );
		const api = mockApi( null, rejection );
		const configurationRepository = new ApiRepoConfigRepository( api );

		return expect( configurationRepository.getRepoConfiguration() )
			.rejects
			.toBe( rejection );
	} );

} );
