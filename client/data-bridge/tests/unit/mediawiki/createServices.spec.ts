import createServices from '@/mediawiki/createServices';
import MwWindow from '@/@types/mediawiki/MwWindow';
import ServiceRepositories from '@/services/ServiceRepositories';
import SpecialPageReadingEntityRepository from '@/data-access/SpecialPageReadingEntityRepository';
import ForeignApiWritingRepository from '@/data-access/ForeignApiWritingRepository';
import ForeignApiEntityLabelRepository from '@/data-access/ForeignApiEntityLabelRepository';
import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';
import WbRepo from '@/@types/wikibase/WbRepo';

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

const mockEntityLabelRepository = {};
jest.mock( '@/data-access/ForeignApiEntityLabelRepository', () => {
	return jest.fn().mockImplementation( () => mockEntityLabelRepository );
} );

const mockMwLanguageInfoRepository = {};
jest.mock( '@/data-access/MwLanguageInfoRepository', () => {
	return jest.fn().mockImplementation( () => {
		return mockMwLanguageInfoRepository;
	} );
} );

function mockMwWindow( options: {
	wbRepo?: WbRepo;
	wgUserName?: string;
	ulsData?: {
		getDir(): string;
	};
	mwLanguage?: {
		bcp47(): string;
	};
	wgPageContentLanguage?: string;
} = {} ): MwWindow {
	const get = jest.fn().mockImplementation( ( key ) => {
		switch ( key ) {
			case 'wbRepo':
				return {
					...{
						url: 'http://localhost',
						scriptPath: '/w',
						articlePath: '/wiki/$1',
					},
					...options.wbRepo,
				};
			case 'wgUserName':
				return options.wgUserName || null;
			case 'wgPageContentLanguage':
				return options.wgPageContentLanguage || 'en';
			default:
				throw new Error( `Unexpected config key ${key}!` );
		}
	} );

	const $ = new ( jest.fn() )();
	const language = options.mwLanguage || { bcp47: jest.fn() };
	const data = options.ulsData || { getDir: jest.fn() };
	$.uls = { data };

	return {
		mw: {
			config: {
				get,
			},
			ForeignApi: jest.fn(),
			language,
		},
		$,
	} as unknown as MwWindow;
}

describe( 'createServices', () => {
	beforeEach( () => {
		( SpecialPageReadingEntityRepository as jest.Mock ).mockClear();
		( ForeignApiWritingRepository as unknown as jest.Mock ).mockClear();
		( MwLanguageInfoRepository as jest.Mock ).mockClear();
		( ForeignApiEntityLabelRepository as jest.Mock ).mockClear();
	} );

	it( 'pulls wbRepo and wgUserName from mw.config, ', () => {
		const wbRepo = {
			url: 'http://localhost',
			scriptPath: '/w',
			articlePath: '/wiki/$1',
		};
		const mwWindow = mockMwWindow( {
			wbRepo,
		} );
		const services = createServices( mwWindow );

		expect( services ).toBeInstanceOf( ServiceRepositories );
		expect( mwWindow.mw.config.get ).toHaveBeenCalledWith( 'wbRepo' );
		expect( ( SpecialPageReadingEntityRepository as jest.Mock ).mock.calls[ 0 ][ 0 ] )
			.toBe( mwWindow.$ );
		expect( ( SpecialPageReadingEntityRepository as jest.Mock ).mock.calls[ 0 ][ 1 ] )
			.toBe( 'http://localhost/wiki/Special:EntityData' );
		expect( services.getReadingEntityRepository() ).toBe( mockReadingEntityRepository );
	} );

	it( 'creates EntityRepository and WritingEntityRepository with it', () => {
		const wgUserName = 'TestUser';
		const wbRepo = {
			url: 'http://localhost',
			scriptPath: '/w',
			articlePath: '',
		};
		const mwWindow = mockMwWindow( {
			wbRepo,
			wgUserName,
		} );
		const services = createServices( mwWindow );

		expect( mwWindow.mw.ForeignApi )
			.toHaveBeenCalledWith( 'http://localhost/w/api.php' );
		expect( ( ForeignApiWritingRepository as unknown as jest.Mock ).mock.calls[ 0 ][ 0 ] )
			.toBeInstanceOf( mwWindow.mw.ForeignApi );
		expect( ( ForeignApiWritingRepository as unknown as jest.Mock ).mock.calls[ 0 ][ 1 ] )
			.toBe( wgUserName );
		expect( services.getWritingEntityRepository() ).toBe( mockWritingEntityRepository );
	} );

	it( 'creates LanguageInfoRepository', () => {
		const ulsData = {
			getDir: jest.fn(),
		};

		const mwLanguage = {
			bcp47: jest.fn(),
		};

		const mwWindow = mockMwWindow( {
			ulsData,
			mwLanguage,
		} );

		const services = createServices( mwWindow );

		expect( services ).toBeInstanceOf( ServiceRepositories );
		expect(
			( MwLanguageInfoRepository as jest.Mock ).mock.calls[ 0 ][ 0 ],
		).toBe( mwLanguage );
		expect(
			( MwLanguageInfoRepository as jest.Mock ).mock.calls[ 0 ][ 1 ],
		).toBe( ulsData );
		expect( services.getLanguageInfoRepository() ).toBe( mockMwLanguageInfoRepository );
	} );

	it( 'creates EntityLabelRepository', () => {
		const wgPageContentLanguage = 'de';

		const mwWindow = mockMwWindow( {
			wgPageContentLanguage,
		} );

		const services = createServices( mwWindow );

		expect( services ).toBeInstanceOf( ServiceRepositories );
		expect(
			( ForeignApiEntityLabelRepository as jest.Mock ).mock.calls[ 0 ][ 0 ],
		).toBe( wgPageContentLanguage );

		expect( ( ForeignApiEntityLabelRepository as jest.Mock ).mock.calls[ 0 ][ 1 ] )
			.toBeInstanceOf( mwWindow.mw.ForeignApi );
		expect( services.getEntityLabelRepository() ).toBe( mockEntityLabelRepository );
	} );

} );
