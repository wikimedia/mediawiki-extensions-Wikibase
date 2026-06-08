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

Object.defineProperty( window, 'scrollTo', { value: jest.fn(), configurable: true } );

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

		it( 'saves scroll position on open and restores it on close', async () => {
			Object.defineProperty( window, 'scrollY', { value: 400, configurable: true } );

			const modal = await mountModalComponent();
			expect( document.body.style.top ).toBe( '-400px' );

			await modal.unmount();
			expect( document.body.style.top ).toBe( '' );
			expect( window.scrollTo ).toHaveBeenCalledWith( 0, 400 );
		} );

		it( 'does not save scroll position again when a second modal opens', async () => {
			Object.defineProperty( window, 'scrollY', { value: 200, configurable: true } );
			const scrollToSpy = jest.spyOn( window, 'scrollTo' );

			const modal1 = await mountModalComponent();
			expect( document.body.style.top ).toBe( '-200px' );

			Object.defineProperty( window, 'scrollY', { value: 0, configurable: true } );
			const modal2 = await mountModalComponent();
			expect( document.body.style.top ).toBe( '-200px' );

			await modal2.unmount();
			expect( scrollToSpy ).not.toHaveBeenCalled();

			await modal1.unmount();
			expect( scrollToSpy ).toHaveBeenCalledWith( 0, 200 );
		} );

		it( 'closes modal on browser back', async () => {
			const modal = await mountModalComponent();
			window.dispatchEvent( new PopStateEvent( 'popstate' ) );
			expect( modal.emitted( 'hide' ) ).toBeTruthy();
		} );

		it( 'only closes the topmost modal on browser back', async () => {
			const modal1 = await mountModalComponent();
			const modal2 = await mountModalComponent();

			window.dispatchEvent( new PopStateEvent( 'popstate' ) );

			expect( modal1.emitted( 'hide' ) ).toBeFalsy();
			expect( modal2.emitted( 'hide' ) ).toBeTruthy();
		} );

		it( 'uses browser history when closed via UI', async () => {
			const backSpy = jest.spyOn( window.history, 'back' ).mockImplementation( () => {} );
			const modal = await mountModalComponent();
			await modal.vm.requestHide();
			expect( backSpy ).toHaveBeenCalled();
		} );
	} );
} );
