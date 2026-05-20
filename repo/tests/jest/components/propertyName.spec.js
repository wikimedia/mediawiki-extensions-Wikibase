jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const propertyNameComponent = require( '../../../resources/wikibase.wbui2025/components/propertyName.vue' );
const { mount } = require( '@vue/test-utils' );
const { storeWithServerRenderedHtml } = require( '../piniaHelpers.js' );

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

		it( 'does not add --deleted class for a non-deleted property', async () => {
			expect( wrapper.find( '.wikibase-wbui2025-property-name' ).classes() )
				.not.toContain( 'wikibase-wbui2025-property-name--deleted' );
		} );

		it( 'sets data-deleted-property attribute to false for a non-deleted property', async () => {
			const link = wrapper.find( '.wikibase-wbui2025-property-name-link' );
			expect( link.attributes( 'data-deleted-property' ) ).toBe( 'false' );
		} );
	} );

	describe( 'deleted property', () => {
		let deletedWrapper;

		beforeEach( async () => {
			deletedWrapper = await mount( propertyNameComponent, {
				props: { propertyId: 'P1' },
				global: {
					plugins: [
						storeWithServerRenderedHtml(
							{},
							{ P1: '<a href="mock-property-url">P1</a>' },
							[],
							[ 'P1' ]
						)
					]
				}
			} );
		} );

		it( 'adds --deleted modifier class', async () => {
			expect( deletedWrapper.find( '.wikibase-wbui2025-property-name' ).classes() )
				.toContain( 'wikibase-wbui2025-property-name--deleted' );
		} );

		it( 'sets data-deleted-property attribute to true', async () => {
			const link = deletedWrapper.find( '.wikibase-wbui2025-property-name-link' );
			expect( link.attributes( 'data-deleted-property' ) ).toBe( 'true' );
		} );
	} );
} );
