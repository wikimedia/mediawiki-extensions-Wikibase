jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconAdd: 'add',
		cdxIconTrash: 'trash'
	} ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/supportedDatatypes.json',
	() => [ 'string', 'tabular-data', 'geo-shape' ],
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/repoSettings.json',
	() => ( {
		tabularDataStorageApiEndpointUrl: 'https://commons.test/w/api.php',
		geoShapeStorageApiEndpointUrl: 'https://commons.test/w/api.php'
	} ),
	{ virtual: true }
);
const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const { mount } = require( '@vue/test-utils' );
const Wbui2025EditableReference = require( '../../../resources/wikibase.wbui2025/components/editableReference.vue' );
const Wbui2025EditableSnak = require( '../../../resources/wikibase.wbui2025/components/editableSnak.vue' );
const { CdxButton } = require( '../../../codex.js' );

describe( 'wikibase.wbui2025.editableReference', () => {
	it( 'defines component', async () => {
		expect( typeof Wbui2025EditableReference ).toBe( 'object' );
		expect( Wbui2025EditableReference )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditableReference' );
	} );

	describe( 'the mounted component', () => {
		const reference = {
			snaks: {
				P1: [ 'snak1' ],
				P6: [ 'snak2', 'snak3' ]
			},
			'snaks-order': [ 'P6', 'P1' ]
		};

		let wrapper;
		let editableSnaks;
		let addButton, removeButton;
		beforeEach( async () => {

			wrapper = await mount( Wbui2025EditableReference, {
				props: { reference },
				shallow: true
			} );
			[ addButton, removeButton ] = wrapper.findAllComponents( CdxButton );
			editableSnaks = wrapper.findAllComponents( Wbui2025EditableSnak );
		} );

		it( 'mounts successfully with its child components', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( addButton.exists() ).toBe( true );
			expect( removeButton.exists() ).toBe( true );
			expect( editableSnaks ).toHaveLength( 3 );
		} );

		it( 'passes the correct props to child Wbui2025EditableSnak components', () => {
			expect( editableSnaks[ 0 ].props() ).toEqual( { propertyId: 'P6', snakKey: 'snak2' } );
			expect( editableSnaks[ 1 ].props() ).toEqual( { propertyId: 'P6', snakKey: 'snak3' } );
			expect( editableSnaks[ 2 ].props() ).toEqual( { propertyId: 'P1', snakKey: 'snak1' } );
		} );

		it( 'emits "remove-reference" when clicking the remove button', async () => {
			await removeButton.vm.$emit( 'click' );
			expect( wrapper.emitted( 'remove-reference' ) ).toHaveLength( 1 );
			expect( wrapper.emitted( 'remove-reference' )[ 0 ] ).toEqual( [ reference ] );
		} );

		it( 'emits "remove-reference-snak" when a snak is removed', async () => {
			await editableSnaks[ 1 ].vm.$emit( 'remove-snak-from-property', 'P6', 'snak3' );
			expect( wrapper.emitted( 'remove-reference-snak' ) ).toHaveLength( 1 );
			expect( wrapper.emitted( 'remove-reference-snak' )[ 0 ] ).toEqual(
				[ reference, 'P6', 'snak3' ]
			);
		} );
	} );
} );
