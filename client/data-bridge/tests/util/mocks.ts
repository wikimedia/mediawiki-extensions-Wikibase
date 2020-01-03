import MwWindow, {
	MwApi,
	MwForeignApiConstructor,
} from '@/@types/mediawiki/MwWindow';
import MwConfig from '@/@types/mediawiki/MwConfig';
import WbRepo from '@/@types/wikibase/WbRepo';
import Api from '@/definitions/data-access/Api';

export function mockMwConfig( values: {
	hrefRegExp?: string|null;
	editTags?: string[];
	usePublish?: boolean;
	wbRepo?: WbRepo;
	wgPageContentLanguage?: string;
	wgUserName?: string;
	wgPageName?: string;
} = {} ): MwConfig {
	if ( values.hrefRegExp === undefined ) {
		values.hrefRegExp = 'https://www\\.wikidata\\.org/wiki/((Q[1-9][0-9]*)).*#(P[1-9][0-9]*)';
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
				case 'wgPageName':
					return values.wgPageName || 'Client_page';
				default:
					throw new Error( `unexpected config key ${key}` );
			}
		},
	};
}

class MockApi implements MwApi {
	public get( ..._args: any[] ): any { return jest.fn(); }

	public getEditToken( ..._args: any[] ): any { return jest.fn(); }
	public getToken( ..._args: any[] ): any { return jest.fn(); }
	public post( ..._args: any[] ): any { return jest.fn(); }

	public postWithEditToken( ..._args: any[] ): any { return jest.fn(); }

	public postWithToken( ..._args: any[] ): any { return jest.fn(); }
	public login( ..._args: any[] ): any { return jest.fn(); }
}

export function mockMwForeignApiConstructor(
	options: {
		expectedUrl?: string;
		get?: ( ...args: unknown[] ) => any;
		postWithEditToken?: ( ...args: unknown[] ) => any;
	},
): MwForeignApiConstructor {
	class MockForeignApi extends MockApi {
		public constructor( url: string, _options?: any ) {
			super();
			if ( options.expectedUrl ) {
				expect( url ).toBe( options.expectedUrl );
			}
		}
	}
	if ( options.get ) {
		MockForeignApi.prototype.get = options.get;
	}
	if ( options.postWithEditToken ) {
		MockForeignApi.prototype.postWithEditToken = options.postWithEditToken;
	}

	return MockForeignApi;
}

export function mockMwEnv(
	using: () => Promise<any> = jest.fn(),
	config: MwConfig = mockMwConfig(),
	warn: () => void = jest.fn(),
	ForeignApi: MwForeignApiConstructor = mockMwForeignApiConstructor( {} ),
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
		Api: jest.fn(),
		ForeignApi,
		language: {
			bcp47: jest.fn(),
		},
		track: jest.fn(),
	};
	( window as MwWindow ).$ = new ( jest.fn() )();
	( window as MwWindow ).$.uls = {
		data: {
			getDir: jest.fn(),
		},
	};
}

export function mockMwForeignApiGet(
	getDataBridgeConfig: Promise<object>,
	foreignApiEntityInfoResponse: Promise<object>,
) {
	return ( params: any ) => {
		if ( params.action === 'query' && (
			params.meta === 'wbdatabridgeconfig' ||
				params.meta.includes( 'wbdatabridgeconfig' )
		) ) {
			return getDataBridgeConfig;
		} else if ( params.action === 'wbgetentities' && params.ids ) {
			return foreignApiEntityInfoResponse;
		}
		throw new Error( 'Request did not match mockForeignApiGet abilities!' );
	};
}

export function mockDataBridgeConfig(): Promise<object> {
	return Promise.resolve( {
		query: {
			wbdatabridgeconfig: {
				dataTypeLimits: {
					string: {
						maxLength: 200,
					},
				},
			},
		},
	} );
}

export function mockMwForeignApiEntityInfoResponse(
	propertyId: string,
	propertyLabel = 'a property',
	language = 'en',
	dataType = 'string',
	fallbackLanguage?: string,
): Promise<object> {
	if ( !fallbackLanguage ) {
		fallbackLanguage = language;
	}
	return Promise.resolve( {
		success: 1,
		entities: {
			[ propertyId ]: {
				id: propertyId,
				datatype: dataType,
				labels: {
					[ language ]: {
						value: propertyLabel,
						language: fallbackLanguage,
						'for-language': language,
					},
				},
			},
		},
	} );
}

export function mockApi( successObject?: unknown, rejectData?: unknown ): Api & MwApi {
	return {
		get(): any {
			if ( successObject ) {
				return Promise.resolve( successObject );
			}
			if ( rejectData ) {
				return Promise.reject( rejectData );
			}
		},
	} as any;
}
