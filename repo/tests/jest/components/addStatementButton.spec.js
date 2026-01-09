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
jest.mock(
	'../../../resources/wikibase.wbui2025/supportedDatatypes.json',
	() => [ 'string', 'tabular-data', 'geo-shape' ],
	{ virtual: true }
);

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

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();

const addStatementButtonComponent = require( '../../../resources/wikibase.wbui2025/components/addStatementButton.vue' );
const propertyLookupComponent = require( '../../../resources/wikibase.wbui2025/components/propertyLookup.vue' );
const { CdxButton, CdxTextInput } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const { storeWithStatements } = require( '../piniaHelpers.js' );

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
		let wrapper, addButton, propertyLookup, publishButton;
		beforeEach( async () => {
			wrapper = await mount( addStatementButtonComponent, {
				props: {
					entityId: 'Q123',
					visible: true,
					sectionKey: 'statements'
				},
				global: {
					plugins: [
						storeWithStatements( [] )
					],
					disableTeleport: true
				}
			} );
			addButton = wrapper.findComponent( CdxButton );
			propertyLookup = wrapper.findComponent( propertyLookupComponent );
		} );

		it( 'the component and child components mount successfully', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( addButton.exists() ).toBe( true );
			expect( propertyLookup.exists() ).toBe( false );
		} );

		it( 'sets the initial properties on the CdxButton component', () => {
			expect( addButton.props( 'action' ) ).toBe( 'progressive' );
			expect( addButton.props( 'weight' ) ).toBe( 'normal' );
		} );

		it( 'shows a property lookup on click', async () => {
			expect( propertyLookup.exists() ).toBe( false );
			await addButton.vm.$emit( 'click' );
			propertyLookup = wrapper.findComponent( propertyLookupComponent );
			publishButton = wrapper.findAllComponents( CdxButton )[ 2 ];
			expect( propertyLookup.exists() ).toBe( true );
			expect( publishButton.props( 'weight' ) ).toBe( 'primary' );
		} );

		describe( 'when a property with string datatype is selected', () => {

			it( 'mounts a text input when a property with string datatype is selected', async () => {
				await addButton.vm.$emit( 'click' );
				propertyLookup = wrapper.findComponent( propertyLookupComponent );
				await propertyLookup.vm.$emit( 'update:selected', 'P23', { datatype: 'string' } );
				// The first CdxTextInput component is a child of Wbui2025PropertyLookup.
				// If the snak value input exists, it is the second one.
				const snakValueInput = wrapper.findAllComponents( CdxTextInput )[ 1 ];
				expect( snakValueInput.exists() ).toBe( true );
			} );

		} );

	} );
} );
