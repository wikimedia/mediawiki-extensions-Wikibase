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

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();

const editableAnyDatatypeSnakValueComponent = require( '../../../resources/wikibase.wbui2025/components/editableAnyDatatypeSnakValue.vue' );
const editableNoValueSomeValueSnakValueComponent = require( '../../../resources/wikibase.wbui2025/components/editableNoValueSomeValueSnakValue.vue' );
const { CdxButton, CdxMenuButton, CdxTextArea, CdxTextInput } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const { storeWithStatements } = require( '../piniaHelpers.js' );
const { useEditStatementsStore, useEditStatementStore } = require( '../../../resources/wikibase.wbui2025/store/editStatementsStore.js' );
const { useParsedValueStore } = require( '../../../resources/wikibase.wbui2025/store/parsedValueStore.js' );

describe( 'wikibase.wbui2025.editableSnakValue', () => {
	it( 'defines component', async () => {
		expect( typeof editableAnyDatatypeSnakValueComponent ).toBe( 'object' );
		expect( editableAnyDatatypeSnakValueComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditableAnyDatatypeSnakValue' );
	} );

	describe( 'string datatype', () => {
		let wrapper, textarea, removeButton, innerSnakValue, snakTypeSelector, snakKey;

		const testPropertyId = 'P1';
		const testStatementId = 'Q1$string-statement-id';
		const testStatement = {
			id: testStatementId,
			mainsnak: {
				property: testPropertyId,
				snaktype: 'value',
				datavalue: {
					value: 'example string',
					type: 'string'
				},
				datatype: 'string'
			},
			rank: 'normal',
			'qualifiers-order': [],
			qualifiers: {},
			references: []
		};

		beforeEach( async () => {
			const testingPinia = storeWithStatements( [ testStatement ] );
			const editStatementsStore = useEditStatementsStore();
			await editStatementsStore.initializeFromStatementStore( [ testStatement.id ], testPropertyId );
			const editStatementStore = useEditStatementStore( testStatementId )();
			const parsedValueStore = useParsedValueStore();
			parsedValueStore.preloadParsedValue( testPropertyId, testStatement.mainsnak.datavalue );

			snakKey = editStatementStore.mainSnakKey;
			wrapper = await mount( editableAnyDatatypeSnakValueComponent, {
				props: {
					snakKey: snakKey,
					removable: true
				},
				global: {
					plugins: [ testingPinia ]
				}
			} );

			textarea = wrapper.findComponent( CdxTextArea );
			removeButton = wrapper.findComponent( CdxButton );
			snakTypeSelector = wrapper.findComponent( CdxMenuButton );
			innerSnakValue = wrapper.findComponent( editableNoValueSomeValueSnakValueComponent );
		} );

		it( 'should set the textarea to the current snak value', async () => {
			expect( textarea.props( 'modelValue' ) ).toBe( 'example string' );
		} );

		it( 'correctly mounts the child components', () => {
			expect( textarea.exists() ).toBe( true );
			expect( textarea.props( 'disabled' ) ).toBe( false );
			expect( snakTypeSelector.exists() ).toBe( true );
			expect( snakTypeSelector.props( 'disabled' ) ).toBe( false );
			expect( removeButton.exists() ).toBe( true );
			expect( removeButton.isDisabled() ).toBe( false );
		} );

		it( 'emits "remove-snak" when the remove button is clicked', async () => {
			await removeButton.vm.$emit( 'click' );
			expect( wrapper.emitted() ).toEqual( { 'remove-snak': [ [ snakKey ] ] } );
		} );

		it( 'allows changing snak type and restores value', async () => {
			expect( textarea.exists() ).toBe( true );
			expect( textarea.classes() ).not.toContain( 'cdx-text-input--status-error' );
			expect( textarea.props( 'modelValue' ) ).toBe( 'example string' );

			// Empty the string input
			await textarea.vm.$emit( 'update:modelValue', '' );
			expect( textarea.props( 'modelValue' ) ).toBe( '' );
			// After a blur on the input, the field should indicate an error
			await textarea.vm.$emit( 'blur' );
			expect( textarea.classes() ).toContain( 'cdx-text-area--status-error' );

			innerSnakValue.vm.snakTypeSelection = 'novalue';
			await innerSnakValue.vm.$nextTick();
			expect( textarea.exists() ).toBe( false );

			// Restore the original value (should no longer be in an erroneous state)
			innerSnakValue.vm.snakTypeSelection = 'value';
			// This needs two ticks to propagate from one child component to another
			await innerSnakValue.vm.$nextTick();
			await innerSnakValue.vm.$nextTick();
			textarea = wrapper.findComponent( CdxTextArea );
			expect( textarea.exists() ).toBe( true );
			expect( textarea.props( 'modelValue' ) ).toBe( 'example string' );
			expect( textarea.classes() ).not.toContain( 'cdx-text-area--status-error' );
		} );

		it( 'should set autocapitalize to "off" for the text input', async () => {
			expect( textarea.find( 'textarea' ).element.getAttribute( 'autocapitalize' ) ).toBe( 'off' );
		} );

		it( 'strips out line breaks from the input', async () => {
			await textarea.find( 'textarea' ).setValue( 'with\nline\rbreaks' );
			expect( textarea.props( 'modelValue' ) ).toBe( 'withlinebreaks' );
		} );

		describe( 'when it is disabled', () => {
			beforeEach( async () => {
				await wrapper.setProps( { disabled: true } );
			} );
			it( 'disables the child components', () => {
				expect( textarea.props( 'disabled' ) ).toBe( true );
				expect( snakTypeSelector.props( 'disabled' ) ).toBe( true );
				expect( removeButton.isDisabled() ).toBe( true );
			} );
		} );
	} );

	describe.each(
		[ 'string', 'url', 'time', 'tabular-data', 'geo-shape', 'external-id' ]
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

				wrapper = await mount( editableAnyDatatypeSnakValueComponent, {
					props: {
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

			it( 'mounts its child components', () => {
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
