import {
	MwApi,
	MwApiConstructor,
	MwForeignApiConstructor,
} from '@/@types/mediawiki/MwWindow';
import MwConfig from '@/@types/mediawiki/MwConfig';
import WbRepo from '@/@types/wikibase/WbRepo';
import { getApiQueryResponsePage } from '@/data-access/ApiQuery';
import Api from '@/definitions/data-access/Api';
import {
	ApiQueryInfoTestResponsePage,
	ApiQueryResponseBody,
	ApiQueryResponsePage,
} from '@/definitions/data-access/ApiQuery';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import clone from '@/store/clone';

export function mockMwConfig( values: {
	hrefRegExp?: string|null;
	editTags?: readonly string[];
	usePublish?: boolean;
	issueReportingLink?: string;
	wbRepo?: WbRepo;
	wgPageContentLanguage?: string;
	wgPageName?: string;
	wgUserName?: string|null;
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
						issueReportingLink: values.issueReportingLink || 'https://bugs.example/new?body=<body>',
					};
				case 'wbRepo':
					return values.wbRepo || {
						url: 'http://localhost',
						scriptPath: '/w',
						articlePath: '/wiki/$1',
					};
				case 'wgPageContentLanguage':
					return values.wgPageContentLanguage || 'en';
				case 'wgPageName':
					return values.wgPageName || 'Client_page';
				case 'wgUserName':
					return values.wgUserName === undefined ? 'Test user' : values.wgUserName;
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

	public assertCurrentUser( ..._args: any[] ): any { return jest.fn(); }
}

export function mockMwApiConstructor(
	options: {
		get?: ( ...args: unknown[] ) => any;
		post?: ( ...args: unknown[] ) => any;
	},
): MwApiConstructor {
	class MockMwApi extends MockApi {
		public constructor( _options?: any ) {
			super();
		}
	}
	if ( options.get ) {
		MockMwApi.prototype.get = options.get;
	}
	if ( options.post ) {
		MockMwApi.prototype.post = options.post;
	}

	return MockMwApi;
}

