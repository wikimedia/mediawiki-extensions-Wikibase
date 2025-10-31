jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconAdd: 'add',
		cdxIconCheck: 'check',
		cdxIconClose: 'close',
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
	'../../resources/wikibase.wbui2025/api/commons.js',
	() => ( {
		searchByDatatype: jest.fn( () => Promise.resolve( { query: { search: [] } } ) ),
		transformSearchResults: jest.fn( ( results ) => results )
	} ),
	{ virtual: true }
);

const languageCode = 'de';
const mockConfig = {
	wgUserLanguage: languageCode
};
mw.config = {
	get: jest.fn( ( key ) => mockConfig[ key ] )
};
const editStatementComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.editStatement.vue' );
const { CdxSelect, CdxTextInput } = require( '../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const Wbui2025AddQualifier = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.addQualifier.vue' );
const Wbui2025EditableQualifiers = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.editableQualifiers.vue' );
const Wbui2025EditableReferencesSection = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.editableReferencesSection.vue' );
const Wbui2025EditableReference = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.editableReference.vue' );
const { storeWithStatements } = require( './piniaHelpers.js' );
const { useEditStatementsStore } = require( '../../resources/wikibase.wbui2025/store/editStatementsStore.js' );

function snakKeysFromReference( reference ) {
	return Object.values( reference.props( 'reference' ).snaks ).reduce(
		( allKeys, propSnaks ) => allKeys.concat( propSnaks )
	);
}

