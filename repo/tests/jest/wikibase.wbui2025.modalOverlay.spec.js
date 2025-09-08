jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);

const modalOverlayComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.modalOverlay.vue' );
const { mount } = require( '@vue/test-utils' );

describe( 'wikibase.wbui2025.modalOverlay', () => {
	it( 'defines component', async () => {
		expect( typeof modalOverlayComponent ).toBe( 'object' );
		expect( modalOverlayComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025ModalOverlay' );
	} );

	describe( 'the mounted component', () => {
		let teleportTarget;
		beforeEach( async () => {
			teleportTarget = document.getElementById( 'mw-teleport-target' );
		} );

		afterEach( () => {
			document.body.outerHTML = '';
		} );

		it( 'mounts inside of mw-teleport-target', async () => {
			await mount( modalOverlayComponent );
			expect( teleportTarget.getElementsByClassName( 'wikibase-wbui2025-modal-overlay' ).length )
				.toEqual( 1 );
		} );

		it( 'can mount multiple modals', async () => {
			await mount( modalOverlayComponent );
			await mount( modalOverlayComponent );
			await mount( modalOverlayComponent );
			expect( teleportTarget.getElementsByClassName( 'wikibase-wbui2025-modal-overlay' ).length )
				.toEqual( 3 );
		} );

		it( 'sets a class on body on mount and removes when closed', async () => {
			expect( document.body.className ).toEqual( '' );
			const modal = await mount( modalOverlayComponent );
			expect( document.body.className ).toEqual( 'wikibase-wbui2025-modal-open' );
			await modal.unmount();
			expect( document.body.className ).toEqual( '' );
		} );

		it( 'only clears modal-open class when last modal is closed', async () => {
			const modal1 = await mount( modalOverlayComponent );
			const modal2 = await mount( modalOverlayComponent );
			await modal1.unmount();
			expect( document.body.className ).toEqual( 'wikibase-wbui2025-modal-open' );
			await modal2.unmount();
			expect( document.body.className ).toEqual( '' );
		} );
	} );
} );
