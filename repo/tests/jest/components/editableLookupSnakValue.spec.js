jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/api/editEntity.js',
	() => ( {
		renderSnakValueText: jest.fn()
	} )
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const wbui2025 = require( 'wikibase.wbui2025.lib' );
wbui2025.api = {
	searchTabularData: jest.fn( () => Promise.resolve( { query: { search: [] } } ) ),
	searchGeoShapes: jest.fn( () => Promise.resolve( { query: { search: [] } } ) ),
	transformSearchResults: jest.fn( ( results ) => results )
};
wbui2025.store = Object.assign( wbui2025.store, {
	snakValueStrategyFactory: {
		searchByDatatype: jest.fn( () => Promise.resolve( { query: { search: [] } } ) ),
		searchByDatatypeDebounced: jest.fn( () => Promise.resolve( { query: { search: [] } } ) )
	}
} );

const { renderSnakValueText: mockRenderSnakValueText } = require( '../../../resources/wikibase.wbui2025/api/editEntity.js' );
const editableLookupSnakValueComponent = require( '../../../resources/wikibase.wbui2025/components/editableLookupSnakValue.vue' );
const { CdxLookup } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const { storeWithStatements } = require( '../piniaHelpers.js' );
const { useEditStatementsStore, useEditStatementStore } = require( '../../../resources/wikibase.wbui2025/store/editStatementsStore.js' );

describe( 'wikibase.wbui2025.editableLookupSnakValue', () => {
	it( 'defines component', async () => {
		expect( typeof editableLookupSnakValueComponent ).toBe( 'object' );
		expect( editableLookupSnakValueComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditableLookupSnakValue' );
	} );

	describe( 'item datatype', () => {
		let wrapper, lookup;

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

			wrapper = await mount( editableLookupSnakValueComponent, {
				props: {
					propertyId: testPropertyId,
					snakKey: editStatementStore.mainSnakKey
				},
				global: {
					plugins: [ testingPinia ]
				}
			} );

			lookup = wrapper.findComponent( CdxLookup );
		} );

		it( 'should set the text-input to the current snak value', async () => {
			expect( lookup.props( 'inputValue' ) ).toBe( 'Some Entity Label' );
		} );

		it( 'indicates an error when the input changes and nothing is selected', async () => {
			expect( wrapper.find( '.cdx-lookup' ).classes() ).not.toContain( 'cdx-text-input--status-error' );
			lookup.vm.$emit( 'blur' );
			await wrapper.vm.onUpdateInputValue( 'foo' );
			expect( wrapper.find( '.cdx-lookup' ).classes() ).toContain( 'cdx-text-input--status-error' );
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

			wrapper = await mount( editableLookupSnakValueComponent, {
				props: {
					propertyId: testPropertyId,
					snakKey: editStatementStore.mainSnakKey
				},
				global: {
					plugins: [ testingPinia ]
				}
			} );

			lookup = wrapper.findComponent( CdxLookup );
		} );

		it( 'renders cdx-lookup instead of cdx-text-input for tabular-data', async () => {
			await wrapper.vm.$nextTick();
			lookup = wrapper.findComponent( CdxLookup );
			expect( lookup.exists() ).toBe( true );
		} );

		it( 'has proper menu config', () => {
			expect( wrapper.vm.menuConfig ).toEqual( { visibleItemLimit: 6 } );
		} );

		it( 'calls searchByDatatypeDebounced when input value changes', async () => {
			await wrapper.vm.onUpdateInputValue( 'Test' );
			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatypeDebounced ).toHaveBeenCalledWith( 'tabular-data', 'Test', 0 );
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

			wrapper = await mount( editableLookupSnakValueComponent, {
				props: {
					propertyId: testPropertyId,
					snakKey: editStatementStore.mainSnakKey
				},
				global: {
					plugins: [
						testingPinia
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

		it( 'calls searchByDatatypeDebounced with geo-shape when input value changes', async () => {
			await wrapper.vm.onUpdateInputValue( 'Region' );
			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatypeDebounced ).toHaveBeenCalledWith( 'geo-shape', 'Region', 0 );
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
			editStatementsStore.initializeFromStatementStore( [ testStatement.id ], testPropertyId );
			const editStatementStore = useEditStatementStore( testStatementId )();

			wrapper = await mount( editableLookupSnakValueComponent, {
				props: {
					propertyId: testPropertyId,
					snakKey: editStatementStore.mainSnakKey
				},
				global: {
					plugins: [ testingPinia ]
				}
			} );
		} );

		it( 'calls searchByDatatype with offset on load more', async () => {
			wrapper.vm.lookupInputValue = 'Test';
			wrapper.vm.lookupMenuItems = [ { label: 'Item1', value: 'Item1' } ];

			await wrapper.vm.onLoadMore();

			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatype ).toHaveBeenCalledWith( 'tabular-data', 'Test', 1 );
		} );

		it( 'does not call API if inputValue is empty', async () => {
			wbui2025.store.snakValueStrategyFactory.searchByDatatype.mockClear();
			wbui2025.store.snakValueStrategyFactory.searchByDatatypeDebounced.mockClear();
			wrapper.vm.lookupInputValue = '';

			await wrapper.vm.onLoadMore();

			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatype ).not.toHaveBeenCalled();
			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatypeDebounced ).not.toHaveBeenCalled();
		} );

		it( 'appends new results to existing menu items', async () => {
			const existingItems = [ { label: 'Item1', value: 'Item1' } ];
			const newResults = [ { description: '', label: 'Item2', value: 'Item2' } ];

			wrapper.vm.lookupInputValue = 'Test';
			wrapper.vm.lookupMenuItems = [ ...existingItems ];

			wbui2025.store.snakValueStrategyFactory.searchByDatatype.mockResolvedValue( {
				query: {
					search: [ { title: 'File:Item2' } ]
				}
			} );
			wbui2025.api.transformSearchResults.mockReturnValue( newResults );

			await wrapper.vm.onLoadMore();

			expect( wrapper.vm.lookupMenuItems ).toEqual( [ ...existingItems, ...newResults ] );
		} );
	} );
} );
