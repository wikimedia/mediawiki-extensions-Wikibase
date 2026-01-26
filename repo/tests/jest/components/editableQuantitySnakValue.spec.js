'use strict';

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
		renderSnakValueHtml: jest.fn( () => Promise.resolve( '' ) ),
		parseValue: jest.fn( () => Promise.resolve( {} ) )
	} )
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const {
	renderSnakValueText: mockRenderSnakValueText
} = require( '../../../resources/wikibase.wbui2025/api/editEntity.js' );

const wbui2025 = require( 'wikibase.wbui2025.lib' );
Object.assign( wbui2025.store.snakValueStrategyFactory, {
	searchByDatatype: jest.fn( () => Promise.resolve( [] ) ),
	searchByDatatypeDebounced: jest.fn( () => Promise.resolve( [] ) )
} );
wbui2025.api = Object.assign( wbui2025.api, {
	transformEntityByConceptUriSearchResults: jest.fn( ( searchResults ) => {
		if ( !searchResults || searchResults.length === 0 ) {
			return [];
		}
		return searchResults.map( ( result ) => ( {
			label: result.label,
			value: result.concepturi || result.url || result.id,
			description: result.description
		} ) );
	} )
} );

const editableQuantitySnakValueComponent = require( '../../../resources/wikibase.wbui2025/components/editableQuantitySnakValue.vue' );
const { CdxTextInput, CdxLookup } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const { storeWithStatements } = require( '../piniaHelpers.js' );
const { useEditStatementsStore, useEditStatementStore } = require( '../../../resources/wikibase.wbui2025/store/editStatementsStore.js' );

describe( 'wikibase.wbui2025.editableQuantitySnakValue', () => {

	const createStatement = ( newStatementId, dataValue = { value: '', type: 'string' } ) => ( {
		id: newStatementId,
		mainsnak: {
			snaktype: 'value',
			datavalue: dataValue,
			datatype: 'quantity'
		},
		rank: 'normal',
		'qualifiers-order': [],
		qualifiers: {},
		references: []
	} );

	it( 'initializes unitconcepturi as null (not undefined) for new statements', async () => {
		const newStatementId = 'Q1$new-quantity-stmt';
		const newStatement = createStatement( newStatementId );

		const testingPinia = storeWithStatements( [ newStatement ] );
		const editStatementsStore = useEditStatementsStore();
		await editStatementsStore.initializeFromStatementStore( [ newStatement.id ], 'P1' );
		const editStatementStore = useEditStatementStore( newStatementId )();

		const wrapper = await mount( editableQuantitySnakValueComponent, {
			props: {
				snakKey: editStatementStore.mainSnakKey,
				disabled: false
			},
			global: { plugins: [ testingPinia ] }
		} );

		const unitLookup = wrapper.findComponent( CdxLookup );
		// This checks the prop passed to CdxLookup for the unit selection
		expect( unitLookup.props( 'selected' ) ).toBeNull();
		expect( mockRenderSnakValueText ).toHaveBeenCalledTimes( 0 );
	} );

	it( 'initializes input correctly for non-empty statements', async () => {
		const newStatementId = 'Q1$new-quantity-with-value-stmt';
		const dataValue = { value: { amount: '+123', unit: '1' }, type: 'quantity' };
		const newStatement = createStatement( newStatementId, dataValue );

		const testingPinia = storeWithStatements( [ newStatement ] );
		const editStatementsStore = useEditStatementsStore();
		await editStatementsStore.initializeFromStatementStore( [ newStatement.id ], 'P1' );
		const editStatementStore = useEditStatementStore( newStatementId )();

		const wrapper = await mount( editableQuantitySnakValueComponent, {
			props: {
				snakKey: editStatementStore.mainSnakKey,
				disabled: false
			},
			global: { plugins: [ testingPinia ] }
		} );

		const unitLookup = wrapper.findComponent( CdxLookup );
		expect( unitLookup.props( 'selected' ) ).toBe( '1' );
		expect( mockRenderSnakValueText ).toHaveBeenCalledWith( {
			type: 'quantity',
			value: {
				amount: '+123',
				unit: '1'
			}
		} );
	} );

	it( 'renders empty string instead of NaN for new blank statements', async () => {
		const newStatementId = 'Q1$new-quantity-stmt-nan';
		const newStatement = createStatement( newStatementId );

		const testingPinia = storeWithStatements( [ newStatement ] );
		const editStatementsStore = useEditStatementsStore();
		await editStatementsStore.initializeFromStatementStore( [ newStatement.id ], 'P1' );
		const editStatementStore = useEditStatementStore( newStatementId )();

		const wrapper = await mount( editableQuantitySnakValueComponent, {
			props: {
				snakKey: editStatementStore.mainSnakKey,
				disabled: false
			},
			global: { plugins: [ testingPinia ] }
		} );

		const input = wrapper.findComponent( CdxTextInput );
		expect( input.props( 'modelValue' ) ).toBe( '' );
		expect( input.props( 'modelValue' ) ).not.toBe( 'NaN' );
	} );

	it( 'updates store unittextvalue when lookup input changes (reactive)', async () => {
		const newStatementId = 'Q1$unit-lookup-reactivity';
		const newStatement = createStatement( newStatementId );

		const testingPinia = storeWithStatements( [ newStatement ] );
		const editStatementsStore = useEditStatementsStore();
		await editStatementsStore.initializeFromStatementStore( [ newStatement.id ], 'P1' );
		const editSnakStore = wbui2025.store.useEditSnakStore( 'snak0' )();

		const wrapper = await mount( editableQuantitySnakValueComponent, {
			props: {
				snakKey: 'snak0',
				disabled: false
			},
			global: { plugins: [ testingPinia ] }
		} );

		wrapper.vm.lookupSource.lookupInputValue.value = 'meter';
		expect( editSnakStore.unittextvalue ).toBe( 'meter' );
	} );
} );
