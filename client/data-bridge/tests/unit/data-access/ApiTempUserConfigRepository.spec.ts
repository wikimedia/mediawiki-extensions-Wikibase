import ApiTempUserConfigRepository from '@/data-access/ApiTempUserConfigRepository';
import { TempUserConfiguration } from '@/definitions/data-access/TempUserConfigRepository';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import { getMockTempUserConfig, mockApi } from '../../util/mocks';
import jqXHR = JQuery.jqXHR;

describe( 'ApiTempUserConfigRepository', () => {

	const autocreatetempuser = getMockTempUserConfig();
	const api = mockApi( {
		query: {
			autocreatetempuser,
		},
	} );

	it( 'calls the api with the correct parameters', () => {
		jest.spyOn( api, 'get' );

		const configurationRepository = new ApiTempUserConfigRepository( api );

		return configurationRepository.getTempUserConfiguration().then( () => {
			expect( api.get ).toHaveBeenCalledTimes( 1 );
			expect( api.get ).toHaveBeenCalledWith( {
				action: 'query',
				meta: new Set( [ 'siteinfo' ] ),
				siprop: new Set( [ 'autocreatetempuser' ] ),
				errorformat: 'raw',
				formatversion: 2,
			} );
		} );
	} );

	it( 'returns the configuration from a well-formed response', () => {
		const configurationRepository = new ApiTempUserConfigRepository( api );

		return configurationRepository.getTempUserConfiguration().then( ( configuration: TempUserConfiguration ) => {
			expect( configuration ).toStrictEqual( autocreatetempuser );
		} );
	} );

	describe( 'if the response does not match the agreed-upon format', () => {
		it( 'rejects if the response does not contain the autocreatetempuser in the expected format ', () => {
			const api = mockApi( {
				query: {
					autocreatetempuser: 'some unexpected content',
				},
			} );

			const configurationRepository = new ApiTempUserConfigRepository( api );

			return expect( configurationRepository.getTempUserConfiguration() )
				.rejects
				.toStrictEqual( new TechnicalProblem( 'Result not well formed.' ) );
		} );

		it( 'it rejects if the enabled key is missing', () => {
			const api = mockApi( {
				query: {
					autocreatetempuser: {},
				},
			} );

			const configurationRepository = new ApiTempUserConfigRepository( api );

			return expect( configurationRepository.getTempUserConfiguration() )
				.rejects
				.toStrictEqual( new TechnicalProblem( 'Result not well formed.' ) );
		} );
	} );

	it( 'passes through rejection from underlying API', () => {
		const rejection = new JQueryTechnicalError( {} as jqXHR );
		const api = mockApi( null, rejection );
		const configurationRepository = new ApiTempUserConfigRepository( api );

		return expect( configurationRepository.getTempUserConfiguration() )
			.rejects
			.toBe( rejection );
	} );

} );
