'use strict';

const { assert, utils, wiki } = require( 'api-testing' );
const { RequestBuilder } = require( '../../../../../rest-api/tests/mocha/helpers/RequestBuilder' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );

async function createItem( item ) {
	return ( await new RequestBuilder()
		.withRoute( 'POST', '/v1/entities/items' )
		.withJsonBodyParam( 'item', item )
		.makeRequest() ).body;
}

function newSearchRequest( language, searchTerm ) {
	return new RequestBuilder()
		.withRoute( 'GET', '/v0/search/items' )
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

		it( 'finds nothing if no items match', async () => {
			const response = await newSearchRequest( 'en', utils.uniq( 40 ) )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );

			const results = response.body.results;
			assert.lengthOf( results, 0 );
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid language code', async () => {
			const response = await newSearchRequest( 'not_a_language', 'search term' )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );

			assert.header( response, 'Content-Language', 'en' );
			assert.strictEqual( response.body.code, 'invalid-query-parameter' );
			assert.deepStrictEqual( response.body.context, { parameter: 'language' } );
		} );

		it( 'User-Agent empty', async () => {
			const response = await newSearchRequest( 'en', 'search term' )
				.withHeader( 'user-agent', '' )
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'missing-user-agent' );
		} );
	} );

} );