export function mockMwForeignApiConstructor(
	options: {
		expectedUrl?: string;
		get?: ( ...args: unknown[] ) => any;
		postWithEditToken?: ( ...args: unknown[] ) => any;
		assertCurrentUser?: ( ...args: unknown[] ) => any;
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
	if ( options.assertCurrentUser ) {
		MockForeignApi.prototype.assertCurrentUser = options.assertCurrentUser;
	}

	return MockForeignApi;
}

export function mockMwEnv(
	using: () => Promise<any> = jest.fn(),
	config: MwConfig = mockMwConfig(),
	warn: () => void = jest.fn(),
	ForeignApi: MwForeignApiConstructor = mockMwForeignApiConstructor( {} ),
	Api: MwApiConstructor = mockMwApiConstructor( {} ),
): void {
	window.mw = {
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
		Api,
		ForeignApi,
		language: {
			bcp47: jest.fn(),
		},
		track: jest.fn(),
		util: {
			getUrl: jest.fn( ( title, _params ) => `https://wiki.example/wiki/${title}` ),
			wikiUrlencode: jest.fn( ( title ) => title ),
		},
	};
	window.$ = new ( jest.fn() )();
	window.$.uls = {
		data: {
			getDir: jest.fn(),
		},
	};
}

export function getOrCreateApiQueryResponsePage( response: ApiQueryResponseBody, title: string ): ApiQueryResponsePage {
	let page = getApiQueryResponsePage( response, title );
	if ( page === null ) {
		page = { title };
		response.pages = [ ...response.pages || [], page ];
	}
	return page;
}

export function getMockBridgeRepoConfig(
	dataBridgeConfig: Partial<WikibaseRepoConfiguration> = {},
): WikibaseRepoConfiguration {
	return {
		dataTypeLimits: {
			string: { maxLength: 123 },
		},
		dataRightsUrl: 'https://creativecommons.org/publicdomain/zero/1.0/',
		dataRightsText: 'Creative Commons CC0',
		termsOfUseUrl: 'https://foundation.wikimedia.org/wiki/Terms_of_Use',
		...dataBridgeConfig,
	};
}

export function addDataBridgeConfigResponse(
	dataBridgeConfig: Partial<WikibaseRepoConfiguration>,
	response: { query?: object },
): object {
	const query: { wbdatabridgeconfig?: object } = response.query || ( response.query = {} );
	query.wbdatabridgeconfig = getMockBridgeRepoConfig( dataBridgeConfig );
	return response;
}

export function addPageInfoNoEditRestrictionsResponse( title: string, response: { query?: object } ): object {
	const query: ApiQueryResponseBody = response.query || ( response.query = {} ),
		page = getOrCreateApiQueryResponsePage( query, title );
	( page as ApiQueryInfoTestResponsePage ).actions = { edit: [] };
	return response;
}

export function addSiteinfoRestrictionsResponse( response: { query?: object } ): object {
	const query: { restrictions?: object } = response.query || ( response.query = {} );
	query.restrictions = { semiprotectedlevels: [ 'autoconfirmed' ] };
	return response;
}

export function addReferenceRenderingResponse( response: Record<string, object> ): object {
	response.wbformatreference = {
		html: '<span>ref1</span>',
	};
	return response;
}

export function addPropertyLabelResponse(
	data: {
		propertyId: string;
		propertyLabel?: string;
		language?: string;
		dataType?: string;
		fallbackLanguage?: string;
	},
	response: { entities?: { [ id: string ]: object } },
): object {
	const propertyId = data.propertyId,
		propertyLabel = data.propertyLabel || 'a property',
		language = data.language || 'en',
		dataType = data.dataType || 'string',
		fallbackLanguage = data.fallbackLanguage || language;
	const entities: { [ id: string ]: any } = response.entities || ( response.entities = {} ),
		entity = entities[ propertyId ] || ( entities[ propertyId ] = { id: propertyId } );
	entity.datatype = dataType;
	const labels: { [ language: string ]: object } = entity.labels || ( entity.labels = {} );
	labels[ language ] = {
		value: propertyLabel,
		language: fallbackLanguage,
		'for-language': language,
	};
	return response;
}

export function addEntityDataResponse(
	data: { entities: { [ id: string ]: object } },
	response: { entities?: { [ id: string ]: object } },
): object {
	const entities: { [ id: string ]: any } = response.entities || ( response.entities = {} );
	for ( const [ id, entity ] of Object.entries( data.entities ) ) {
		if ( id in entities ) {
			throw new Error( 'Merging entity data is currently not supported' );
		}
		entities[ id ] = clone( entity );
	}
	return response;
}

export function getMockFullRepoBatchedQueryResponse(
	propertyLabelResponseInput: {
		propertyId: string;
		propertyLabel?: string;
		language?: string;
		dataType?: string;
		fallbackLanguage?: string;
	},
	entityTitle: string,
	entities: { entities: { [ id: string ]: object } },
	dataBridgeConfig: Partial<WikibaseRepoConfiguration> = {},
): jest.Mock {
	return jest.fn().mockResolvedValue(
		addEntityDataResponse(
			entities,
			addPropertyLabelResponse(
				propertyLabelResponseInput,
				addPageInfoNoEditRestrictionsResponse(
					entityTitle,
					addSiteinfoRestrictionsResponse(
						addDataBridgeConfigResponse(
							dataBridgeConfig,
							{},
						),
					),
				),
			),
		),
	);
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
		post(): any {
			return Promise.resolve();
		},
		postWithEditToken(): any {
			if ( successObject ) {
				return Promise.resolve( successObject );
			}
			if ( rejectData ) {
				return Promise.reject( rejectData );
			}
		},
		postWithEditTokenAndAssertUser(): any {
			if ( successObject ) {
				return Promise.resolve( successObject );
			}
			if ( rejectData ) {
				return Promise.reject( rejectData );
			}
		},
		assertCurrentUser( params: any ): any {
			return params;
		},
	} as any;
}
