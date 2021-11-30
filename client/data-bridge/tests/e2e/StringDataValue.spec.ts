import EditFlow from '@/definitions/EditFlow';
import init from '@/mediawiki/init';
import { launch } from '@/main';
import createServices from '@/services/createServices';
import {
	addDataBridgeConfigResponse,
	addPageInfoNoEditRestrictionsResponse,
	addReferenceRenderingResponse,
	addSiteinfoRestrictionsResponse,
	getMockFullRepoBatchedQueryResponse,
	mockMwForeignApiConstructor,
	mockMwConfig,
	mockMwEnv,
	mockMwApiConstructor,
	addEntityDataResponse,
} from '../util/mocks';
import { budge } from '../util/timer';
import {
	select,
	insert,
	selectRadioInput,
} from '../util/e2e';
import Entities from '@/mock-data/data/Q42.data.json';
import { createApp } from 'vue';

const manager = {
	on: jest.fn(),
};
const dialog = {
	getManager: jest.fn().mockReturnValue( manager ),
};

const mockPrepareContainer = jest.fn( ( _x?: any, _y?: any, _z?: any ) => dialog );

jest.mock( '@/mediawiki/prepareContainer', () => ( {
	__esModule: true,
	default: ( oo: any, $: any ) => mockPrepareContainer( oo, $ ),
} ) );

const DEFAULT_ENTITY = 'Q42';
const DEFAULT_PROPERTY = 'P373';

function prepareTestEnv( options: {
	entityId?: string;
	propertyId?: string;
	editFlow?: string;
	pageLanguage?: string;
} ): HTMLElement {
	const entityId = options.entityId || DEFAULT_ENTITY;
	const entityTitle = entityId;
	const propertyId = options.propertyId || DEFAULT_PROPERTY;
	const editFlow = options.editFlow || EditFlow.SINGLE_BEST_VALUE;
	const pageLanguage = options.pageLanguage || 'en';
	const clientPageTitle = 'Client_page';

	const app = { launch, createServices };
	const require = jest.fn().mockResolvedValueOnce( app );
	const vue = { createMwApp: createApp };
	require.mockReturnValueOnce( vue );
	const using = jest.fn().mockResolvedValue( require );

	mockMwEnv(
		using,
		mockMwConfig( { wgPageContentLanguage: pageLanguage, wgPageName: clientPageTitle } ),
		undefined,
		mockMwForeignApiConstructor( {
			get: getMockFullRepoBatchedQueryResponse(
				{ propertyId, language: pageLanguage },
				entityTitle,
				Entities,
			),
		} ),
		mockMwApiConstructor( {
			get: jest.fn().mockResolvedValue(
				addPageInfoNoEditRestrictionsResponse(
					clientPageTitle,
					addSiteinfoRestrictionsResponse(
						addReferenceRenderingResponse( {} ),
					),
				),
			),
		} ),
	);
	window.$ = {
		get() {
			return Promise.resolve( JSON.parse( JSON.stringify( Entities ) ) );
		},
		uls: {
			data: {
				getDir: jest.fn().mockReturnValue( 'ltr' ),
			},
		},
	} as any;
	window.mw.message = jest.fn( ( key: string, ..._params: readonly ( string|HTMLElement )[] ) => {
		return {
			text: () => `⧼${key}⧽`,
			parse: () => `⧼${key}⧽`,
		};
	} );
	window.mw.language = {
		bcp47: jest.fn( ( x: string ) => x ),
	};

	const testLinkHref = `https://www.wikidata.org/wiki/${entityTitle}?uselang=en#${propertyId}`;
	document.body.innerHTML = `
<span data-bridge-edit-flow="${editFlow}">
	<a rel="nofollow" class="external text" href="${testLinkHref}">a link to be selected</a>
</span>
<div id="data-bridge-container"/>`;
	return document.querySelector( 'a' ) as HTMLElement;
}

