jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
const ScopedTypeaheadSearch = require( '../../resources/wikibase.vector.scopedtypeaheadsearch/ScopedTypeaheadSearch.vue' );

describe( 'ScopedTypeaheadSearch', () => {

	it( 'defines component', async () => {
		expect( typeof ScopedTypeaheadSearch ).toBe( 'object' );
		expect( ScopedTypeaheadSearch ).toHaveProperty( 'name', 'ScopedTypeaheadSearch' );
	} );

} );
