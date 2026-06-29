jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconClose: 'close',
		cdxIconPrevious: 'previous',
		cdxIconNext: 'next'
	} ),
	{ virtual: true }
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const wbui2025 = require( '../../../resources/wikibase.wbui2025/lib.js' );
const indicatorsComponent = require( '../../../resources/wikibase.wbui2025/components/indicators.vue' );
const indicatorPopoverComponent = require( '../../../resources/wikibase.wbui2025/components/indicatorPopover.vue' );
const { mount } = require( '@vue/test-utils' );
const { createTestingPinia } = require( '@pinia/testing' );

let pinia;

describe( 'wikibase.wbui2025.indicators', () => {
	it( 'defines the component', () => {
		expect( typeof indicatorsComponent ).toBe( 'object' );
		expect( indicatorsComponent ).toHaveProperty( 'name', 'WikibaseWbui2025Indicators' );
	} );

	describe( 'the mounted component', () => {
		const snakHash = 'ee6053a6982690ba0f5227d587394d9111eea401',
			statementId = 'Q1$789eef0c-4108-cdda-1a63-505cdd324564',
			referenceHash = '1e638f52eb8d0d3a9453aa05143fa059657dd9d3',
			iconHtml = '<span class="icon-class"></span>';

		beforeEach( () => {
			pinia = createTestingPinia();
		} );

		function mountIndicators( props = {} ) {
			return mount( indicatorsComponent, {
				props: props,
				global: {
					stubs: { WikibaseWbui2025IndicatorPopover: true },
					plugins: [ pinia ]
				}
			} );
		}

		describe.each( [
			[
				'main snak',
				{},
				() => wbui2025.store.setIndicatorHtmlForMainSnak(
					statementId,
					iconHtml
				),
				{
					isQualifier: false,
					referenceHash: null
				}
			],
			[
				'qualifier',
				{ isQualifier: true },
				() => wbui2025.store.setIndicatorHtmlForQualifier(
					statementId,
					snakHash,
					iconHtml
				),
				{
					isQualifier: true,
					referenceHash: null
				}
			],
			[
				'reference',
				{ referenceHash },
				() => wbui2025.store.setIndicatorHtmlForReferenceSnak(
					statementId,
					referenceHash,
					snakHash,
					iconHtml
				),
				{
					isQualifier: false,
					referenceHash
				}
			]
		] )( 'indicator for %s', ( _kind, props, storeSetup, expectedPopoverProps ) => {
			let wrapper;

			describe( 'without indicator html', () => {
				beforeEach( async () => {
					wrapper = await mountIndicators( Object.assign( {
						statementId,
						snakHash
					}, props ) );
				} );

				it( 'has no content', () => {
					expect( wrapper.findAll( { ref: 'indicatorAnchor' } ) ).toHaveLength( 0 );
					expect( wrapper.findComponent( indicatorPopoverComponent ).exists() ).toBeFalsy();
				} );
			} );

			describe( 'when indicator html exists', () => {
				let indicatorSpan;

				beforeEach( async () => {
					storeSetup();
					wrapper = await mountIndicators( Object.assign( {
						statementId,
						snakHash
					}, props ) );
					indicatorSpan = wrapper.find( { ref: 'indicatorAnchor' } );
				} );

				it( 'exists with the expected content', () => {
					expect( wrapper.exists() ).toBeTruthy();
					expect( indicatorSpan.exists() ).toBeTruthy();
					expect( indicatorSpan.html() ).toContain( iconHtml );
				} );

				it( 'mounts the popover with the right props when icon is clicked', async () => {
					let popover = wrapper.findComponent( indicatorPopoverComponent );
					expect( popover.exists() ).toBeFalsy();

					expect( wrapper.findAllComponents( indicatorPopoverComponent ) ).toHaveLength( 0 );
					await indicatorSpan.trigger( 'click' );

					popover = wrapper.findComponent( indicatorPopoverComponent );
					expect( popover.exists() ).toBeTruthy();

					expect( popover.props() ).toEqual( Object.assign( {
						anchor: indicatorSpan.element,
						snakHash,
						statementId
					}, expectedPopoverProps ) );
				} );

				it( 'clicking the icon closes an open popover', async () => {
					await wrapper.setData( { popoverVisible: true } );
					expect( wrapper.findComponent( indicatorPopoverComponent ).exists() ).toBeTruthy();

					await indicatorSpan.trigger( 'click' );
					expect( wrapper.findComponent( indicatorPopoverComponent ).exists() ).toBeFalsy();
				} );
			} );
		} );
	} );
} );