describe( 'string data value', () => {
	const pageLanguage = 'en';

	it( 'handles string data value types', async () => {
		const testLink = prepareTestEnv( {} );
		await init();

		testLink!.click();
		await budge();

		expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
		expect( select( '.wb-db-app' ) ).not.toBeNull();
		expect( select( '.wb-db-app .wb-db-bridge .wb-db-string-value' ) ).not.toBeNull();
		expect( select( '.wb-db-app .wb-ui-processdialog-header' ) ).not.toBeNull();
		expect(
			select( '.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--primaryProgressive' ),
		).not.toBeNull();
	} );

	describe( 'property label', () => {
		it( 'is used to illustrate property', async () => {
			const propertyId = 'P373';
			const propertyLabel = 'Queen';

			const testLink = prepareTestEnv( { propertyId } );

			const get = getMockFullRepoBatchedQueryResponse(
				{ propertyId, propertyLabel, language: pageLanguage },
				DEFAULT_ENTITY,
				Entities,
			);

			window.mw.ForeignApi = mockMwForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get,
			} );

			await init();
			testLink!.click();
			await budge();

			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );

			const label = select( '.wb-db-app .wb-db-string-value .wb-db-property-label' );
			const text = select( '.wb-db-app .wb-db-string-value .wb-db-property-label .wb-db-term-label' );

			expect( label ).not.toBeNull();
			expect( text ).not.toBeNull();
			expect( ( label as HTMLElement ).tagName.toLowerCase() ).toBe( 'label' );
			expect( ( text as HTMLElement ).getAttribute( 'lang' ) ).toBe( pageLanguage );
			expect( ( text as HTMLElement ).textContent ).toBe( propertyLabel );
			expect( get ).toHaveBeenCalledWith( {
				action: 'wbgetentities',
				ids: [ propertyId, DEFAULT_ENTITY ],
				languagefallback: true,
				languages: [ pageLanguage ],
				props: [ 'labels', 'datatype', 'info', 'claims' ],
				errorformat: 'raw',
				formatversion: '2',
			} );
		} );

		it( 'optionally uses label in fallback language', async () => {
			const propertyId = 'P373';
			const propertyLabel = 'Jochen';
			const language = 'de';

			const testLink = prepareTestEnv( { propertyId } );

			const get = getMockFullRepoBatchedQueryResponse(
				{
					propertyId,
					propertyLabel,
					language: pageLanguage,
					fallbackLanguage: language,
				},
				DEFAULT_ENTITY,
				Entities,
			);
			window.mw.ForeignApi = mockMwForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get,
			} );

			await init();
			testLink!.click();
			await budge();

			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );

			const label = select( '.wb-db-app .wb-db-string-value .wb-db-property-label' );
			const text = select( '.wb-db-app .wb-db-string-value .wb-db-property-label .wb-db-term-label' );

			expect( label ).not.toBeNull();
			expect( text ).not.toBeNull();
			expect( ( label as HTMLElement ).tagName.toLowerCase() ).toBe( 'label' );
			expect( ( text as HTMLElement ).getAttribute( 'lang' ) ).toBe( language );
			expect( ( text as HTMLElement ).textContent ).toBe( propertyLabel );
			expect( get ).toHaveBeenCalledWith( {
				action: 'wbgetentities',
				ids: [ propertyId, DEFAULT_ENTITY ],
				languagefallback: true,
				languages: [ pageLanguage ],
				props: [ 'labels', 'datatype', 'info', 'claims' ],
				errorformat: 'raw',
				formatversion: '2',
			} );
		} );

		it( 'falls back to the property id, if the api call fails', async () => {
			const propertyId = 'P373';
			const testLink = prepareTestEnv( { propertyId } );

			const get = jest.fn().mockResolvedValue(
				addEntityDataResponse(
					Entities,
					// no addPropertyLabelResponse( … )
					addPageInfoNoEditRestrictionsResponse(
						DEFAULT_ENTITY,
						addSiteinfoRestrictionsResponse(
							addDataBridgeConfigResponse( {}, {
								entities: {
									[ propertyId ]: {
										id: propertyId,
										datatype: 'string',
										labels: {},
									},
								},
							} as any ),
						),
					),
				),
			);

			window.mw.ForeignApi = mockMwForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get,
			} );

			await init();
			testLink!.click();
			await budge();

			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );

			const label = select( '.wb-db-app .wb-db-string-value .wb-db-property-label' );
			const text = select( '.wb-db-app .wb-db-string-value .wb-db-property-label .wb-db-term-label' );

			expect( label ).not.toBeNull();
			expect( text ).not.toBeNull();
			expect( ( label as HTMLElement ).tagName.toLowerCase() ).toBe( 'label' );
			expect( ( text as HTMLElement ).textContent ).toBe( propertyId );
			expect( ( text as HTMLElement ).getAttribute( 'lang' ) ).toBe( 'zxx' );
			expect( get ).toHaveBeenCalledWith( {
				action: 'wbgetentities',
				ids: [ propertyId, DEFAULT_ENTITY ],
				languagefallback: true,
				languages: [ pageLanguage ],
				props: [ 'labels', 'datatype', 'info', 'claims' ],
				errorformat: 'raw',
				formatversion: '2',
			} );
		} );
	} );

	describe( 'language utils', () => {
		it( 'determines the directionality of the given language', async () => {
			const propertyId = 'P373';
			const propertyLabel = 'רתֵּסְאֶ';
			const language = 'he';

			const testLink = prepareTestEnv( { propertyId } );

			window.mw.ForeignApi = mockMwForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get: getMockFullRepoBatchedQueryResponse(
					{
						propertyId,
						propertyLabel,
						language: pageLanguage,
						fallbackLanguage: language,
					},
					DEFAULT_ENTITY,
					Entities,
				),
			} );

			window.$.uls!.data.getDir = jest.fn( ( x: string ) => {
				return x === 'he' ? 'rtl' : 'ltr';
			} );

			await init();
			testLink!.click();
			await budge();

			const label = select( '.wb-db-app .wb-db-string-value .wb-db-property-label' );
			const text = select( '.wb-db-app .wb-db-string-value .wb-db-property-label .wb-db-term-label' );

			expect( label ).not.toBeNull();
			expect( text ).not.toBeNull();
			expect( ( text as HTMLElement ).getAttribute( 'dir' ) ).toBe( 'rtl' );
			expect( window.$.uls!.data.getDir ).toHaveBeenCalledWith( language );
		} );

		it( 'standardized language code', async () => {
			const propertyId = 'P373';
			const propertyLabel = 'Jochen';
			const language = 'de-formal';

			const testLink = prepareTestEnv( { propertyId } );

			window.mw.ForeignApi = mockMwForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get: getMockFullRepoBatchedQueryResponse(
					{
						propertyId,
						propertyLabel,
						language: pageLanguage,
						fallbackLanguage: language,
					},
					DEFAULT_ENTITY,
					Entities,
				),
			} );

			window.mw.language = {
				bcp47: jest.fn( ( x: string ) => {
					return x === 'de-formal' ? 'de' : 'en';
				} ),
			};

			await init();
			testLink!.click();
			await budge();

			const label = select( '.wb-db-app .wb-db-string-value .wb-db-property-label' );
			const text = select( '.wb-db-app .wb-db-string-value .wb-db-property-label .wb-db-term-label' );

			expect( label ).not.toBeNull();
			expect( text ).not.toBeNull();
			expect( ( label as HTMLElement ).tagName.toLowerCase() ).toBe( 'label' );
			expect( ( text as HTMLElement ).getAttribute( 'lang' ) ).toBe( 'de' );
			expect( window.mw.language.bcp47 ).toHaveBeenCalledWith( language );
		} );
	} );

	describe( 'input', () => {
		it( 'has a input field', async () => {
			const testLink = prepareTestEnv( {} );

			await init();
			testLink!.click();
			await budge();

			const input = select( '.wb-db-app .wb-db-string-value__input' );

			expect( input ).not.toBeNull();
			expect( ( input as HTMLElement ).tagName.toLowerCase() ).toBe( 'textarea' );
		} );

		it( 'can alter its value', async () => {
			const testNewValue = 'test1234';
			const testLink = prepareTestEnv( {} );

			await init();
			testLink!.click();
			await budge();

			const input = select( '.wb-db-app .wb-db-string-value .wb-db-string-value__input' );

			expect( input ).not.toBeNull();
			expect( ( input as HTMLElement ).tagName.toLowerCase() ).toBe( 'textarea' );

			await insert( input as HTMLTextAreaElement, testNewValue );
			expect( ( input as HTMLTextAreaElement ).value ).toBe( testNewValue );
		} );

		describe( 'influence on save button', () => {
			it( 'enables the save button, if it has a different value than the original value', async () => {
				const testNewValue = 'test1234';
				const testLink = prepareTestEnv( {} );
				await init();

				testLink!.click();
				await budge();

				const replaceInputDecision = select( '.wb-db-app input[name=editDecision][value=replace]' );
				await selectRadioInput( replaceInputDecision as HTMLInputElement );

				let save = select( '.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--disabled' );
				expect( save ).not.toBeNull();

				const input = select( '.wb-db-app .wb-db-string-value .wb-db-string-value__input' );

				expect( input ).not.toBeNull();
				expect( ( input as HTMLElement ).tagName.toLowerCase() ).toBe( 'textarea' );

				await insert( input as HTMLTextAreaElement, testNewValue );
				expect( ( input as HTMLTextAreaElement ).value ).toBe( testNewValue );

				save = select( '.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--disabled' );
				expect( save ).toBeNull();
			} );

			it( 'disables the save button, if it has the same value like the original value', async () => {
				const testNewValue = 'test1234';
				const testLink = prepareTestEnv( {} );
				await init();

				testLink!.click();
				await budge();

				const replaceInputDecision = select( '.wb-db-app input[name=editDecision][value=replace]' );
				await selectRadioInput( replaceInputDecision as HTMLInputElement );

				let save = select( '.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--disabled' );
				expect( save ).not.toBeNull();

				const input = select( '.wb-db-app .wb-db-string-value .wb-db-string-value__input' );

				expect( input ).not.toBeNull();
				expect( ( input as HTMLElement ).tagName.toLowerCase() ).toBe( 'textarea' );

				await insert( input as HTMLTextAreaElement, testNewValue );
				expect( ( input as HTMLTextAreaElement ).value ).toBe( testNewValue );

				save = select( '.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--disabled' );
				expect( save ).toBeNull();

				await insert(
					input as HTMLTextAreaElement,
					Entities.entities[ DEFAULT_ENTITY ].claims[ DEFAULT_PROPERTY ][ 0 ].mainsnak.datavalue.value,
				);

				expect( ( input as HTMLTextAreaElement ).value ).toBe(
					Entities.entities[ DEFAULT_ENTITY ].claims[ DEFAULT_PROPERTY ][ 0 ].mainsnak.datavalue.value,
				);
				save = select( '.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--disabled' );
				expect( input ).not.toBeNull();
			} );
		} );

		it( 'propagates the max length to the input field', async () => {
			const maxLength = 666;
			const testLink = prepareTestEnv( {} );

			window.mw.ForeignApi = mockMwForeignApiConstructor( {
				get: getMockFullRepoBatchedQueryResponse(
					{ propertyId: DEFAULT_PROPERTY },
					DEFAULT_ENTITY,
					Entities,
					{
						dataTypeLimits: {
							string: {
								maxLength,
							},
						},
					},
				),
			} );

			await init();
			testLink!.click();
			await budge();

			const input = select( '.wb-db-app .wb-db-string-value .wb-db-string-value__input' );

			expect( input ).not.toBeNull();
			expect( ( input as HTMLTextAreaElement ).maxLength ).toBe( maxLength );
		} );
	} );
} );
