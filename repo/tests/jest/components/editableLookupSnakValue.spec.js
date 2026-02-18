jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconTrash: 'trash'
	} ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/api/editEntity.js',
	() => ( {
		renderSnakValueText: jest.fn(),
		parseValue: jest.fn( () => Promise.resolve( {} ) )
	} )
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const wbui2025 = require( 'wikibase.wbui2025.lib' );
wbui2025.api = Object.assign( wbui2025.api, {
	searchTabularData: jest.fn( () => Promise.resolve( { query: { search: [] } } ) ),
	searchGeoShapes: jest.fn( () => Promise.resolve( { query: { search: [] } } ) ),
	transformSearchResults: jest.fn( ( results ) => results )
} );
Object.assign( wbui2025.store.snakValueStrategyFactory, {
	searchByDatatype: jest.fn( () => Promise.resolve( [] ) ),
	searchByDatatypeDebounced: jest.fn( () => Promise.resolve( [] ) )
} );

const { renderSnakValueText: mockRenderSnakValueText } = require( '../../../resources/wikibase.wbui2025/api/editEntity.js' );
const editableLookupSnakValueComponent = require( '../../../resources/wikibase.wbui2025/components/editableLookupSnakValue.vue' );
const apiItemLookupComponent = require( '../../../resources/wikibase.wbui2025/components/apiItemLookup.vue' );
const { CdxLookup } = require( '../../../codex.js' );
const { mount, flushPromises } = require( '@vue/test-utils' );
const { storeWithStatements } = require( '../piniaHelpers.js' );
const { useEditStatementsStore, useEditStatementStore } = require( '../../../resources/wikibase.wbui2025/store/editStatementsStore.js' );

