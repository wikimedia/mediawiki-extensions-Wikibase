jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconClose: 'close'
	} ),
	{ virtual: true }
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const propertyNameComponent = require( '../../../resources/wikibase.wbui2025/components/propertyName.vue' );
const snakValueComponent = require( '../../../resources/wikibase.wbui2025/components/snakValue.vue' );
const qualifiersComponent = require( '../../../resources/wikibase.wbui2025/components/qualifiers.vue' );
const { mount } = require( '@vue/test-utils' );
const { createTestingPinia } = require( '@pinia/testing' );

describe( 'wikibase.wbui2025.qualifiers', () => {
	it( 'defines component', async () => {
		expect( typeof qualifiersComponent ).toBe( 'object' );
		expect( qualifiersComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025Qualifiers' );
	} );

	describe( 'the mounted component', () => {
		let wrapper;
		beforeEach( async () => {
			wrapper = await mount( qualifiersComponent, {
				props: {
					qualifiers: {
						P1: [
							{
								snaktype: 'value',
								property: 'P1',
								hash: '95a120b4070ea96a26bb3479eab1610ead132571',
								datavalue: { value: 'qualifier10', type: 'string' },
								datatype: 'string'
							},
							{
								snaktype: 'value',
								property: 'P1',
								hash: '089ceab3355c0af3734eb28fac5cf8b7dd335b25',
								datavalue: { value: 'qualifier11', type: 'string' },
								datatype: 'string'
							}
						],
						P2: [
							{
								snaktype: 'value',
								property: 'P2',
								hash: '8b1b8328c423a80dfa21af60c46e816a3607904d',
								datavalue: { value: 'qualifier20', type: 'string' },
								datatype: 'string'
							}
						]
					},
					qualifiersOrder: [ 'P2', 'P1' ],
					statementId: 'Q1$789eef0c-4108-cdda-1a63-505cdd324564'
				},
				global: {
					plugins: [
						createTestingPinia()
					]
				}
			} );
		} );

		it( 'mounts successfully', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( wrapper.findAll( '.wikibase-wbui2025-qualifiers' ) ).toHaveLength( 1 );
		} );

		it( 'mounts property name and snak value', () => {
			const propertyNames = wrapper.findAllComponents( propertyNameComponent );
			expect( propertyNames ).toHaveLength( 3 );
			expect( propertyNames[ 0 ].props( 'propertyId' ) ).toBe( 'P2' );
			expect( propertyNames[ 1 ].props( 'propertyId' ) ).toBe( 'P1' );
			expect( propertyNames[ 2 ].props( 'propertyId' ) ).toBe( 'P1' );
			const snakValues = wrapper.findAllComponents( snakValueComponent );
			expect( snakValues ).toHaveLength( 3 );
			expect( snakValues[ 0 ].props( 'snak' ) )
				.toHaveProperty( [ 'datavalue', 'value' ], 'qualifier20' );
			expect( snakValues[ 1 ].props( 'snak' ) )
				.toHaveProperty( [ 'datavalue', 'value' ], 'qualifier10' );
			expect( snakValues[ 2 ].props( 'snak' ) )
				.toHaveProperty( [ 'datavalue', 'value' ], 'qualifier11' );
		} );
	} );
} );
