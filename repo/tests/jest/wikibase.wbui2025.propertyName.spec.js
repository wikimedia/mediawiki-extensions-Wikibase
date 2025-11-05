jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);

const propertyNameComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.propertyName.vue' );
const { mount } = require( '@vue/test-utils' );
const { storeWithServerRenderedHtml } = require( './piniaHelpers.js' );

describe( 'wikibase.wbui2025.propertyName', () => {
	it( 'defines component', async () => {
		expect( typeof propertyNameComponent ).toBe( 'object' );
		expect( propertyNameComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025PropertyName' );
	} );

	describe( 'the mounted component', () => {
		let wrapper;
		beforeEach( async () => {
			wrapper = await mount( propertyNameComponent, {
				props: {
					propertyId: 'P1'
				},
				global: {
					plugins: [
						storeWithServerRenderedHtml(
							{},
							{ P1: '<a href="mock-property-url">P1</a>' }
						)
					]
				}
			} );
		} );

		it( 'the component and child components/elements mount successfully', async () => {
			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '.wikibase-wbui2025-property-name' ) ).toHaveLength( 1 );
		} );

		it( 'sets the right content on property element', async () => {
			const propertyName = wrapper.find( '.wikibase-wbui2025-property-name' );
			expect( propertyName.find( '.wikibase-wbui2025-property-name-link' )
				.element
				.getAttribute( 'data-property-id' ) )
				.toBe( 'P1' );
			expect( propertyName.find( '.wikibase-wbui2025-property-name-link>a' ).text() ).toBe( 'P1' );
			expect( propertyName.find( '.wikibase-wbui2025-property-name-link>a' ).element.href )
				.toContain( 'mock-property-url' );
		} );
	} );
} );
