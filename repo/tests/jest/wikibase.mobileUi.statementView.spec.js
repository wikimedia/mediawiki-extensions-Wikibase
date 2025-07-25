jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);

const statementView = require( '../../resources/wikibase.mobileUi/wikibase.mobileUi.statementView.vue' );
const { mount } = require( '@vue/test-utils' );
const { createTestingPinia } = require( '@pinia/testing' );

describe( 'wikibase.mobileUi.statementView', () => {
	it( 'defines component', async () => {
		expect( typeof statementView ).toBe( 'object' );
		expect( statementView ).toHaveProperty( 'name', 'WikibaseWbui2025Statement' );
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
		beforeEach( async () => {
			wrapper = await mount( statementView, {
				props: {
					statements: [ mockStatement ],
					propertyId: 'P1'
				},
				global: {
					plugins: [ createTestingPinia( {
						initialState: {
							serverRenderedHtml: {
								propertyLinks: new Map( [
									[ 'P1', '<a href="mock-property-url">P1</a>' ]
								] ),
								snakHtmls: new Map( [
									[ 'ee6053a6982690ba0f5227d587394d9111eea401', '<span>p1</span>' ]
								] )
							}
						}
					} ) ]
				}
			} );
		} );

		it( 'the component and child components/elements mount successfully', async () => {
			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '.wikibase-wbui2025-statement-group' ) ).toHaveLength( 1 );
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
