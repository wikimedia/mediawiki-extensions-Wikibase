jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconAdd: 'add',
		cdxIconArrowPrevious: 'arrowPrevious',
		cdxIconCheck: 'check',
		cdxIconClose: 'close',
		cdxIconTrash: 'trash'
	} ),
	{ virtual: true }
);

const editStatementGroupComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.editStatementGroup.vue' );
const editStatementComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.editStatement.vue' );
const { CdxButton, CdxIcon } = require( '../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const { createTestingPinia } = require( '@pinia/testing' );

describe( 'wikibase.wbui2025.editStatementGroup', () => {
	it( 'defines component', async () => {
		expect( typeof editStatementGroupComponent ).toBe( 'object' );
		expect( editStatementGroupComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditStatementGroup' );
	} );

	describe( 'the mounted component', () => {
		let wrapper, statementForm, addValueButton, closeButton, publishButton, backIcon;
		beforeEach( async () => {
			wrapper = await mount( editStatementGroupComponent, {
				props: {
					propertyId: 'P1'
				},
				global: {
					plugins: [
						createTestingPinia()
					]
				}
			} );
			statementForm = wrapper.findComponent( editStatementComponent );
			const buttons = wrapper.findAllComponents( CdxButton );
			addValueButton = buttons[ buttons.length - 3 ];
			closeButton = buttons[ buttons.length - 2 ];
			publishButton = buttons[ buttons.length - 1 ];
			backIcon = wrapper.findComponent( CdxIcon );
		} );

		it( 'mount its child components', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( statementForm.exists() ).toBe( true );
			expect( addValueButton.exists() ).toBe( true );
			expect( closeButton.exists() ).toBe( true );
			expect( publishButton.exists() ).toBe( true );
			expect( backIcon.exists() ).toBe( true );
		} );

		it( 'emits a hide event when close button is clicked', async () => {
			await closeButton.trigger( 'click' );
			expect( wrapper.emitted() ).toHaveProperty( 'hide' );
			expect( wrapper.emitted( 'hide' ).length ).toBe( 1 );
		} );

		it( 'emits a hide event when back icon is clicked', async () => {
			await backIcon.trigger( 'click' );
			expect( wrapper.emitted() ).toHaveProperty( 'hide' );
			expect( wrapper.emitted( 'hide' ).length ).toBe( 1 );
		} );

		it( 'adds a new value when add value is clicked', async () => {
			expect( wrapper.vm.valueForms.length ).toBe( 1 );
			await addValueButton.trigger( 'click' );
			expect( wrapper.vm.valueForms.length ).toBe( 2 );
		} );

		it( 'removes a value when remove is triggered', async () => {
			expect( wrapper.vm.valueForms.length ).toBe( 1 );
			await statementForm.vm.$emit( 'remove', 0 );
			expect( wrapper.vm.valueForms.length ).toBe( 0 );
		} );
	} );
} );
