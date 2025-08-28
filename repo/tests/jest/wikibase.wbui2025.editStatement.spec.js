jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconAdd: 'add',
		cdxIconTrash: 'trash'
	} ),
	{ virtual: true }
);

const editStatementComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.editStatement.vue' );
const { CdxButton, CdxSelect, CdxTextInput } = require( '../../codex.js' );
const { mount } = require( '@vue/test-utils' );

describe( 'wikibase.wbui2025.editStatement', () => {
	it( 'defines component', async () => {
		expect( typeof editStatementComponent ).toBe( 'object' );
		expect( editStatementComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditStatement' );
	} );

	describe( 'the mounted component', () => {
		let wrapper, addQualifierButton, addReferenceButton, removeButton, textInput, select;
		beforeEach( async () => {
			wrapper = await mount( editStatementComponent, {
				props: {
					valueId: 1
				}
			} );
			const buttons = wrapper.findAllComponents( CdxButton );
			addQualifierButton = buttons[ 0 ];
			addReferenceButton = buttons[ 1 ];
			removeButton = buttons[ 2 ];
			textInput = wrapper.findComponent( CdxTextInput );
			select = wrapper.findComponent( CdxSelect );
		} );

		it( 'mount its child components', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( addQualifierButton.exists() ).toBe( true );
			expect( addReferenceButton.exists() ).toBe( true );
			expect( removeButton.exists() ).toBe( true );
			expect( textInput.exists() ).toBe( true );
			expect( select.exists() ).toBe( true );
		} );

		it( 'emits a remove event when remove is clicked', async () => {
			await removeButton.trigger( 'click' );
			expect( wrapper.emitted() ).toHaveProperty( 'remove' );
			expect( wrapper.emitted( 'remove' ).length ).toBe( 1 );
		} );
	} );
} );
