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

const manager = jest.fn();
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

describe( 'init', () => {
	let app: any;
	let require: any;
	let using;

	beforeEach( () => {
		app = { launch };
		require = jest.fn( () => app );
		using = jest.fn( () => new Promise( ( resolve ) => resolve( require ) ) );

		mockMwEnv( using, mockMwConfig( { wgPageContentLanguage: 'de' } ) );
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
			bcp47: jest.fn( () => 'de' ),
		};
	} );

	it( 'has a input and a label', async () => {
		const propertyId = 'P349';
		const propertyLabel = 'Jochen';
		const language = 'de';
		const dir = 'ltr';
		const testLink = prepareTestEnv( { propertyId } );

		( window as MwWindow ).mw.ForeignApi = mockForeignApiConstructor( {
			expectedUrl: 'http://localhost/w/api.php',
			get: jest.fn( () => {
				return Promise.resolve( {
					success: 1,
					entities: {
						[ propertyId ]: {
							id: propertyId,
							labels: {
								[ language ]: {
									value: propertyLabel,
									language,
								},
							},
						},
					},
				} );
			} ),
		} );

		await init();
		testLink!.click();
		expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
		await budge();

		const input = select( '.wb-db-app .wb-db-stringValue__input' );
		const label = select( '.wb-db-app .wb-db-PropertyLabel' );

		expect( label ).not.toBeNull();
		expect( ( label as HTMLElement ).tagName.toLowerCase() ).toBe( 'label' );
		expect( ( label as HTMLElement ).textContent ).toBe( propertyLabel );
		expect( ( label as HTMLElement ).getAttribute( 'lang' ) ).toBe( language );
		expect( ( label as HTMLElement ).getAttribute( 'dir' ) ).toBe( dir );

		expect( input ).not.toBeNull();
		expect( ( input as HTMLElement ).tagName.toLowerCase() ).toBe( 'textarea' );
	} );

	it( 'can alter its value', async () => {
		const testNewValue = 'test1234';
		const testLink = prepareTestEnv( {} );

		await init();
		testLink!.click();
		await budge();

		const input = select( '.wb-db-app .wb-db-stringValue__input' );

		expect( input ).not.toBeNull();
		expect( ( input as HTMLElement ).tagName.toLowerCase() ).toBe( 'textarea' );
		await insert( input as HTMLTextAreaElement, testNewValue );

		expect( ( input as HTMLTextAreaElement ).value ).toBe( testNewValue );
	} );
} );
