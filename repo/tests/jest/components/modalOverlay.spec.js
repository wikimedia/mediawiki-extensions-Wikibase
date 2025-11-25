jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);

jest.mock(
	'../../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconArrowPrevious: 'arrowPrevious',
		cdxIconCheck: 'check',
		cdxIconClose: 'close'
	} ),
	{ virtual: true }
);

const { createTestingPinia } = require( '@pinia/testing' );
const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const modalOverlayComponent = require( '../../../resources/wikibase.wbui2025/components/modalOverlay.vue' );
const { mount } = require( '@vue/test-utils' );

describe( 'wikibase.wbui2025.modalOverlay', () => {
	it( 'defines component', async () => {
		expect( typeof modalOverlayComponent ).toBe( 'object' );
		expect( modalOverlayComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025ModalOverlay' );
	} );

	async function mountModalComponent() {
		return mount( modalOverlayComponent, {
			global: {
				plugins: [ createTestingPinia() ]
			}
		} );
	}

	describe( 'the mounted component', () => {
		let teleportTarget;
		beforeEach( async () => {
			createTestingPinia();
			teleportTarget = document.getElementById( 'mw-teleport-target' );
		} );

		afterEach( () => {
			document.body.outerHTML = '';
		} );

		it( 'mounts inside of mw-teleport-target', async () => {
			await mountModalComponent();
			expect( teleportTarget.getElementsByClassName( 'wikibase-wbui2025-modal-overlay' ).length )
				.toEqual( 1 );
		} );

		it( 'can mount multiple modals', async () => {
			await mountModalComponent();
			await mountModalComponent();
			await mountModalComponent();
			expect( teleportTarget.getElementsByClassName( 'wikibase-wbui2025-modal-overlay' ).length )
				.toEqual( 3 );
		} );

		it( 'sets a class on body on mount and removes when closed', async () => {
			expect( document.body.className ).toEqual( '' );
			const modal = await mountModalComponent();
			expect( document.body.className ).toEqual( 'wikibase-wbui2025-modal-open' );
			await modal.unmount();
			expect( document.body.className ).toEqual( '' );
		} );

		it( 'only clears modal-open class when last modal is closed', async () => {
			const modal1 = await mountModalComponent();
			const modal2 = await mountModalComponent();
			await modal1.unmount();
			expect( document.body.className ).toEqual( 'wikibase-wbui2025-modal-open' );
			await modal2.unmount();
			expect( document.body.className ).toEqual( '' );
		} );
	} );
} );
