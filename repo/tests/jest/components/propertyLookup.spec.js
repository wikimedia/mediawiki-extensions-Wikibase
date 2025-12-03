jest.useFakeTimers();

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

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const wbui2025 = require( 'wikibase.wbui2025.lib' );
wbui2025.api.api = { get: jest.fn() };
const propertyLookupComponent = require( '../../../resources/wikibase.wbui2025/components/propertyLookup.vue' );
const { CdxLookup } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );

describe( 'wikibase.wbui2025.propertySelector', () => {
	it( 'defines component', async () => {
		expect( typeof propertyLookupComponent ).toBe( 'object' );
		expect( propertyLookupComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025PropertyLookup' );
	} );

	describe( 'the mounted component', () => {
		const emptySearchResult = {
			searchInfo: 'search term',
			search: [],
			success: 1
		};
		const p123MenuItem = {
			value: 'P123',
			datatype: 'string',
			label: 'eine Beschriftung',
			match: '(search term)',
			description: 'a description',
			language: {
				label: 'de',
				match: 'en',
				description: 'en'
			}
		};
		const p123SearchResult = {
			searchinfo: 'search term',
			search: [
				{
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
				}
			],
			success: 1
		};
		const p456MenuItem = {
			value: 'P456',
			datatype: 'wikibase-item',
			label: undefined,
			match: '',
			description: undefined,
			language: {
				label: undefined,
				match: undefined,
				description: undefined
			}
		};
		const p456SearchResult = {
			searchinfo: 'search term',
			search: [
				{
					id: 'P456',
					datatype: 'wikibase-item',
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
		wbui2025.api.api.get.mockResolvedValue( emptySearchResult );

		const languageCode = 'de';
		const mockConfig = {
			wgUserLanguage: languageCode
		};
		mw.config = {
			get: jest.fn( ( key ) => mockConfig[ key ] )
		};

		let wrapper, lookup;
		beforeEach( async () => {
			wrapper = await mount( propertyLookupComponent );
			lookup = wrapper.findComponent( CdxLookup );
		} );

		it( 'the component and child components mount successfully', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( lookup.exists() ).toBe( true );
		} );

		it( 'sets the initial properties on the CdxLookup component', () => {
			expect( lookup.props( 'menuItems' ) ).toEqual( [] );
			expect( lookup.props( 'menuConfig' ) ).toEqual( {
				visibleItemLimit: 3
			} );
		} );

		it( 'text input causes an API call and updates menu items', async () => {
			wbui2025.api.api.get.mockResolvedValueOnce( p123SearchResult );
			await lookup.vm.$emit( 'update:input-value', 'search term' );
			await jest.advanceTimersByTime( 300 );
			await lookup.vm.$nextTick();
			await lookup.vm.$nextTick();

			expect( wbui2025.api.api.get ).toHaveBeenCalledTimes( 1 );
			expect( wbui2025.api.api.get ).toHaveBeenCalledWith( {
				action: 'wbsearchentities',
				language: languageCode,
				type: 'property',
				search: 'search term'
			} );
			expect( lookup.props( 'menuItems' ) )
				.toEqual( [ p123MenuItem ] );
		} );

		it( 'does not make an API call when the input is blank', async () => {
			await lookup.vm.$emit( 'update:input-value', '' );

			expect( wbui2025.api.api.get ).not.toHaveBeenCalled();
		} );

		it( 'emits an event when a property is selected', async () => {
			await wrapper.setData( {
				menuItems: [ p123MenuItem, p456MenuItem ]
			} );

			await lookup.vm.$emit( 'update:selected', 'P123' );
			expect( wrapper.emitted()[ 'update:selected' ] ).toEqual( [
				[ 'P123', p123MenuItem ]
			] );
		} );

		describe( 'loading more results', () => {
			beforeEach( async () => {
				wbui2025.api.api.get.mockResolvedValueOnce( p123SearchResult );
				await lookup.vm.$emit( 'update:input-value', 'search term' );
				await jest.advanceTimersByTime( 300 );
			} );

			it( 'makes another api call when `load-more` is emitted and adds more menu items', async () => {
				wbui2025.api.api.get.mockResolvedValueOnce( p456SearchResult );
				await lookup.vm.$emit( 'load-more' );
				await lookup.vm.$nextTick();

				expect( wbui2025.api.api.get ).toHaveBeenCalledTimes( 2 );
				expect( wbui2025.api.api.get ).toHaveBeenCalledWith( expect.objectContaining( {
					continue: 1
				} ) );
				expect( lookup.props( 'menuItems' )[ 1 ] ).toEqual( p456MenuItem );
			} );

			it( 'excludes duplicates from the results', async () => {
				wbui2025.api.api.get.mockResolvedValueOnce( p123SearchResult );
				await lookup.vm.$emit( 'load-more' );
				await lookup.vm.$nextTick();

				expect( wbui2025.api.api.get ).toHaveBeenCalledTimes( 2 );
				expect( lookup.props( 'menuItems' ) ).toHaveLength( 1 );
			} );
		} );
	} );
} );
