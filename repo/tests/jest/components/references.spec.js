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
const referencesComponent = require( '../../../resources/wikibase.wbui2025/components/references.vue' );
const { mount } = require( '@vue/test-utils' );
const { createTestingPinia } = require( '@pinia/testing' );

describe( 'wikibase.wbui2025.references', () => {
	it( 'defines component', async () => {
		expect( typeof referencesComponent ).toBe( 'object' );
		expect( referencesComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025References' );
	} );

	describe( 'the mounted component', () => {
		let wrapper;
		beforeEach( async () => {
			wrapper = await mount( referencesComponent, {
				props: {
					references: [
						{
							snaks: {
								P1: [
									{
										snaktype: 'value',
										property: 'P1',
										hash: '3ab28b81d81a4d2ec1227cf068e09850d6d8b2e3',
										datavalue: { value: 'reference10', type: 'string' },
										datatype: 'string'
									},
									{
										snaktype: 'value',
										property: 'P1',
										hash: '24f38d18cb3c564dc39dc73abc54acaf38194666',
										datavalue: { value: 'reference11', type: 'string' },
										datatype: 'string'
									}
								],
								P2: [
									{
										snaktype: 'value',
										property: 'P2',
										hash: 'ed7b027c838c304a0d455dc0b9c99a75a1ca7751',
										datavalue: { value: 'reference20', type: 'string' },
										datatype: 'string'
									}
								]
							},
							'snaks-order': [ 'P2', 'P1' ]
						},
						{
							snaks: {
								P3: [
									{
										snaktype: 'value',
										property: 'P3',
										hash: '16b2c8d03729480fd64a1c34c40a0aa0b8f7d823',
										datavalue: { value: 'reference30', type: 'string' },
										datatype: 'string'
									}
								]
							},
							'snaks-order': [ 'P3' ]
						}
					]
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
			expect( wrapper.findAll( '.wikibase-wbui2025-clickable' ) ).toHaveLength( 1 );
		} );

		it( 'mounts property name and snak value', () => {
			const propertyNames = wrapper.findAllComponents( propertyNameComponent );
			expect( propertyNames ).toHaveLength( 4 );
			expect( propertyNames[ 0 ].props( 'propertyId' ) ).toBe( 'P2' );
			expect( propertyNames[ 1 ].props( 'propertyId' ) ).toBe( 'P1' );
			expect( propertyNames[ 2 ].props( 'propertyId' ) ).toBe( 'P1' );
			expect( propertyNames[ 3 ].props( 'propertyId' ) ).toBe( 'P3' );
			const snakValues = wrapper.findAllComponents( snakValueComponent );
			expect( snakValues ).toHaveLength( 4 );
			expect( snakValues[ 0 ].props( 'snak' ) )
				.toHaveProperty( [ 'datavalue', 'value' ], 'reference20' );
			expect( snakValues[ 1 ].props( 'snak' ) )
				.toHaveProperty( [ 'datavalue', 'value' ], 'reference10' );
			expect( snakValues[ 2 ].props( 'snak' ) )
				.toHaveProperty( [ 'datavalue', 'value' ], 'reference11' );
			expect( snakValues[ 3 ].props( 'snak' ) )
				.toHaveProperty( [ 'datavalue', 'value' ], 'reference30' );
			// TODO assert that the two references are in separate wrapper elements (T400237)
		} );
	} );
} );
