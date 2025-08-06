jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);

const propertyNameComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.propertyName.vue' );
const statementDetailViewComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.statementDetailView.vue' );
const statementViewComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.statementView.vue' );
const { mount } = require( '@vue/test-utils' );
const { createTestingPinia } = require( '@pinia/testing' );

describe( 'wikibase.wbui2025.statementView', () => {
	it( 'defines component', async () => {
		expect( typeof statementViewComponent ).toBe( 'object' );
		expect( statementViewComponent ).toHaveProperty( 'name', 'WikibaseWbui2025StatementView' );
	} );

	describe( 'the mounted component', () => {
		let wrapper;
		const mockConfig = {
			wgNamespaceIds: {
				property: 122
			}
		};
		mw.config = {
			get: jest.fn( ( key ) => mockConfig[ key ] )
		};

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
			mainsnak: { snaktype: 'somevalue' },
			type: 'statement',
			id: 'Q1$18ed80a7-62a8-4779-a7dd-3876e835979a',
			rank: 'normal'
		};
		beforeEach( async () => {
			wrapper = await mount( statementViewComponent, {
				props: {
					statements: [ mockStatement, mockStatement2 ],
					propertyId: 'P1'
				},
				global: {
					plugins: [ createTestingPinia( {
						initialState: {
							serverRenderedHtml: {
								propertyLinks: new Map( [
									[ 'P1', '<a href="mock-property-url">P1</a>' ]
								] ),
								snakValues: new Map( [
									[ 'ee6053a6982690ba0f5227d587394d9111eea401', '<span>p1</span>' ]
								] )
							}
						}
					} ) ]
				}
			} );
		} );

		it( 'the component and child components/elements mount successfully', async () => {
			expect( wrapper.exists() ).toBe( true );
			expect( wrapper.findAll( '.wikibase-wbui2025-statement-group' ) ).toHaveLength( 1 );
			const propertyNames = wrapper.findAllComponents( propertyNameComponent );
			expect( propertyNames ).toHaveLength( 1 );
			expect( propertyNames[ 0 ].props( 'propertyId' ) ).toBe( 'P1' );
			const statementDetailViews = wrapper.findAllComponents( statementDetailViewComponent );
			expect( statementDetailViews ).toHaveLength( 2 );
			expect( statementDetailViews[ 0 ].props( 'statement' ) ).toEqual( mockStatement );
			expect( statementDetailViews[ 1 ].props( 'statement' ) ).toEqual( mockStatement2 );
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
