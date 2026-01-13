jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);

jest.mock(
	'../../../resources/wikibase.wbui2025/icons.json',
	() => ( { } ),
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
	renderSnakValueText: mockRenderSnakValueText,
	parseValue: mockParseValue
} = require( '../../../resources/wikibase.wbui2025/api/editEntity.js' );

const editableGlobeCoordinateComponent = require( '../../../resources/wikibase.wbui2025/components/editableGlobeCoordinateSnakValue.vue' );
const { CdxPopover, CdxButton, CdxTextInput } = require( '../../../codex.js' );
const { mount, flushPromises } = require( '@vue/test-utils' );
const { storeWithStatements } = require( '../piniaHelpers.js' );
const { useEditStatementsStore, useEditStatementStore, useEditSnakStore } = require( '../../../resources/wikibase.wbui2025/store/editStatementsStore.js' );

describe( 'wikibase.wbui2025.editableGlobeCoordinateSnakValue', () => {
	it( 'defines component', async () => {
		expect( typeof editableGlobeCoordinateComponent ).toBe( 'object' );
		expect( editableGlobeCoordinateComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditableGlobeCoordinateSnakValue' );
	} );

	describe( 'globe coordinate datatype', () => {
		let wrapper, textInput, snakKey, popover;

		const testPropertyId = 'P1';
		const testStatementId = 'Q1$globe-coordinate-statement-id';
		const snakHash = '898076faf7900dcc6205cc370458d16487cf2bb6';
		const rawValue = '46°3\'23"N, 14°31\'1"E';
		const parsedValue = {
			value: {
				latitude: 46.05638888888889,
				longitude: 14.516666666666667,
				altitude: null,
				precision: 0.0002777777777777778,
				globe: 'http://www.wikidata.org/entity/Q2'
			},
			raw: rawValue,
			type: 'globecoordinate'
		};
		const testStatement = {
			id: testStatementId,
			mainsnak: {
				property: testPropertyId,
				snaktype: 'value',
				datavalue: parsedValue,
				datatype: 'globe-coordinate',
				hash: snakHash
			},
			rank: 'normal',
			'qualifiers-order': [],
			qualifiers: {},
			references: []
		};

		beforeEach( async () => {
			mockRenderSnakValueText.mockResolvedValueOnce( rawValue );
			mockParseValue.mockResolvedValue( parsedValue );

			const testingPinia = storeWithStatements( [ testStatement ] );
			const editStatementsStore = useEditStatementsStore();
			await editStatementsStore.initializeFromStatementStore( [ testStatement.id ], testPropertyId );
			const editStatementStore = useEditStatementStore( testStatementId )();

			snakKey = editStatementStore.mainSnakKey;
			const editSnakStoreGetter = useEditSnakStore( snakKey );
			await editSnakStoreGetter().valueStrategy.getParsedValue();
			wrapper = await mount( editableGlobeCoordinateComponent, {
				props: {
					disabled: false,
					snakKey: snakKey
				},
				global: {
					plugins: [ testingPinia ]
				},
				attachTo: document.body
			} );

			textInput = wrapper.findComponent( CdxTextInput );
			popover = wrapper.findComponent( CdxPopover );
		} );

		it( 'sets the text input to the current snak value (rendered as text)', async () => {
			expect( textInput.props( 'modelValue' ) ).toBe( rawValue );
		} );

		it( 'correctly mounts the child components', () => {
			expect( textInput.exists() ).toBe( true );
			expect( textInput.props( 'disabled' ) ).toBe( false );
			expect( popover.exists() ).toBe( true );
			expect( popover.props( 'open' ) ).toBe( false );
			expect( popover.props( 'useCloseButton' ) ).toBe( true );
		} );

		it( 'opens the popover when input is focused', async () => {
			expect( popover.props( 'open' ) ).toBe( false );
			await wrapper.vm.focus();
			expect( popover.props( 'open' ) ).toBe( true );
		} );

		describe( 'with the popover open', () => {
			let closeButton, precisionSelect, precisionOptions;

			beforeEach( async () => {
				await wrapper.setData( { showPopover: true } );
				closeButton = popover.findComponent( CdxButton );
				precisionSelect = popover.find( 'select' );
				precisionOptions = popover.findAll( 'select option' );
			} );

			// The popover's `use-close-button` property is ignored when the #header slot is used.
			// Confirming the property is set correctly is insufficient to confirm the button actually exists.
			it( 'has a close button', () => {
				expect( closeButton.exists() ).toBe( true );
			} );

			it( 'has the right set of precision options', () => {
				expect( precisionOptions.map( ( option ) => option.wrapperElement.value ) ).toEqual( [
					'auto',
					'2.7777777777777776e-7',
					'0.000002777777777777778',
					'0.00002777777777777778',
					'0.0002777777777777778',
					'0.016666666666666666',
					'0.000001',
					'0.00001',
					'0.0001',
					'0.001',
					'0.01',
					'0.1',
					'1',
					'10'
				] );
			} );

			it( 'selecting a precision triggers a parseValue call and updates the snak precision', async () => {
				await wrapper.setData( { selectedPrecision: '0.016666666666666666' } );

				expect( mockParseValue ).toHaveBeenLastCalledWith( rawValue, {
					options: '{"precision":0.016666666666666666}',
					property: testPropertyId
				} );
				expect( precisionSelect.wrapperElement.value ).toEqual( '0.016666666666666666' );
				expect( wrapper.vm.precision ).toEqual( '0.016666666666666666' );
			} );

			it( 'auto precision omits precision arguments from the parseValue call', async () => {
				await wrapper.setData( { selectedPrecision: 'auto' } );
				expect( mockParseValue ).toHaveBeenLastCalledWith( rawValue, { property: testPropertyId } );
			} );

			it( 'changing the text input triggers a parsevalue call', async () => {
				const newRawInput = '52°31\'9.54"N, 13°30\'48.53"E';
				await textInput.setValue( newRawInput );
				expect( mockParseValue ).toHaveBeenLastCalledWith( newRawInput, { property: testPropertyId } );
				expect( textInput.props( 'modelValue' ) ).toBe( newRawInput );
			} );

			it( 'shows an error when parsing fails', async () => {
				mockParseValue.mockResolvedValueOnce( null );
				await textInput.setValue( 'not a coordinate' );
				await flushPromises();
				expect( wrapper.find( '.wikibase-coordinate-popover__malformed' ).exists() ).toBe( true );
			} );
		} );
	} );
} );
