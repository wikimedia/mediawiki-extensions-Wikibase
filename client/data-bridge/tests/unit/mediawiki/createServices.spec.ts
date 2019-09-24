import createServices from '@/mediawiki/createServices';
import MwWindow, { MwMessages } from '@/@types/mediawiki/MwWindow';
import ServiceRepositories from '@/services/ServiceRepositories';
import SpecialPageReadingEntityRepository from '@/data-access/SpecialPageReadingEntityRepository';
import ForeignApiWritingRepository from '@/data-access/ForeignApiWritingRepository';
import ForeignApiEntityLabelRepository from '@/data-access/ForeignApiEntityLabelRepository';
import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';
import MwMessagesRepository from '@/data-access/MwMessagesRepository';
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

const mockMessagesRepository = {};
jest.mock( '@/data-access/MwMessagesRepository', () => {
	return jest.fn().mockImplementation( () => mockMessagesRepository );
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
	editTags?: string[];
	message?: MwMessages;
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
			case 'wbDataBridgeConfig':
				return {
					editTags: options.editTags || [],
				};
			default:
				throw new Error( `Unexpected config key ${key}!` );
		}
	} );

	const $ = new ( jest.fn() )();
	const language = options.mwLanguage || { bcp47: jest.fn() };
	const data = options.ulsData || { getDir: jest.fn() };
	$.uls = { data };
	const message = options.message || jest.fn();

	return {
		mw: {
			config: {
				get,
			},
			ForeignApi: jest.fn(),
			language,
			message,
		},
		$,
	} as unknown as MwWindow;
}

describe( 'createServices', () => {
	it( 'pulls wbRepo and wgUserName from mw.config, ', () => {
		const wbRepo = {
			url: 'http://localhost',
			scriptPath: '/w',
			articlePath: '/wiki/$1',
		};
		const mwWindow = mockMwWindow( {
			wbRepo,
		} );
		const services = createServices( mwWindow, [] );

		expect( services ).toBeInstanceOf( ServiceRepositories );
		expect( mwWindow.mw.config.get ).toHaveBeenCalledWith( 'wbRepo' );
		expect( SpecialPageReadingEntityRepository ).toHaveBeenCalledTimes( 1 );
		expect( SpecialPageReadingEntityRepository ).toHaveBeenCalledWith(
			mwWindow.$,
			'http://localhost/wiki/Special:EntityData',
		);
		expect( services.getReadingEntityRepository() ).toBe( mockReadingEntityRepository );
	} );

	describe( 'WritingEntityRepository', () => {
		it( 'creates WritingEntityRepository with it', () => {
			const wgUserName = 'TestUser';
			const wbRepo = {
				url: 'http://localhost',
				scriptPath: '/w',
				articlePath: '',
			};
			const editTags = [ 'a' ];
			const mwWindow = mockMwWindow( {
				wbRepo,
				wgUserName,
			} );
			const services = createServices( mwWindow, editTags );

			expect( mwWindow.mw.ForeignApi )
				.toHaveBeenCalledWith( 'http://localhost/w/api.php' );
			expect( ( ForeignApiWritingRepository as unknown as jest.Mock ).mock.calls[ 0 ][ 0 ] )
				.toBeInstanceOf( mwWindow.mw.ForeignApi );
			expect( ( ForeignApiWritingRepository as unknown as jest.Mock ).mock.calls[ 0 ][ 1 ] )
				.toBe( wgUserName );
			expect( ( ForeignApiWritingRepository as unknown as jest.Mock ).mock.calls[ 0 ][ 2 ] )
				.toBe( editTags );
			expect( services.getWritingEntityRepository() ).toBe( mockWritingEntityRepository );
		} );

		it( 'add undefinded to tags, if they are a empty list', () => {
			const wgUserName = 'TestUser';
			const wbRepo = {
				url: 'http://localhost',
				scriptPath: '/w',
				articlePath: '',
			};
			const editTags: string[] = [];
			const mwWindow = mockMwWindow( {
				wbRepo,
				wgUserName,
			} );
			const services = createServices( mwWindow, editTags );

			expect( services ).toBeInstanceOf( ServiceRepositories );
			expect( ( ForeignApiWritingRepository as unknown as jest.Mock ).mock.calls[ 0 ][ 2 ] )
				.toBeUndefined();
			expect( services.getWritingEntityRepository() ).toBe( mockWritingEntityRepository );
		} );
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

		const services = createServices( mwWindow, [] );

		expect( services ).toBeInstanceOf( ServiceRepositories );
		expect( MwLanguageInfoRepository ).toHaveBeenCalledTimes( 1 );
		expect( MwLanguageInfoRepository ).toHaveBeenCalledWith( mwLanguage, ulsData );
		expect( services.getLanguageInfoRepository() ).toBe( mockMwLanguageInfoRepository );
	} );

	it( 'creates EntityLabelRepository', () => {
		const wgPageContentLanguage = 'de';

		const mwWindow = mockMwWindow( {
			wgPageContentLanguage,
		} );

		const services = createServices( mwWindow, [] );

		expect( services ).toBeInstanceOf( ServiceRepositories );
		expect(
			( ForeignApiEntityLabelRepository as jest.Mock ).mock.calls[ 0 ][ 0 ],
		).toBe( wgPageContentLanguage );

		expect( ( ForeignApiEntityLabelRepository as jest.Mock ).mock.calls[ 0 ][ 1 ] )
			.toBeInstanceOf( mwWindow.mw.ForeignApi );
		expect( services.getEntityLabelRepository() ).toBe( mockEntityLabelRepository );
	} );

	it( 'creates MessagesRepository', () => {
		const message = jest.fn();
		const mwWindow = mockMwWindow( { message } );

		const services = createServices( mwWindow, [] );

		expect( services ).toBeInstanceOf( ServiceRepositories );
		expect( MwMessagesRepository ).toHaveBeenCalledTimes( 1 );
		expect( MwMessagesRepository ).toHaveBeenCalledWith( message );
		expect( services.getMessagesRepository() ).toBe( mockMessagesRepository );
	} );

} );
