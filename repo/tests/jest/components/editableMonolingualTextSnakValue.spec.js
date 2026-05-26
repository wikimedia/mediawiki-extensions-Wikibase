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
		renderSnakValueText: jest.fn( ( datavalue ) => {
			if ( datavalue.type === 'string' ) {
				return datavalue.value;
			}
			return datavalue.value.text;
		} ),
		renderSnakValueHtml: jest.fn( () => Promise.resolve( '' ) ),
		parseValue: jest.fn( ( value, options ) => {
			/* We need a more complete implementation of the server-side parse here
			 * to be able to test the different possible input error states (T426663)
			 */
			if ( value === '' ) {
				return Promise.resolve( '' );
			}
			if ( !options.options ) {
				return Promise.resolve( null );
			}
			const result = {
				text: value,
				language: JSON.parse( options.options ).valuelang
			};
			return Promise.resolve( result );
		} )
	} )
);

jest.mock(
	'../../../resources/wikibase.wbui2025/api/commons.js',
	() => ( {
		searchLanguages: jest.fn( () => Promise.resolve( { tr: 'tr - Türkçe' } ) ),
		transformLanguageSearchResults: jest.fn().mockReturnValue( [] )
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
const { CdxTextArea, CdxLookup } = require( '../../../codex.js' );
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

		const textarea = wrapper.findComponent( CdxTextArea );
		expect( textarea.props( 'modelValue' ) ).toBe( '' );
		expect( textarea.props( 'modelValue' ) ).not.toBe( 'NaN' );
	} );

	it( 'shows existing string value in the input', async () => {
		const existingStatementId = '$Q1$existing-statement';
		const existingStatement = createStatement( existingStatementId );
		existingStatement.mainsnak.datavalue = {
			value: {
				text: 'existing value',
				language: 'tr'
			},
			type: 'monolingualtext'
		};

		const testingPinia = storeWithStatements( [ existingStatement ] );
		const editStatementsStore = useEditStatementsStore();
		await editStatementsStore.initializeFromStatementStore( [ existingStatementId ], 'P1' );
		const editStatementStore = useEditStatementStore( existingStatementId )();

		const wrapper = await mount( editableMonolingualTextSnakValueComponent, {
			props: {
				snakKey: editStatementStore.mainSnakKey,
				disabled: false
			},
			global: { plugins: [ testingPinia ] }
		} );

		const textarea = wrapper.findComponent( CdxTextArea );
		expect( textarea.props( 'modelValue' ) ).toBe( 'existing value' );
		const languageLookup = wrapper.findComponent( CdxLookup );
		expect( languageLookup.props( 'selected' ) ).toBe( 'tr' );
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

	it( 'shows the error state for the text input if the text input is incomplete when focus is lost', async () => {
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

		const textarea = wrapper.findComponent( CdxTextArea );
		wrapper.vm.focus();
		expect( textarea.props( 'modelValue' ) ).toBe( '' );
		expect( textarea.props( 'status' ) ).toBe( 'default' );
		textarea.vm.$emit( 'blur' );
		await wrapper.vm.$nextTick();
		expect( textarea.props( 'status' ) ).toBe( 'error' );
	} );

	it( 'shows the default state for the text input if the text input is valid (but language unset) when focus is lost', async () => {
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

		const textarea = wrapper.findComponent( CdxTextArea );
		wrapper.vm.focus();
		expect( textarea.props( 'modelValue' ) ).toBe( '' );
		await textarea.vm.$emit( 'update:modelValue', 'frog' );
		expect( textarea.props( 'modelValue' ) ).toBe( 'frog' );
		expect( textarea.props( 'status' ) ).toBe( 'default' );
		textarea.vm.$emit( 'blur' );
		await wrapper.vm.$nextTick();
		expect( textarea.props( 'status' ) ).toBe( 'default' );
	} );

	it( 'shows the error state for the lookup if the language is unset when focus is lost', async () => {
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

		const textarea = wrapper.findComponent( CdxTextArea );
		const lookup = wrapper.findComponent( CdxLookup );
		lookup.wrapperElement.focus();
		await textarea.vm.$emit( 'update:modelValue', 'frog' );
		expect( textarea.props( 'modelValue' ) ).toBe( 'frog' );
		expect( lookup.props( 'modelValue' ) ).toBe( undefined );
		expect( lookup.props( 'status' ) ).toBe( 'default' );
		expect( textarea.props( 'status' ) ).toBe( 'default' );
		lookup.vm.$emit( 'blur' );
		await wrapper.vm.$nextTick();
		expect( textarea.props( 'status' ) ).toBe( 'default' );
		expect( lookup.props( 'status' ) ).toBe( 'error' );
	} );

	it( 'shows the default state for the lookup if the language is set when focus is lost', async () => {
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

		const textarea = wrapper.findComponent( CdxTextArea );
		const lookup = wrapper.findComponent( CdxLookup );
		lookup.wrapperElement.focus();
		await textarea.vm.$emit( 'update:modelValue', 'frog' );
		wrapper.vm.lookupSource.lookupMenuItems.value = [ { value: 'tr', label: 'Türkçe' } ];
		await lookup.vm.$emit( 'update:input-value', 'tr' );
		await lookup.vm.$emit( 'update:selected', 'tr' );
		expect( textarea.props( 'modelValue' ) ).toBe( 'frog' );
		expect( lookup.props( 'selected' ) ).toBe( 'tr' );
		expect( lookup.props( 'status' ) ).toBe( 'default' );
		expect( textarea.props( 'status' ) ).toBe( 'default' );
		lookup.vm.$emit( 'blur' );
		await wrapper.vm.$nextTick();
		expect( textarea.props( 'status' ) ).toBe( 'default' );
		expect( lookup.props( 'status' ) ).toBe( 'default' );
	} );
} );
