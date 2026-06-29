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
const Wbui2025Indicators = require( '../../../resources/wikibase.wbui2025/components/indicators.vue' );
const Wbui2025SnakValue = require( '../../../resources/wikibase.wbui2025/components/snakValue.vue' );
const { mount } = require( '@vue/test-utils' );
const { storeWithServerRenderedHtml } = require( '../piniaHelpers.js' );

describe( 'wikibase.wbui2025.mainSnak', () => {
	describe( 'the mounted component', () => {
		const statementId = 'Q1$789eef0c-4108-cdda-1a63-505cdd324564';
		const snakHash = 'ee6053a6982690ba0f5227d587394d9111eea401';

		function mountMainSnakView( props = {}, snakHashToHtmlMap = {} ) {
			return mount( mainSnakView, {
				props,
				global: {
					plugins: [ storeWithServerRenderedHtml( snakHashToHtmlMap ) ]
				}
			} );
		}

		it( 'mounts child components with the right props', async () => {
			const mainSnak = {
				datatype: 'string',
				hash: snakHash,
				property: 'P1',
				datavalue: { value: 'p1', type: 'string' }
			};

			const wrapper = await mountMainSnakView(
				{
					mainSnak,
					rank: 'normal',
					statementId
				},
				{ ee6053a6982690ba0f5227d587394d9111eea401: '<span>p1</span>' }
			);
			const indicators = wrapper.findComponent( Wbui2025Indicators );
			const snakValue = wrapper.findComponent( Wbui2025SnakValue );

			expect( indicators.exists() ).toBeTruthy();
			expect( indicators.props( 'snakHash' ) ).toEqual( snakHash );
			expect( indicators.props( 'statementId' ) ).toEqual( statementId );
			expect( indicators.props( 'isQualifier' ) ).toEqual( false );
			expect( indicators.props( 'referenceHash' ) ).toBeNull();

			expect( snakValue.exists() ).toBeTruthy();
			expect( snakValue.props( 'snak' ) ).toEqual( mainSnak );
		} );

		it( 'correctly sets the properties in the HTML', async () => {
			const wrapper = await mountMainSnakView(
				{
					mainSnak: {
						datatype: 'string',
						hash: snakHash,
						property: 'P1',
						datavalue: { value: 'p1', type: 'string' }
					},
					rank: 'deprecated',
					statementId
				},
				{ ee6053a6982690ba0f5227d587394d9111eea401: '<span>p1</span>' }
			);

			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '.wikibase-wbui2025-snak-value' ) ).toHaveLength( 1 );
			const snak = wrapper.find( ' .wikibase-wbui2025-snak-value' );
			expect( snak.text() ).toEqual( 'p1' );
			expect( snak.attributes()[ 'data-snak-hash' ] ).toEqual( snakHash );
			expect( snak.attributes().class ).toEqual( 'wikibase-wbui2025-snak-value' );
			const rankSelector = wrapper.find( '.wikibase-wbui2025-rankselector span' );
			expect( rankSelector.attributes().class ).toContain( 'wikibase-rankselector-deprecated' );
		} );

		it( 'sets a custom class for a media snak', async () => {
			const wrapper = await mountMainSnakView( {
				mainSnak: {
					datatype: 'commonsMedia',
					hash: snakHash,
					property: 'P1',
					datavalue: { value: 'p1', type: 'string' }
				},
				rank: 'normal',
				statementId
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
					hash: snakHash,
					property: 'P1',
					datavalue: { value: 'p1', type: 'time' }
				},
				rank: 'normal',
				statementId
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
