jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconTrash: 'trash'
	} ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/supportedDatatypes.json',
	() => [ 'string', 'tabular-data', 'geo-shape' ],
	{ virtual: true }
);
const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();

const { mount } = require( '@vue/test-utils' );
const { createTestingPinia } = require( '@pinia/testing' );
const WikibaseWbui2025NewReferenceSnak = require( '../../../resources/wikibase.wbui2025/components/newReferenceSnak.vue' );
const WikibaseWbui2025PropertyLookup = require( '../../../resources/wikibase.wbui2025/components/propertyLookup.vue' );
const WikibaseWbui2025EditableSnakValue = require( '../../../resources/wikibase.wbui2025/components/editableSnakValue.vue' );
const { CdxButton } = require( '../../../codex.js' );

describe( 'wikibase.wbui2025.newReferenceSnak', () => {
	it( 'defines component', () => {
		expect( typeof WikibaseWbui2025NewReferenceSnak ).toBe( 'object' );
		expect( WikibaseWbui2025NewReferenceSnak )
			.toHaveProperty( 'name', 'WikibaseWbui2025NewReferenceSnak' );
	} );
	describe( 'the mounted component', () => {
		let wrapper, propertyLookup, editableSnakValue, propertyTrashButton;
		const snakKey = 'snakKey3';
		beforeEach( async () => {
			wrapper = await mount( WikibaseWbui2025NewReferenceSnak, {
				props: { snakKey },
				shallow: false,
				global: {
					stubs: { WikibaseWbui2025EditableSnakValue: true },
					plugins: [
						createTestingPinia()
					]
				}
			} );
			propertyLookup = wrapper.findComponent( WikibaseWbui2025PropertyLookup );
			editableSnakValue = wrapper.findComponent( WikibaseWbui2025EditableSnakValue );

			// Note: Since the WikibaseWbui2025EditableSnakValue component is stubbed, we can safely
			// assume that the only button found by the next line is the one we intend.
			propertyTrashButton = wrapper.findComponent( CdxButton );
		} );

		it( 'mounts successfully with its child components', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( propertyLookup.exists() ).toBe( true );
			expect( editableSnakValue.exists() ).toBe( true );
		} );

		it( 'sets the right properties on the child components', () => {
			expect( editableSnakValue.props( 'snakKey' ) ).toEqual( snakKey );
			expect( editableSnakValue.props( 'disabled' ) ).toEqual( true );
		} );

		it( 'emits remove-snak when clicking the remove button next to property lookup', async () => {
			await propertyTrashButton.vm.$emit( 'click' );
			expect( wrapper.emitted( 'remove-snak' ) ).toHaveLength( 1 );
			expect( wrapper.emitted( 'remove-snak' )[ 0 ] ).toEqual( [ 'snakKey3' ] );
		} );

		describe( 'when a property is selected', () => {
			beforeEach( async () => {
				await propertyLookup.vm.$emit( 'update:selected', 'P456', { datatype: 'string' } );
			} );

			it( 'EditableSnakValue is no longer disabled when a property is selected', () => {
				expect( editableSnakValue.props( 'disabled' ) ).toEqual( false );
			} );

			it( 'emits "remove-snak" when EditableSnakValue emits "remove-snak"', async () => {
				await editableSnakValue.vm.$emit( 'remove-snak', [ 'snakKey3' ] );
				expect( wrapper.emitted( 'remove-snak' ) ).toHaveLength( 1 );
				expect( wrapper.emitted( 'remove-snak' )[ 0 ] ).toEqual( [ 'snakKey3' ] );
			} );

			describe( 'and the property is later deselected', () => {
				beforeEach( async () => {
					await propertyLookup.vm.$emit( 'update:selected', null, null );
				} );
				it( 'sets EditableSnakValue to disabled again', () => {
					expect( editableSnakValue.props( 'disabled' ) ).toEqual( true );
				} );
			} );
		} );
	} );
} );
