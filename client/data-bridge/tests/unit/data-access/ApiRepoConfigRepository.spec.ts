import ApiRepoConfigRepository from '@/data-access/ApiRepoConfigRepository';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import { getMockBridgeRepoConfig, mockApi } from '../../util/mocks';
import jqXHR = JQuery.jqXHR;
import clone from '@/store/clone';

describe( 'ApiRepoConfigRepository', () => {

	const wbdatabridgeconfig = getMockBridgeRepoConfig();
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

	describe( 'if the response does not match the agreed-upon format', () => {
		it( 'rejects if the response does not contain the wbdatabridgeconfig in the expected format ', () => {
			const api = mockApi( {
				query: {
					wbdatabridgeconfig: 'some unexpected content',
				},
			} );

			const configurationRepository = new ApiRepoConfigRepository( api );

			return expect( configurationRepository.getRepoConfiguration() )
				.rejects
				.toStrictEqual( new TechnicalProblem( 'Result not well formed.' ) );
		} );

		it.each(
			[ 'dataRightsUrl', 'dataRightsText', 'termsOfUseUrl', 'dataTypeLimits' ] as const,
		)( 'it rejects if the %s is missing', ( configKey: keyof WikibaseRepoConfiguration ) => {
			const localWbdatabridgeconfig = clone( wbdatabridgeconfig );
			delete localWbdatabridgeconfig[ configKey ];
			const api = mockApi( {
				query: {
					wbdatabridgeconfig: localWbdatabridgeconfig,
				},
			} );

			const configurationRepository = new ApiRepoConfigRepository( api );

			return expect( configurationRepository.getRepoConfiguration() )
				.rejects
				.toStrictEqual( new TechnicalProblem( 'Result not well formed.' ) );
		} );
	} );

	it( 'rejects if the response indicates relevant API endpoint is disabled in repo', () => {
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
