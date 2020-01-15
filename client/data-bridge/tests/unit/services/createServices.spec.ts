import createServices from '@/services/createServices';
import MwWindow, { MwMessages } from '@/@types/mediawiki/MwWindow';
import ServiceContainer from '@/services/ServiceContainer';
import SpecialPageReadingEntityRepository from '@/data-access/SpecialPageReadingEntityRepository';
import ApiWritingRepository from '@/data-access/ApiWritingRepository';
import ApiEntityLabelRepository from '@/data-access/ApiEntityLabelRepository';
import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';
import MwMessagesRepository from '@/data-access/MwMessagesRepository';
import WbRepo from '@/@types/wikibase/WbRepo';
import ApiCore from '@/data-access/ApiCore';
import BatchingApi from '@/data-access/BatchingApi';
import ApiRepoConfigRepository from '@/data-access/ApiRepoConfigRepository';
import DataBridgeTrackerService from '@/data-access/DataBridgeTrackerService';
import EventTracker from '@/mediawiki/facades/EventTracker';
import ApiPropertyDataTypeRepository from '@/data-access/ApiPropertyDataTypeRepository';
import ApiPageEditPermissionErrorsRepository from '@/data-access/ApiPageEditPermissionErrorsRepository';
import CombiningPermissionsRepository from '@/data-access/CombiningPermissionsRepository';
import RepoRouter from '@/data-access/RepoRouter';

const mockReadingEntityRepository = {};
jest.mock( '@/data-access/SpecialPageReadingEntityRepository', () => {
	return jest.fn().mockImplementation( () => {
		return mockReadingEntityRepository;
	} );
} );

const mockWritingEntityRepository = {};
jest.mock( '@/data-access/ApiWritingRepository', () => {
	return jest.fn().mockImplementation( () => mockWritingEntityRepository );
} );

const mockEntityLabelRepository = {};
jest.mock( '@/data-access/ApiEntityLabelRepository', () => {
	return jest.fn().mockImplementation( () => mockEntityLabelRepository );
} );

