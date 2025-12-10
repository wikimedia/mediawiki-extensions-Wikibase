jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconClose: 'close'
	} ),
	{ virtual: true }
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const mainSnakView = require( '../../../resources/wikibase.wbui2025/components/mainSnak.vue' );
const { mount } = require( '@vue/test-utils' );
const { storeWithServerRenderedHtml } = require( '../piniaHelpers.js' );

describe( 'wikibase.wbui2025.mainSnak', () => {
	describe( 'the mounted component', () => {
		function mountMainSnakView( props = {}, snakHashToHtmlMap = {} ) {
			return mount( mainSnakView, {
				props,
				global: {
					plugins: [ storeWithServerRenderedHtml( snakHashToHtmlMap ) ]
				}
			} );
		}

		it( 'correctly sets the properties in the HTML', async () => {
			const wrapper = await mountMainSnakView(
				{
					mainSnak: {
						datatype: 'string',
						hash: 'ee6053a6982690ba0f5227d587394d9111eea401',
						property: 'P1',
						datavalue: { value: 'p1', type: 'string' }
					},
					rank: 'deprecated'
				},
				{ ee6053a6982690ba0f5227d587394d9111eea401: '<span>p1</span>' }
			);

			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '.wikibase-wbui2025-snak-value' ) ).toHaveLength( 1 );
			const snak = wrapper.find( ' .wikibase-wbui2025-snak-value' );
			expect( snak.text() ).toEqual( 'p1' );
			expect( snak.attributes()[ 'data-snak-hash' ] ).toEqual( 'ee6053a6982690ba0f5227d587394d9111eea401' );
			expect( snak.attributes().class ).toEqual( 'wikibase-wbui2025-snak-value' );
			const rankSelector = wrapper.find( '.wikibase-wbui2025-rankselector span' );
			expect( rankSelector.attributes().class ).toContain( 'wikibase-rankselector-deprecated' );
		} );

		it( 'sets a custom class for a media snak', async () => {
			const wrapper = await mountMainSnakView( {
				mainSnak: {
					datatype: 'commonsMedia',
					hash: 'ee6053a6982690ba0f5227d587394d9111eea401',
					property: 'P1',
					datavalue: { value: 'p1', type: 'string' }
				},
				rank: 'normal'
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
				},
				rank: 'normal'
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
