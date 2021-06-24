import ApiPurge from '@/data-access/ApiPurge';
import { mockApi } from '../../util/mocks';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';

describe( 'ApiPurge', () => {

	it( 'makes API post request with the correct parameters', () => {
		const api = mockApi();
		jest.spyOn( api, 'post' );
		const mockTitles = [ 'South_Pole_Telescope' ];

		const purgeService = new ApiPurge( api );

		return purgeService.purge( mockTitles ).then(
			() => {
				expect( api.post ).toHaveBeenCalledTimes( 1 );
				expect( api.post ).toHaveBeenCalledWith( {
					action: 'purge',
					titles: mockTitles,
					forcelinkupdate: true,
					errorformat: 'raw',
					formatversion: 2,
				} );
			},
		);
	} );

	it( 'throws a technical error if more than 50 titles are passed for purging', () => {
		const api = mockApi();
		jest.spyOn( api, 'post' );
		const mockTitles = Array( 51 ).fill( 'South_Pole_Telescope' ); // array with 51 entries

		const purgeService = new ApiPurge( api );

		return expect( () => purgeService.purge( mockTitles ) )
			.toThrow( new TechnicalProblem( 'You cannot purge more than 50 titles' ) );
	} );

} );
