jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const wbui2025 = require( '../../../resources/wikibase.wbui2025/lib.js' );
const snakValueVue = require( '../../../resources/wikibase.wbui2025/components/snakValue.vue' );
const { mount } = require( '@vue/test-utils' );
const { storeWithServerRenderedHtml } = require( '../piniaHelpers.js' );

describe( 'wikibase.wbui2025.snakValue', () => {
	describe( 'the mounted component', () => {
		function mountSnakValue( props = {}, snakHashToHtmlMap = {} ) {
			return mount( snakValueVue, {
				props,
				global: {
					plugins: [ storeWithServerRenderedHtml( snakHashToHtmlMap ) ]
				}
			} );
		}

		it( 'correctly sets the properties in the HTML', async () => {
			const wrapper = await mountSnakValue(
				{
					snak: {
						datatype: 'string',
						hash: 'ee6053a6982690ba0f5227d587394d9111eea401',
						property: 'P1',
						datavalue: { value: 'p1', type: 'string' }
					}
				},
				{ ee6053a6982690ba0f5227d587394d9111eea401: '<span>p1</span>' }
			);

			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '.wikibase-wbui2025-snak-value' ) ).toHaveLength( 1 );
			const snak = wrapper.find( ' .wikibase-wbui2025-snak-value' );
			expect( snak.text() ).toEqual( 'p1' );
			expect( snak.attributes()[ 'data-snak-hash' ] ).toEqual( 'ee6053a6982690ba0f5227d587394d9111eea401' );
			expect( snak.attributes().class ).toEqual( 'wikibase-wbui2025-snak-value' );
		} );

		it( 'displays indicators if they are set', async () => {
			const wrapper = await mountSnakValue(
				{
					snak: {
						datatype: 'string',
						hash: 'ee6053a6982690ba0f5227d587394d9111eea401',
						property: 'P1',
						datavalue: { value: 'p1', type: 'string' }
					}
				},
				{ ee6053a6982690ba0f5227d587394d9111eea401: '<span>p1</span>' }
			);
			wbui2025.store.setIndicatorsHtmlForSnakHash( 'ee6053a6982690ba0f5227d587394d9111eea401', '<span>Icon1</span>' );
			await wrapper.vm.$nextTick();
			expect( wrapper.findAll( '.wikibase-wbui2025-snak-value' ) ).toHaveLength( 1 );
			const snak = wrapper.find( ' .wikibase-wbui2025-snak-value' );
			expect( snak.text() ).toEqual( 'p1Icon1' );
		} );

		it( 'sets the musical-notation-value class for musical notation datatype', async () => {
			const wrapper = await mountSnakValue( {
				snak: {
					datatype: 'musical-notation',
					hash: 'musical1234567890123456789012345678901234567890',
					property: 'P1',
					datavalue: { value: '\\relative c\' { c4 e g c }', type: 'string' }
				}
			} );

			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '.wikibase-wbui2025-snak-value' ) ).toHaveLength( 1 );
			const snak = wrapper.find( '.wikibase-wbui2025-snak-value' );
			const classes = snak.attributes().class.split( ' ' );
			expect( classes ).toContain( 'wikibase-wbui2025-snak-value' );
			expect( classes ).toContain( 'wikibase-wbui2025-musical-notation-value' );
		} );

		it( 'does not set musical-notation-value class for other datatypes', async () => {
			const wrapper = await mountSnakValue( {
				snak: {
					datatype: 'string',
					hash: 'string1234567890123456789012345678901234567890',
					property: 'P1',
					datavalue: { value: 'test', type: 'string' }
				}
			} );

			expect( wrapper.exists() ).toBeTruthy();
			const snak = wrapper.find( '.wikibase-wbui2025-snak-value' );
			const classes = snak.attributes().class.split( ' ' );
			expect( classes ).not.toContain( 'wikibase-wbui2025-musical-notation-value' );
		} );
	} );

	it( 'defines component', async () => {
		expect( typeof snakValueVue ).toBe( 'object' );
		expect( snakValueVue ).toHaveProperty( 'name', 'WikibaseWbui2025SnakValue' );
	} );

} );
