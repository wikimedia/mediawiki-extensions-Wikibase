import ForeignApiRepoConfigRepository from '@/data-access/ForeignApiRepoConfigRepository';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import { ForeignApi } from '@/@types/mediawiki/MwWindow';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';

function mockForeignApi( successObject?: unknown, rejectData?: unknown ): ForeignApi {
	return {
		get(): any {
			if ( successObject ) {
				return Promise.resolve( successObject );
			}
			if ( rejectData ) {
				return Promise.reject( rejectData );
			}
		},
	} as any;
}

describe( 'ForeignApiRepoConfigRepository', () => {

	const wbdatabridgeconfig = {
		dataTypeLimits: {
			string: {
				maxLength: 400,
			},
		},
	};
	const foreignApi = mockForeignApi( {
		query: {
			wbdatabridgeconfig,
		},
	} );

	it( 'calls the foreignApi with the correct parameters', () => {
		jest.spyOn( foreignApi, 'get' );

		const configurationRepository = new ForeignApiRepoConfigRepository( foreignApi );

		return configurationRepository.getRepoConfiguration().then( () => {
			expect( foreignApi.get ).toHaveBeenCalledTimes( 1 );
			expect( foreignApi.get ).toHaveBeenCalledWith( {
				action: 'query',
				meta: 'wbdatabridgeconfig',
				errorformat: 'none',
				formatversion: 2,
			} );
		} );
	} );

	it( 'returns the configuration from a well-formed response', () => {
		const configurationRepository = new ForeignApiRepoConfigRepository( foreignApi );

		return configurationRepository.getRepoConfiguration().then( ( configuration: WikibaseRepoConfiguration ) => {
			expect( configuration ).toStrictEqual( wbdatabridgeconfig );
		} );
	} );

	it( 'rejects if the response does not match the agreed-upon format', () => {
		const foreignApi = mockForeignApi( {
			query: {
				wbdatabridgeconfig: {
					foobar: 'yes',
				},
			},
		} );

		const configurationRepository = new ForeignApiRepoConfigRepository( foreignApi );

		return expect( configurationRepository.getRepoConfiguration() )
			.rejects
			.toStrictEqual( new TechnicalProblem( 'Result not well formed.' ) );
	} );

	it( 'rejects if the response indicates revelant API endpoint is disabled in repo', () => {
		const foreignApi = mockForeignApi( {
			warnings: [
				{
					code: 'unrecognizedvalues',
					module: 'query',
				},
			],
		} );

		const configurationRepository = new ForeignApiRepoConfigRepository( foreignApi );

		return expect( configurationRepository.getRepoConfiguration() )
			.rejects
			.toStrictEqual( new TechnicalProblem( 'Result indicates repo API is disabled (see dataBridgeEnabled).' ) );
	} );

	it( 'rejects if there was a serverside problem with the API', () => {
		const foreignApi = mockForeignApi( null, { status: 500 } );
		const configurationRepository = new ForeignApiRepoConfigRepository( foreignApi );

		return expect( configurationRepository.getRepoConfiguration() )
			.rejects
			.toStrictEqual( new JQueryTechnicalError( { status: 500 } as any ) );
	} );

} );
