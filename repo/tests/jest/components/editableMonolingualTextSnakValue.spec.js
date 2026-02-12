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

jest.mock(
	'../../../resources/wikibase.wbui2025/api/commons.js',
	() => ( {
		searchLanguages: jest.fn( () => Promise.resolve( { tr: 'tr - Türkçe' } ) )
	} )
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();

const wbui2025 = require( 'wikibase.wbui2025.lib' );
Object.assign( wbui2025.store.snakValueStrategyFactory, {
	searchByDatatype: jest.fn( () => Promise.resolve( [] ) ),
	searchByDatatypeDebounced: jest.fn( () => Promise.resolve( [] ) )
} );

const editableMonolingualTextSnakValueComponent = require( '../../../resources/wikibase.wbui2025/components/editableMonolingualTextSnakValue.vue' );
const { CdxTextInput, CdxLookup } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const { storeWithStatements } = require( '../piniaHelpers.js' );
const { useEditStatementsStore, useEditStatementStore } = require( '../../../resources/wikibase.wbui2025/store/editStatementsStore.js' );

describe( 'wikibase.wbui2025.editableMonolingualTextSnakValue', () => {

	const createStatement = ( newStatementId ) => ( {
		id: newStatementId,
		mainsnak: {
			snaktype: 'value',
			datavalue: {
				value: '',
				type: 'string'
			},
			datatype: 'monolingualtext'
		},
		rank: 'normal',
		'qualifiers-order': [],
		qualifiers: {},
		references: []
	} );

	it( 'initializes monolingualtextlanguagecode as null (not undefined) for new statements', async () => {
		const newStatementId = 'Q1$new-quantity-stmt';
		const newStatement = createStatement( newStatementId );

		const testingPinia = storeWithStatements( [ newStatement ] );
		const editStatementsStore = useEditStatementsStore();
		await editStatementsStore.initializeFromStatementStore( [ newStatement.id ], 'P1' );
		const editStatementStore = useEditStatementStore( newStatementId )();

		const wrapper = await mount( editableMonolingualTextSnakValueComponent, {
			props: {
				snakKey: editStatementStore.mainSnakKey,
				disabled: false
			},
			global: { plugins: [ testingPinia ] }
		} );

		const languageLookup = wrapper.findComponent( CdxLookup );
		expect( languageLookup.props( 'selected' ) ).toBeNull();
	} );

	it( 'renders empty string instead of NaN for new blank statements', async () => {
		const newStatementId = 'Q1$new-quantity-stmt-nan';
		const newStatement = createStatement( newStatementId );

		const testingPinia = storeWithStatements( [ newStatement ] );
		const editStatementsStore = useEditStatementsStore();
		await editStatementsStore.initializeFromStatementStore( [ newStatement.id ], 'P1' );
		const editStatementStore = useEditStatementStore( newStatementId )();

		const wrapper = await mount( editableMonolingualTextSnakValueComponent, {
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

	it( 'updates store monolingualtextlanguagecode when lookup input changes (reactive)', async () => {
		const newStatementId = 'Q1$language-lookup-reactivity';
		const newStatement = createStatement( newStatementId );

		const testingPinia = storeWithStatements( [ newStatement ] );
		const editStatementsStore = useEditStatementsStore();
		await editStatementsStore.initializeFromStatementStore( [ newStatement.id ], 'P1' );
		const editSnakStore = wbui2025.store.useEditSnakStore( 'snak0' )();

		const wrapper = await mount( editableMonolingualTextSnakValueComponent, {
			props: {
				snakKey: 'snak0',
				disabled: false
			},
			global: { plugins: [ testingPinia ] }
		} );

		wrapper.vm.lookupSource.lookupSelection.value = 'tr';
		expect( editSnakStore.monolingualtextlanguagecode ).toBe( 'tr' );
	} );
} );
