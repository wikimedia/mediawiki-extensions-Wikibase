'use strict';

const SwaggerParser = require( '@apidevtools/swagger-parser' );
const { expect } = require( '../helpers/chaiHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );

describe( 'GET /openapi.json', () => {

	it( 'can GET the latest version of the OpenAPI document', async () => {
		const spec = await SwaggerParser.bundle( './specs/openapi.json' );
		const response = await new RequestBuilder()
			.withRoute( 'GET', '/openapi.json' )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response.body ).to.deep.equal( spec );
	} );

} );
