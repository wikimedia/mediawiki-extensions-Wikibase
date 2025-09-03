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

const editStatementComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.editStatement.vue' );
const { CdxButton, CdxSelect, CdxTextInput } = require( '../../codex.js' );
const { createTestingPinia } = require( '@pinia/testing' );
const { mount } = require( '@vue/test-utils' );
const Wbui2025Qualifiers = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.qualifiers.vue' );
const Wbui2025References = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.references.vue' );

describe( 'wikibase.wbui2025.editStatement', () => {
	it( 'defines component', async () => {
		expect( typeof editStatementComponent ).toBe( 'object' );
		expect( editStatementComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditStatement' );
	} );

	describe( 'the mounted component', () => {
		let wrapper, addQualifierButton, addReferenceButton, removeButton, textInput, select, qualifiers, references;
		beforeEach( async () => {
			wrapper = await mount( editStatementComponent, {
				props: {
					valueId: 1,
					rank: 'normal',
					mainSnak: {
						datavalue: {
							value: 'test value',
							type: 'string'
						}
					},
					statement: {
						mainSnak: {
							datavalue: {
								value: 'test value',
								type: 'string'
							}
						},
						'qualifiers-order': [ 'P1' ],
						qualifiers: {
							P1: [ {
								snaktype: 'value',
								property: 'P1',
								hash: '1a97f9d234d412c3daae7fc5e2a6a8ade8742638',
								datavalue: {
									value: "I'm its qualifier",
									type: 'string'
								},
								datatype: 'string'
							} ]
						},
						references: [ {
							hash: '32c451f202d636407a08953a1754752a000909da',
							snaks: {
								P1: [ {
									snaktype: 'value',
									property: 'P1',
									hash: '8374f86cf4335926633fe80c2adbad3b2865e075',
									datavalue: {
										value: "Ofc it's a string reference",
										type: 'string'
									},
									datatype: 'string'
								} ],
								P2: [ {
									snaktype: 'value',
									property: 'P2',
									hash: '4fd80c9f4a37746f632dbe390417a927f6518668',
									datavalue: {
										value: {
											time: '+1999-00-00T00:00:00Z',
											timezone: 0,
											before: 0,
											after: 0,
											precision: 9,
											calendarmodel: 'http://www.wikidata.org/entity/Q1985727'
										},
										type: 'time'
									},
									datatype: 'time'
								} ]
							},
							'snaks-order': [ 'P1',
								'P2' ]
						},
						{
							hash: '1263ebe0153579e910515f6feb6e2722a07dc38a',
							snaks: {
								P1: [ {
									snaktype: 'value',
									property: 'P1',
									hash: 'ed0ed7ec4e19a81c0b79a828877c1513ec744588',
									datavalue: {
										value: 'Second ref of second q',
										type: 'string'
									},
									datatype: 'string'
								} ],
								P2: [ {
									snaktype: 'value',
									property: 'P2',
									hash: 'e8c1903e44c8dbd58d6d23b8bb1b305195e8e40d',
									datavalue: {
										value: {
											time: '+1881-00-00T00:00:00Z',
											timezone: 0,
											before: 0,
											after: 0,
											precision: 9,
											calendarmodel: 'http://www.wikidata.org/entity/Q1985727'
										}, type: 'time'
									},
									datatype: 'time'
								} ]
							}, 'snaks-order': [ 'P1', 'P2' ]
						} ]
					}
				},
				global: {
					plugins: [
						createTestingPinia()
					]
				}
			} );
			const buttons = wrapper.findAllComponents( CdxButton );
			addQualifierButton = buttons[ 0 ];
			addReferenceButton = buttons[ 1 ];
			removeButton = buttons[ 2 ];
			textInput = wrapper.findComponent( CdxTextInput );
			select = wrapper.findComponent( CdxSelect );
			qualifiers = wrapper.findAllComponents( Wbui2025Qualifiers );
			references = wrapper.findAllComponents( Wbui2025References );
		} );

		it( 'mount its child components', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( addQualifierButton.exists() ).toBe( true );
			expect( addReferenceButton.exists() ).toBe( true );
			expect( removeButton.exists() ).toBe( true );
			expect( textInput.exists() ).toBe( true );
			expect( select.exists() ).toBe( true );
		} );

		it( 'emits a remove event when remove is clicked', async () => {
			await removeButton.trigger( 'click' );
			expect( wrapper.emitted() ).toHaveProperty( 'remove' );
			expect( wrapper.emitted( 'remove' ).length ).toBe( 1 );
		} );

		it( 'loads and shows data correctly', () => {
			expect( textInput.props( 'modelValue' ) ).toBe( 'test value' );
			expect( qualifiers ).toHaveLength( 1 );
			expect( wrapper.findAll( '.wikibase-wbui2025-qualifier' ) ).toHaveLength( 1 );
			expect( references ).toHaveLength( 1 );
			expect( wrapper.findAll( '.wikibase-wbui2025-reference' ) ).toHaveLength( 4 );
		} );
	} );
} );
