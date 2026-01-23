'use strict';

const { assert, utils, wiki } = require( 'api-testing' );
const { RequestBuilder } = require( '../../../../../rest-api/tests/mocha/helpers/RequestBuilder' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );

async function createItem( item ) {
	return ( await new RequestBuilder()
		.withRoute( 'POST', '/v1/entities/items' )
		.withJsonBodyParam( 'item', item )
		.makeRequest() ).body;
}

function newSearchRequest( language, searchTerm ) {
	return new RequestBuilder()
		.withRoute( 'GET', '/v1/search/items' )
		.withQueryParam( 'language', language )
		.withQueryParam( 'q', searchTerm );
}

describe( 'Simple item search', () => {
	let item1;
	let itemWithoutDescription;
	let itemWithoutLabel;

	const englishTermMatchingAllItems = 'label-' + utils.uniq();
	const item1Label = englishTermMatchingAllItems;
	const item1Description = 'item 1 en';
	const item1GermanLabel = 'de-label-' + utils.uniq();
	const item1GermanDescription = 'item 1 de';

	const item2Label = englishTermMatchingAllItems;

	const item3Description = 'item without label';
	const item3Alias = englishTermMatchingAllItems;

	before( async () => {
		item1 = await createItem( {
			labels: { en: item1Label, de: item1GermanLabel },
			descriptions: { en: item1Description, de: item1GermanDescription }
		} );
		itemWithoutDescription = await createItem( { labels: { en: item2Label } } );

		itemWithoutLabel = await createItem( {
			descriptions: { en: item3Description },
			aliases: { en: [ item3Alias ] }
		} );

		await wiki.runAllJobs();
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 ); // apparently the index update still needs a bit after runAllJobs()
		} );
	} );

	describe( '200 success response', () => {
		it( 'finds items matching the search term', async () => {
			const language = 'en';
			const response = await newSearchRequest( language, englishTermMatchingAllItems )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );

			const results = response.body.results;
			assert.lengthOf( results, 3 );

			const item1Result = results.find( ( { id } ) => id === item1.id );
			assert.deepEqual( item1Result, {
				id: item1.id,
				'display-label': { language, value: item1Label },
				description: { language, value: item1Description },
				match: { type: 'label', language, text: item1Label }
			} );

			const item2Result = results.find( ( { id } ) => id === itemWithoutDescription.id );
			assert.deepEqual( item2Result, {
				id: itemWithoutDescription.id,
				'display-label': { language, value: item2Label },
				description: null,
				match: { type: 'label', language, text: item2Label }
			} );
		} );

		it( 'finds items matching the search term in another language', async () => {
			const language = 'de';
			const response = await newSearchRequest( language, item1GermanLabel )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.deepEqual( response.body.results, [ {
				id: item1.id,
				'display-label': { language, value: item1GermanLabel },
				description: { language, value: item1GermanDescription },
				match: { type: 'label', language, text: item1GermanLabel }
			} ] );
		} );

		it( 'finds item without a label by alias', async () => {
			const language = 'en';
			const response = await newSearchRequest( language, item3Alias )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );

			const results = response.body.results;
			assert.lengthOf( results, 3 );

			const itemResult = results.find( ( { id } ) => id === itemWithoutLabel.id );
			assert.deepEqual( itemResult, {
				id: itemWithoutLabel.id,
				'display-label': { language, value: item3Alias },
				description: { language, value: item3Description },
				match: { type: 'alias', language, text: item3Alias }
			} );
		} );

		it( 'finds an item by a label in a fallback language', async () => {
			const language = 'de-ch';
			const expectedFallbackLanguage = 'de';
			const response = await newSearchRequest( language, item1GermanLabel )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.deepEqual( response.body.results, [ {
				id: item1.id,
				'display-label': { language: expectedFallbackLanguage, value: item1GermanLabel },
				description: { language: expectedFallbackLanguage, value: item1GermanDescription },
				match: { type: 'label', language: expectedFallbackLanguage, text: item1GermanLabel }
			} ] );
		} );

		it( 'finds an item by Q-ID', async () => {
			const response = await newSearchRequest( 'en', item1.id )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.deepEqual( response.body.results, [ {
				id: item1.id,
				'display-label': { language: 'en', value: item1Label },
				description: { language: 'en', value: item1Description },
				match: { type: 'entityId', text: item1.id }
			} ] );
		} );

		it( 'finds nothing if no items match', async () => {
			const response = await newSearchRequest( 'en', utils.uniq( 40 ) )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );

			const results = response.body.results;
			assert.lengthOf( results, 0 );
		} );

		describe( 'pagination', () => {
			let allMatchingResults;
			const searchLanguage = 'en';

			before( async () => {
				const response = await newSearchRequest( searchLanguage, englishTermMatchingAllItems )
					.assertValidRequest()
					.makeRequest();

				expect( response ).to.have.status( 200 );
				allMatchingResults = response.body.results;
			} );

			Object.entries( {
				'with limit and offset parameters': {
					params: { limit: 2, offset: 1 },
					expectedResults: () => [ allMatchingResults[ 1 ], allMatchingResults[ 2 ] ]
				},
				'with just limit parameter': {
					params: { limit: 1 },
					expectedResults: () => [ allMatchingResults[ 0 ] ]
				},
				'with just offset parameter': {
					params: { offset: 2 },
					expectedResults: () => [ allMatchingResults[ 2 ] ]
				}
			} ).forEach( ( [ title, { params, expectedResults } ] ) => {
				it( `finds items matching the search term ${title}`, async () => {
					const request = newSearchRequest( searchLanguage, englishTermMatchingAllItems );

					Object.entries( params ).forEach( ( [ key, value ] ) => {
						request.withQueryParam( key, value );
					} );

					const response = await request.assertValidRequest().makeRequest();
					expect( response ).to.have.status( 200 );

					const results = response.body.results;
					assert.deepEqual( results, expectedResults() );
				} );
			} );
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid language code', async () => {
			const response = await newSearchRequest( 'not_a_language', 'search term' )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-query-parameter', { parameter: 'language' } );
		} );

		it( 'User-Agent empty', async () => {
			const response = await newSearchRequest( 'en', 'search term' )
				.withHeader( 'user-agent', '' )
				.makeRequest();

			assertValidError( response, 400, 'missing-user-agent' );
		} );

		Object.entries( {
			'invalid limit parameter - exceeds max limit 500': {
				parameter: 'limit',
				value: 501
			},
			'invalid limit parameter - negative limit': {
				parameter: 'limit',
				value: -1
			},
			'invalid offset parameter - negative offset': {
				parameter: 'offset',
				value: -2
			}
		} ).forEach( ( [ title, { parameter, value } ] ) => {
			it( title, async () => {

				const response = await newSearchRequest( 'en', 'search term' )
					.withQueryParam( parameter, value )
					.makeRequest();

				assertValidError( response, 400, 'invalid-query-parameter', { parameter } );
			} );
		} );
	} );
} );
