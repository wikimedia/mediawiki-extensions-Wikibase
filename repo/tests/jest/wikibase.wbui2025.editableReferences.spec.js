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
jest.mock(
	'../../resources/wikibase.wbui2025/supportedDatatypes.json',
	() => [ 'string', 'tabular-data', 'geo-shape' ],
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/repoSettings.json',
	() => ( {
		tabularDataStorageApiEndpointUrl: 'https://commons.test/w/api.php',
		geoShapeStorageApiEndpointUrl: 'https://commons.test/w/api.php'
	} ),
	{ virtual: true }
);
const { mockLibWbui2025 } = require( './libWbui2025Helpers.js' );
mockLibWbui2025();
const { mount } = require( '@vue/test-utils' );
const Wbui2025EditableReferencesSection = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.editableReferencesSection.vue' );
const Wbui2025EditableReference = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.editableReference.vue' );
const { CdxAccordion } = require( '../../codex.js' );

describe( 'wikibase.wbui2025.editableReferencesSection', () => {
	it( 'defines the component', async () => {
		expect( typeof Wbui2025EditableReferencesSection ).toBe( 'object' );
		expect( Wbui2025EditableReferencesSection )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditableReferencesSection' );
	} );

	describe( 'the mounted component', () => {
		const reference1 = {
			snaks: { P1: [ 'snak1' ] },
			'snaks-order': [ 'P1' ]
		};
		const reference2 = {
			snaks: { P1: [ 'snak2' ], P2: [ 'snak3' ] },
			'snaks-order': [ 'P2', 'P1' ]
		};
		const references = [ reference1, reference2 ];
		let wrapper;
		let accordion;
		let editableReferenceChildren;
		beforeEach( async () => {
			wrapper = await mount( Wbui2025EditableReferencesSection, {
				props: { references },
				shallow: true,
				global: {
					stubs: { CdxAccordion: false }
				}
			} );
			accordion = wrapper.findComponent( CdxAccordion );
			editableReferenceChildren = wrapper.findAllComponents( Wbui2025EditableReference );
		} );

		it( 'mounts successfully with its child components', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( accordion.exists() ).toBe( true );
			expect( editableReferenceChildren ).toHaveLength( 2 );
		} );

		it( 'passes the correct props to child Wbui2025EditableReference components', async () => {
			expect( accordion.props( 'modelValue' ) ).toEqual( false );
			expect( editableReferenceChildren[ 0 ].props() ).toEqual( { reference: reference1 } );
			expect( editableReferenceChildren[ 1 ].props() ).toEqual( { reference: reference2 } );
		} );

		it( 'bubbles up a "remove-reference" event from a child component', async () => {
			await editableReferenceChildren[ 0 ].vm.$emit( 'remove-reference', reference1 );
			expect( wrapper.emitted( 'remove-reference' ) ).toHaveLength( 1 );
			expect( wrapper.emitted( 'remove-reference' )[ 0 ] ).toEqual( [ reference1 ] );
		} );

		it( 'bubbles up a "remove-reference-snak" event from a child component', async () => {
			await editableReferenceChildren[ 1 ].vm.$emit( 'remove-reference-snak', reference2, 'P1', 'snak2' );
			expect( wrapper.emitted( 'remove-reference-snak' ) ).toHaveLength( 1 );
			expect( wrapper.emitted( 'remove-reference-snak' )[ 0 ] ).toEqual( [ reference2, 'P1', 'snak2' ] );
		} );
	} );
} );
