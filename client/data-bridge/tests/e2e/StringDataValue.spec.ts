import Vue from 'vue';
import EditFlow from '@/definitions/EditFlow';
import init from '@/mediawiki/init';
import { launch } from '@/main';
import MwWindow from '@/@types/mediawiki/MwWindow';
import {
	mockForeignApiConstructor,
	mockMwConfig,
	mockMwEnv,
} from '../util/mocks';
import { budge } from '../util/timer';
import {
	select,
	insert,
} from '../util/e2e';
import Entities from '@/mock-data/data/Q42.data.json';

Vue.config.devtools = false;

const manager = {
	on: jest.fn(),
};
const dialog = {
	getManager: jest.fn( () => manager ),
};

const mockPrepareContainer = jest.fn( ( _x?: any, _y?: any, _z?: any ) => dialog );

jest.mock( '@/mediawiki/prepareContainer', () => ( {
	__esModule: true,
	default: ( oo: any, $: any ) => mockPrepareContainer( oo, $ ),
} ) );

function prepareTestEnv( options: {
	entityId?: string;
	propertyId?: string;
	editFlow?: string;
} ): HTMLElement|null {
	const entityId = options.entityId || 'Q42';
	const propertyId = options.propertyId || 'P349';
	const editFlow = options.editFlow || EditFlow.OVERWRITE;

	const testLinkHref = `https://www.wikidata.org/wiki/${entityId}?uselang=en#${propertyId}`;
	document.body.innerHTML = `
<span data-bridge-edit-flow="${editFlow}">
	<a rel="nofollow" class="external text" href="${testLinkHref}">a link to be selected</a>
</span>
<div id="data-bridge-container"/>`;
	return document.querySelector( 'a' );
}

function mockPropertyLabelResponse(
	propertyId: string,
	propertyLabel: string,
	language: string,
	fallback?: string,
): any {

	if ( !fallback ) {
		fallback = language;
	}
	return {
		success: 1,
		entities: {
			[ propertyId ]: {
				id: propertyId,
				labels: {
					[ language ]: {
						value: propertyLabel,
						language: fallback,
						'for-language': language,
					},
				},
			},
		},
	};
}
const queryDataBridgeConfigResponse = {
	query: {
		wbdatabridgeconfig: {
			dataTypeLimits: {
				string: {
					maxLength: 200,
				},
			},
		},
	},
};

