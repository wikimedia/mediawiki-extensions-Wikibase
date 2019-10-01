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
	enter,
	space,
} from '../util/e2e';
import Entities from '@/mock-data/data/Q42.data.json';

Vue.config.devtools = false;

const on = jest.fn();
const clearWindows = jest.fn( () => Promise.resolve() );
const manager = {
	clearWindows,
	on,
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

describe( 'app', () => {
	let app: any;
	let require: any;
	let using;

	beforeEach( () => {
		app = { launch };
		require = jest.fn( () => app );
		using = jest.fn( () => new Promise( ( resolve ) => resolve( require ) ) );

		mockMwEnv( using );
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

	describe( 'app states', () => {
		it( 'shows loading when app is launched', () => {
			const testLink = prepareTestEnv( {} );

			return init().then( () => {
				testLink!.click();

				expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
				expect( on ).toHaveBeenCalledTimes( 1 );
				expect( select( '.wb-db-app' ) ).not.toBeNull();
				expect( select( '.wb-db-app .wb-db-init' ) ).not.toBeNull();
				expect( select( '.wb-db-app .wb-ui-processdialog-header' ) ).not.toBeNull();
				expect(
					select(
						'.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--primaryProgressive',
					),
				).not.toBeNull();

			} );
		} );

		it( 'shows databridge on valid data', async () => {
			const testLink = prepareTestEnv( {} );

			await init();
			testLink!.click();
			await budge();

			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
			expect( on ).toHaveBeenCalledTimes( 1 );
			expect( select( '.wb-db-app' ) ).not.toBeNull();
			expect( select( '.wb-db-app .wb-db-bridge' ) ).not.toBeNull();
			expect( select( '.wb-db-app .wb-ui-processdialog-header' ) ).not.toBeNull();
			expect(
				select(
					'.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--primaryProgressive',
				),
			).not.toBeNull();

		} );

		it( 'shows error on invalid data', async () => {
			const testLink = prepareTestEnv( { propertyId: 'P4711' } );

			await init();
			testLink!.click();
			await budge();

			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
			expect( on ).toHaveBeenCalledTimes( 1 );
			expect( select( '.wb-db-app' ) ).not.toBeNull();
			expect( select( '.wb-db-app .wb-db-error' ) ).not.toBeNull();
			expect( select( '.wb-db-app .wb-ui-processdialog-header' ) ).not.toBeNull();
			expect(
				select(
					'.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--primaryProgressive',
				),
			).not.toBeNull();
		} );

	} );

	describe( 'api call and ooui trigger on save', () => {
		const propertyId = 'P31';
		const entityId = 'Q42';
		const testSet = {
			entities: {
				[ entityId ]: {
					id: entityId,
					lastrevid: 0,
					claims: {
						[ propertyId ]: [ {
							type: 'statement',
							id: 'opaque statement ID',
							rank: 'normal',
							mainsnak: {
								snaktype: 'value',
								property: propertyId,
								datatype: 'string',
								datavalue: {
									type: 'string',
									value: 'a string value',
								},
							},
						} ],
					},
				},
			},
		};

		const postWithEditToken = jest.fn( () => {
			return Promise.resolve( {
				entity: {
					lastrevid: 2183,
					id: entityId,
					claims: {
						[ propertyId ]: [ {
							mainsnak: {
								snaktype: 'value',
								property: propertyId,
								datavalue: {
									value: 'String for Wikidata bridge',
									type: 'string',
								},
								datatype: 'string',
							},
						} ],
					},
				},
				success: 1,
			} );
		} );

		it( 'asserts username and tags, if given', async () => {
			( window as MwWindow ).mw.ForeignApi = mockForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				postWithEditToken,
			} );

			const assertuser = 'asserUsername';
			const tags = [ 'abc' ];
			( window as MwWindow ).$.get = () => Promise.resolve( testSet ) as any;

			( window as MwWindow ).mw.config = mockMwConfig( {
				wgUserName: assertuser,
				editTags: tags,
			} );

			const testLink = prepareTestEnv( { propertyId, entityId } );
			await init();

			testLink!.click();
			await budge();

			const save = select(
				'.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--primaryProgressive',
			);
			expect( select( '.wb-db-app .wb-ui-processdialog-header' ) ).not.toBeNull();
			expect( save ).not.toBeNull();

			save!.click();
			await budge();

			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
			expect( postWithEditToken ).toHaveBeenCalledWith( {
				action: 'wbeditentity',
				id: 'Q42',
				baserevid: 0,
				data: JSON.stringify( { claims: testSet.entities.Q42.claims } ),
				assertuser,
				tags,
			} );

		} );

		it( 'saves on click', async () => {
			( window as MwWindow ).mw.ForeignApi = mockForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				postWithEditToken,
			} );

			( window as MwWindow ).$.get = () => Promise.resolve( testSet ) as any;

			const testLink = prepareTestEnv( { propertyId, entityId } );
			await init();

			testLink!.click();
			await budge();

			const save = select(
				'.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--primaryProgressive',
			);
			expect( select( '.wb-db-app .wb-ui-processdialog-header' ) ).not.toBeNull();
			expect( save ).not.toBeNull();

			save!.click();
			await budge();

			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
			expect( postWithEditToken ).toHaveBeenCalledWith( {
				action: 'wbeditentity',
				id: 'Q42',
				baserevid: 0,
				data: JSON.stringify( { claims: testSet.entities.Q42.claims } ),
				assertuser: 'Test User',
				tags: undefined,
			} );
			expect( clearWindows ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'saves on enter', async () => {
			( window as MwWindow ).mw.ForeignApi = mockForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				postWithEditToken,
			} );

			( window as MwWindow ).$.get = () => Promise.resolve( testSet ) as any;

			const testLink = prepareTestEnv( { propertyId, entityId } );

			await init();

			testLink!.click();
			await budge();

			const save = select(
				'.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--primaryProgressive',
			);
			expect( select( '.wb-db-app .wb-ui-processdialog-header' ) ).not.toBeNull();
			expect( save ).not.toBeNull();

			enter( save! );
			await budge();

			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
			expect( postWithEditToken ).toHaveBeenCalledWith( {
				action: 'wbeditentity',
				id: 'Q42',
				baserevid: 0,
				data: JSON.stringify( { claims: testSet.entities.Q42.claims } ),
				assertuser: 'Test User',
				tags: undefined,
			} );
			expect( clearWindows ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'saves on space', async () => {
			( window as MwWindow ).mw.ForeignApi = mockForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				postWithEditToken,
			} );

			( window as MwWindow ).$.get = () => Promise.resolve( testSet ) as any;

			const testLink = prepareTestEnv( { propertyId, entityId } );

			await init();

			testLink!.click();
			await budge();

			const save = select(
				'.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--primaryProgressive',
			);
			expect( select( '.wb-db-app .wb-ui-processdialog-header' ) ).not.toBeNull();
			expect( save ).not.toBeNull();

			space( save! );
			await budge();

			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
			expect( postWithEditToken ).toHaveBeenCalledWith( {
				action: 'wbeditentity',
				id: 'Q42',
				baserevid: 0,
				data: JSON.stringify( { claims: testSet.entities.Q42.claims } ),
				assertuser: 'Test User',
				tags: undefined,
			} );
			expect( clearWindows ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );
