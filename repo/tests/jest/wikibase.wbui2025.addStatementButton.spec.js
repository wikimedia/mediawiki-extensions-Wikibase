jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/icons.json',
	() => ( { cdxIconAdd: 'add' } ),
	{ virtual: true }
);

const addStatementButtonComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.addStatementButton.vue' );
const { CdxButton } = require( '../../codex.js' );
const { mount } = require( '@vue/test-utils' );

describe( 'wikibase.wbui2025.references', () => {
	it( 'defines component', async () => {
		expect( typeof addStatementButtonComponent ).toBe( 'object' );
		expect( addStatementButtonComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025AddStatementButton' );
	} );

	describe( 'the mounted component', () => {
		let wrapper, addButton;
		beforeEach( async () => {
			wrapper = await mount( addStatementButtonComponent );
			addButton = wrapper.findComponent( CdxButton );
		} );

		it( 'the component and child components mount successfully', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( addButton.exists() ).toBe( true );
		} );

		it( 'sets the initial properties on the CdxButton component', () => {
			expect( addButton.props( 'action' ) ).toBe( 'default' );
			expect( addButton.props( 'weight' ) ).toBe( 'normal' );
		} );

		it( 'shows a property selector on click', async () => {
			await addButton.vm.$emit( 'click' );
			const propertySelector = wrapper.find( '.wikibase-wbui2025-add-statement-button' ).find( 'div' );
			expect( propertySelector.text() ).toBe( 'A property selector' );
		} );
	} );
} );
