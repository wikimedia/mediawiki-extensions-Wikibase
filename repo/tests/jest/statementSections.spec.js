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

const { mockLibWbui2025 } = require( './libWbui2025Helpers.js' );
mockLibWbui2025();
const statementSections = require( '../../resources/wikibase.wbui2025/statementSections.vue' );
const { mount } = require( '@vue/test-utils' );
const {
	storeWithHtmlAndStatements,
	storeContentsWithServerRenderedHtml,
	storeContentWithStatementsAndProperties
} = require( './piniaHelpers.js' );

describe( 'wikibase.wbui2025.statementSections', () => {
	it( 'defines component', async () => {
		expect( typeof statementSections ).toBe( 'object' );
		expect( statementSections ).toHaveProperty( 'name', 'WikibaseWbui2025StatementSections' );
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
		beforeEach( async () => {
			wrapper = await mount( statementSections, {
				props: {
					sectionHeadingHtml: '<h2>Heading</h2>',
					propertyList: [ 'P1' ],
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
								P1: [ mockStatement ]
							} )
						)
					]
				}
			} );
		} );

		it( 'populates the section heading', async () => {
			expect( wrapper.find( '.wikibase-wbui2025-statement-section-heading' ).html() ).toContain( '<h2>Heading</h2>' );
		} );

		it( 'adds a top-level element for the statement section', async () => {
			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '.wikibase-wbui2025-statement-section' ) ).toHaveLength( 1 );
		} );

		it( 'adds an element to contain the statements for a property', async () => {
			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '#wikibase-wbui2025-statementwrapper-P1' ) ).toHaveLength( 1 );
		} );

		it( 'sets the right content on claim elements', async () => {
			const statements = wrapper.findAll( '.wikibase-wbui2025-statement-group' );
			const statement = statements[ 0 ];
			expect( statement.find( '.wikibase-wbui2025-property-name a' ).text() ).toBe( mockStatement.mainsnak.property );
			expect( statement.find( '.wikibase-wbui2025-property-name a' ).element.href ).toContain( 'mock-property-url' );

			expect( statement.find( '.wikibase-wbui2025-snak-value' ).text() ).toBe( mockStatement.mainsnak.datavalue.value );
		} );
	} );
} );
