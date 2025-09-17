jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/icons.json',
	() => ( { cdxIconCheck: 'check', cdxIconClose: 'close' } ),
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/api/api.js',
	() => ( { api: { get: jest.fn() } } )
);

const propertySelectorComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.propertySelector.vue' );
const { CdxButton, CdxLookup } = require( '../../codex.js' );
const { api } = require( '../../resources/wikibase.wbui2025/api/api.js' );
const { mount } = require( '@vue/test-utils' );

describe( 'wikibase.wbui2025.propertySelector', () => {
	it( 'defines component', async () => {
		expect( typeof propertySelectorComponent ).toBe( 'object' );
		expect( propertySelectorComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025PropertySelector' );
	} );

	describe( 'the mounted component', () => {
		const emptySearchResult = {
			searchInfo: 'search term',
			search: [],
			success: 1
		};
		const p123SearchResult = {
			searchinfo: 'search term',
			search: [
				{
					id: 'P123',
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
				}
			],
			success: 1
		};
		const p456SearchResult = {
			searchinfo: 'search term',
			search: [
				{
					id: 'P456',
					url: 'unused',
					display: {},
					match: {
						type: 'entityId',
						text: 'P456'
					},
					aliases: [ 'P456' ]
				}
			],
			success: 1
		};
		api.get.mockResolvedValue( emptySearchResult );

		const languageCode = 'de';
		const mockConfig = {
			wgUserLanguage: languageCode
		};
		mw.config = {
			get: jest.fn( ( key ) => mockConfig[ key ] )
		};

		let wrapper, cancelButton, addButton, lookup;
		beforeEach( async () => {
			wrapper = await mount( propertySelectorComponent, {
				props: {
					headingMessageKey: 'message-key'
				}
			} );
			const buttons = wrapper.findAllComponents( CdxButton );
			cancelButton = buttons[ 0 ];
			addButton = buttons[ 1 ];
			lookup = wrapper.findComponent( CdxLookup );
		} );

		it( 'the component and child components mount successfully', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( cancelButton.exists() ).toBe( true );
			expect( addButton.exists() ).toBe( true );
			expect( lookup.exists() ).toBe( true );
		} );

		it( 'sets the initial properties on the CdxButton components', () => {
			expect( cancelButton.props( 'action' ) ).toBe( 'default' );
			expect( cancelButton.props( 'weight' ) ).toBe( 'quiet' );
			expect( addButton.props( 'action' ) ).toBe( 'progressive' );
			expect( addButton.props( 'weight' ) ).toBe( 'primary' );
			expect( addButton.isDisabled() ).toBe( true );
		} );

		it( 'sets the initial properties on the CdxLookup component', () => {
			expect( lookup.props( 'menuItems' ) ).toEqual( [] );
			expect( lookup.props( 'menuConfig' ) ).toEqual( {
				visibleItemLimit: 3
			} );
		} );

		it( 'text input causes an API call and updates menu items', async () => {
			api.get.mockResolvedValueOnce( p123SearchResult );
			await lookup.vm.$emit( 'update:input-value', 'search term' );
			await lookup.vm.$nextTick();

			expect( api.get ).toHaveBeenCalledTimes( 1 );
			expect( api.get ).toHaveBeenCalledWith( {
				action: 'wbsearchentities',
				language: languageCode,
				uselang: languageCode,
				type: 'property',
				search: 'search term'
			} );
			expect( lookup.props( 'menuItems' ) ).toEqual( [
				{
					value: 'P123',
					label: 'eine Beschriftung',
					match: '(search term)',
					description: 'a description',
					language: {
						label: 'de',
						match: 'en',
						description: 'en'
					}
				}
			] );
		} );

		it( 'does not make an API call when the input is blank', async () => {
			await lookup.vm.$emit( 'update:input-value', '' );

			expect( api.get ).not.toHaveBeenCalled();
		} );

		it( 'enables the add button after selecting a property', async () => {
			await lookup.vm.$emit( 'update:selected', 'P123' );

			expect( addButton.isDisabled() ).toBe( false );
		} );

		describe( 'loading more results', () => {
			beforeEach( async () => {
				api.get.mockResolvedValueOnce( p123SearchResult );
				await lookup.vm.$emit( 'update:input-value', 'search term' );
			} );

			it( 'makes another api call when `load-more` is emitted and adds more menu items', async () => {
				api.get.mockResolvedValueOnce( p456SearchResult );
				await lookup.vm.$emit( 'load-more' );
				await lookup.vm.$nextTick();

				expect( api.get ).toHaveBeenCalledTimes( 2 );
				expect( api.get ).toHaveBeenCalledWith( expect.objectContaining( {
					continue: 1
				} ) );
				expect( lookup.props( 'menuItems' )[ 1 ] ).toEqual( {
					value: 'P456',
					label: undefined,
					match: '',
					description: undefined,
					language: {
						label: undefined,
						match: undefined,
						description: undefined
					}
				} );
			} );

			it( 'excludes duplicates from the results', async () => {
				api.get.mockResolvedValueOnce( p123SearchResult );
				await lookup.vm.$emit( 'load-more' );
				await lookup.vm.$nextTick();

				expect( api.get ).toHaveBeenCalledTimes( 2 );
				expect( lookup.props( 'menuItems' ) ).toHaveLength( 1 );
			} );
		} );
	} );
} );
