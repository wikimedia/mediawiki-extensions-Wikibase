'use strict';

const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { RequestBuilder } = require( '../../../../../rest-api/tests/mocha/helpers/RequestBuilder' );

describe( 'GET /property-data-types', () => {
	it( '200 OK response is valid', async () => {
		const response = await new RequestBuilder()
			.withRoute( 'GET', '/v1/property-data-types' )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );
} );