describe( 'wikibase.wbui2025.editStatement', () => {
	it( 'defines component', async () => {
		expect( typeof editStatementComponent ).toBe( 'object' );
		expect( editStatementComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditStatement' );
	} );

	describe( 'the mounted component', () => {
		mw.Api.prototype.get = jest.fn().mockResolvedValue( { result: '<div>html value</div>' } );

		let wrapper, addQualifierButton, addReferenceButton, removeButton, textInput, select, qualifiers, references, referencesSection;
		beforeEach( async () => {
			const testPropertyId = 'P1';
			const testStatementId = 'Q1$f80539f8-4635-4e4d-ae20-41e027e093b9';
			const testStatement = {
				id: testStatementId,
				mainsnak: {
					hash: 'fbf16ff62a0b6cc7f47d92482bc75c7c',
					snaktype: 'value',
					datavalue: {
						value: 'test value',
						type: 'string'
					}
				},
				rank: 'normal',
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
				references: [
					{
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
						'snaks-order': [ 'P1', 'P2' ]
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
					},
					{
						hash: 'e597070a1faf189b52dcc69a9509ef4614dbbf30',
						snaks: {
							P1: [
								{
									snaktype: 'value',
									property: 'P1',
									hash: '75b303a518c166faead3ae3f6d66ba53f781249b',
									datavalue: {
										value: 'single snak reference',
										type: 'string'
									},
									datatype: 'string'
								}
							]
						},
						'snaks-order': [ 'P1' ]
					}
				]
			};
			wrapper = await mount( editStatementComponent, {
				props: {
					propertyId: testPropertyId,
					statementId: testStatementId
				},
				global: {
					plugins: [
						storeWithStatements( [ testStatement ] )
					] }
			} );
			const editStatementsStore = useEditStatementsStore();
			editStatementsStore.initializeFromStatementStore( [ testStatement.id ], testPropertyId );
			await wrapper.vm.$nextTick();
			addQualifierButton = wrapper.findComponent( '.wikibase-wbui2025-add-qualifier-button' );
			addReferenceButton = wrapper.findComponent( '.wikibase-wbui2025-add-reference-button' );
			removeButton = wrapper.findComponent( '.wikibase-wbui2025-remove-value button' );
			textInput = wrapper.findComponent( CdxTextInput );
			select = wrapper.findComponent( CdxSelect );
			qualifiers = wrapper.findAllComponents( Wbui2025EditableQualifiers );
			referencesSection = wrapper.findComponent( Wbui2025EditableReferencesSection );
			references = referencesSection.findAllComponents( Wbui2025EditableReference );
		} );

		it( 'mount its child components', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( addQualifierButton.exists() ).toBe( true );
			expect( addReferenceButton.exists() ).toBe( true );
			expect( removeButton.exists() ).toBe( true );
			expect( textInput.exists() ).toBe( true );
			expect( select.exists() ).toBe( true );
			expect( wrapper.findComponent( Wbui2025AddQualifier ).exists() ).toBe( false );
		} );

		it( 'emits a remove event when remove is clicked', async () => {
			await removeButton.trigger( 'click' );
			expect( wrapper.emitted() ).toHaveProperty( 'remove' );
			expect( wrapper.emitted( 'remove' ).length ).toBe( 1 );
		} );

		it( 'loads and shows data correctly', () => {
			expect( textInput.props( 'modelValue' ) ).toBe( 'test value' );
			expect( qualifiers ).toHaveLength( 1 );
			expect( wrapper.findAll( '.wikibase-wbui2025-edit-qualifier' ) ).toHaveLength( 1 );
			expect( referencesSection.props() ).toEqual( {
				references: [
					expect.objectContaining( { hash: '32c451f202d636407a08953a1754752a000909da' } ),
					expect.objectContaining( { hash: '1263ebe0153579e910515f6feb6e2722a07dc38a' } ),
					expect.objectContaining( { hash: 'e597070a1faf189b52dcc69a9509ef4614dbbf30' } )
				]
			} );
		} );

		it( 'removes a qualifier when "remove-snak-from-property" is emitted', async () => {
			expect( qualifiers[ 0 ].findAll( '.wikibase-wbui2025-edit-qualifier' ) ).toHaveLength( 1 );
			await qualifiers[ 0 ].vm.$emit( 'remove-snak-from-property', 'P1', 1 );
			expect( qualifiers[ 0 ].findAll( '.wikibase-wbui2025-edit-qualifier' ) ).toHaveLength( 0 );
		} );

		it( 'removes a reference when "remove-reference" is emitted', async () => {
			expect( references ).toHaveLength( 3 );
			await referencesSection.vm.$emit( 'remove-reference', references[ 0 ].props( 'reference' ) );
			expect( referencesSection.findAllComponents( Wbui2025EditableReference ) ).toHaveLength( 2 );
		} );

		it( 'removes a snak from a reference when "remove-reference-snak" is emitted', async () => {
			const snakKeys = snakKeysFromReference( references[ 0 ] );
			await referencesSection.vm.$emit( 'remove-reference-snak', references[ 0 ].props( 'reference' ), 'P1', snakKeys[ 0 ] );
			expect( references[ 0 ].props( 'reference' ) ).toEqual( {
				hash: '32c451f202d636407a08953a1754752a000909da',
				snaks: {
					P2: [ snakKeys[ 1 ] ]
				},
				'snaks-order': [ 'P2' ]
			} );
		} );

		it( 'removes the reference when "remove-reference-snak" is emitted for the only snak', async () => {
			const [ snakKey ] = snakKeysFromReference( references[ 2 ] );
			await referencesSection.vm.$emit( 'remove-reference-snak', references[ 2 ].props( 'reference' ), 'P1', snakKey );
			expect( referencesSection.findAllComponents( Wbui2025EditableReference ) ).toHaveLength( 2 );
		} );

		describe( 'add qualifier', () => {
			let addQualifierForm;

			beforeEach( async () => {
				await addQualifierButton.trigger( 'click' );
				addQualifierForm = wrapper.findComponent( Wbui2025AddQualifier );
			} );

			it( 'mounts the add qualifier component when add qualifier is clicked', () => {
				expect( addQualifierForm.exists() ).toBe( true );
			} );

			it( 'hides the form when "hide" is emitted', async () => {
				await addQualifierForm.vm.$emit( 'hide' );
				expect( addQualifierForm.exists() ).toBe( false );
			} );

			it( 'adds the new qualifier and hides the form when "add-qualifier" is emitted', async () => {
				const snakData = {
					snaktype: 'value',
					hash: 'placeholder-hash',
					property: 'P23',
					datavalue: {
						value: 'string value',
						type: 'string'
					},
					datatype: 'string'
				};
				await addQualifierForm.vm.$emit( 'add-qualifier', 'P23', snakData );
				expect( addQualifierForm.exists() ).toBe( false );
				expect( qualifiers[ 0 ].props( 'qualifiers' ) ).toEqual( expect.objectContaining( {
					P23: [ snakData.hash ]
				} ) );
			} );
		} );
	} );
} );
