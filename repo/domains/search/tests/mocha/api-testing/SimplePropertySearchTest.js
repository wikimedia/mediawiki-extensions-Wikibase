'use strict';

const { assert, utils, wiki } = require( 'api-testing' );
const { RequestBuilder } = require( '../../../../../rest-api/tests/mocha/helpers/RequestBuilder' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );

async function createProperty( property ) {
	return ( await new RequestBuilder()
		.withRoute( 'POST', '/v1/entities/properties' )
		.withJsonBodyParam( 'property', property )
		.makeRequest() ).body;
}

function newSearchRequest( language, searchTerm ) {
	return new RequestBuilder()
		.withRoute( 'GET', '/v0/search/properties' )
		.withQueryParam( 'language', language )
		.withQueryParam( 'q', searchTerm );
}

describe( 'Simple property search', () => {
	let property1;
	let property2;

	const englishTermMatchingTwoProperties = 'label-' + utils.uniq();

	before( async () => {
		property1 = await createProperty( {
			data_type: 'string',
			labels: { en: englishTermMatchingTwoProperties, de: 'de-label-' + utils.uniq() },
			descriptions: { en: 'property 1 en', de: 'property 1 de' }
		} );

		property2 = await createProperty( {
			data_type: 'string',
			aliases: { en: [ englishTermMatchingTwoProperties ] }
		} );

		await wiki.runAllJobs();
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 ); // apparently the index update still needs a bit after runAllJobs()
		} );
	} );

	describe( '200 success response', () => {
		it( 'finds properties matching the search term', async () => {
			const language = 'en';
			const response = await newSearchRequest( language, englishTermMatchingTwoProperties )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );

			const results = response.body.results;
			assert.lengthOf( results, 2 );

			const property1Result = results.find( ( { id } ) => id === property1.id );
			assert.deepEqual( property1Result, {
				id: property1.id,
				'display-label': { language, value: property1.labels.en },
				description: { language, value: property1.descriptions.en },
				match: { type: 'label', language, text: property1.labels.en }
			} );

			const property2Result = results.find( ( { id } ) => id === property2.id );
			assert.deepEqual( property2Result, {
				id: property2.id,
				'display-label': { language, value: property2.aliases.en[ 0 ] },
				description: null,
				match: { type: 'alias', language, text: property2.aliases.en[ 0 ] }
			} );
		} );

		it( 'finds properties matching the search term in another language', async () => {
			const language = 'de';
			const response = await newSearchRequest( language, property1.labels.de )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.deepEqual( response.body.results, [ {
				id: property1.id,
				'display-label': { language, value: property1.labels.de },
				description: { language, value: property1.descriptions.de },
				match: { type: 'label', language, text: property1.labels.de }
			} ] );
		} );

		it( 'finds property without a label by alias', async () => {
			const language = 'en';
			const response = await newSearchRequest( language, property2.aliases.en[ 0 ] )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );

			const propertyResult = response.body.results.find( ( { id } ) => id === property2.id );
			assert.deepEqual( propertyResult, {
				id: property2.id,
				'display-label': { language, value: property2.aliases.en[ 0 ] },
				description: null,
				match: { type: 'alias', language, text: property2.aliases.en[ 0 ] }
			} );
		} );

		it( 'finds a property by a label in a fallback language', async () => {
			const language = 'de-ch';
			const expectedFallbackLanguage = 'de';
			const response = await newSearchRequest( language, property1.labels.de )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.deepEqual( response.body.results, [ {
				id: property1.id,
				'display-label': { language: expectedFallbackLanguage, value: property1.labels.de },
				description: { language: expectedFallbackLanguage, value: property1.descriptions.de },
				match: { type: 'label', language: expectedFallbackLanguage, text: property1.labels.de }
			} ] );
		} );

		it( 'finds nothing if no properties match', async () => {
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
