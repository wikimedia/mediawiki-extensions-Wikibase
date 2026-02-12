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

const { ref } = require( 'vue' );
const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const wbui2025 = require( '../../../resources/wikibase.wbui2025/lib.js' );
const indicatorPopoverVue = require( '../../../resources/wikibase.wbui2025/components/indicatorPopover.vue' );
const Wbui2025Stepper = require( '../../../resources/wikibase.wbui2025/components/stepper.vue' );
const { CdxPopover, CdxButton } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const { createTestingPinia } = require( '@pinia/testing' );

let pinia;

describe( 'wikibase.wbui2025.indicatorPopover', () => {
	describe( 'the mounted component', () => {
		const snakHash = 'ad11db2dbfd7099ea788fc26a68dac40',
			statementId = 'Q1$789eef0c-4108-cdda-1a63-505cdd324564',
			referenceHash = '1e638f52eb8d0d3a9453aa05143fa059657dd9d3';

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

		describe( 'with a single issue on the main snak', () => {
			let wrapper, cdxPopover;
			beforeEach( async () => {
				wbui2025.store.setPopoverContentForMainSnak( statementId, [ {
					iconClass: 'wikibase-wbui2025-icon-edit-small',
					title: 'single issue title',
					bodyHtml: '<p>Popover Content</p>',
					footerHtml: '<p>links placeholder</p>'
				} ] );

				wrapper = await mountIndicatorPopover( { snakHash, statementId } );
				cdxPopover = wrapper.findComponent( CdxPopover );
			} );

			it( 'correctly displays the content', () => {
				expect( wrapper.exists() ).toBeTruthy();
				expect( cdxPopover.exists() ).toBeTruthy();
				expect( cdxPopover.text() ).toContain( 'single issue title' );
				expect( cdxPopover.find( '.wikibase-wbui2025-icon-edit-small' ).exists() ).toBeTruthy();
				expect( cdxPopover.html() ).toContain( '<p>Popover Content</p>' );
				expect( cdxPopover.html() ).toContain( '<p>links placeholder</p>' );
			} );

			it( 'does not include components specific to multiple issues', async () => {
				expect( wrapper.findComponent( Wbui2025Stepper ).exists() ).toBeFalsy();
				// there should just be one button - the close button
				expect( wrapper.findAllComponents( CdxButton ).length ).toBe( 1 );
			} );
		} );

		it( 'displays a single issue on a qualifier', async () => {
			wbui2025.store.setPopoverContentForQualifier( statementId, snakHash, [ {
				iconClass: 'wikibase-wbui2025-icon-edit-small',
				title: 'single issue title',
				bodyHtml: '<p>Popover Content</p>',
				footerHtml: '<p>links placeholder</p>'
			} ] );

			const wrapper = await mountIndicatorPopover( { snakHash, statementId, isQualifier: true } );
			const cdxPopover = wrapper.findComponent( CdxPopover );

			expect( wrapper.exists() ).toBeTruthy();
			expect( cdxPopover.exists() ).toBeTruthy();
			expect( cdxPopover.text() ).toContain( 'single issue title' );
			expect( cdxPopover.find( '.wikibase-wbui2025-icon-edit-small' ).exists() ).toBeTruthy();
			expect( cdxPopover.html() ).toContain( '<p>Popover Content</p>' );
			expect( cdxPopover.html() ).toContain( '<p>links placeholder</p>' );
		} );

		it( 'displays a single issue on a reference', async () => {
			wbui2025.store.setPopoverContentForReferenceSnak( statementId, referenceHash, snakHash, [ {
				iconClass: 'wikibase-wbui2025-icon-edit-small',
				title: 'single issue title',
				bodyHtml: '<p>Popover Content</p>',
				footerHtml: '<p>links placeholder</p>'
			} ] );

			const wrapper = await mountIndicatorPopover( { snakHash, statementId, referenceHash } );
			const cdxPopover = wrapper.findComponent( CdxPopover );

			expect( wrapper.exists() ).toBeTruthy();
			expect( cdxPopover.exists() ).toBeTruthy();
			expect( cdxPopover.text() ).toContain( 'single issue title' );
			expect( cdxPopover.find( '.wikibase-wbui2025-icon-edit-small' ).exists() ).toBeTruthy();
			expect( cdxPopover.html() ).toContain( '<p>Popover Content</p>' );
			expect( cdxPopover.html() ).toContain( '<p>links placeholder</p>' );
		} );

		describe( 'with multiple issues', () => {
			let wrapper, cdxPopover, stepper, prevButton, nextButton;
			const issue1 = {
				iconClass: 'first-icon-class',
				title: 'first title',
				bodyHtml: '<p>text explaining the first issue</p>',
				footerHtml: '<a>help-1</a> | <a>discuss</a>'
			};
			const issue2 = {
				iconClass: 'second-icon-class',
				title: 'second title',
				bodyHtml: '<p>text explaining the second issue</p>',
				footerHtml: '<a>help-2</a> | <a>discuss</a>'
			};
			const issue3 = {
				iconClass: 'third-icon-class',
				title: 'third title',
				bodyHtml: '<p>text explaining the third issue</p>',
				footerHtml: '<a>help-3</a> | <a>discuss</a>'
			};
			beforeEach( async () => {
				wbui2025.store.setPopoverContentForMainSnak(
					statementId,
					[ issue1, issue2, issue3 ]
				);
				wrapper = await mountIndicatorPopover( { snakHash, statementId } );
				cdxPopover = wrapper.findComponent( CdxPopover );
				stepper = wrapper.findComponent( Wbui2025Stepper );
				[ prevButton, nextButton ] = wrapper.findAllComponents( '.wikibase-wbui2025-indicator-popover-multistep-navigation button' );
			} );

			it( 'mounts the child components correctly', () => {
				expect( wrapper.exists() ).toBeTruthy();
				expect( cdxPopover.exists() ).toBeTruthy();
				expect( stepper.exists() ).toBeTruthy();
				expect( stepper.props() ).toEqual( {
					currentStep: 1,
					totalSteps: 3
				} );
				expect( prevButton.exists() ).toBeTruthy();
				expect( nextButton.exists() ).toBeTruthy();
			} );

			it( 'clicking the navigation buttons changes the current step', async () => {
				expect( wrapper.vm.currentIndex ).toEqual( 0 );
				expect( stepper.props( 'currentStep' ) ).toEqual( 1 );

				await nextButton.vm.$emit( 'click' );
				expect( wrapper.vm.currentIndex ).toEqual( 1 );
				expect( stepper.props( 'currentStep' ) ).toEqual( 2 );

				await prevButton.vm.$emit( 'click' );
				expect( wrapper.vm.currentIndex ).toEqual( 0 );
				expect( stepper.props( 'currentStep' ) ).toEqual( 1 );

			} );

			it( 'when on the first step, previous is disabled, next is enabled', async () => {
				await wrapper.setData( { currentIndex: 0 } );
				expect( prevButton.isDisabled() ).toBeTruthy();
				expect( nextButton.isDisabled() ).toBeFalsy();
			} );

			it( 'when on the last step, next is disabled, previous is enabled', async () => {
				await wrapper.setData( { currentIndex: 2 } );
				expect( prevButton.isDisabled() ).toBeFalsy();
				expect( nextButton.isDisabled() ).toBeTruthy();
			} );

			it( 'only displays the current step', async () => {
				await wrapper.setData( { currentIndex: 1 } );
				expect( wrapper.text() ).toContain( 'second title' );
				expect( wrapper.text() ).not.toContain( 'first title' );
				expect( wrapper.text() ).not.toContain( 'third title' );

				expect( wrapper.text() ).toContain( 'help-2' );
				expect( wrapper.text() ).not.toContain( 'help-3' );
				expect( wrapper.text() ).not.toContain( 'help-1' );

				expect( wrapper.html() ).toContain( '<p>text explaining the second issue</p>' );
				expect( wrapper.html() ).not.toContain( '<p>text explaining the first issue</p>' );
				expect( wrapper.html() ).not.toContain( '<p>text explaining the third issue</p>' );
			} );
		} );
	} );

	it( 'defines component', () => {
		expect( typeof indicatorPopoverVue ).toBe( 'object' );
		expect( indicatorPopoverVue ).toHaveProperty( 'name', 'WikibaseWbui2025IndicatorPopover' );
	} );

} );
