jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/icons.json',
	() => ( { cdxIconAdd: 'add', cdxIconCheck: 'check', cdxIconClose: 'close' } ),
	{ virtual: true }
);

const addStatementButtonComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.addStatementButton.vue' );
const propertySelectorComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.propertySelector.vue' );
const { CdxButton } = require( '../../codex.js' );
const { mount } = require( '@vue/test-utils' );

describe( 'wikibase.wbui2025.references', () => {
	it( 'defines component', async () => {
		expect( typeof addStatementButtonComponent ).toBe( 'object' );
		expect( addStatementButtonComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025AddStatementButton' );
	} );

	mw.config = {
		get: () => 'en'
	};

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
			let propertySelector = wrapper.findComponent( propertySelectorComponent );
			expect( propertySelector.exists() ).toBe( false );
			await addButton.vm.$emit( 'click' );
			propertySelector = wrapper.findComponent( propertySelectorComponent );
			expect( propertySelector.exists() ).toBe( true );
		} );
	} );
} );
