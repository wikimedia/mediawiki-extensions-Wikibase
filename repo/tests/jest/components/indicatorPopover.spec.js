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

const { ref } = require( 'vue' );
const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const wbui2025 = require( '../../../resources/wikibase.wbui2025/lib.js' );
const indicatorPopoverVue = require( '../../../resources/wikibase.wbui2025/components/indicatorPopover.vue' );
const { mount } = require( '@vue/test-utils' );
const { createTestingPinia } = require( '@pinia/testing' );

let pinia;

describe( 'wikibase.wbui2025.indicatorPopover', () => {
	describe( 'the mounted component', () => {

		beforeEach( () => {
			pinia = createTestingPinia();
		} );

		function mountIndicatorPopover( props = {} ) {
			const anchor = ref();
			return mount( indicatorPopoverVue, {
				props: Object.assign( { anchor }, props ),
				global: {
					plugins: [ pinia ]
				}
			} );
		}

		it( 'correctly sets the properties in the HTML', async () => {
			const snakHash = 'ad11db2dbfd7099ea788fc26a68dac40';
			wbui2025.store.setPopoverContentForSnakHash( snakHash, {
				icon: '<span class="wikibase-wbui2025-icon-edit-small"></span>',
				title: 'Popover Title',
				bodyHtml: '<p>Popover Content</p>'
			} );
			const wrapper = await mountIndicatorPopover( { snakHash } );

			expect( wrapper.exists() ).toBeTruthy();
		} );

	} );

	it( 'defines component', async () => {
		expect( typeof indicatorPopoverVue ).toBe( 'object' );
		expect( indicatorPopoverVue ).toHaveProperty( 'name', 'WikibaseWbui2025IndicatorPopover' );
	} );

} );