describe( 'string data value', () => {
	const pageLanguage = 'en';
	let app: any;
	let require: any;
	let using;

	beforeEach( () => {
		app = { launch };
		require = jest.fn( () => app );
		using = jest.fn( () => new Promise( ( resolve ) => resolve( require ) ) );

		mockMwEnv(
			using,
			mockMwConfig( { wgPageContentLanguage: pageLanguage } ),
			undefined,
			mockForeignApiConstructor( {
				get() {
					return Promise.resolve( queryDataBridgeConfigResponse );
				},
			} ),
		);
		( window as MwWindow ).$ = {
			get() {
				return Promise.resolve( JSON.parse( JSON.stringify( Entities ) ) );
			},
			uls: {
				data: {
					getDir: jest.fn( () => 'ltr' ),
				},
			},
		} as any;
		( window as MwWindow ).mw.message = jest.fn( ( key: string ) => {
			return {
				text: () => `<${key}>`,
			};
		} );
		( window as MwWindow ).mw.language = {
			bcp47: jest.fn( ( x: string ) => x ),
		};
	} );

	it( 'handels string data value types', async () => {
		const testLink = prepareTestEnv( {} );
		await init();

		testLink!.click();
		expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );

		expect( select( '.wb-db-app' ) ).not.toBeNull();
		await budge();
		expect( select( '.wb-db-app .wb-db-bridge .wb-db-stringValue' ) ).not.toBeNull();
		expect( select( '.wb-db-app .wb-ui-processdialog-header' ) ).not.toBeNull();
		expect(
			select( '.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--primaryProgressive' ),
		).not.toBeNull();
	} );

	describe( 'label fallback', () => {
		it( 'uses property label', async () => {
			const propertyId = 'P349';
			const propertyLabel = 'Queen';

			const get = jest.fn( () => ( { ...mockPropertyLabelResponse(
				propertyId,
				propertyLabel,
				pageLanguage,
			), ...queryDataBridgeConfigResponse } ) );

			( window as MwWindow ).mw.ForeignApi = mockForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get,
			} );

			const testLink = prepareTestEnv( { propertyId } );

			await init();
			testLink!.click();
			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
			await budge();

			const label = select( '.wb-db-app .wb-db-stringValue .wb-db-PropertyLabel' );

			expect( label ).not.toBeNull();
			expect( ( label as HTMLElement ).tagName.toLowerCase() ).toBe( 'label' );
			expect( ( label as HTMLElement ).getAttribute( 'lang' ) ).toBe( pageLanguage );
			expect( ( label as HTMLElement ).textContent ).toBe( propertyLabel );
			expect( get ).toHaveBeenCalledWith( {
				action: 'wbgetentities',
				ids: propertyId,
				languagefallback: 1,
				languages: pageLanguage,
				props: 'labels',
			} );
		} );

		it( 'ueses labels fallback language', async () => {
			const propertyId = 'P349';
			const propertyLabel = 'Jochen';
			const language = 'de';

			const get = jest.fn( () => ( { ...mockPropertyLabelResponse(
				propertyId,
				propertyLabel,
				pageLanguage,
				language,
			), ...queryDataBridgeConfigResponse } ) );

			( window as MwWindow ).mw.ForeignApi = mockForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get,
			} );

			const testLink = prepareTestEnv( { propertyId } );

			await init();
			testLink!.click();
			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
			await budge();

			const label = select( '.wb-db-app .wb-db-stringValue .wb-db-PropertyLabel' );

			expect( label ).not.toBeNull();
			expect( ( label as HTMLElement ).tagName.toLowerCase() ).toBe( 'label' );
			expect( ( label as HTMLElement ).getAttribute( 'lang' ) ).toBe( language );
			expect( ( label as HTMLElement ).textContent ).toBe( propertyLabel );
			expect( get ).toHaveBeenCalledWith( {
				action: 'wbgetentities',
				ids: propertyId,
				languagefallback: 1,
				languages: pageLanguage,
				props: 'labels',
			} );
		} );

		it( 'falls back to the property id, if the api call fails', async () => {
			const propertyId = 'P349';
			const testLink = prepareTestEnv( { propertyId } );

			const get = jest.fn( () => ( {
				// no entities in the response
				...queryDataBridgeConfigResponse } ) );
			( window as MwWindow ).mw.ForeignApi = mockForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get,
			} );

			await init();
			testLink!.click();
			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
			await budge();

			const label = select( '.wb-db-app .wb-db-stringValue .wb-db-PropertyLabel' );
			expect( label ).not.toBeNull();
			expect( ( label as HTMLElement ).tagName.toLowerCase() ).toBe( 'label' );
			expect( ( label as HTMLElement ).textContent ).toBe( propertyId );
			expect( ( label as HTMLElement ).getAttribute( 'lang' ) ).toBe( 'zxx' );
			expect( get ).toHaveBeenCalledWith( {
				action: 'wbgetentities',
				ids: propertyId,
				languagefallback: 1,
				languages: pageLanguage,
				props: 'labels',
			} );
		} );
	} );

	describe( 'language utils', () => {
		it( 'determines the directionality of the given language', async () => {
			const propertyId = 'P349';
			const propertyLabel = 'רתֵּסְאֶ';
			const language = 'he';

			( window as MwWindow ).mw.ForeignApi = mockForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get: jest.fn( () => ( { ...mockPropertyLabelResponse(
					propertyId,
					propertyLabel,
					pageLanguage,
					language,
				), ...queryDataBridgeConfigResponse } ) ),
			} );

			( window as MwWindow ).$.uls!.data.getDir = jest.fn( ( x: string ) => {
				return x === 'he' ? 'rtl' : 'ltr';
			} );

			const testLink = prepareTestEnv( { propertyId } );

			await init();
			testLink!.click();
			await budge();

			const label = select( '.wb-db-app .wb-db-stringValue .wb-db-PropertyLabel' );

			expect( label ).not.toBeNull();
			expect( ( label as HTMLElement ).getAttribute( 'dir' ) ).toBe( 'rtl' );
			expect( ( window as MwWindow ).$.uls!.data.getDir ).toHaveBeenCalledWith( language );
		} );

		it( 'standardized language code', async () => {
			const propertyId = 'P349';
			const propertyLabel = 'Jochen';
			const language = 'de-formal';

			( window as MwWindow ).mw.ForeignApi = mockForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get: jest.fn( () => ( { ...mockPropertyLabelResponse(
					propertyId,
					propertyLabel,
					pageLanguage,
					language,
				), ...queryDataBridgeConfigResponse } ) ),
			} );

			( window as MwWindow ).mw.language = {
				bcp47: jest.fn( ( x: string ) => {
					return x === 'de-formal' ? 'de' : 'en';
				} ),
			};

			const testLink = prepareTestEnv( { propertyId } );

			await init();
			testLink!.click();
			await budge();

			const label = select( '.wb-db-app .wb-db-stringValue .wb-db-PropertyLabel' );

			expect( label ).not.toBeNull();
			expect( ( label as HTMLElement ).tagName.toLowerCase() ).toBe( 'label' );
			expect( ( label as HTMLElement ).getAttribute( 'lang' ) ).toBe( 'de' );
			expect( ( window as MwWindow ).mw.language.bcp47 ).toHaveBeenCalledWith( language );
		} );
	} );

	describe( 'input', () => {
		it( 'has a input field', async () => {
			const testLink = prepareTestEnv( {} );

			await init();
			testLink!.click();
			await budge();

			const input = select( '.wb-db-app .wb-db-stringValue__input' );

			expect( input ).not.toBeNull();
			expect( ( input as HTMLElement ).tagName.toLowerCase() ).toBe( 'textarea' );
		} );

		it( 'can alter its value', async () => {
			const testNewValue = 'test1234';
			const testLink = prepareTestEnv( {} );

			await init();
			testLink!.click();
			await budge();

			const input = select( '.wb-db-app .wb-db-stringValue .wb-db-stringValue__input' );

			expect( input ).not.toBeNull();
			expect( ( input as HTMLElement ).tagName.toLowerCase() ).toBe( 'textarea' );

			await insert( input as HTMLTextAreaElement, testNewValue );
			expect( ( input as HTMLTextAreaElement ).value ).toBe( testNewValue );
		} );

		describe( 'influence on save button', () => {
			it( 'enables the save button, if it has a different value then the original value', async () => {
				const testNewValue = 'test1234';
				const testLink = prepareTestEnv( {} );
				await init();

				testLink!.click();
				await budge();

				let save = select( '.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--disabled' );
				expect( save ).not.toBeNull();

				const input = select( '.wb-db-app .wb-db-stringValue .wb-db-stringValue__input' );

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

				let save = select( '.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--disabled' );
				expect( save ).not.toBeNull();

				const input = select( '.wb-db-app .wb-db-stringValue .wb-db-stringValue__input' );

				expect( input ).not.toBeNull();
				expect( ( input as HTMLElement ).tagName.toLowerCase() ).toBe( 'textarea' );

				await insert( input as HTMLTextAreaElement, testNewValue );
				expect( ( input as HTMLTextAreaElement ).value ).toBe( testNewValue );

				save = select( '.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--disabled' );
				expect( save ).toBeNull();

				await insert(
					input as HTMLTextAreaElement,
					Entities.entities.Q42.claims.P349[ 0 ].mainsnak.datavalue.value,
				);

				expect( ( input as HTMLTextAreaElement ).value ).toBe(
					Entities.entities.Q42.claims.P349[ 0 ].mainsnak.datavalue.value,
				);
				save = select( '.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--disabled' );
				expect( input ).not.toBeNull();
			} );
		} );

		it( 'propagates the max length to the input field', async () => {
			const maxLength = 666;
			const queryDataBridgeConfigResponse = {
				query: {
					wbdatabridgeconfig: {
						dataTypeLimits: {
							string: {
								maxLength,
							},
						},
					},
				},
			};

			( window as MwWindow ).mw.ForeignApi = mockForeignApiConstructor( {
				get() {
					return Promise.resolve( queryDataBridgeConfigResponse );
				},
			} );

			const testLink = prepareTestEnv( {} );

			await init();
			testLink!.click();
			await budge();

			const input = select( '.wb-db-app .wb-db-stringValue .wb-db-stringValue__input' );

			expect( input ).not.toBeNull();
			expect( ( input as HTMLTextAreaElement ).maxLength ).toBe( maxLength );
		} );
	} );
} );
