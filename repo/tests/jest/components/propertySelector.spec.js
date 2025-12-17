jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/icons.json',
	() => ( { cdxIconCheck: 'check', cdxIconClose: 'close' } ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/supportedDatatypes.json',
	() => ( [
		'string'
	] ),
	{ virtual: true }
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const propertySelectorComponent = require( '../../../resources/wikibase.wbui2025/components/propertySelector.vue' );
const propertyLookupComponent = require( '../../../resources/wikibase.wbui2025/components/propertyLookup.vue' );
const { CdxButton } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );

describe( 'wikibase.wbui2025.propertySelector', () => {
	it( 'defines component', async () => {
		expect( typeof propertySelectorComponent ).toBe( 'object' );
		expect( propertySelectorComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025PropertySelector' );
	} );

	describe( 'the mounted component', () => {
		const propertyData = {
			id: 'P123',
			datatype: 'string',
			url: 'unused',
			label: 'eine Beschriftung',
			description: 'a description',
			display: {
				label: {
					value: 'eine Beschriftung',
					language: 'de'
				},
				description: {
					value: 'a description',
					language: 'en'
				}
			},
			match: {
				type: 'alias',
				language: 'en',
				text: 'search term'
			},
			aliases: [ 'search term' ]
		};

		const languageCode = 'de';
		const mockConfig = {
			wgUserLanguage: languageCode
		};
		mw.config = {
			get: jest.fn( ( key ) => mockConfig[ key ] )
		};

		let wrapper, cancelButton, addButton, propertyLookup;
		beforeEach( async () => {
			wrapper = await mount( propertySelectorComponent, {
				props: {
					headingMessageKey: 'message-key'
				}
			} );
			const buttons = wrapper.findAllComponents( CdxButton );
			cancelButton = buttons[ 0 ];
			addButton = buttons[ 1 ];
			propertyLookup = wrapper.findComponent( propertyLookupComponent );
		} );

		it( 'the component and child components mount successfully', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( cancelButton.exists() ).toBe( true );
			expect( addButton.exists() ).toBe( true );
			expect( propertyLookup.exists() ).toBe( true );
		} );

		it( 'sets the initial properties on the CdxButton components', () => {
			expect( cancelButton.props( 'action' ) ).toBe( 'default' );
			expect( cancelButton.props( 'weight' ) ).toBe( 'quiet' );
			expect( addButton.props( 'action' ) ).toBe( 'progressive' );
			expect( addButton.props( 'weight' ) ).toBe( 'primary' );
			expect( addButton.isDisabled() ).toBe( true );
		} );

		it( 'enables the add button after selecting a property', async () => {
			await propertyLookup.vm.$emit( 'update:selected', 'P123', propertyData );

			expect( addButton.isDisabled() ).toBe( false );
		} );
	} );
} );
