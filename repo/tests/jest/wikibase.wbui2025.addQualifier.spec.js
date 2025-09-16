jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconAdd: 'add',
		cdxIconCheck: 'check',
		cdxIconClose: 'close'
	} ),
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/supportedDatatypes.json',
	() => [ 'string', 'tabular-data', 'geo-shape' ],
	{ virtual: true }
);

const mockConfig = {
	wgUserLanguage: 'en'
};
mw.config = {
	get: jest.fn( ( key ) => mockConfig[ key ] )
};
const { CdxButton, CdxTextInput } = require( '../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const Wbui2025AddQualifier = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.addQualifier.vue' );
const Wbui2025PropertyLookup = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.propertyLookup.vue' );

describe( 'wikibase.wbui2025.addQualifier', () => {
	it( 'defines component', async () => {
		expect( typeof Wbui2025AddQualifier ).toBe( 'object' );
		expect( Wbui2025AddQualifier )
			.toHaveProperty( 'name', 'WikibaseWbui2025AddQualifier' );
	} );

	describe( 'the mounted component', () => {
		mw.Api.prototype.get = jest.fn().mockResolvedValue( {} );

		let wrapper, closeButton, addButton, propertyLookup;
		beforeEach( async () => {
			wrapper = await mount( Wbui2025AddQualifier );
			[ closeButton, addButton ] = wrapper.findAllComponents( CdxButton );
			propertyLookup = wrapper.findComponent( Wbui2025PropertyLookup );
		} );

		it( 'mounts its child components with expected properties', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( closeButton.exists() ).toBe( true );
			expect( addButton.exists() ).toBe( true );
			expect( propertyLookup.exists() ).toBe( true );

			expect( closeButton.props( 'action' ) ).toBe( 'default' );
			expect( closeButton.props( 'weight' ) ).toBe( 'quiet' );
			expect( addButton.props( 'action' ) ).toBe( 'progressive' );
			expect( addButton.props( 'weight' ) ).toBe( 'normal' );
			expect( addButton.isDisabled() ).toBe( true );

			expect( wrapper.findComponent( '.wikibase-wbui2025-add-qualifier-value' ).exists() ).toBe( false );
		} );

		it( 'emits "hide" when the close button is clicked', async () => {
			await closeButton.trigger( 'click' );
			expect( wrapper.emitted( 'hide' ) ).toHaveLength( 1 );
		} );

		describe( 'when a property with string datatype is selected', () => {
			let snakValueInput;

			beforeEach( async () => {
				await propertyLookup.vm.$emit( 'update:selected', 'P23', { datatype: 'string' } );
				// The first CdxTextInput component is a child of Wbui2025PropertyLookup.
				// If the snak value input exists, it is the second one.
				snakValueInput = wrapper.findAllComponents( CdxTextInput )[ 1 ];
			} );

			it( 'mounts a text input when a property with string datatype is selected', async () => {
				expect( snakValueInput.exists() ).toBe( true );
			} );

			describe( 'with a blank snak value', () => {
				beforeEach( async () => {
					await snakValueInput.vm.$emit( 'update:modelValue', ' ' );
				} );

				it( 'the add button is disabled', async () => {
					expect( addButton.isDisabled() ).toBe( true );
				} );

				it( 'does not emit an event when the add button is clicked', async () => {
					await addButton.trigger( 'click' );
					expect( wrapper.emitted() ).toEqual( {} );
				} );
			} );

			describe( 'with a non-blank snak value', () => {
				beforeEach( async () => {
					await snakValueInput.vm.$emit( 'update:modelValue', 'a string value' );
				} );

				it( 'when a snak value is entered, add button becomes active', () => {
					expect( addButton.isDisabled() ).toBe( false );
				} );

				it( 'emits an event when the add button is clicked', async () => {
					await addButton.trigger( 'click' );
					expect( wrapper.emitted( 'add-qualifier' )[ 0 ] ).toEqual( [
						'P23',
						{
							datatype: 'string',
							datavalue: {
								type: 'string',
								value: 'a string value'
							},
							property: 'P23',
							snaktype: 'value'
						}
					] );
				} );
			} );
		} );
	} );
} );
