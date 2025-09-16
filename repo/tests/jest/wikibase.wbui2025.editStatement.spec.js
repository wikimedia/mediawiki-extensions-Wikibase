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
const { CdxButton, CdxLookup, CdxSelect, CdxTextInput } = require( '../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const Wbui2025AddQualifier = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.addQualifier.vue' );
const Wbui2025Qualifiers = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.qualifiers.vue' );
const Wbui2025References = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.references.vue' );
const { storeWithStatements } = require( './piniaHelpers.js' );
const { useEditStatementsStore } = require( '../../resources/wikibase.wbui2025/store/editStatementsStore.js' );

describe( 'wikibase.wbui2025.editStatement', () => {
	it( 'defines component', async () => {
		expect( typeof editStatementComponent ).toBe( 'object' );
		expect( editStatementComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditStatement' );
	} );

	describe( 'the mounted component', () => {
		mw.Api.prototype.get = jest.fn().mockResolvedValue( { result: '<div>html value</div>' } );

		let wrapper, addQualifierButton, addReferenceButton, removeButton, textInput, select, qualifiers, references;
		beforeEach( async () => {
			const testPropertyId = 'P1';
			const testStatementId = 'Q1$f80539f8-4635-4e4d-ae20-41e027e093b9';
			const testStatement = {
				id: testStatementId,
				mainsnak: {
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
				} ]
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
			expect( wrapper.findAll( '.wikibase-wbui2025-qualifier' ) ).toHaveLength( 1 );
			expect( references ).toHaveLength( 1 );
			expect( wrapper.findAll( '.wikibase-wbui2025-reference-snak' ) ).toHaveLength( 4 );
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
					P23: [ snakData ]
				} ) );
			} );
		} );
	} );

	describe( 'tabular-data datatype', () => {
		let wrapper, lookup;

		beforeEach( async () => {
			const testPropertyId = 'P1';
			const testStatementId = 'Q1$tabular-data-statement-id';
			const testStatement = {
				id: testStatementId,
				mainsnak: {
					datavalue: {
						value: 'Data:Example.tab',
						type: 'string'
					},
					datatype: 'tabular-data'
				},
				rank: 'normal',
				'qualifiers-order': [],
				qualifiers: {},
				references: []
			};

			wrapper = await mount( editStatementComponent, {
				props: {
					propertyId: testPropertyId,
					statementId: testStatementId,
					datatype: 'tabular-data'
				},
				global: {
					plugins: [
						storeWithStatements( [ testStatement ] )
					]
				}
			} );

			lookup = wrapper.findComponent( CdxLookup );
		} );

		it( 'renders cdx-lookup instead of cdx-text-input for tabular-data', async () => {
			await wrapper.vm.$nextTick();
			lookup = wrapper.findComponent( CdxLookup );
			expect( lookup.exists() ).toBe( true );
		} );

		it( 'sets isTabularOrGeoShapeDataType to true', () => {
			expect( wrapper.vm.isTabularOrGeoShapeDataType ).toBe( true );
		} );

		it( 'has proper menu config', () => {
			expect( wrapper.vm.menuConfig ).toEqual( { visibleItemLimit: 6 } );
		} );

		it( 'calls searchByDatatype when input value changes', async () => {
			const { searchByDatatype } = require( '../../resources/wikibase.wbui2025/api/commons.js' );
			await wrapper.vm.onUpdateInputValue( 'Test' );
			expect( searchByDatatype ).toHaveBeenCalledWith( 'tabular-data', 'Test', 0 );
		} );

		it( 'clears menu items when input is empty', async () => {
			wrapper.vm.lookupMenuItems = [ { label: 'Test', value: 'Test' } ];
			await wrapper.vm.onUpdateInputValue( '' );
			expect( wrapper.vm.lookupMenuItems ).toEqual( [] );
		} );
	} );

	describe( 'geo-shape datatype', () => {
		let wrapper, lookup;

		beforeEach( async () => {
			const testPropertyId = 'P1';
			const testStatementId = 'Q1$geo-shape-statement-id';
			const testStatement = {
				id: testStatementId,
				mainsnak: {
					datavalue: {
						value: 'Data:Hamburg.map',
						type: 'string'
					},
					datatype: 'geo-shape'
				},
				rank: 'normal',
				'qualifiers-order': [],
				qualifiers: {},
				references: []
			};

			wrapper = await mount( editStatementComponent, {
				props: {
					propertyId: testPropertyId,
					statementId: testStatementId,
					datatype: 'geo-shape'
				},
				global: {
					plugins: [
						storeWithStatements( [ testStatement ] )
					]
				}
			} );

			lookup = wrapper.findComponent( CdxLookup );
		} );

		it( 'renders cdx-lookup instead of cdx-text-input for geo-shape', async () => {
			await wrapper.vm.$nextTick();
			lookup = wrapper.findComponent( CdxLookup );
			expect( lookup.exists() ).toBe( true );
		} );

		it( 'sets isTabularOrGeoShapeDataType to true', () => {
			expect( wrapper.vm.isTabularOrGeoShapeDataType ).toBe( true );
		} );

		it( 'calls searchByDatatype with geo-shape when input value changes', async () => {
			const { searchByDatatype } = require( '../../resources/wikibase.wbui2025/api/commons.js' );
			await wrapper.vm.onUpdateInputValue( 'Region' );
			expect( searchByDatatype ).toHaveBeenCalledWith( 'geo-shape', 'Region', 0 );
		} );
	} );

	describe( 'lookup load more functionality', () => {
		let wrapper;

		beforeEach( async () => {
			const testPropertyId = 'P1';
			const testStatementId = 'Q1$tabular-data-statement-id';
			const testStatement = {
				id: testStatementId,
				mainsnak: {
					datavalue: {
						value: 'Data:Example.tab',
						type: 'string'
					},
					datatype: 'tabular-data'
				},
				rank: 'normal',
				'qualifiers-order': [],
				qualifiers: {},
				references: []
			};

			wrapper = await mount( editStatementComponent, {
				props: {
					propertyId: testPropertyId,
					statementId: testStatementId,
					datatype: 'tabular-data'
				},
				global: {
					plugins: [
						storeWithStatements( [ testStatement ] )
					]
				}
			} );
		} );

		it( 'calls searchByDatatype with offset on load more', async () => {
			const { searchByDatatype } = require( '../../resources/wikibase.wbui2025/api/commons.js' );
			wrapper.vm.lookupInputValue = 'Test';
			wrapper.vm.lookupMenuItems = [ { label: 'Item1', value: 'Item1' } ];

			await wrapper.vm.onLoadMore();

			expect( searchByDatatype ).toHaveBeenCalledWith( 'tabular-data', 'Test', 1 );
		} );

		it( 'does not call API if inputValue is empty', async () => {
			const { searchByDatatype } = require( '../../resources/wikibase.wbui2025/api/commons.js' );
			searchByDatatype.mockClear();
			wrapper.vm.lookupInputValue = '';

			await wrapper.vm.onLoadMore();

			expect( searchByDatatype ).not.toHaveBeenCalled();
		} );

		it( 'appends new results to existing menu items', async () => {
			const { searchByDatatype, transformSearchResults } = require( '../../resources/wikibase.wbui2025/api/commons.js' );
			const existingItems = [ { label: 'Item1', value: 'Item1' } ];
			const newResults = [ { label: 'Item2', value: 'Item2' } ];

			wrapper.vm.lookupInputValue = 'Test';
			wrapper.vm.lookupMenuItems = [ ...existingItems ];

			searchByDatatype.mockResolvedValue( {
				query: {
					search: [ { title: 'File:Item2' } ]
				}
			} );
			transformSearchResults.mockReturnValue( newResults );

			await wrapper.vm.onLoadMore();

			expect( wrapper.vm.lookupMenuItems ).toEqual( [ ...existingItems, ...newResults ] );
		} );
	} );

	describe( 'the mounted component with a novalue statement', () => {
		let wrapper, textInput, noValueSomeValuePlaceholder;
		beforeEach( async () => {
			const testPropertyId = 'P1';
			const testNoValueStatementId = 'Q1$98ce7596-5188-4218-9195-6d9ccdcc82bd';
			const testNoValueStatement = {
				id: testNoValueStatementId,
				mainsnak: {
					snaktype: 'novalue'
				},
				rank: 'normal'
			};
			wrapper = await mount( editStatementComponent, {
				props: {
					propertyId: testPropertyId,
					statementId: testNoValueStatementId
				},
				global: {
					plugins: [
						storeWithStatements( [ testNoValueStatement ] )
					] }
			} );
			const editStatementsStore = useEditStatementsStore();
			editStatementsStore.initializeFromStatementStore( [ testNoValueStatementId ], testPropertyId );
			await wrapper.vm.$nextTick();
			textInput = wrapper.findComponent( CdxTextInput );
			noValueSomeValuePlaceholder = wrapper.find( 'div.wikibase-wbui2025-novalue-somevalue-holder' );
		} );

		it( 'mount its child components', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( textInput.exists() ).toBe( false );
			expect( noValueSomeValuePlaceholder.exists() ).toBe( true );
		} );

		it( 'loads and shows data correctly', () => {
			expect( noValueSomeValuePlaceholder.text() ).toContain( 'wikibase-snakview-variations-novalue-label' );
		} );
	} );

} );
