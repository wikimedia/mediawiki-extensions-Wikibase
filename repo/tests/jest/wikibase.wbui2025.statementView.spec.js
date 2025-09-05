jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);

const mainSnakComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.mainSnak.vue' );
const qualifiersComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.qualifiers.vue' );
const referencesComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.references.vue' );
const statementViewComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.statementView.vue' );
const { mount } = require( '@vue/test-utils' );
const { createTestingPinia } = require( '@pinia/testing' );

describe( 'wikibase.wbui2025.statementView', () => {
	it( 'defines component', async () => {
		expect( typeof statementViewComponent ).toBe( 'object' );
		expect( statementViewComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025StatementView' );
	} );

	describe( 'the mounted component', () => {
		const globalOptions = function ( initialState ) {
			return {
				plugins: [
					createTestingPinia( { initialState } )
				]
			};
		};

		it( 'mounts successfully with empty default qualifiers and references', () => {
			const wrapper = mount( statementViewComponent, {
				props: {
					statementId: 'Q1$49578930-35cd-4c30-9b61-8f4766cd8dd7'
				},
				global: globalOptions( {
					statements: {
						statements: new Map( [
							[ 'Q1$49578930-35cd-4c30-9b61-8f4766cd8dd7', {
								id: 'Q1$49578930-35cd-4c30-9b61-8f4766cd8dd7',
								mainsnak: { snaktype: 'somevalue' }
							} ]
						] )
					}
				} )
			} );

			expect( wrapper.exists() ).toBe( true );
			expect( wrapper.findAll( '.wikibase-wbui2025-statement-view' ) ).toHaveLength( 1 );
			const qualifiersWrappers = wrapper.findAllComponents( qualifiersComponent );
			expect( qualifiersWrappers ).toHaveLength( 1 );
			expect( qualifiersWrappers[ 0 ].props( 'qualifiers' ) ).toEqual( {} );
			expect( qualifiersWrappers[ 0 ].props( 'qualifiersOrder' ) ).toEqual( [] );
			const referencesWrappers = wrapper.findAllComponents( referencesComponent );
			expect( referencesWrappers ).toHaveLength( 1 );
			expect( referencesWrappers[ 0 ].props( 'references' ) ).toEqual( [] );
		} );

		it( 'mounts main snak, qualifiers and references', () => {
			const mainSnak = {
				snaktype: 'value',
				property: 'P1',
				hash: 'ee6053a6982690ba0f5227d587394d9111eea401',
				datavalue: { value: 'p1', type: 'string' },
				datatype: 'string'
			};
			const qualifiersOrder = [ 'P1' ], qualifiers = {
				P1: [ {
					snaktype: 'value',
					property: 'P1',
					hash: '95a120b4070ea96a26bb3479eab1610ead132571',
					datavalue: { value: 'qualifier10', type: 'string' },
					datatype: 'string'
				} ]
			};
			const references = [ {
				P1: [ {
					snaktype: 'value',
					property: 'P1',
					hash: '3ab28b81d81a4d2ec1227cf068e09850d6d8b2e3',
					datavalue: { value: 'reference10', type: 'string' },
					datatype: 'string'
				} ]
			} ];
			const wrapper = mount( statementViewComponent, {
				props: {
					statementId: 'Q1$330e02ca-b106-4dd5-9d02-8d2578d2b3a2'
				},
				global: globalOptions( {
					statements: {
						statements: new Map( [
							[ 'Q1$330e02ca-b106-4dd5-9d02-8d2578d2b3a2', {
								id: 'Q1$330e02ca-b106-4dd5-9d02-8d2578d2b3a2',
								mainsnak: mainSnak,
								qualifiers,
								'qualifiers-order': qualifiersOrder,
								references
							} ]
						] )
					}
				} )
			} );

			const mainSnakWrappers = wrapper.findAllComponents( mainSnakComponent );
			expect( mainSnakWrappers ).toHaveLength( 1 );
			expect( mainSnakWrappers[ 0 ].props( 'mainSnak' ) ).toEqual( mainSnak );
			const qualifiersWrappers = wrapper.findAllComponents( qualifiersComponent );
			expect( qualifiersWrappers ).toHaveLength( 1 );
			expect( qualifiersWrappers[ 0 ].props( 'qualifiers' ) ).toEqual( qualifiers );
			expect( qualifiersWrappers[ 0 ].props( 'qualifiersOrder' ) ).toEqual( qualifiersOrder );
			const referencesWrappers = wrapper.findAllComponents( referencesComponent );
			expect( referencesWrappers ).toHaveLength( 1 );
			expect( referencesWrappers[ 0 ].props( 'references' ) ).toEqual( references );
		} );
	} );
} );
