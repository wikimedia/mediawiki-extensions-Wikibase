jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.vector.scopedtypeaheadsearch/scopedTypeaheadSearchConfig.json',
	() => require( './scopedTypeaheadSearchConfig.json' ),
	{ virtual: true }
);
const ScopedTypeaheadSearch = require( '../../resources/wikibase.vector.scopedtypeaheadsearch/ScopedTypeaheadSearch.vue' );
const { CdxSelect, CdxTypeaheadSearch, CdxButton } = require( '../../codex.js' );

const { mount } = require( '@vue/test-utils' );

describe( 'ScopedTypeaheadSearch', () => {

	it( 'defines component', async () => {
		expect( typeof ScopedTypeaheadSearch ).toBe( 'object' );
		expect( ScopedTypeaheadSearch ).toHaveProperty( 'name', 'ScopedTypeaheadSearch' );
	} );

	describe( 'initial input', () => {
		it( 'correctly populates input field property on load', async () => {
			const wrapper = await mount( ScopedTypeaheadSearch, { props: { initialInput: 'someValue' } } );
			expect( wrapper.props( 'initialInput' ) ).toBe( 'someValue' );
			const typeaheadComponent = wrapper.findComponent( CdxTypeaheadSearch );
			expect( typeaheadComponent.props( 'initialInputValue' ) ).toBe( 'someValue' );
		} );
	} );

	describe( 'the mounted component', () => {
		const searchResultsWithMatches = {
			searchinfo: {
				search: 'crow'
			},
			search: [
				{
					id: 'Q666368',
					url: '//www.wikidata.org/wiki/Q666368',
					display: {
						label: {
							value: 'Euploea',
							language: 'en'
						},
						description: {
							value: 'genus of insects',
							language: 'en'
						}
					},
					label: 'Euploea',
					description: 'genus of insects',
					match: {
						type: 'alias',
						language: 'en',
						text: 'Crow'
					},
					aliases: [
						'Crow'
					]
				},
				{
					id: 'Q10406',
					url: '//www.wikidata.org/wiki/Q10406',
					display: {
						label: {
							value: 'Corona Borealis',
							language: 'en'
						},
						description: {
							value: 'constellation in the northern celestial hemisphere',
							language: 'en'
						}
					},
					label: 'Corona Borealis',
					description: 'constellation in the northern celestial hemisphere',
					match: {
						type: 'alias',
						language: 'en',
						text: 'Crown of Ariadne'
					},
					aliases: [
						'Crown of Ariadne'
					]
				},
				{
					id: 'Q43365',
					url: '//www.wikidata.org/wiki/Q43365',
					display: {
						label: {
							value: 'Corvus',
							language: 'en'
						},
						description: {
							value: 'genus of birds',
							language: 'en'
						}
					},
					label: 'Corvus',
					description: 'genus of birds',
					match: {
						type: 'alias',
						language: 'en',
						text: 'crow'
					},
					aliases: [
						'crow'
					]
				},
				{
					id: 'Q26198',
					url: '//www.wikidata.org/wiki/Q26198',
					display: {
						label: {
							value: 'Carrion Crow',
							language: 'en'
						},
						description: {
							value: 'species of bird',
							language: 'en'
						}
					},
					label: 'Carrion Crow',
					description: 'species of bird',
					match: {
						type: 'alias',
						language: 'en',
						text: 'Crow'
					},
					aliases: [
						'Crow'
					]
				}
			],
			'search-continue': 4,
			success: 1
		};
		const emptySearchResult = {
			searchInfo: 'searchterm',
			search: [],
			success: 1
		};
		const mockedGet = jest.fn().mockResolvedValue( emptySearchResult );
		mw.Api.prototype.get = mockedGet;

		const mockConfig = {
			wgNamespaceIds: {
				item: 0,
				property: 122,
				lexeme: 146,
				p: 122,
				l: 146
			},
			wgScript: 'fakeBaseUrl',
			wgUserLanguage: 'ar'
		};
		mw.config = {
			get: jest.fn( ( key ) => mockConfig[ key ] )
		};

		let wrapper, typeaheadComponent, selectComponent;
		beforeEach( async () => {
			wrapper = await mount( ScopedTypeaheadSearch );
			typeaheadComponent = wrapper.findComponent( CdxTypeaheadSearch );
			selectComponent = wrapper.findComponent( CdxSelect );
		} );

		it( 'the component and child components mount successfully', async () => {
			expect( wrapper.exists() ).toBeTruthy();
			expect( wrapper.findComponent( CdxSelect ).exists() ).toBeTruthy();
			expect( wrapper.findComponent( CdxTypeaheadSearch ).exists() ).toBeTruthy();
		} );

		it( 'sets the right properties on the CdxSelect component', async () => {
			expect( selectComponent.props( 'selected' ) ).toEqual( 'item' );
			expect( selectComponent.props( 'menuItems' ) ).toEqual(
				[
					{
						label: 'wikibase-scoped-search-search-entities',
						description: 'wikibase-scoped-search-search-entities-description',
						items: [
							{ label: 'item-message', value: 'item' },
							{ label: 'property-message', value: 'property' },
							{ label: 'lexeme-message', value: 'lexeme' },
							{ label: 'entity-schema-message', value: 'entity-schema' }
						]
					}
				]
			);
		} );

		it( 'sets initial properties on the CdxTypeaheadSearch component', async () => {
			expect( typeaheadComponent.props() ).toMatchObject( {
				formAction: 'fakeBaseUrl',
				searchFooterUrl: 'fakeBaseUrl?language=ar&search=&title=Special%3ASearch&fulltext=1&ns0=1',
				searchResults: []
			} );
		} );

		it( 'text input causes an API call, and updates searchFooterUrl', async () => {
			await typeaheadComponent.vm.$emit( 'input', 'searchterm' );

			expect( mockedGet ).toHaveBeenCalledWith( expect.objectContaining( {
				action: 'wbsearchentities',
				type: 'item',
				search: 'searchterm'
			} ) );
			expect( typeaheadComponent.props( 'searchFooterUrl' ) ).toMatch( /search=searchterm/ );
			expect( typeaheadComponent.props( 'searchFooterUrl' ) ).toMatch( /language=ar/ );
		} );

		it( 'searches in the user-configured language', async () => {
			await typeaheadComponent.vm.$emit( 'input', 'searchterm' );

			expect( mockedGet ).toHaveBeenCalledWith( expect.objectContaining( {
				action: 'wbsearchentities',
				type: 'item',
				search: 'searchterm',
				language: 'ar',
				uselang: 'ar'
			} ) );
			expect( typeaheadComponent.props( 'searchFooterUrl' ) ).toMatch( /search=searchterm/ );
		} );

		it( 'does not make an API call when the input is blank', async () => {
			expect( mockedGet ).not.toBeCalled();
			await typeaheadComponent.vm.$emit( 'input', '' );
		} );

		it( 'selected scope is used when searching', async () => {
			await selectComponent.vm.$emit( 'update:selected', 'lexeme' );

			expect( typeaheadComponent.props( 'searchFooterUrl' ) ).toMatch( /ns146=1/ );
			expect( mockedGet ).toHaveBeenCalledWith( expect.objectContaining( {
				action: 'wbsearchentities',
				type: 'lexeme'
			} ) );
		} );

		describe( 'when the input matches a configured prefix', () => {
			beforeEach( async () => {
				await typeaheadComponent.vm.$emit( 'input', 'p:' );
			} );
			it( 'changes the scope', async () => {
				expect( selectComponent.props( 'selected' ) ).toEqual( 'property' );
				expect( mockedGet ).toHaveBeenCalledWith( expect.objectContaining( {
					action: 'wbsearchentities',
					type: 'property'
				} ) );

			} );
		} );

		it( 'input ending in a `:` not matching a configured prefix is treated as plain input', async () => {
			await typeaheadComponent.vm.$emit( 'input', 'z:' );
			expect( selectComponent.props( 'selected' ) ).toEqual( 'item' );
			expect( mockedGet ).toHaveBeenCalledWith( expect.objectContaining( {
				action: 'wbsearchentities',
				type: 'item',
				search: 'z:'
			} ) );
		} );

		it( 'is case-insensitive for prefix searching', async () => {
			await typeaheadComponent.vm.$emit( 'input', 'L:' );
			expect( selectComponent.props( 'selected' ) ).toEqual( 'lexeme' );
			expect( mockedGet ).toHaveBeenCalledWith( expect.objectContaining( {
				action: 'wbsearchentities',
				type: 'lexeme'
			} ) );
		} );

		describe( 'scope was previously set by prefix', () => {
			beforeEach( async () => {
				await typeaheadComponent.vm.$emit( 'input', 'p:' );
			} );

			it( 'omits the prefix from the search', async () => {
				await typeaheadComponent.vm.$emit( 'input', 'p:something' );
				expect( mockedGet ).toHaveBeenCalledWith( expect.objectContaining( {
					action: 'wbsearchentities',
					type: 'property',
					search: 'something'
				} ) );
				expect( typeaheadComponent.props( 'searchFooterUrl' ) ).toMatch( /search=something.*ns122=1/ );
			} );

			it( 'includes the prefix in the search if the scope changed', async () => {
				await selectComponent.vm.$emit( 'update:selected', 'item' );

				await typeaheadComponent.vm.$emit( 'input', 'p:something' );
				expect( mockedGet ).toHaveBeenCalledWith( expect.objectContaining( {
					action: 'wbsearchentities',
					type: 'item',
					search: 'p:something'
				} ) );
				expect( typeaheadComponent.props( 'searchFooterUrl' ) ).toMatch( /search=p%3Asomething.*ns0=1/ );
			} );
		} );

		describe( 'loading more results', () => {
			beforeEach( async () => {
				mockedGet.mockResolvedValueOnce( searchResultsWithMatches );
				await typeaheadComponent.vm.$emit( 'input', 'cr' );
			} );
			it( 'makes another api call when `load-more` is emitted', async () => {
				await typeaheadComponent.vm.$emit( 'load-more' );

				expect( mockedGet ).toHaveBeenCalledTimes( 2 );
				expect( mockedGet ).toHaveBeenCalledWith( expect.objectContaining( {
					continue: 4
				} ) );
			} );
			it( 'excludes duplicates from the results', async () => {
				const newResult = {
					id: 'Q1126556',
					url: '//www.wikidata.org/wiki/Q1126556',
					display: {
						label: {
							value: 'bird vocalization',
							language: 'en'
						},
						description: {
							value: 'sounds birds use to communicate',
							language: 'en'
						}
					},
					label: 'bird vocalization',
					description: 'sounds birds use to communicate',
					match: {
						type: 'alias',
						language: 'en',
						text: 'crowing'
					}
				};

				const duplicates = searchResultsWithMatches.search.slice( 2, 4 );

				mockedGet.mockResolvedValueOnce(
					Object.assign(
						{},
						searchResultsWithMatches,
						{
							search: duplicates.concat( newResult )
						}
					)
				);

				await typeaheadComponent.vm.$emit( 'load-more' );
				expect( typeaheadComponent.props( 'searchResults' ).map( ( result ) => result.value ) ).toMatchObject(
					[ 'Q666368', 'Q10406', 'Q43365', 'Q26198', 'Q1126556' ]
				);
			} );
		} );

		it( 'has a search button', () => {
			expect( wrapper.findComponent( CdxButton ).exists() ).toBeTruthy();
		} );
	} );
} );
