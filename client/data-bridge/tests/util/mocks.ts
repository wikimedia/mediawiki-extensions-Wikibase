import MwWindow, {
	ForeignApi,
	ForeignApiConstructor,
} from '@/@types/mediawiki/MwWindow';
import MwConfig from '@/@types/mediawiki/MwConfig';
import WbRepo from '@/@types/wikibase/WbRepo';

export function mockMwConfig( values: {
	hrefRegExp?: string|null;
	wbRepo?: WbRepo;
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
					};
				case 'wbRepo':
					return values.wbRepo || {
						url: 'http://localhost',
						scriptPath: '/w',
						articlePath: '/wiki/$1',
					};
				case 'wgUserName':
					return 'Test User';
				case 'wgPageContentLanguage':
					return 'en';
				default:
					throw new Error( `unexpected config key ${key}` );
			}
		},
	};
}

export function mockForeignApiConstructor( expectedUrl?: string ): ForeignApiConstructor {
	return class MockForeignApi implements ForeignApi {
		public constructor( url: string, _options?: any ) {
			if ( expectedUrl ) {
				expect( url ).toBe( expectedUrl );
			}
		}
		public get( ..._args: any[] ): any { return jest.fn(); }
		public getEditToken( ..._args: any[] ): any { return jest.fn(); }
		public getToken( ..._args: any[] ): any { return jest.fn(); }
		public post( ..._args: any[] ): any { return jest.fn(); }
		public postWithEditToken( ..._args: any[] ): any { return jest.fn(); }
		public postWithToken( ..._args: any[] ): any { return jest.fn(); }
		public login( ..._args: any[] ): any { return jest.fn(); }
	};
}

export function mockMwEnv(
	using: () => Promise<any> = jest.fn(),
	config: MwConfig = mockMwConfig(),
	warn: () => void = jest.fn(),
	ForeignApi: ForeignApiConstructor = mockForeignApiConstructor(),
): void {
	( window as MwWindow ).mw = {
		loader: {
			using,
		},
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
