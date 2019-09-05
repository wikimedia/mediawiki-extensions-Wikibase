import createServices from '@/mediawiki/createServices';
import MwWindow from '@/@types/mediawiki/MwWindow';
import ServiceRepositories from '@/services/ServiceRepositories';
import SpecialPageReadingEntityRepository from '@/data-access/SpecialPageReadingEntityRepository';
import ForeignApiWritingRepository from '@/data-access/ForeignApiWritingRepository';

const mockReadingEntityRepository = {};
jest.mock( '@/data-access/SpecialPageReadingEntityRepository', () => {
	return jest.fn().mockImplementation( () => {
		return mockReadingEntityRepository;
	} );
} );

const mockWritingEntityRepository = {};
jest.mock( '@/data-access/ForeignApiWritingRepository', () => {
	return jest.fn().mockImplementation( () => mockWritingEntityRepository );
} );

describe( 'createServices', () => {
	it( 'pulls wbRepo and wgUserName from mw.config, ' +
		'creates ReadingEntityRepository and WritingEntityRepository with it', () => {
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
		expect( ( SpecialPageReadingEntityRepository as jest.Mock ).mock.calls[ 0 ][ 0 ] )
			.toBe( $ );
		expect( ( SpecialPageReadingEntityRepository as jest.Mock ).mock.calls[ 0 ][ 1 ] )
			.toBe( 'http://localhost/wiki/Special:EntityData' );
		expect( services.getReadingEntityRepository() ).toBe( mockReadingEntityRepository );
		expect( mwWindow.mw.ForeignApi )
			.toHaveBeenCalledWith( 'http://localhost/w/api.php' );
		expect( ( ForeignApiWritingRepository as unknown as jest.Mock ).mock.calls[ 0 ][ 0 ] )
			.toBeInstanceOf( mwWindow.mw.ForeignApi );
		expect( ( ForeignApiWritingRepository as unknown as jest.Mock ).mock.calls[ 0 ][ 1 ] )
			.toBe( 'Test User' );
		expect( services.getWritingEntityRepository() ).toBe( mockWritingEntityRepository );
	} );
} );
