jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);

const mainSnakView = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.mainSnak.vue' );
const { mount } = require( '@vue/test-utils' );
const { createTestingPinia } = require( '@pinia/testing' );

describe( 'wikibase.wbui2025.mainSnak', () => {
	describe( 'the mounted component', () => {
		function mountMainSnakView( props = {}, initialState = {} ) {
			return mount( mainSnakView, {
				props,
				global: {
					plugins: [ createTestingPinia( {
						initialState
					} ) ]
				}
			} );
		}

		it( 'correctly sets the properties in the HTML', async () => {
			const wrapper = await mountMainSnakView( {
				mainSnak: {
					datatype: 'string',
					hash: 'ee6053a6982690ba0f5227d587394d9111eea401',
					property: 'P1',
					datavalue: { value: 'p1', type: 'string' }
				}
			}, {
				serverRenderedHtml: {
					snakValues: new Map( [
						[ 'ee6053a6982690ba0f5227d587394d9111eea401', '<span>p1</span>' ]
					] )
				}
			} );

			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '.wikibase-wbui2025-snak-value' ) ).toHaveLength( 1 );
			const snak = wrapper.find( ' .wikibase-wbui2025-snak-value' );
			expect( snak.text() ).toEqual( 'p1' );
			expect( snak.attributes()[ 'data-snak-hash' ] ).toEqual( 'ee6053a6982690ba0f5227d587394d9111eea401' );
			expect( snak.attributes().class ).toEqual( 'wikibase-wbui2025-snak-value' );
		} );

		it( 'sets a custom class for a media snak', async () => {
			const wrapper = await mountMainSnakView( {
				mainSnak: {
					datatype: 'commonsMedia',
					hash: 'ee6053a6982690ba0f5227d587394d9111eea401',
					property: 'P1',
					datavalue: { value: 'p1', type: 'string' }
				}
			} );

			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '.wikibase-wbui2025-snak-value' ) ).toHaveLength( 1 );
			expect( wrapper.find( ' .wikibase-wbui2025-snak-value' ).attributes().class.split( ' ' ) )
				.toContain( 'wikibase-wbui2025-media-value' );
		} );

		it( 'sets a custom class for a time snak', async () => {
			const wrapper = await mountMainSnakView( {
				mainSnak: {
					datatype: 'time',
					hash: 'ee6053a6982690ba0f5227d587394d9111eea401',
					property: 'P1',
					datavalue: { value: 'p1', type: 'time' }
				}
			} );

			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '.wikibase-wbui2025-snak-value' ) ).toHaveLength( 1 );
			expect( wrapper.find( ' .wikibase-wbui2025-snak-value' ).attributes().class.split( ' ' ) )
				.toContain( 'wikibase-wbui2025-time-value' );
		} );

	} );

	it( 'defines component', async () => {
		expect( typeof mainSnakView ).toBe( 'object' );
		expect( mainSnakView ).toHaveProperty( 'name', 'WikibaseWbui2025MainSnak' );
	} );

} );
