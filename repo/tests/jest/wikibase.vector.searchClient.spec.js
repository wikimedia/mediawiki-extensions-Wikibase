describe( 'Vector Search Client', () => {

	function getFakeMw( fakeConfig, fakeApiInstance ) {
		return {
			config: {
				get: ( key ) => { return fakeConfig[ key ]; },
				set: jest.fn()
			},
			msg: jest.fn().mockImplementation( ( key, ...args ) => {
				if ( key === 'parentheses' ) {
					expect( args ).toHaveLength( 1 );
					return `(${args[ 0 ]})`;
				} else {
					throw new Error( `Unknown message ${key}` );
				}
			} ),
			Api: jest.fn().mockImplementation( () => {
				return fakeApiInstance;
			} )
		};
	}

	// TODO: figure out a nicer way to inject and assert results
	const mockApiResults = [ {
		id: 'Q2497232',
		title: 'Q2497232',
		pageid: 2410715,
		display: {
			label: {
				value: 'Brasilianische Akademie der Wissenschaften',
				language: 'de'
			},
			description: {
				value: 'academy of sciences in Brazil',
				language: 'en'
			}
		},
		repository: 'wikidata',
		url: '//www.wikidata.org/wiki/Q2497232',
		concepturi: 'http://www.wikidata.org/entity/Q2497232',
		label: 'Brasilianische Akademie der Wissenschaften',
		description: 'academy of sciences in Brazil',
		match: {
			type: 'alias',
			language: 'de',
			text: 'ABC'
		},
		aliases: [
			'ABC'
		]
	} ];

	beforeEach( () => {
		// ensure that require( searchClient.js ) has the side-effect we want to test each time
		jest.resetModules();
	} );

	it.each( [
		[ 'fetchByTitle' ],
		[ 'loadMore' ]
	] )( 'test construction and %s behavior', async ( name ) => {
		const fakeApiInstance = {
			get: jest.fn().mockResolvedValue( {
				search: mockApiResults
			} ),
			abort: jest.fn()
		};
		const userLanguage = 'de';
		global.mw = getFakeMw(
			{
				skin: 'vector-2022',
				wgUserLanguage: userLanguage
			},
			fakeApiInstance
		);
		require( '../../resources/wikibase.vector.searchClient.js' );
		expect( global.mw.config.set.mock.calls[ 0 ][ 0 ] ).toBe( 'wgVectorSearchClient' );
		const vectorSearchClient = global.mw.config.set.mock.calls[ 0 ][ 1 ];

		const exampleSearchString = 'abc';
		const vectorOffset = 20;
		const vectorLimit = 10;

		const expectedParams = {
			action: 'wbsearchentities',
			search: exampleSearchString,
			limit: vectorLimit,
			language: userLanguage,
			uselang: userLanguage,
			type: 'item',
			format: 'json',
			errorformat: 'plaintext'
		};
		let apiController;
		if ( name === 'fetchByTitle' ) {
			apiController = vectorSearchClient.fetchByTitle(
				exampleSearchString,
				vectorLimit,
				true
			);
		} else if ( name === 'loadMore' ) {
			apiController = vectorSearchClient.loadMore(
				exampleSearchString,
				vectorOffset,
				vectorLimit,
				true
			);
			expectedParams.continue = vectorOffset;
		} else {
			throw new Error( `Unexpected test name ${name}` );
		}
		expect( fakeApiInstance.get ).toHaveBeenCalledWith( expectedParams );

		const actualTransformedResult = await apiController.fetch;
		expect( actualTransformedResult ).toStrictEqual( {
			query: exampleSearchString,
			results: [
				{
					label: 'Brasilianische Akademie der Wissenschaften',
					description: 'academy of sciences in Brazil',
					language: {
						label: 'de',
						description: 'en',
						match: 'de'
					},
					match: '(ABC)',
					url: '//www.wikidata.org/wiki/Q2497232',
					value: 'Q2497232'
				}
			]
		} );
		expect( mw.msg ).toHaveBeenCalled();
	} );

	const testCases = [];

	testCases.push( {
		name: 'doesn\'t show an extra match for a search hit on the label',
		searchQuery: 'male',
		mockApiResult: {
			id: 'Q6581097',
			title: 'Q6581097',
			pageid: 6406677,
			display: {
				label: {
					value: 'male',
					language: 'en'
				},
				description: {
					value: 'human who is male  (use with P21)',
					language: 'en'
				}
			},
			repository: 'wikidata',
			url: '//www.wikidata.org/wiki/Q6581097',
			concepturi: 'http://www.wikidata.org/entity/Q6581097',
			label: 'male',
			description: 'human who is male  (use with P21)',
			match: {
				type: 'label',
				language: 'en',
				text: 'male'
			},
			aliases: [
				'fireann'
			]
		},
		expectedTransformedResult: {
			query: 'male',
			results: [
				{
					label: 'male',
					description: 'human who is male  (use with P21)',
					language: {
						label: 'en',
						description: 'en',
						match: 'en'
					},
					match: '',
					url: '//www.wikidata.org/wiki/Q6581097',
					value: 'Q6581097'
				}
			]
		}
	} );

	testCases.push( {
		name: 'supports showing a match of a label in a different language',
		searchQuery: 'fireann',
		mockApiResult: {
			id: 'Q6581097',
			title: 'Q6581097',
			pageid: 6406677,
			display: {
				label: {
					value: 'male',
					language: 'en'
				},
				description: {
					value: 'human who is male  (use with P21)',
					language: 'en'
				}
			},
			repository: 'wikidata',
			url: '//www.wikidata.org/wiki/Q6581097',
			concepturi: 'http://www.wikidata.org/entity/Q6581097',
			label: 'male',
			description: 'human who is male  (use with P21)',
			match: {
				type: 'label',
				language: 'ga',
				text: 'fireann'
			},
			aliases: [
				'fireann'
			]
		},
		expectedTransformedResult: {
			query: 'fireann',
			results: [
				{
					label: 'male',
					description: 'human who is male  (use with P21)',
					language: {
						label: 'en',
						description: 'en',
						match: 'ga'
					},
					match: '(fireann)',
					url: '//www.wikidata.org/wiki/Q6581097',
					value: 'Q6581097'
				}
			]
		}
	} );

	it.each( testCases )( '$name', async ( { searchQuery, mockApiResult, expectedTransformedResult } ) => {
		const fakeApiInstance = {
			get: jest.fn().mockResolvedValue( {
				search: [ mockApiResult ]
			} ),
			abort: jest.fn()
		};

		global.mw = getFakeMw(
			{
				skin: 'vector-2022',
				wgUserLanguage: 'en'
			},
			fakeApiInstance
		);
		require( '../../resources/wikibase.vector.searchClient.js' );
		expect( global.mw.config.set.mock.calls[ 0 ][ 0 ] ).toBe( 'wgVectorSearchClient' );
		const vectorSearchClient = global.mw.config.set.mock.calls[ 0 ][ 1 ];

		const apiController = vectorSearchClient.fetchByTitle(
			searchQuery,
			10,
			true
		);

		const actualTransformedResult = await apiController.fetch;
		expect( actualTransformedResult ).toStrictEqual( expectedTransformedResult );
	} );
} );
