import createServices from '@/mediawiki/createServices';
import MwWindow from '@/@types/mediawiki/MwWindow';
import ServiceRepositories from '@/services/ServiceRepositories';
import SpecialPageEntityRepository from '@/data-access/SpecialPageEntityRepository';

const mockEntityRepository = {};
jest.mock( '@/data-access/SpecialPageEntityRepository', () => {
	return jest.fn().mockImplementation( () => {
		return mockEntityRepository;
	} );
} );

describe( 'createServices', () => {
	it( 'pulls wbRepo from mw.config, creates EntityRepository with it', () => {
		const get = jest.fn().mockImplementation( () => ( {
			url: 'http://localhost',
			scriptPath: '/w/',
			articlePath: '/wiki/$1',
		} ) );
		const $ = new ( jest.fn() )();
		const mwWindow = {
			mw: {
				config: {
					get,
				},
			},
			$,
		} as unknown as MwWindow;

		const services = createServices( mwWindow );

		expect( services ).toBeInstanceOf( ServiceRepositories );
		expect( get ).toHaveBeenCalledWith( 'wbRepo' );
		expect( ( SpecialPageEntityRepository as jest.Mock ).mock.calls[ 0 ][ 0 ] )
			.toBe( $ );
		expect( ( SpecialPageEntityRepository as jest.Mock ).mock.calls[ 0 ][ 1 ] )
			.toBe( 'http://localhost/wiki/Special:EntityData' );
		expect( services.getEntityRepository() ).toBe( mockEntityRepository );
	} );
} );
