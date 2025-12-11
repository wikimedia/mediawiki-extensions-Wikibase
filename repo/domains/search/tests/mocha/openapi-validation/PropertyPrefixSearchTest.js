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
		.withRoute( 'GET', '/v0/suggest/properties' )
		.withQueryParam( 'language', language )
		.withQueryParam( 'q', searchTerm );
}

describe( 'property prefix search', () => {
	const propertyEnLabel = utils.title( 'english-label' );
	let property;

	before( async () => {
		property = await createProperty( {
			data_type: 'string',
			labels: { en: propertyEnLabel }
		} );

		await wiki.runAllJobs();
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 ); // apparently the index update still needs a bit after runAllJobs()
		} );
	} );

	it( '200 - non-empty search response', async () => {
		// Remove the last 3 characters to ensure only a prefix match
		const response = await newSearchRequest( 'en', propertyEnLabel.slice( 0, -3 ) )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.lengthOf( response.body.results, 1 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '200 - empty search response', async () => {
		const response = await newSearchRequest( 'en', utils.uniq( 40 ) )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.lengthOf( response.body.results, 0 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '200 - search response by ID (without language match)', async () => {
		const response = await newSearchRequest( 'en', property.id )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );
		assert.lengthOf( response.body.results, 1 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 - invalid language code', async () => {
		const response = await newSearchRequest( 'not_a_valid_language', utils.uniq( 40 ) )
			.assertInvalidRequest()
			.makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );
} );
