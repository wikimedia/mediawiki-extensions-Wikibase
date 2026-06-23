jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/icons.json',
	() => ( { cdxIconAdd: 'add', cdxIconCheck: 'check', cdxIconClose: 'close', cdxIconArrowPrevious: 'arrowPrevious' } ),
	{ virtual: true }
);

Object.defineProperty( window, 'scrollTo', { value: jest.fn(), configurable: true } );

const crypto = require( 'crypto' );

// eslint-disable-next-line no-undef
Object.defineProperty( globalThis, 'wikibase', {
	value: {
		utilities: {
			ClaimGuidGenerator: class {
				constructor( entityId ) {
					this.entityId = entityId;
				}

				newGuid() {
					return this.entityId + '$' + crypto.randomUUID();
				}
			}
		}
	}
} );

const mockConfig = {
	wgUserLanguage: 'en'
};
mw.config = {
	get: jest.fn( ( key ) => mockConfig[ key ] )
};

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();

const addStatementFloatingButtonComponent = require( '../../../resources/wikibase.wbui2025/components/addStatementFloatingButton.vue' );
const addStatementModalComponent = require( '../../../resources/wikibase.wbui2025/components/addStatementModal.vue' );
const propertyLookupComponent = require( '../../../resources/wikibase.wbui2025/components/propertyLookup.vue' );
const { CdxButton } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const { storeWithStatements } = require( '../piniaHelpers.js' );

describe( 'wikibase.wbui2025.addStatementFloatingButton', () => {
	it( 'defines component', async () => {
		expect( typeof addStatementFloatingButtonComponent ).toBe( 'object' );
		expect( addStatementFloatingButtonComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025AddStatementFloatingButton' );
	} );

	describe( 'the mounted component', () => {
		let wrapper, addButton, addStatementModal, propertyLookup, publishButton;
		beforeEach( async () => {
			wrapper = await mount( addStatementFloatingButtonComponent, {
				props: {
					entityId: 'Q123'
				},
				global: {
					plugins: [
						storeWithStatements( [] )
					],
					disableTeleport: true
				}
			} );
			addButton = wrapper.findComponent( CdxButton );
			addStatementModal = wrapper.findComponent( addStatementModalComponent );
			propertyLookup = wrapper.findComponent( propertyLookupComponent );
		} );

		it( 'the component and child components mount successfully', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( addButton.exists() ).toBe( false );
			expect( propertyLookup.exists() ).toBe( false );
			expect( addStatementModal.exists() ).toBe( false );
		} );

		it( 'replaces the disc with an add statement button on click', async () => {
			expect( addButton.exists() ).toBe( false );
			jest.spyOn( wrapper.vm, 'elementOnScreen' ).mockReturnValue( false );
			wrapper.vm.scrollPositionUpdated();
			await wrapper.vm.$nextTick();
			await wrapper.find( '.wikibase-wbui2025-add-statement-float-disc' ).trigger( 'click' );
			addButton = wrapper.findComponent( CdxButton );
			expect( addButton.exists() ).toBe( true );
		} );

		it( 'the disc can be restored by clicking the close icon', async () => {
			expect( addButton.exists() ).toBe( false );
			jest.spyOn( wrapper.vm, 'elementOnScreen' ).mockReturnValue( false );
			wrapper.vm.scrollPositionUpdated();
			await wrapper.vm.$nextTick();
			await wrapper.find( '.wikibase-wbui2025-add-statement-float-disc' ).trigger( 'click' );
			addButton = wrapper.findComponent( CdxButton );
			expect( wrapper.findAll( '.wikibase-wbui2025-add-statement-float-disc' ) ).toHaveLength( 0 );
			expect( addButton.exists() ).toBe( true );
			await wrapper.find( '.wikibase-wbui2025-add-statement-float-button-close-icon' ).trigger( 'click' );
			await wrapper.vm.$nextTick();
			addButton = wrapper.findComponent( CdxButton );
			expect( addButton.exists() ).toBe( false );
			expect( wrapper.findAll( '.wikibase-wbui2025-add-statement-float-disc' ) ).toHaveLength( 1 );
		} );

		it( 'shows a property lookup on click', async () => {
			expect( propertyLookup.exists() ).toBe( false );
			jest.spyOn( wrapper.vm, 'elementOnScreen' ).mockReturnValue( false );
			wrapper.vm.scrollPositionUpdated();
			await wrapper.vm.$nextTick();
			await wrapper.find( '.wikibase-wbui2025-add-statement-float-disc' ).trigger( 'click' );
			addButton = wrapper.findComponent( CdxButton );
			await addButton.vm.$emit( 'click' );
			propertyLookup = wrapper.findComponent( propertyLookupComponent );
			publishButton = wrapper.findAllComponents( CdxButton )[ 2 ];
			expect( propertyLookup.exists() ).toBe( true );
			expect( publishButton.props( 'weight' ) ).toBe( 'primary' );
		} );

	} );
} );
