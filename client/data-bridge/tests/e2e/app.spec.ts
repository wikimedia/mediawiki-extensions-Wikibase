import EditFlow from '@/definitions/EditFlow';
import init from '@/mediawiki/init';
import { launch } from '@/main';
import createServices from '@/services/createServices';
import clone from '@/store/clone';
import {
	addPageInfoNoEditRestrictionsResponse,
	addSiteinfoRestrictionsResponse,
	getMockFullRepoBatchedQueryResponse,
	getOrCreateApiQueryResponsePage,
	mockMwForeignApiConstructor,
	mockMwConfig,
	mockMwEnv,
	mockMwApiConstructor,
	addPropertyLabelResponse,
	addDataBridgeConfigResponse,
	addReferenceRenderingResponse,
	addEntityDataResponse,
} from '../util/mocks';
import { budge } from '../util/timer';
import {
	select,
	enter,
	space,
	insert,
	selectRadioInput,
} from '../util/e2e';
import Entities from '@/mock-data/data/Q42.data.json';
import { v4 as uuid } from 'uuid';
import { ApiQueryInfoTestResponsePage, ApiQueryResponseBody } from '@/definitions/data-access/ApiQuery';
import { ApiErrorRawErrorformat } from '@/data-access/ApiPageEditPermissionErrorsRepository';
import { SpecialPageWikibaseEntityResponse } from '@/data-access/SpecialPageReadingEntityRepository';
import MwConfig from '@/@types/mediawiki/MwConfig';
import { createApp } from 'vue';