const mockPropertyDataTypeRepository = {};
jest.mock( '@/data-access/ApiPropertyDataTypeRepository', () => {
	return jest.fn().mockImplementation( () => mockPropertyDataTypeRepository );
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

const mockRepoApiCore = {};
const mockClientApiCore = {};
jest.mock( '@/data-access/ApiCore', () => {
	return jest.fn().mockImplementation( () => {} );
} );

const mockBatchingApi = {};
jest.mock( '@/data-access/BatchingApi', () => {
	return jest.fn().mockImplementation( () => mockBatchingApi );
} );

const mockWikibaseRepoConfigRepository = {};
jest.mock( '@/data-access/ApiRepoConfigRepository', () => {
	return jest.fn().mockImplementation( () => mockWikibaseRepoConfigRepository );
} );

const mockDataBridgeTrackerService = {};
jest.mock( '@/data-access/DataBridgeTrackerService', () => {
	return jest.fn().mockImplementation( () => mockDataBridgeTrackerService );
} );

const mockEventTracker = {};
jest.mock( '@/mediawiki/facades/EventTracker', () => {
	return jest.fn().mockImplementation( () => mockEventTracker );
} );

const mockCombiningPermissionsRepository = {};
jest.mock( '@/data-access/CombiningPermissionsRepository', () => {
	return jest.fn().mockImplementation( () => mockCombiningPermissionsRepository );
} );

const mockRepoEditPermissionsErrorsRepository = {};
const mockClientEditPermissionsErrorsRepository = {};
jest.mock( '@/data-access/ApiPageEditPermissionErrorsRepository', () => {
	return jest.fn().mockImplementation( () => {} );
} );

beforeEach( () => {
	( ApiCore as jest.Mock )
		.mockImplementationOnce( () => mockRepoApiCore )
		.mockImplementationOnce( () => mockClientApiCore );
	( ApiPageEditPermissionErrorsRepository as jest.Mock )
		.mockImplementationOnce( () => mockRepoEditPermissionsErrorsRepository )
		.mockImplementationOnce( () => mockClientEditPermissionsErrorsRepository );
} );

const mockRepoRouter = {
	getPageUrl: jest.fn(),
};
jest.mock( '@/data-access/RepoRouter', () => {
	return jest.fn().mockImplementation( () => mockRepoRouter );
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
	tracker?: ( key: string, payload?: unknown ) => void;
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
	$.param = jest.fn();
	const message = options.message || jest.fn();
	const track = options.tracker || jest.fn();

	return {
		mw: {
			config: {
				get,
			},
			Api: jest.fn(),
			ForeignApi: jest.fn(),
			language,
			message,
			track,
			util: {
				wikiUrlencode: jest.fn(),
			},
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
		const specialEntityDataUrl = 'http://localhost/wiki/Special:EntityData';
		mockRepoRouter.getPageUrl.mockReturnValue( specialEntityDataUrl );
		const mwWindow = mockMwWindow( {
			wbRepo,
		} );
		const services = createServices( mwWindow, [] );

		expect( services ).toBeInstanceOf( ServiceContainer );
		expect( mwWindow.mw.config.get ).toHaveBeenCalledWith( 'wbRepo' );
		expect( SpecialPageReadingEntityRepository ).toHaveBeenCalledTimes( 1 );
		expect( SpecialPageReadingEntityRepository ).toHaveBeenCalledWith(
			mwWindow.$,
			specialEntityDataUrl,
		);
		expect( mockRepoRouter.getPageUrl ).toHaveBeenCalledWith( 'Special:EntityData' );
		expect( services.get( 'readingEntityRepository' ) ).toBe( mockReadingEntityRepository );
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
			expect( ( ApiWritingRepository as unknown as jest.Mock ).mock.calls[ 0 ][ 0 ] )
				.toBeInstanceOf( mwWindow.mw.ForeignApi );
			expect( ( ApiWritingRepository as unknown as jest.Mock ).mock.calls[ 0 ][ 1 ] )
				.toBe( wgUserName );
			expect( ( ApiWritingRepository as unknown as jest.Mock ).mock.calls[ 0 ][ 2 ] )
				.toBe( editTags );
			expect( services.get( 'writingEntityRepository' ) ).toBe( mockWritingEntityRepository );
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

			expect( services ).toBeInstanceOf( ServiceContainer );
			expect( ( ApiWritingRepository as unknown as jest.Mock ).mock.calls[ 0 ][ 2 ] )
				.toBeUndefined();
			expect( services.get( 'writingEntityRepository' ) ).toBe( mockWritingEntityRepository );
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

		expect( services ).toBeInstanceOf( ServiceContainer );
		expect( MwLanguageInfoRepository ).toHaveBeenCalledTimes( 1 );
		expect( MwLanguageInfoRepository ).toHaveBeenCalledWith( mwLanguage, ulsData );
		expect( services.get( 'languageInfoRepository' ) ).toBe( mockMwLanguageInfoRepository );
	} );

	it( 'creates ApiEntityLabelRepository', () => {
		const wgPageContentLanguage = 'de';

		const mwWindow = mockMwWindow( {
			wgPageContentLanguage,
		} );

		const services = createServices( mwWindow, [] );

		expect( services ).toBeInstanceOf( ServiceContainer );
		expect( mwWindow.mw.ForeignApi )
			.toHaveBeenCalledWith( 'http://localhost/w/api.php' );
		expect( ( ApiCore as unknown as jest.Mock ).mock.calls[ 0 ][ 0 ] )
			.toBeInstanceOf( mwWindow.mw.ForeignApi );
		expect( BatchingApi )
			.toHaveBeenCalledWith( mockRepoApiCore );

		expect(
			( ApiEntityLabelRepository as jest.Mock ).mock.calls[ 0 ][ 0 ],
		).toBe( wgPageContentLanguage );
		expect( ( ApiEntityLabelRepository as jest.Mock ).mock.calls[ 0 ][ 1 ] )
			.toBe( mockBatchingApi );
		expect( services.get( 'entityLabelRepository' ) ).toBe( mockEntityLabelRepository );
	} );

	it( 'creates ApiPropertyDataTypeRepository', () => {
		const wgPageContentLanguage = 'de';

		const mwWindow = mockMwWindow( {
			wgPageContentLanguage,
		} );

		const services = createServices( mwWindow, [] );

		expect( services ).toBeInstanceOf( ServiceContainer );

		expect( mwWindow.mw.ForeignApi )
			.toHaveBeenCalledWith( 'http://localhost/w/api.php' );
		expect( ( ApiCore as unknown as jest.Mock ).mock.calls[ 0 ][ 0 ] )
			.toBeInstanceOf( mwWindow.mw.ForeignApi );
		expect( BatchingApi )
			.toHaveBeenCalledWith( mockRepoApiCore );

		expect( ( ApiPropertyDataTypeRepository as jest.Mock ).mock.calls[ 0 ][ 0 ] )
			.toBe( mockBatchingApi );
		expect( services.get( 'propertyDatatypeRepository' ) ).toBe( mockPropertyDataTypeRepository );
	} );

	it( 'creates MessagesRepository', () => {
		const message = jest.fn();
		const mwWindow = mockMwWindow( { message } );

		const services = createServices( mwWindow, [] );

		expect( services ).toBeInstanceOf( ServiceContainer );
		expect( MwMessagesRepository ).toHaveBeenCalledTimes( 1 );
		expect( MwMessagesRepository ).toHaveBeenCalledWith( message );
		expect( services.get( 'messagesRepository' ) ).toBe( mockMessagesRepository );
	} );

	it( 'creates WikibaseRepoConfigRepository', () => {
		const wbRepo = {
			url: 'http://localhost',
			scriptPath: '/w',
			articlePath: '',
		};
		const mwWindow = mockMwWindow( {
			wbRepo,
		} );
		const services = createServices( mwWindow, [] );

		expect( mwWindow.mw.ForeignApi )
			.toHaveBeenCalledWith( 'http://localhost/w/api.php' );
		expect( ( ApiCore as unknown as jest.Mock ).mock.calls[ 0 ][ 0 ] )
			.toBeInstanceOf( mwWindow.mw.ForeignApi );
		expect( BatchingApi )
			.toHaveBeenCalledWith( mockRepoApiCore );
		expect( ApiRepoConfigRepository )
			.toHaveBeenCalledWith( mockBatchingApi );
		expect( services.get( 'wikibaseRepoConfigRepository' ) )
			.toBe( mockWikibaseRepoConfigRepository );
	} );

	it( 'creates DataBridgeTrackerService', () => {
		const tracker = jest.fn();
		const mwWindow = mockMwWindow( { tracker } );

		const services = createServices( mwWindow, [] );

		expect( services ).toBeInstanceOf( ServiceContainer );
		expect( EventTracker ).toHaveBeenCalledWith( tracker );
		expect( DataBridgeTrackerService ).toHaveBeenCalledWith( mockEventTracker );
		expect( services.get( 'tracker' ) ).toBe( mockDataBridgeTrackerService );
	} );

	it( 'creates CombiningPermissionsRepository', () => {
		const mwWindow = mockMwWindow();
		const services = createServices( mwWindow, [] );

		expect( mwWindow.mw.ForeignApi )
			.toHaveBeenCalledWith( 'http://localhost/w/api.php' );
		expect( ( ApiCore as jest.Mock ).mock.calls[ 0 ][ 0 ] )
			.toBeInstanceOf( mwWindow.mw.ForeignApi );
		expect( ( BatchingApi as jest.Mock ).mock.calls[ 0 ][ 0 ] )
			.toBe( mockRepoApiCore );
		expect( ( ApiPageEditPermissionErrorsRepository as jest.Mock ).mock.calls[ 0 ][ 0 ] )
			.toBe( mockBatchingApi );

		expect( mwWindow.mw.Api ).toHaveBeenCalledTimes( 1 );
		expect( ( ApiCore as jest.Mock ).mock.calls[ 1 ][ 0 ] )
			.toBeInstanceOf( mwWindow.mw.Api );
		expect( ( ApiPageEditPermissionErrorsRepository as jest.Mock ).mock.calls[ 1 ][ 0 ] )
			.toBe( mockClientApiCore );

		expect( CombiningPermissionsRepository ).toHaveBeenCalledTimes( 1 );
		expect( ( CombiningPermissionsRepository as jest.Mock ).mock.calls[ 0 ][ 0 ] )
			.toBe( mockRepoEditPermissionsErrorsRepository );
		expect( ( CombiningPermissionsRepository as jest.Mock ).mock.calls[ 0 ][ 1 ] )
			.toBe( mockClientEditPermissionsErrorsRepository );

		expect( services.get( 'editAuthorizationChecker' ) )
			.toBe( mockCombiningPermissionsRepository );
	} );

	it( 'creates RepoRouter', () => {
		const wbRepo = {
			scriptPath: '/w',
			articlePath: '/wiki/$1',
			url: 'http://localhost',
		};
		const mwWindow = mockMwWindow( {
			wbRepo,
		} );

		const services = createServices( mwWindow, [] );

		expect( services ).toBeInstanceOf( ServiceContainer );
		expect( RepoRouter ).toHaveBeenCalledWith( wbRepo, mwWindow.mw.util.wikiUrlencode, mwWindow.$.param );
		expect( services.get( 'repoRouter' ) ).toBe( mockRepoRouter );
	} );
} );
