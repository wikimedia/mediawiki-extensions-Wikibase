import MwWindow, {
	ForeignApi,
	ForeignApiConstructor,
} from '@/@types/mediawiki/MwWindow';
import MwConfig from '@/@types/mediawiki/MwConfig';
import WbRepo from '@/@types/wikibase/WbRepo';

export function mockMwConfig( values: {
	hrefRegExp?: string|null;
	editTags?: string[];
	usePublish?: boolean;
	wbRepo?: WbRepo;
	wgPageContentLanguage?: string;
	wgUserName?: string;
} = {} ): MwConfig {
	if ( values.hrefRegExp === undefined ) {
		values.hrefRegExp = 'https://www\\.wikidata\\.org/wiki/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)';
	}
	return {
		get( key: string ): any {
			switch ( key ) {
				case 'wbDataBridgeConfig':
					return {
						hrefRegExp: values.hrefRegExp,
						editTags: values.editTags || [],
						usePublish: values.usePublish || false,
					};
				case 'wbRepo':
					return values.wbRepo || {
						url: 'http://localhost',
						scriptPath: '/w',
						articlePath: '/wiki/$1',
					};
				case 'wgUserName':
					return values.wgUserName || 'Test User';
				case 'wgPageContentLanguage':
					return values.wgPageContentLanguage || 'en';
				default:
					throw new Error( `unexpected config key ${key}` );
			}
		},
	};
}

export function mockForeignApiConstructor(
	options: {
		expectedUrl?: string;
		get?: ( ...args: unknown[] ) => any;
		postWithEditToken?: ( ...args: unknown[] ) => any;
	},
): ForeignApiConstructor {
	return class MockForeignApi implements ForeignApi {
		public constructor( url: string, _options?: any ) {
			if ( options.expectedUrl ) {
				expect( url ).toBe( options.expectedUrl );
			}
		}

		public get( ...args: any[] ): any {
			if ( options.get ) {
				return options.get( ...args );
			}
			return jest.fn();
		}

		public getEditToken( ..._args: any[] ): any { return jest.fn(); }
		public getToken( ..._args: any[] ): any { return jest.fn(); }
		public post( ..._args: any[] ): any { return jest.fn(); }

		public postWithEditToken( ...args: any[] ): any {
			if ( options.postWithEditToken ) {
				return options.postWithEditToken( ...args );
			}

			return jest.fn();
		}

		public postWithToken( ..._args: any[] ): any { return jest.fn(); }
		public login( ..._args: any[] ): any { return jest.fn(); }
	};
}

export function mockMwEnv(
	using: () => Promise<any> = jest.fn(),
	config: MwConfig = mockMwConfig(),
	warn: () => void = jest.fn(),
	ForeignApi: ForeignApiConstructor = mockForeignApiConstructor( {} ),
): void {
	( window as MwWindow ).mw = {
		loader: {
			using,
		},
		message: jest.fn(),
		config,
		log: {
			deprecate: jest.fn(),
			error: jest.fn(),
			warn,
		},
		ForeignApi,
		language: {
			bcp47: jest.fn(),
		},
	};
	( window as MwWindow ).$ = new ( jest.fn() )();
	( window as MwWindow ).$.uls = {
		data: {
			getDir: jest.fn(),
		},
	};
}
