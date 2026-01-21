'use strict';

const { assert, utils, wiki } = require( 'api-testing' );
const { RequestBuilder } = require( '../../../../../rest-api/tests/mocha/helpers/RequestBuilder' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );

async function createProperty( property ) {
	return ( await new RequestBuilder()
		.withRoute( 'POST', '/v1/entities/properties' )
		.withJsonBodyParam( 'property', property )
		.makeRequest() ).body;
}

function newSearchRequest( language, searchTerm ) {
	return new RequestBuilder()
		.withRoute( 'GET', '/v1/search/properties' )
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

		it( 'finds a property by P-ID', async () => {
			const response = await newSearchRequest( 'en', property1.id )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.deepEqual( response.body.results, [ {
				id: property1.id,
				'display-label': { language: 'en', value: property1.labels.en },
				description: { language: 'en', value: property1.descriptions.en },
				match: { type: 'entityId', text: property1.id }
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

		describe( 'pagination', () => {
			let allMatchingResults;
			const searchLanguage = 'en';

			before( async () => {
				const response = await newSearchRequest( searchLanguage, englishTermMatchingTwoProperties )
					.assertValidRequest()
					.makeRequest();

				expect( response ).to.have.status( 200 );
				allMatchingResults = response.body.results;
			} );

			Object.entries( {
				'with limit and offset parameters': {
					params: { limit: 1, offset: 1 },
					expectedResults: () => [ allMatchingResults[ 1 ] ]
				},
				'with just limit parameter': {
					params: { limit: 1 },
					expectedResults: () => [ allMatchingResults[ 0 ] ]
				},
				'with just offset parameter': {
					params: { offset: 1 },
					expectedResults: () => [ allMatchingResults[ 1 ] ]
				}
			} ).forEach( ( [ title, { params, expectedResults } ] ) => {
				it( `finds properties matching the search term ${title}`, async () => {
					const request = newSearchRequest( searchLanguage, englishTermMatchingTwoProperties );

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
