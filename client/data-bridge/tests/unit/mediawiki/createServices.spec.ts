import createServices from '@/mediawiki/createServices';
import MwWindow from '@/@types/mediawiki/MwWindow';
import ServiceRepositories from '@/services/ServiceRepositories';
import SpecialPageEntityRepository from '@/data-access/SpecialPageEntityRepository';
import ForeignApiWritingRepository from '@/data-access/ForeignApiWritingRepository';

const mockEntityRepository = {};
jest.mock( '@/data-access/SpecialPageEntityRepository', () => {
	return jest.fn().mockImplementation( () => {
		return mockEntityRepository;
	} );
} );

const mockWritingEntityRepository = {};
jest.mock( '@/data-access/ForeignApiWritingRepository', () => {
	return jest.fn().mockImplementation( () => mockWritingEntityRepository );
} );

describe( 'createServices', () => {
	it( 'pulls wbRepo and wgUserName from mw.config, ' +
		'creates EntityRepository and WritingEntityRepository with it', () => {
		const get = jest.fn().mockImplementation( ( key ) => {
			switch ( key ) {
				case 'wbRepo':
					return {
						url: 'http://localhost',
						scriptPath: '/w',
						articlePath: '/wiki/$1',
					};
				case 'wgUserName':
					return 'Test User';
				default:
					throw new Error( `Unexpected config key ${key}!` );
			}
		} );
		const $ = new ( jest.fn() )();
		const mwWindow = {
			mw: {
				config: {
					get,
				},
				ForeignApi: jest.fn(),
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
		expect( mwWindow.mw.ForeignApi )
			.toHaveBeenCalledWith( 'http://localhost/w/api.php' );
		expect( ( ForeignApiWritingRepository as unknown as jest.Mock ).mock.calls[ 0 ][ 0 ] )
			.toBeInstanceOf( mwWindow.mw.ForeignApi );
		expect( ( ForeignApiWritingRepository as unknown as jest.Mock ).mock.calls[ 0 ][ 1 ] )
			.toBe( 'Test User' );
		expect( services.getWritingEntityRepository() ).toBe( mockWritingEntityRepository );
	} );
} );