describe( 'wikibase.wbui2025.editableLookupSnakValue', () => {
	it( 'defines component', async () => {
		expect( typeof editableLookupSnakValueComponent ).toBe( 'object' );
		expect( editableLookupSnakValueComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditableLookupSnakValue' );
	} );

	describe( 'item datatype', () => {
		let outerWrapper, innerWrapper, lookup;

		beforeEach( async () => {
			jest.mock(
				'../../../resources/wikibase.wbui2025/api/editEntity.js',
				() => require( '../../../resources/wikibase.wbui2025/api/editEntity.js' )
			);
			const testPropertyId = 'P1';
			const testStatementId = 'Q1$entity-statement-id';
			const testStatement = {
				id: testStatementId,
				mainsnak: {
					snaktype: 'value',
					datavalue: {
						value: { id: 'Q1', 'numeric-id': 1, 'entity-type': 'item' },
						type: 'wikibase-entity-id'
					},
					datatype: 'wikibase-item'
				},
				rank: 'normal',
				'qualifiers-order': [],
				qualifiers: {},
				references: []
			};
			const testingPinia = storeWithStatements( [ testStatement ] );

			mockRenderSnakValueText.mockResolvedValueOnce( 'Some Entity Label' );
			const editStatementsStore = useEditStatementsStore();
			await editStatementsStore.initializeFromStatementStore( [ testStatement.id ], testPropertyId );
			const editStatementStore = useEditStatementStore( testStatementId )();

			outerWrapper = await mount( editableLookupSnakValueComponent, {
				props: {
					snakKey: editStatementStore.mainSnakKey,
					disabled: false
				},
				global: {
					plugins: [ testingPinia ]
				}
			} );

			innerWrapper = outerWrapper.findComponent( apiItemLookupComponent );
			lookup = innerWrapper.findComponent( CdxLookup );
		} );

		it( 'should set the text-input to the current snak value', async () => {
			expect( lookup.props( 'inputValue' ) ).toBe( 'Some Entity Label' );
		} );

		it( 'indicates an error when the input changes and nothing is selected', async () => {
			expect( innerWrapper.find( '.cdx-lookup' ).classes() ).not.toContain( 'cdx-text-input--status-error' );
			lookup.vm.$emit( 'blur' );
			innerWrapper.vm.lookupSelection = null;
			innerWrapper.vm.lookupInputValue = 'foo';
			await flushPromises();
			expect( innerWrapper.find( '.cdx-lookup' ).classes() ).toContain( 'cdx-text-input--status-error' );
		} );

		it( 'should set autocapitalize to "off" for the text input', async () => {
			expect( lookup.find( 'input' ).element.getAttribute( 'autocapitalize' ) ).toBe( 'off' );
		} );

		it( 'passes null (not undefined) to CdxLookup when value is empty', async () => {
			// Simulate a new empty statement
			const emptyStatementId = 'Q1$empty-statement-id';
			const emptyStatement = {
				id: emptyStatementId,
				mainsnak: {
					snaktype: 'value',
					datavalue: {
						value: '',
						type: 'string' // New statements start as string type before being set to item
					},
					datatype: 'wikibase-item'
				},
				rank: 'normal',
				'qualifiers-order': [],
				qualifiers: {},
				references: []
			};
			const testingPinia = storeWithStatements( [ emptyStatement ] );
			const editStatementsStore = useEditStatementsStore();
			await editStatementsStore.initializeFromStatementStore( [ emptyStatement.id ], 'P1' );
			const editStatementStore = useEditStatementStore( emptyStatementId )();

			const wrapper = await mount( editableLookupSnakValueComponent, {
				props: {
					snakKey: editStatementStore.mainSnakKey,
					disabled: false
				},
				global: { plugins: [ testingPinia ] }
			} );

			const innerLookup = wrapper.findComponent( CdxLookup );
			// This matches the "selected" prop of CdxLookup (v-model:selected="lookupSelection")
			// We want to ensure it is null, not undefined, to avoid Vue prop validation warning.
			expect( innerLookup.props( 'selected' ) ).toBeNull();
		} );
	} );

	describe( 'tabular-data datatype', () => {
		let outerWrapper, innerWrapper, lookup;

		beforeEach( async () => {
			const testPropertyId = 'P1';
			const testStatementId = 'Q1$tabular-data-statement-id';
			const testStatement = {
				id: testStatementId,
				mainsnak: {
					snaktype: 'value',
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
			const testingPinia = storeWithStatements( [ testStatement ] );
			const editStatementsStore = useEditStatementsStore();
			await editStatementsStore.initializeFromStatementStore( [ testStatement.id ], testPropertyId );
			const editStatementStore = useEditStatementStore( testStatementId )();

			outerWrapper = await mount( editableLookupSnakValueComponent, {
				props: {
					snakKey: editStatementStore.mainSnakKey,
					disabled: false
				},
				global: {
					plugins: [ testingPinia ]
				}
			} );

			innerWrapper = outerWrapper.findComponent( apiItemLookupComponent );
			lookup = innerWrapper.findComponent( CdxLookup );
		} );

		it( 'renders cdx-lookup instead of cdx-text-input for tabular-data', async () => {
			await innerWrapper.vm.$nextTick();
			lookup = innerWrapper.findComponent( CdxLookup );
			expect( lookup.exists() ).toBe( true );
		} );

		it( 'has proper menu config', () => {
			expect( innerWrapper.vm.menuConfig ).toEqual( { visibleItemLimit: 6 } );
		} );

		it( 'calls searchByDatatypeDebounced when input value changes', async () => {
			innerWrapper.vm.lookupInputValue = 'Test';
			await flushPromises();
			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatypeDebounced ).toHaveBeenCalledWith( 'tabular-data', 'Test', 0 );
		} );

		it( 'clears menu items when input is empty', async () => {
			innerWrapper.vm.lookupMenuItems = [ { label: 'Test', value: 'Test' } ];
			innerWrapper.vm.lookupInputValue = '';
			await flushPromises();
			expect( innerWrapper.vm.lookupMenuItems ).toEqual( [] );
		} );

		it( 'should set autocapitalize to "off" for the text input', async () => {
			expect( lookup.find( 'input' ).element.getAttribute( 'autocapitalize' ) ).toBe( 'off' );
		} );
	} );

	describe( 'geo-shape datatype', () => {
		let outerWrapper, innerWrapper, lookup;

		beforeEach( async () => {
			const testPropertyId = 'P1';
			const testStatementId = 'Q1$geo-shape-statement-id';
			const testStatement = {
				id: testStatementId,
				mainsnak: {
					snaktype: 'value',
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

			const testingPinia = storeWithStatements( [ testStatement ] );
			const editStatementsStore = useEditStatementsStore();
			await editStatementsStore.initializeFromStatementStore( [ testStatement.id ], testPropertyId );
			const editStatementStore = useEditStatementStore( testStatementId )();

			outerWrapper = await mount( editableLookupSnakValueComponent, {
				props: {
					snakKey: editStatementStore.mainSnakKey,
					disabled: false
				},
				global: {
					plugins: [
						testingPinia
					]
				}
			} );

			innerWrapper = outerWrapper.findComponent( apiItemLookupComponent );
			lookup = innerWrapper.findComponent( CdxLookup );
		} );

		it( 'renders cdx-lookup instead of cdx-text-input for geo-shape', async () => {
			await innerWrapper.vm.$nextTick();
			lookup = innerWrapper.findComponent( CdxLookup );
			expect( lookup.exists() ).toBe( true );
		} );

		it( 'calls searchByDatatypeDebounced with geo-shape when input value changes', async () => {
			innerWrapper.vm.lookupInputValue = 'Region';
			await flushPromises();
			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatypeDebounced ).toHaveBeenCalledWith( 'geo-shape', 'Region', 0 );
		} );

		it( 'should set autocapitalize to "off" for the text input', async () => {
			expect( lookup.find( 'input' ).element.getAttribute( 'autocapitalize' ) ).toBe( 'off' );
		} );
	} );

	describe( 'commonsMedia datatype', () => {
		let outerWrapper, innerWrapper, lookup;

		beforeEach( async () => {
			const testPropertyId = 'P1';
			const testStatementId = 'Q1$commonsMedia-statement-id';
			const testStatement = {
				id: testStatementId,
				mainsnak: {
					snaktype: 'value',
					datavalue: {
						value: 'Douglas adams portrait.jpg',
						type: 'string'
					},
					datatype: 'commonsMedia'
				},
				rank: 'normal',
				'qualifiers-order': [],
				qualifiers: {},
				references: []
			};

			const testingPinia = storeWithStatements( [ testStatement ] );
			const editStatementsStore = useEditStatementsStore();
			await editStatementsStore.initializeFromStatementStore( [ testStatement.id ], testPropertyId );
			const editStatementStore = useEditStatementStore( testStatementId )();

			outerWrapper = await mount( editableLookupSnakValueComponent, {
				props: {
					snakKey: editStatementStore.mainSnakKey,
					disabled: false
				},
				global: {
					plugins: [
						testingPinia
					]
				}
			} );

			innerWrapper = outerWrapper.findComponent( apiItemLookupComponent );
			lookup = innerWrapper.findComponent( CdxLookup );
		} );

		it( 'renders cdx-lookup instead of cdx-text-input for commonsMedia', async () => {
			await innerWrapper.vm.$nextTick();
			lookup = innerWrapper.findComponent( CdxLookup );
			expect( lookup.exists() ).toBe( true );
		} );

		it( 'calls searchByDatatypeDebounced with commonsMedia when input value changes', async () => {
			innerWrapper.vm.lookupInputValue = 'Test.jpg';
			await flushPromises();
			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatypeDebounced ).toHaveBeenCalledWith( 'commonsMedia', 'Test.jpg', 0 );
		} );

		it( 'should set autocapitalize to "off" for the text input', async () => {
			expect( lookup.find( 'input' ).element.getAttribute( 'autocapitalize' ) ).toBe( 'off' );
		} );
	} );

	describe( 'lookup load more functionality', () => {
		let outerWrapper, innerWrapper;

		beforeEach( async () => {
			const testPropertyId = 'P1';
			const testStatementId = 'Q1$tabular-data-statement-id';
			const testStatement = {
				id: testStatementId,
				mainsnak: {
					snaktype: 'value',
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

			const testingPinia = storeWithStatements( [ testStatement ] );
			const editStatementsStore = useEditStatementsStore();
			await editStatementsStore.initializeFromStatementStore( [ testStatement.id ], testPropertyId );
			const editStatementStore = useEditStatementStore( testStatementId )();

			outerWrapper = await mount( editableLookupSnakValueComponent, {
				props: {
					snakKey: editStatementStore.mainSnakKey,
					disabled: false
				},
				global: {
					plugins: [ testingPinia ]
				}
			} );
			innerWrapper = outerWrapper.findComponent( apiItemLookupComponent );
		} );

		it( 'calls searchByDatatype with offset on load more', async () => {
			innerWrapper.vm.lookupInputValue = 'Test';
			innerWrapper.vm.lookupMenuItems = [ { label: 'Item1', value: 'Item1' } ];

			await innerWrapper.vm.onLoadMore();

			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatype ).toHaveBeenCalledWith( 'tabular-data', 'Test', 1 );
		} );

		it( 'does not call API if inputValue is empty', async () => {
			wbui2025.store.snakValueStrategyFactory.searchByDatatype.mockClear();
			wbui2025.store.snakValueStrategyFactory.searchByDatatypeDebounced.mockClear();
			innerWrapper.vm.lookupInputValue = '';

			await innerWrapper.vm.onLoadMore();

			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatype ).not.toHaveBeenCalled();
			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatypeDebounced ).not.toHaveBeenCalled();
		} );

		it( 'appends new results to existing menu items', async () => {
			const existingItems = [ { label: 'Item1', value: 'Item1' } ];
			const newResults = [ { description: '', label: 'Item2', value: 'Item2' } ];

			innerWrapper.vm.lookupInputValue = 'Test';
			innerWrapper.vm.lookupSource.lookupMenuItems.value = [ ...existingItems ];

			jest.spyOn( innerWrapper.vm.lookupSource, 'fetchLookupResults' )
				.mockResolvedValue( newResults );

			wbui2025.store.snakValueStrategyFactory.searchByDatatype.mockResolvedValue( {
				query: {
					search: [ { title: 'File:Item2' } ]
				}
			} );
			wbui2025.api.transformSearchResults.mockReturnValue( newResults );

			await innerWrapper.vm.onLoadMore();

			expect( innerWrapper.vm.lookupSource.lookupMenuItems.value ).toEqual( [ ...existingItems, ...newResults ] );
		} );
	} );
} );