const on = jest.fn();
const clearWindows = jest.fn( () => Promise.resolve() );
const manager = {
	clearWindows,
	on,
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
	mwConfig?: MwConfig;
} ): HTMLElement {
	const entityId = options.entityId || DEFAULT_ENTITY;
	const entityTitle = entityId;
	const propertyId = options.propertyId || DEFAULT_PROPERTY;
	const editFlow = options.editFlow || EditFlow.SINGLE_BEST_VALUE;

	const app = { launch, createServices };
	const require = jest.fn().mockResolvedValueOnce( app );
	require.mockResolvedValueOnce( { createMwApp: createApp } );
	const using = jest.fn().mockResolvedValue( require );

	mockMwEnv(
		using,
		options.mwConfig,
		undefined,
		mockMwForeignApiConstructor( {
			get: getMockFullRepoBatchedQueryResponse(
				{ propertyId },
				entityTitle,
				Entities,
			),
		} ),
		mockMwApiConstructor( {
			get: jest.fn().mockResolvedValue(
				addPageInfoNoEditRestrictionsResponse(
					'Client_page',
					addSiteinfoRestrictionsResponse(
						addReferenceRenderingResponse( {} ),
					),
				),
			),
		} ),
	);
	window.$ = {
		uls: {
			data: {
				getDir: jest.fn().mockReturnValue( 'ltr' ),
			},
		},
		param( params: Record<string, unknown> ) {
			return new URLSearchParams( params as Record<string, string> ).toString();
		},
	} as any;
	window.mw.message = jest.fn( ( key: string, ..._params: readonly ( string|HTMLElement )[] ) => {
		return {
			text: () => `⧼${key}⧽`,
			parse: () => `⧼${key}⧽`,
		};
	} );
	window.mw.language = {
		bcp47: jest.fn().mockReturnValue( 'de' ),
	};

	// @ts-ignore
	delete window.location;
	// @ts-ignore
	window.location = { reload: jest.fn() };

	const testLinkHref = `https://www.wikidata.org/wiki/${entityTitle}?uselang=en#${propertyId}`;
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
			window.mw.ForeignApi!.prototype.get = function () {
				return new Promise( () => { /* never resolves */ } );
			} as any;

			await init();
			testLink!.click();
			await budge();

			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
			expect( on ).toHaveBeenCalledTimes( 1 );
			expect( select( '.wb-db-app' ) ).not.toBeNull();
			expect( select( '.wb-db-app .wb-db-load' ) ).not.toBeNull();
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
			).toBeNull();
		} );

		it( 'shows warning when anonymous', async () => {
			const testLink = prepareTestEnv( { mwConfig: mockMwConfig( { wgUserName: null } ) } );

			await init();
			testLink!.click();
			await budge();

			expect( mockPrepareContainer ).toHaveBeenCalledTimes( 1 );
			expect( on ).toHaveBeenCalledTimes( 1 );
			expect( select( '.wb-db-app' ) ).not.toBeNull();
			expect( select( '.wb-db-app .wb-db-warning-anonymous-edit' ) ).not.toBeNull();
			expect( select( '.wb-db-app .wb-ui-processdialog-header' ) ).not.toBeNull();
			expect(
				select(
					'.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--primaryProgressive',
				),
			).toBeNull();
		} );

	} );

	describe( 'api call and ooui trigger on save', () => {
		let propertyId: string;
		let entityId: string;
		let entityTitle: string;
		let testSet: SpecialPageWikibaseEntityResponse;
		let postWithEditToken: jest.Mock;
		let assertCurrentUser: jest.Mock;
		let newStringDataValue: string;

		beforeEach( () => {
			propertyId = 'P31';
			entityId = 'Q42';
			entityTitle = entityId;
			testSet = {
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

			postWithEditToken = jest.fn().mockResolvedValue( {
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

			assertCurrentUser = jest.fn( ( params ) => params );

			newStringDataValue = uuid();
		} );

		async function getEnabledSaveButton( testLink: HTMLElement ): Promise<HTMLElement> {
			window.mw.ForeignApi = mockMwForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get: getMockFullRepoBatchedQueryResponse(
					{ propertyId },
					entityTitle,
					testSet,
				),
				postWithEditToken,
				assertCurrentUser,
			} );

			await init();

			testLink!.click();
			await budge();

			const input = select( '.wb-db-app .wb-db-string-value .wb-db-string-value__input' );
			await insert( input as HTMLTextAreaElement, newStringDataValue );

			const replaceInputDecision = select( '.wb-db-app input[name=editDecision][value=replace]' );
			await selectRadioInput( replaceInputDecision as HTMLInputElement );

			const save = select(
				'.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--primaryProgressive',
			);
			expect( select( '.wb-db-app .wb-ui-processdialog-header' ) ).not.toBeNull();
			expect( save ).not.toBeNull();

			// showing the license
			save!.click();
			await budge();

			return save as HTMLElement;
		}

		it( 'asserts current user and tags, if given', async () => {
			const tags = [ 'abc' ];

			const testLink = prepareTestEnv( { propertyId, entityId } );

			window.mw.config = mockMwConfig( {
				editTags: tags,
			} );

			const expectedData = { claims: clone( testSet.entities[ entityId ].claims ) };
			expectedData.claims[ propertyId ][ 0 ].mainsnak.datavalue!.value = newStringDataValue;
			const expectedParams = {
				action: 'wbeditentity',
				id: entityId,
				baserevid: 0,
				data: JSON.stringify( expectedData ),
				tags,
			};
			const expectedAssertingParams = {
				assertuser: 'assertUsername',
				...expectedParams,
			};
			assertCurrentUser.mockReturnValue( expectedAssertingParams );

			const save = await getEnabledSaveButton( testLink );

			save!.click();
			await budge();

			expect( assertCurrentUser ).toHaveBeenCalledTimes( 1 );
			expect( assertCurrentUser ).toHaveBeenCalledWith( expectedParams );
			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
			expect( postWithEditToken ).toHaveBeenCalledWith( expectedAssertingParams );

		} );

		it( 'saves on click and reloads on second click', async () => {
			const pageTitle = 'Client_page';
			const testLink = prepareTestEnv( { propertyId, entityId } );
			const clientApiPost = jest.fn().mockResolvedValue( {
				batchcomplete: true,
				purge: [ {
					ns: 1,
					title: pageTitle,
					purged: true,
					linkupdate: true,
				} ],
			} );
			window.mw.Api = mockMwApiConstructor( {
				get: jest.fn().mockResolvedValue(
					addPageInfoNoEditRestrictionsResponse(
						pageTitle,
						addSiteinfoRestrictionsResponse(
							{},
						),
					),
				),
				post: clientApiPost,
			} );

			const save = await getEnabledSaveButton( testLink );

			save!.click();
			await budge();

			const sentData = { claims: testSet.entities[ entityId ].claims };
			sentData.claims[ propertyId ][ 0 ].mainsnak.datavalue!.value = newStringDataValue;

			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
			expect( postWithEditToken ).toHaveBeenCalledWith( {
				action: 'wbeditentity',
				id: entityId,
				baserevid: 0,
				data: JSON.stringify( sentData ),
				tags: undefined,
			} );
			expect( clientApiPost ).toHaveBeenCalledTimes( 1 );
			expect( clientApiPost ).toHaveBeenCalledWith( {
				action: 'purge',
				titles: [ pageTitle ],
				forcelinkupdate: true,
				errorformat: 'raw',
				formatversion: 2,
			} );

			const editReferences = select(
				'.wb-db-app .wb-db-thankyou a.wb-ui-event-emitting-button--primaryProgressive',
			);
			editReferences!.click();
			await budge();
			expect( location.reload ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'saves on enter and reloads on second enter', async () => {
			const testLink = prepareTestEnv( { propertyId, entityId } );

			const save = await getEnabledSaveButton( testLink );

			enter( save! );
			await budge();

			const sentData = { claims: testSet.entities[ entityId ].claims };
			sentData.claims[ propertyId ][ 0 ].mainsnak.datavalue!.value = newStringDataValue;

			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
			expect( postWithEditToken ).toHaveBeenCalledWith( {
				action: 'wbeditentity',
				id: entityId,
				baserevid: 0,
				data: JSON.stringify( sentData ),
				tags: undefined,
			} );

			const editReferences = select(
				'.wb-db-app .wb-db-thankyou a.wb-ui-event-emitting-button--primaryProgressive',
			);
			enter( editReferences! );
			await budge();
			expect( location.reload ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'saves on space and reloads on enter', async () => {
			const testLink = prepareTestEnv( { propertyId, entityId } );

			const save = await getEnabledSaveButton( testLink );

			space( save! );
			await budge();

			const sentData = { claims: testSet.entities[ entityId ].claims };
			sentData.claims[ propertyId ][ 0 ].mainsnak.datavalue!.value = newStringDataValue;

			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
			expect( postWithEditToken ).toHaveBeenCalledWith( {
				action: 'wbeditentity',
				id: entityId,
				baserevid: 0,
				data: JSON.stringify( sentData ),
				tags: undefined,
			} );

			const editReferences = select(
				'.wb-db-app .wb-db-thankyou a.wb-ui-event-emitting-button--primaryProgressive',
			);
			enter( editReferences! ); // this cannot be a space, space does not activate link buttons
			await budge();
			expect( location.reload ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'creates a new statement with the user\'s value if update is chosen', async () => {
			const testLink = prepareTestEnv( { propertyId, entityId } );

			const save = await getEnabledSaveButton( testLink );

			const updateInputDecision = select( '.wb-db-app input[name=editDecision][value=update]' );
			await selectRadioInput( updateInputDecision as HTMLInputElement );

			save!.click();
			await budge();

			const sentData = { claims: { [ propertyId ]: [ {
				rank: 'preferred',
				type: 'statement',
				mainsnak: {
					snaktype: 'value',
					property: propertyId,
					datatype: 'string',
					datavalue: {
						type: 'string',
						value: newStringDataValue,
					},
				},
			} ] } };

			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
			expect( postWithEditToken ).toHaveBeenCalledWith( {
				action: 'wbeditentity',
				id: entityId,
				baserevid: 0,
				data: JSON.stringify( sentData ),
				tags: undefined,
			} );
		} );

		it( 'has the save button initially disabled', async () => {
			const testLink = prepareTestEnv( { propertyId } );
			window.mw.ForeignApi = mockMwForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get: getMockFullRepoBatchedQueryResponse(
					{ propertyId },
					entityTitle,
					testSet,
				),
				postWithEditToken,
			} );

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

			const input = select( '.wb-db-app .wb-db-string-value .wb-db-string-value__input' );
			await insert( input as HTMLTextAreaElement, uuid() );

			save!.click();
			await budge();
			expect( postWithEditToken ).toHaveBeenCalledTimes( 0 );

			const replaceInputDecision = select( '.wb-db-app input[name=editDecision][value=replace]' );
			await selectRadioInput( replaceInputDecision as HTMLInputElement );

			// showing the license
			save!.click();
			await budge();

			// actually triggering save
			save!.click();
			await budge();
			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'doesn\'t save if the license isn\'t showing', async () => {
			const testLink = prepareTestEnv( { propertyId } );
			window.mw.ForeignApi = mockMwForeignApiConstructor( {
				expectedUrl: 'http://localhost/w/api.php',
				get: getMockFullRepoBatchedQueryResponse(
					{ propertyId },
					entityTitle,
					testSet,
				),
				postWithEditToken,
			} );

			await init();

			testLink!.click();
			await budge();

			const save = select(
				'.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--primaryProgressive',
			);

			const input = select( '.wb-db-app .wb-db-string-value .wb-db-string-value__input' );
			await insert( input as HTMLTextAreaElement, uuid() );

			const replaceInputDecision = select( '.wb-db-app input[name=editDecision][value=replace]' );
			await selectRadioInput( replaceInputDecision as HTMLInputElement );

			// showing the license
			save!.click();
			await budge();

			const getLicenseCloseButton = function (): HTMLElement | null {
				return select(
					'.wb-db-app .wb-db-license a.wb-ui-event-emitting-button--close',
				);
			};
			let licenseCloseButton = getLicenseCloseButton();
			licenseCloseButton!.click();
			await budge();

			// showing the license again
			save!.click();
			await budge();
			expect( postWithEditToken ).not.toHaveBeenCalled();

			licenseCloseButton = getLicenseCloseButton();
			licenseCloseButton!.click();
			await budge();

			// showing the license again
			save!.click();
			await budge();
			expect( postWithEditToken ).not.toHaveBeenCalled();

			// actually triggering save
			save!.click();
			await budge();
			expect( postWithEditToken ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	describe( 'close', () => {
		let close: HTMLElement|null;

		beforeEach( async () => {
			const testLink = prepareTestEnv( {} );
			await init();

			testLink!.click();
			await budge();

			close = select(
				'.wb-db-app .wb-ui-processdialog-header a.wb-ui-event-emitting-button--close',
			);

			expect( select( '.wb-db-app .wb-ui-processdialog-header' ) ).not.toBeNull();
			expect( close ).not.toBeNull();
		} );

		it( 'closes on click', async () => {
			close!.click();
			await budge();

			expect( clearWindows ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'closes on enter', async () => {
			enter( close! );
			await budge();

			expect( clearWindows ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'closes on space', async () => {
			space( close! );
			await budge();

			expect( clearWindows ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	it( 'tracks opening of bridge with opening performance, data type, and error', async () => {
		const propertyId = 'P4711';
		const dataType = 'peculiar-type';
		const testLink = prepareTestEnv( { propertyId } );
		window.mw.ForeignApi = mockMwForeignApiConstructor( {
			get: getMockFullRepoBatchedQueryResponse(
				{ propertyId, propertyLabel: 'something', dataType },
				DEFAULT_ENTITY,
				Entities,
			),
		} );
		const mockTracker = jest.fn();
		window.mw.track = mockTracker;

		await init();

		testLink!.click();
		await budge();

		expect( mockTracker ).toHaveBeenCalledTimes( 4 );

		expect( mockTracker.mock.calls[ 0 ][ 0 ] ).toBe(
			'timing.MediaWiki.wikibase.client.databridge.clickDelay',
		);
		expect( mockTracker.mock.calls[ 0 ][ 1 ] ).toBeGreaterThan( 0 );

		expect( mockTracker ).toHaveBeenNthCalledWith(
			2,
			`counter.MediaWiki.wikibase.client.databridge.datatype.${dataType}`,
			1,
		);

		expect( mockTracker ).toHaveBeenNthCalledWith(
			3,
			'counter.MediaWiki.wikibase.client.databridge.error.all.invalid_entity_state_error',
			1,
		);

		expect( mockTracker ).toHaveBeenNthCalledWith(
			4,
			'counter.MediaWiki.wikibase.client.databridge.error.unknown.invalid_entity_state_error',
			1,
		);
	} );

	it( 'formats the relevant references using the API', async () => {
		const contentLanguage = 'fr';
		const testLink = prepareTestEnv( {
			mwConfig: mockMwConfig( {
				wgPageContentLanguage: contentLanguage,
			} ),
		} );
		const referenceHtml = [ '<span>ref1</span>', '<span>ref2</span>' ];
		let mockReferenceId = 0;
		const clientApiGet = jest.fn( ( query ) => {
			if ( query.action === 'wbformatreference' ) {
				return Promise.resolve( {
					wbformatreference: {
						html: referenceHtml[ mockReferenceId++ ] || fail(),
					},
				} );
			}
			return Promise.resolve( addPageInfoNoEditRestrictionsResponse(
				'Client_page',
				addSiteinfoRestrictionsResponse( {} ),
			) );
		} );
		window.mw.Api = mockMwApiConstructor( {
			get: clientApiGet,
		} );

		await init();

		testLink!.click();
		await budge();

		Entities.entities[ DEFAULT_ENTITY ].claims[ DEFAULT_PROPERTY ][ 0 ].references.forEach( ( reference ) => {
			expect( clientApiGet ).toHaveBeenCalledWith( {
				action: 'wbformatreference',
				reference: JSON.stringify( reference ),
				style: 'internal-data-bridge',
				outputformat: 'html',
				errorformat: 'raw',
				formatversion: 2,
				uselang: contentLanguage,
			} );
		} );

		const references = document.querySelectorAll(
			'.wb-db-app .wb-db-references .wb-db-references__listItem',
		);
		expect( references[ 0 ].innerHTML ).toBe( referenceHtml[ 0 ] );
		expect( references[ 1 ].innerHTML ).toBe( referenceHtml[ 1 ] );
	} );

	describe( 'error state specialized for permission errors', () => {
		function addPageInfoProtectedpageResponse( title: string, response: { query?: object } ): object {
			const query: ApiQueryResponseBody = response.query || ( response.query = {} ),
				page = getOrCreateApiQueryResponsePage( query, title ),
				apiError: ApiErrorRawErrorformat = {
					code: 'protectedpage',
					key: 'protectedpagetext',
					params: [
						'editsemiprotected',
						'edit',
					],
				};
			( page as ApiQueryInfoTestResponsePage ).actions = { edit: [ apiError ] };

			return response;
		}

		function addPageInfoCascadeprotectedResponse( title: string, response: { query?: object } ): object {
			const query: ApiQueryResponseBody = response.query || ( response.query = {} ),
				page = getOrCreateApiQueryResponsePage( query, title ),
				apiError: ApiErrorRawErrorformat = {
					code: 'cascadeprotected',
					key: 'cascadeprotected',
					params: [
						2,
						'* [[:Art]]\n* [[:Category:Cat]]\n',
						'edit',
					],
				};
			( page as ApiQueryInfoTestResponsePage ).actions = { edit: [ apiError ] };

			return response;
		}

		it( 'shows reason if item semiprotected on repo', async () => {
			const testLink = prepareTestEnv( {} );
			window.mw.ForeignApi = mockMwForeignApiConstructor( {
				get: jest.fn().mockResolvedValue(
					addEntityDataResponse(
						Entities,
						addPropertyLabelResponse(
							{
								propertyId: DEFAULT_PROPERTY,
							},
							addPageInfoProtectedpageResponse(
								DEFAULT_ENTITY,
								addSiteinfoRestrictionsResponse(
									addDataBridgeConfigResponse(
										{},
										{},
									),
								),
							),
						),
					),
				),
			} );

			await init();
			testLink!.click();
			await budge();

			expect( select( '.wb-db-app' ) ).not.toBeNull();
			const errorsWrapper = select( '.wb-db-app .wb-db-error' );
			expect( errorsWrapper ).not.toBeNull();
			expect( ( errorsWrapper as HTMLElement ).innerHTML )
				.toContain( '⧼wikibase-client-data-bridge-permissions-error⧽' );
			const permissionErrors = document.querySelectorAll(
				'.wb-db-app .wb-db-error .wb-db-error-permission-info',
			);
			expect( permissionErrors ).toHaveLength( 1 );
			expect( permissionErrors[ 0 ].innerHTML )
				.toContain( '⧼wikibase-client-data-bridge-semiprotected-on-repo-head⧽' );
		} );

		it( 'enumerates reasons if item semiprotected on repo & article cascadeprotected on client', async () => {
			const testLink = prepareTestEnv( {} );
			window.mw.Api = mockMwApiConstructor( {
				get: jest.fn().mockResolvedValue(
					addPageInfoCascadeprotectedResponse(
						'Client_page',
						addSiteinfoRestrictionsResponse(
							{},
						),
					),
				),
			} );
			window.mw.ForeignApi = mockMwForeignApiConstructor( {
				get: jest.fn().mockResolvedValue(
					addEntityDataResponse(
						Entities,
						addPropertyLabelResponse(
							{
								propertyId: DEFAULT_PROPERTY,
							},
							addPageInfoProtectedpageResponse(
								DEFAULT_ENTITY,
								addSiteinfoRestrictionsResponse(
									addDataBridgeConfigResponse(
										{},
										{},
									),
								),
							),
						),
					),
				),
			} );

			await init();
			testLink!.click();
			await budge();

			expect( select( '.wb-db-app' ) ).not.toBeNull();
			const errorsWrapper = select( '.wb-db-app .wb-db-error' );
			expect( errorsWrapper ).not.toBeNull();
			expect( ( errorsWrapper as HTMLElement ).innerHTML )
				.toContain( '⧼wikibase-client-data-bridge-permissions-error⧽' );
			const permissionErrors = document.querySelectorAll(
				'.wb-db-app .wb-db-error .wb-db-error-permission-info',
			);
			expect( permissionErrors ).toHaveLength( 2 );
			expect( permissionErrors[ 0 ].innerHTML )
				.toContain( '⧼wikibase-client-data-bridge-semiprotected-on-repo-head⧽' );
			expect( permissionErrors[ 1 ].innerHTML )
				.toContain( '⧼wikibase-client-data-bridge-cascadeprotected-on-client-head⧽' );
		} );
	} );
} );
