'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );

describe( 'GET /property-data-types', () => {

	it( '200 OK response is valid', async () => {
		const response = await new RequestBuilder()
			.withRoute( 'GET', '/property-data-types' )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );
} );
