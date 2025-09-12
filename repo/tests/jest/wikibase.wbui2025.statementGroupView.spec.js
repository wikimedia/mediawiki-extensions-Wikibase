jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconAdd: 'add',
		cdxIconArrowPrevious: 'arrowPrevious',
		cdxIconCheck: 'check',
		cdxIconClose: 'close',
		cdxIconTrash: 'trash'
	} ),
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/supportedDatatypes.json',
	() => [ 'string' ],
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/api/api.js',
	() => ( { api: { get: jest.fn() } } )
);

const propertyNameComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.propertyName.vue' );
const statementViewComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.statementView.vue' );
const statementGroupViewComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.statementGroupView.vue' );
const { mount } = require( '@vue/test-utils' );
const {
	storeWithHtmlAndStatements,
	storeContentsWithServerRenderedHtml,
	storeContentWithStatementsAndProperties
} = require( './piniaHelpers.js' );

describe( 'wikibase.wbui2025.statementGroupView', () => {
	it( 'defines component', async () => {
		expect( typeof statementGroupViewComponent ).toBe( 'object' );
		expect( statementGroupViewComponent ).toHaveProperty( 'name', 'WikibaseWbui2025StatementGroupView' );
	} );

	describe( 'the mounted component', () => {
		let wrapper;

		const mockStatement = {
			mainsnak: {
				snaktype: 'value',
				property: 'P1',
				hash: 'ee6053a6982690ba0f5227d587394d9111eea401',
				datavalue: { value: 'p1', type: 'string' },
				datatype: 'string'
			},
			type: 'statement',
			id: 'Q1$eb7fdbb4-45d1-f59d-bb3b-013935de1085',
			rank: 'normal'
		};
		const mockStatement2 = {
			mainsnak: { snaktype: 'somevalue', datavalue: { type: 'string', value: '' } },
			type: 'statement',
			id: 'Q1$18ed80a7-62a8-4779-a7dd-3876e835979a',
			rank: 'normal'
		};
		beforeEach( async () => {
			wrapper = await mount( statementGroupViewComponent, {
				props: {
					propertyId: 'P1',
					entityId: 'Q1'
				},
				global: {
					plugins: [
						storeWithHtmlAndStatements(
							storeContentsWithServerRenderedHtml(
								{ ee6053a6982690ba0f5227d587394d9111eea401: '<span>p1</span>' },
								{ P1: '<a href="mock-property-url">P1</a>' }
							),
							storeContentWithStatementsAndProperties( {
								P1: [ mockStatement, mockStatement2 ]
							} )
						)
					]
				}
			} );
		} );

		it( 'the component and child components/elements mount successfully', async () => {
			expect( wrapper.exists() ).toBe( true );
			expect( wrapper.findAll( '.wikibase-wbui2025-statement-group' ) ).toHaveLength( 1 );
			const propertyNames = wrapper.findAllComponents( propertyNameComponent );
			expect( propertyNames ).toHaveLength( 1 );
			expect( propertyNames[ 0 ].props( 'propertyId' ) ).toBe( 'P1' );
			const statementViews = wrapper.findAllComponents( statementViewComponent );
			expect( statementViews ).toHaveLength( 2 );
			expect( statementViews[ 0 ].props( 'statementId' ) ).toEqual( mockStatement.id );
			expect( statementViews[ 1 ].props( 'statementId' ) ).toEqual( mockStatement2.id );
		} );

		it( 'sets the right content on claim elements', async () => {
			const statements = wrapper.findAll( '.wikibase-wbui2025-statement-group' );
			const statement = statements[ 0 ];
			expect( statement.find( '.wikibase-wbui2025-property-name a' ).text() ).toBe( mockStatement.mainsnak.property );
			expect( statement.find( '.wikibase-wbui2025-property-name a' ).element.href ).toContain( 'mock-property-url' );

			expect( statement.find( '.wikibase-wbui2025-snak-value' ).text() ).toBe( mockStatement.mainsnak.datavalue.value );
		} );

		it( 'opens modal edit form when clicking edit link', async () => {
			mw.config = { get: () => false };
			await wrapper.find( '.wikibase-wbui2025-edit-link' ).trigger( 'click' );
			expect( wrapper.find( '.modal-statement-edit-form-anchor' ).exists() ).toBe( true );
		} );
	} );

	describe( 'statement with uneditable data type', () => {
		let wrapper;
		const mockStatement = {
			mainsnak: {
				snaktype: 'value',
				property: 'P2',
				hash: '1725f8bd2897fb1a3491f94bf04869dbc4f68df5',
				datavalue: { value: 'https://example.com/', type: 'string' },
				datatype: 'url'
			},
			type: 'statement',
			id: 'Q1$52f7d93d-9146-41b2-b12c-7520302ce998',
			rank: 'normal'
		};

		beforeEach( async () => {
			wrapper = await mount( statementGroupViewComponent, {
				props: {
					propertyId: 'P2',
					entityId: 'Q1'
				},
				global: {
					plugins: [
						storeWithHtmlAndStatements(
							storeContentsWithServerRenderedHtml(
								{ '1725f8bd2897fb1a3491f94bf04869dbc4f68df5': '<a href="https://example.com/">https://example.com/</a>' },
								{ P2: '<a href="mock-property-url">P2</a>' }
							),
							storeContentWithStatementsAndProperties( {
								P2: [ mockStatement ]
							} )
						)
					]
				}
			} );
		} );

		it( 'has appropriate CSS classes', async () => {
			expect( wrapper.exists() ).toBe( true );
			const editLink = wrapper.find( '.wikibase-wbui2025-edit-link' );
			expect( editLink.exists() ).toBe( true );
			expect( editLink.classes() ).toContain( 'wikibase-wbui2025-edit-link-unsupported' );
			expect( editLink.classes() ).toContain( 'is-red-link' );
		} );

		it( 'does nothing when clicking edit link', async () => {
			await wrapper.find( '.wikibase-wbui2025-edit-link' ).trigger( 'click' );
			expect( wrapper.find( '.modal-statement-edit-form-anchor' ).exists() ).toBe( false );
		} );
	} );

} );
