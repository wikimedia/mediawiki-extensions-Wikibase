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

const editableSnakValueComponent = require( '../../../resources/wikibase.wbui2025/components/editableSnakValue.vue' );
const { CdxButton, CdxMenuButton, CdxTextInput } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const { storeWithStatements } = require( '../piniaHelpers.js' );
const { useEditStatementsStore, useEditStatementStore } = require( '../../../resources/wikibase.wbui2025/store/editStatementsStore.js' );

describe( 'wikibase.wbui2025.editableSnakValue', () => {
	it( 'defines component', async () => {
		expect( typeof editableSnakValueComponent ).toBe( 'object' );
		expect( editableSnakValueComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditableSnakValue' );
	} );

	describe( 'string datatype', () => {
		let wrapper, textInput, removeButton, snakTypeSelector, snakKey;

		const testPropertyId = 'P1';
		const testStatementId = 'Q1$string-statement-id';
		const testStatement = {
			id: testStatementId,
			mainsnak: {
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

			snakKey = editStatementStore.mainSnakKey;
			wrapper = await mount( editableSnakValueComponent, {
				props: {
					propertyId: testPropertyId,
					snakKey: snakKey,
					removable: true
				},
				global: {
					plugins: [ testingPinia ]
				}
			} );

			textInput = wrapper.findComponent( CdxTextInput );
			removeButton = wrapper.findComponent( CdxButton );
			snakTypeSelector = wrapper.findComponent( CdxMenuButton );
		} );

		it( 'should set the text-input to the current snak value', async () => {
			expect( textInput.props( 'modelValue' ) ).toBe( 'example string' );
		} );

		it( 'correctly mounts the child components', () => {
			expect( textInput.exists() ).toBe( true );
			expect( textInput.props( 'disabled' ) ).toBe( false );
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
			expect( textInput.exists() ).toBe( true );
			expect( textInput.props( 'modelValue' ) ).toBe( 'example string' );

			// Empty the string input
			await textInput.vm.$emit( 'update:modelValue', '' );
			expect( textInput.props( 'modelValue' ) ).toBe( '' );
			expect( textInput.classes() ).not.toContain( 'cdx-text-input--status-error' );
			// After a blur on the input, the field should indicate an error
			await textInput.vm.$emit( 'blur' );
			expect( textInput.classes() ).toContain( 'cdx-text-input--status-error' );

			wrapper.vm.snakTypeSelection = 'novalue';
			await wrapper.vm.$nextTick();
			expect( textInput.exists() ).toBe( false );

			// Restore the original value (should no longer be in an erroneous state)
			wrapper.vm.snakTypeSelection = 'value';
			// This needs two ticks to propagate from one child component to another
			await wrapper.vm.$nextTick();
			await wrapper.vm.$nextTick();
			textInput = wrapper.findComponent( CdxTextInput );
			expect( textInput.exists() ).toBe( true );
			expect( textInput.props( 'modelValue' ) ).toBe( 'example string' );
			expect( textInput.classes() ).not.toContain( 'cdx-text-input--status-error' );
		} );

		it( 'should set autocapitalize to "off" for the text input', async () => {
			expect( textInput.find( 'input' ).element.getAttribute( 'autocapitalize' ) ).toBe( 'off' );
		} );

		describe( 'when it is disabled', () => {
			beforeEach( async () => {
				await wrapper.setProps( { disabled: true } );
			} );
			it( 'disables the child components', () => {
				expect( textInput.props( 'disabled' ) ).toBe( true );
				expect( snakTypeSelector.props( 'disabled' ) ).toBe( true );
				expect( removeButton.isDisabled() ).toBe( true );
			} );
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
