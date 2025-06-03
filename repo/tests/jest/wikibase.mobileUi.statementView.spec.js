jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);

const statementView = require( '../../resources/wikibase.mobileUi/wikibase.mobileUi.statementView.vue' );
const { mount } = require( '@vue/test-utils' );

describe( 'wikibase.mobileUi.statementView', () => {
	it( 'defines component', async () => {
		expect( typeof statementView ).toBe( 'object' );
		expect( statementView ).toHaveProperty( 'name', 'WikibaseMexStatement' );
	} );

	describe( 'the mounted component', () => {
		let wrapper;
		const mockStatement = {
			mainsnak: {
				snaktype: 'value',
				property: 'P1',
				hash: 'ee6053a6982690ba0f5227d587394d9111eea401',
				datavalue: { value: 'p1', type: 'string' },
				datatype: 'string'
			},
			type: 'statement',
			id: 'Q1$eb7fdbb4-45d1-f59d-bb3b-013935de1085',
			rank: 'normal'
		};
		beforeEach( async () => {
			wrapper = await mount( statementView, {
				props: {
					statement: mockStatement
				}
			} );
		} );

		it( 'the component and child components/elements mount successfully', async () => {
			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findAll( '.wikibase-mex-statement' ) ).toHaveLength( 1 );
		} );

		it( 'sets the right content on claim elements', async () => {
			const statements = wrapper.findAll( '.wikibase-mex-statement' );
			const statement = statements[ 0 ];
			expect( statement.find( '.wikibase-mex-property-name a' ).text() ).toBe( mockStatement.mainsnak.property );
			expect( statement.find( '.wikibase-mex-property-name a' ).element.href ).toContain( `/wiki/Property:${ mockStatement.mainsnak.property }` );

			expect( statement.find( '.wikibase-mex-snak-value' ).text() ).toBe( mockStatement.mainsnak.datavalue.value );
		} );
	} );

} );
