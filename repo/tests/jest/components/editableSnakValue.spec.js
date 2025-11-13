jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconAdd: 'add',
		cdxIconCheck: 'check',
		cdxIconClose: 'close',
		cdxIconTrash: 'trash'
	} ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/supportedDatatypes.json',
	() => [ 'string', 'tabular-data', 'geo-shape' ],
	{ virtual: true }
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const languageCode = 'de';
const mockConfig = {
	wgUserLanguage: languageCode
};
mw.config = {
	get: jest.fn( ( key ) => mockConfig[ key ] )
};
wbui2025.api = {
	searchTabularData: jest.fn( () => Promise.resolve( { query: { search: [] } } ) ),
	searchGeoShapes: jest.fn( () => Promise.resolve( { query: { search: [] } } ) ),
	transformSearchResults: jest.fn( ( results ) => results )
};
wbui2025.store = Object.assign( wbui2025.store, {
	snakValueStrategyFactory: {
		searchByDatatype: jest.fn( () => Promise.resolve( { query: { search: [] } } ) )
	}
} );

const editableSnakValueComponent = require( '../../../resources/wikibase.wbui2025/components/editableSnakValue.vue' );
const { CdxLookup, CdxTextInput } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const { storeWithStatements } = require( '../piniaHelpers.js' );
const { useEditStatementsStore, useEditStatementStore } = require( '../../../resources/wikibase.wbui2025/store/editStatementsStore.js' );

describe( 'wikibase.wbui2025.editableSnakValue', () => {
	it( 'defines component', async () => {
		expect( typeof editableSnakValueComponent ).toBe( 'object' );
		expect( editableSnakValueComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditableSnakValue' );
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

			wrapper = await mount( editableSnakValueComponent, {
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

		it( 'sets isLookupDatatype to true', () => {
			expect( wrapper.vm.valueStrategy.isLookupDatatype() ).toBe( true );
		} );

		it( 'has proper menu config', () => {
			expect( wrapper.vm.menuConfig ).toEqual( { visibleItemLimit: 6 } );
		} );

		it( 'calls searchTabularData when input value changes', async () => {
			await wrapper.vm.onUpdateInputValue( 'Test' );
			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatype ).toHaveBeenCalledWith( 'tabular-data', 'Test', 0 );
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

			wrapper = await mount( editableSnakValueComponent, {
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

		it( 'sets isLookupDatatype to true', () => {
			expect( wrapper.vm.valueStrategy.isLookupDatatype() ).toBe( true );
		} );

		it( 'calls searchGeoShapes with geo-shape when input value changes', async () => {
			await wrapper.vm.onUpdateInputValue( 'Region' );
			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatype ).toHaveBeenCalledWith( 'geo-shape', 'Region', 0 );
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

			wrapper = await mount( editableSnakValueComponent, {
				props: {
					propertyId: testPropertyId,
					snakKey: editStatementStore.mainSnakKey
				},
				global: {
					plugins: [ testingPinia ]
				}
			} );
		} );

		it( 'calls searchTabularData with offset on load more', async () => {
			wrapper.vm.lookupInputValue = 'Test';
			wrapper.vm.lookupMenuItems = [ { label: 'Item1', value: 'Item1' } ];

			await wrapper.vm.onLoadMore();

			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatype ).toHaveBeenCalledWith( 'tabular-data', 'Test', 1 );
		} );

		it( 'does not call API if inputValue is empty', async () => {
			wbui2025.store.snakValueStrategyFactory.searchByDatatype.mockClear();
			wrapper.vm.lookupInputValue = '';

			await wrapper.vm.onLoadMore();

			expect( wbui2025.store.snakValueStrategyFactory.searchByDatatype ).not.toHaveBeenCalled();
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

	describe.each(
		[ 'string', 'tabular-data', 'geo-shape' ]
	)( 'the mounted component with %s datatype', ( datatype ) => {
		describe.each(
			[ 'novalue', 'somevalue' ]
		)( 'and %s snaktype', ( snaktype ) => {

			let wrapper, textInput, noValueSomeValuePlaceholder;
			beforeEach( async () => {
				const testPropertyId = 'P1';
				const testNoValueStatementId = 'Q1$98ce7596-5188-4218-9195-6d9ccdcc82bd';
				const testNoValueStatement = {
					id: testNoValueStatementId,
					mainsnak: {
						hash: 'placeholder-hash',
						snaktype,
						datatype
					},
					rank: 'normal'
				};

				const testingPinia = storeWithStatements( [ testNoValueStatement ] );
				const editStatementsStore = useEditStatementsStore();
				await editStatementsStore.initializeFromStatementStore( [ testNoValueStatement.id ], testPropertyId );
				const editStatementStore = useEditStatementStore( testNoValueStatementId )();

				wrapper = await mount( editableSnakValueComponent, {
					props: {
						propertyId: testPropertyId,
						snakKey: editStatementStore.mainSnakKey
					},
					global: {
						plugins: [ testingPinia ]
					}
				} );
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
				expect( noValueSomeValuePlaceholder.text() ).toContain( `wikibase-snakview-variations-${ snaktype }-label` );
			} );
		} );
	} );
} );
