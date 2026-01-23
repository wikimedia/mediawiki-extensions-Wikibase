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
	'../../../resources/wikibase.wbui2025/api/editEntity.js',
	() => ( {
		renderSnakValueText: jest.fn(),
		renderSnakValueHtml: jest.fn(),
		parseValue: jest.fn()
	} )
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();

const {
	renderSnakValueHtml: mockRenderSnakValueHtml,
	renderSnakValueText: mockRenderSnakValueText,
	parseValue: mockParseValue
} = require( '../../../resources/wikibase.wbui2025/api/editEntity.js' );
const editableTimeSnakValueComponent = require( '../../../resources/wikibase.wbui2025/components/editableTimeSnakValue.vue' );
const { CdxPopover, CdxButton, CdxTextInput } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const { storeWithStatements } = require( '../piniaHelpers.js' );
const { useEditStatementsStore, useEditStatementStore, useEditSnakStore } = require( '../../../resources/wikibase.wbui2025/store/editStatementsStore.js' );
const { updateSnakValueHtmlForHash } = require( '../../../resources/wikibase.wbui2025/store/serverRenderedHtml.js' );

describe( 'wikibase.wbui2025.editableTimeSnakValue', () => {
	it( 'defines component', async () => {
		expect( typeof editableTimeSnakValueComponent ).toBe( 'object' );
		expect( editableTimeSnakValueComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditableTimeSnakValue' );
	} );

	describe( 'time datatype', () => {
		let wrapper, textInput, closeButton, popover, snakKey;

		const testPropertyId = 'P1';
		const testStatementId = 'Q1$time-statement-id';
		const snakHash = 'a9564ebc3289b7a14551baf8ad5ec60a';
		const parsedValue = {
			value: {
				time: '+4234-00-00T00:00:00Z',
				timezone: 0,
				before: 0,
				precision: 7,
				after: 0,
				calendarmodel: 'http://www.wikidata.org/entity/Q1985727'
			},
			raw: '4234',
			type: 'time'
		};
		const testStatement = {
			id: testStatementId,
			mainsnak: {
				property: 'P1',
				snaktype: 'value',
				datavalue: parsedValue,
				datatype: 'time',
				hash: snakHash
			},
			rank: 'normal',
			'qualifiers-order': [],
			qualifiers: {},
			references: []
		};

		beforeEach( async () => {
			mockRenderSnakValueText.mockResolvedValueOnce( '4234' );
			mockParseValue.mockResolvedValueOnce( parsedValue );

			const testingPinia = storeWithStatements( [ testStatement ] );
			const editStatementsStore = useEditStatementsStore();
			await editStatementsStore.initializeFromStatementStore( [ testStatement.id ], testPropertyId );
			const editStatementStore = useEditStatementStore( testStatementId )();
			updateSnakValueHtmlForHash( snakHash, '4234 CE <sup>Gregorian</sup>' );
			snakKey = editStatementStore.mainSnakKey;
			const editSnakStoreGetter = useEditSnakStore( snakKey );
			await editSnakStoreGetter().valueStrategy.getParsedValue();
			wrapper = await mount( editableTimeSnakValueComponent, {
				props: {
					disabled: false,
					snakKey: snakKey
				},
				global: {
					plugins: [ testingPinia ]
				},
				// Use 'attachTo' so that focus events get fired
				attachTo: document.body
			} );

			textInput = wrapper.findComponent( CdxTextInput );
			closeButton = wrapper.findComponent( CdxButton );
			popover = wrapper.findComponent( CdxPopover );
		} );

		it( 'should set the text-input to the current snak value (rendered as text)', async () => {
			expect( textInput.props( 'modelValue' ) ).toBe( '4234' );
		} );

		it( 'correctly mounts the child components', () => {
			expect( textInput.exists() ).toBe( true );
			expect( textInput.props( 'disabled' ) ).toBe( false );
			expect( popover.exists() ).toBe( true );
			expect( popover.props( 'open' ) ).toBe( false );
			expect( closeButton.exists() ).toBe( false );
			expect( mockRenderSnakValueHtml ).toHaveBeenCalledTimes( 0 );
		} );

		it( 'loads the properties of the saved value into the edit statement store', async () => {
			expect( wrapper.vm.precision ).toBe( 7 );
			expect( wrapper.vm.calendar ).toBe( 'http://www.wikidata.org/entity/Q1985727' );
		} );

		it( 'should set autocapitalize to "off" for the text input', async () => {
			expect( textInput.find( 'input' ).element.getAttribute( 'autocapitalize' ) ).toBe( 'off' );
		} );

		it( 'should open the popup if the input is focussed', async () => {
			await textInput.find( 'input' ).trigger( 'focus' );
			expect( popover.props( 'open' ) ).toBe( true );
			closeButton = wrapper.findComponent( CdxButton );
			expect( closeButton.exists() ).toBe( true );
			// the existing snakHtml should be loaded from the cache - this should not be called.
			expect( mockRenderSnakValueHtml ).toHaveBeenCalledTimes( 0 );
			expect( mockParseValue ).toHaveBeenCalledTimes( 1 );
			expect( popover.find( 'div.time-options' ).html() ).toContain( '4234 CE <sup>Gregorian</sup>' );
			expect( popover.findAll( 'p.option-and-select' )[ 0 ].html() ).toContain( '<b>valueview-expert-timeinput-precision</b>valueview-expert-timeinput-precision-year100' );
			expect( popover.findAll( 'p.option-and-select' )[ 1 ].html() ).toContain( '<b>valueview-expert-timeinput-calendar</b>valueview-expert-timevalue-calendar-gregorian' );
		} );

		it( 'entering a new value should cause parsing to be triggered in automatic mode', async () => {
			await textInput.find( 'input' ).trigger( 'focus' );
			expect( popover.props( 'open' ) ).toBe( true );
			closeButton = wrapper.findComponent( CdxButton );
			expect( closeButton.exists() ).toBe( true );
			// the existing snakHtml should be loaded from the cache - this should not be called.
			expect( mockRenderSnakValueHtml ).toHaveBeenCalledTimes( 0 );
			expect( mockParseValue ).toHaveBeenCalledTimes( 1 );
			expect( popover.find( 'div.time-options' ).html() ).toContain( '4234 CE <sup>Gregorian</sup>' );
			expect( popover.findAll( 'p.option-and-select' )[ 0 ].html() ).toContain( '<b>valueview-expert-timeinput-precision</b>valueview-expert-timeinput-precision-year100' );
			expect( popover.findAll( 'p.option-and-select' )[ 1 ].html() ).toContain( '<b>valueview-expert-timeinput-calendar</b>valueview-expert-timevalue-calendar-gregorian' );
			// The form opens with the calendar settings from the previous input. We nevertheless want the parses
			// for newly-typed values to be sent with default options (unless a precision / calendarmodel is explicitly
			// selected)
			await textInput.setValue( '12' );
			expect( textInput.props( 'modelValue' ) ).toBe( '12' );
			mockParseValue.mockImplementation( ( value, parseOptions ) => {
				expect( value ).toBe( '12' );
				expect( parseOptions ).toStrictEqual( { property: 'P1' } );
				return Promise.resolve( parsedValue );
			} );
			const editSnakStoreGetter = useEditSnakStore( snakKey );
			await editSnakStoreGetter().valueStrategy.getParsedValue();
			expect( mockParseValue ).toHaveBeenCalledTimes( 2 );
		} );

		it( 'should close the popup if the close button is clicked', async () => {
			await textInput.find( 'input' ).trigger( 'focus' );
			expect( popover.props( 'open' ) ).toBe( true );
			closeButton = wrapper.findComponent( CdxButton );
			expect( closeButton.exists() ).toBe( true );
			await closeButton.find( 'button' ).trigger( 'click' );
			expect( popover.props( 'open' ) ).toBe( false );
		} );

		describe( 'when it is disabled', () => {
			beforeEach( async () => {
				await wrapper.setProps( { disabled: true } );
			} );
			it( 'disables the child components', () => {
				expect( textInput.props( 'disabled' ) ).toBe( true );
			} );
		} );

	} );

} );
