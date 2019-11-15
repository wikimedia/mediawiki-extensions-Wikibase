import Vue from 'vue';
import EditFlow from '@/definitions/EditFlow';
import init from '@/mediawiki/init';
import { launch } from '@/main';
import MwWindow from '@/@types/mediawiki/MwWindow';
import createServices from '@/services/createServices';
import {
	mockForeignApiConstructor,
	mockMwConfig,
	mockMwEnv,
	mockForeignApiGet,
	mockDataBridgeConfig,
	mockForeignApiEntityInfoResponse,
} from '../util/mocks';
import { budge } from '../util/timer';
import {
	select,
	enter,
	space,
	insert,
} from '../util/e2e';
import Entities from '@/mock-data/data/Q42.data.json';
import { v4 as uuid } from 'uuid';

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

const DEFAULT_ENTITY = 'Q42';
const DEFAULT_PROPERTY = 'P349';

function prepareTestEnv( options: {
	entityId?: string;
	propertyId?: string;
	editFlow?: string;
} ): HTMLElement {
	const entityId = options.entityId || DEFAULT_ENTITY;
	const propertyId = options.propertyId || DEFAULT_PROPERTY;
	const editFlow = options.editFlow || EditFlow.OVERWRITE;

	const app = { launch, createServices };
	const require = jest.fn( () => app );
	const using = jest.fn( () => new Promise( ( resolve ) => resolve( require ) ) );

	mockMwEnv(
		using,
		undefined,
		undefined,
		mockForeignApiConstructor( {
			get: mockForeignApiGet(
				mockDataBridgeConfig(),
				mockForeignApiEntityInfoResponse( propertyId ),
			),
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
		bcp47: jest.fn( () => 'de' ),
	};

	const testLinkHref = `https://www.wikidata.org/wiki/${entityId}?uselang=en#${propertyId}`;
	document.body.innerHTML = `
<span data-bridge-edit-flow="${editFlow}">
	<a rel="nofollow" class="external text" href="${testLinkHref}">a link to be selected</a>
</span>
<div id="data-bridge-container"/>`;
	return document.querySelector( 'a' ) as HTMLElement;
}

describe( 'app', () => {

	describe( 'app states', () => {
		it( 'shows loading when app is launched', async () => {
			const testLink = prepareTestEnv( {} );
			( window as MwWindow ).$.get = function () {
				return new Promise( () => { /* never resolves */ } );
			} as any;

			await init();
			testLink!.click();
			await budge();

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

		async function getEnabledSaveButton( testLink: HTMLElement ): Promise<HTMLElement> {
			( window as MwWindow ).mw.ForeignApi = mockForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get: mockForeignApiGet(
					mockDataBridgeConfig(),
					mockForeignApiEntityInfoResponse( propertyId ),
				),
				postWithEditToken,
			} );
			( window as MwWindow ).$.get = () => Promise.resolve( testSet ) as any;

			await init();

			testLink!.click();
			await budge();

			const input = select( '.wb-db-app .wb-db-stringValue .wb-db-stringValue__input' );
			await insert( input as HTMLTextAreaElement, uuid() );

			const save = select(
				'.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--primaryProgressive',
			);
			expect( select( '.wb-db-app .wb-ui-processdialog-header' ) ).not.toBeNull();
			expect( save ).not.toBeNull();

			return save as HTMLElement;
		}

		it( 'asserts username and tags, if given', async () => {
			const assertuser = 'assertUsername';
			const tags = [ 'abc' ];

			const testLink = prepareTestEnv( { propertyId, entityId } );

			( window as MwWindow ).mw.config = mockMwConfig( {
				wgUserName: assertuser,
				editTags: tags,
			} );

			const save = await getEnabledSaveButton( testLink );

			save!.click();
			await budge();

			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
			expect( postWithEditToken ).toHaveBeenCalledWith( {
				action: 'wbeditentity',
				id: entityId,
				baserevid: 0,
				data: JSON.stringify( { claims: testSet.entities[ entityId ].claims } ),
				assertuser,
				tags,
			} );

		} );

		it( 'saves on click', async () => {
			const testLink = prepareTestEnv( { propertyId, entityId } );

			const save = await getEnabledSaveButton( testLink );

			save!.click();
			await budge();

			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
			expect( postWithEditToken ).toHaveBeenCalledWith( {
				action: 'wbeditentity',
				id: entityId,
				baserevid: 0,
				data: JSON.stringify( { claims: testSet.entities[ entityId ].claims } ),
				assertuser: 'Test User',
				tags: undefined,
			} );
			expect( clearWindows ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'saves on enter', async () => {
			const testLink = prepareTestEnv( { propertyId, entityId } );

			const save = await getEnabledSaveButton( testLink );

			enter( save! );
			await budge();

			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
			expect( postWithEditToken ).toHaveBeenCalledWith( {
				action: 'wbeditentity',
				id: entityId,
				baserevid: 0,
				data: JSON.stringify( { claims: testSet.entities[ entityId ].claims } ),
				assertuser: 'Test User',
				tags: undefined,
			} );
			expect( clearWindows ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'saves on space', async () => {
			const testLink = prepareTestEnv( { propertyId, entityId } );

			const save = await getEnabledSaveButton( testLink );

			space( save! );
			await budge();

			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
			expect( postWithEditToken ).toHaveBeenCalledWith( {
				action: 'wbeditentity',
				id: entityId,
				baserevid: 0,
				data: JSON.stringify( { claims: testSet.entities[ entityId ].claims } ),
				assertuser: 'Test User',
				tags: undefined,
			} );
			expect( clearWindows ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'has the save button initially disabled', async () => {
			const testLink = prepareTestEnv( { propertyId } );
			( window as MwWindow ).mw.ForeignApi = mockForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get: mockForeignApiGet(
					mockDataBridgeConfig(),
					mockForeignApiEntityInfoResponse( propertyId ),
				),
				postWithEditToken,
			} );

			( window as MwWindow ).$.get = () => Promise.resolve( testSet ) as any;

			await init();

			testLink!.click();
			await budge();

			const save = select(
				'.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--primaryProgressive',
			);

			save!.click();
			await budge();
			expect( postWithEditToken ).toHaveBeenCalledTimes( 0 );

			enter( save! );
			await budge();
			expect( postWithEditToken ).toHaveBeenCalledTimes( 0 );

			space( save! );
			await budge();
			expect( postWithEditToken ).toHaveBeenCalledTimes( 0 );

			const input = select( '.wb-db-app .wb-db-stringValue .wb-db-stringValue__input' );
			await insert( input as HTMLTextAreaElement, uuid() );

			save!.click();
			await budge();
			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	describe( 'cancel', () => {
		let cancel: HTMLElement|null;

		beforeEach( async () => {
			const testLink = prepareTestEnv( {} );
			await init();

			testLink!.click();
			await budge();

			cancel = select(
				'.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--cancel',
			);

			expect( select( '.wb-db-app .wb-ui-processdialog-header' ) ).not.toBeNull();
			expect( cancel ).not.toBeNull();
		} );

		it( 'closes on click', async () => {
			cancel!.click();
			await budge();

			expect( clearWindows ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'closes on enter', async () => {
			enter( cancel! );
			await budge();

			expect( clearWindows ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'closes on space', async () => {
			space( cancel! );
			await budge();

			expect( clearWindows ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	it( 'tracks opening of bridge with opening performance and data type', async () => {
		const propertyId = 'P4711';
		const dataType = 'peculiar-type';
		const testLink = prepareTestEnv( { propertyId } );
		( window as MwWindow ).mw.ForeignApi = mockForeignApiConstructor( {
			get: mockForeignApiGet(
				mockDataBridgeConfig(),
				mockForeignApiEntityInfoResponse( propertyId, 'something', 'en', dataType ),
			),
		} );
		const mockTracker = jest.fn();
		( window as MwWindow ).mw.track = mockTracker;

		await init();

		testLink!.click();
		await budge();

		expect( mockTracker ).toHaveBeenCalledTimes( 2 );

		expect( mockTracker.mock.calls[ 0 ][ 0 ] ).toBe(
			'timing.MediaWiki.wikibase.client.databridge.clickDelay',
		);
		expect( mockTracker.mock.calls[ 0 ][ 1 ] ).toBeGreaterThan( 0 );

		expect( mockTracker ).toHaveBeenNthCalledWith(
			2,
			`counter.MediaWiki.wikibase.client.databridge.datatype.${dataType}`,
			1,
		);
	} );

} );
