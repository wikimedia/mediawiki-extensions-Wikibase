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
const { CdxButton } = require( '../../../codex.js' );
const modalOverlayComponent = require( '../../../resources/wikibase.wbui2025/components/modalOverlay.vue' );
const copyrightDialogComponent = require( '../../../resources/wikibase.wbui2025/components/copyrightDialog.vue' );
const { mount } = require( '@vue/test-utils' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
wbui2025.copyright.isAccepted = jest.fn();

describe( 'wikibase.wbui2025.modalOverlay', () => {
	it( 'defines component', async () => {
		expect( typeof modalOverlayComponent ).toBe( 'object' );
		expect( modalOverlayComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025ModalOverlay' );
	} );

	async function mountModalComponent() {
		return mount( modalOverlayComponent, {
			global: {
				plugins: [ createTestingPinia() ],
				stubs: { WikibaseWbui2025CopyrightDialog: true }
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

		describe( 'message interactions', () => {
			it( 'clears messages when it opens, errors when it closes', async () => {
				const clearStatusMock = jest.fn();
				const clearErrorMock = jest.fn();
				wbui2025.store.useMessageStore = function () {
					return {
						clearStatusMessages: clearStatusMock,
						clearErrorMessages: clearErrorMock
					};
				};
				expect( document.body.className ).toEqual( '' );
				const modal = await mountModalComponent();
				expect( clearStatusMock ).toHaveBeenCalledTimes( 1 );
				expect( clearErrorMock ).not.toHaveBeenCalled();
				expect( document.body.className ).toEqual( 'wikibase-wbui2025-modal-open' );
				await modal.unmount();
				expect( clearStatusMock ).toHaveBeenCalledTimes( 1 );
				expect( clearErrorMock ).toHaveBeenCalled();
				expect( document.body.className ).toEqual( '' );
			} );
		} );

		describe( 'when copyright acceptance is required to save', () => {
			let wrapper, copyrightDialog, saveButton;

			beforeEach( async () => {
				wrapper = await mountModalComponent();
				await wrapper.setProps( { copyrightAcceptanceRequired: true } );
				copyrightDialog = wrapper.findComponent( copyrightDialogComponent );
				[ , , saveButton ] = wrapper.findAllComponents( CdxButton );
			} );

			it( 'does not immediately show the copyright dialog', () => {
				expect( copyrightDialog.props( 'open' ) ).toBe( false );
			} );

			describe( 'the user has not accepted for all edits', () => {
				beforeEach( () => {
					wbui2025.copyright.isAccepted.mockReturnValue( false );
				} );

				it( 'clicking save shows the dialog', async () => {
					await saveButton.vm.$emit( 'click' );
					expect( copyrightDialog.props( 'open' ) ).toBe( true );
					expect( wrapper.emitted( 'save' ) ).toBeFalsy();
				} );

				describe( 'the dialog is open', () => {
					beforeEach( async () => {
						await wrapper.setData( { showCopyrightDialog: true } );
						expect( copyrightDialog.props( 'open' ) ).toBe( true );
					} );

					it( 'closes the dialog and emits "save" when confirmed', async () => {
						await copyrightDialog.vm.$emit( 'confirm' );
						expect( copyrightDialog.props( 'open' ) ).toBe( false );
						expect( wrapper.emitted( 'save' ) ).toHaveLength( 1 );
					} );

					it( 'closes the dialog and emits nothing when acceptance is cancelled', async () => {
						await copyrightDialog.vm.$emit( 'hide' );
						expect( copyrightDialog.props( 'open' ) ).toBe( false );
						expect( wrapper.emitted() ).toEqual( {} );
					} );
				} );
			} );

			describe( 'the user has already accepted for future edits', () => {
				beforeEach( () => {
					wbui2025.copyright.isAccepted.mockReturnValue( true );
				} );

				it( 'clicking save emits "save" and does not show the dialog', async () => {
					await saveButton.vm.$emit( 'click' );
					expect( copyrightDialog.props( 'open' ) ).toBe( false );
					expect( wrapper.emitted( 'save' ) ).toHaveLength( 1 );
				} );
			} );
		} );
	} );
} );
