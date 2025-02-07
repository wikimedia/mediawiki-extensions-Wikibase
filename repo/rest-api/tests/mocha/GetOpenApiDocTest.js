'use strict';

const { bundle, loadConfig } = require( '@redocly/openapi-core' );
const { expect } = require( './helpers/chaiHelper' );
const { RequestBuilder } = require( './helpers/RequestBuilder' );

describe( 'GET /openapi.json', () => {

	it( 'can GET the latest version of the OpenAPI document', async () => {
		const config = await loadConfig( { configPath: 'redocly.yaml' } );
		const schema = ( await bundle( { ref: './specs/openapi-joined.json', config, dereference: true } ) ).bundle.parsed;
		const response = await new RequestBuilder()
			.withRoute( 'GET', '/v1/openapi.json' )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response.body ).to.deep.equal( schema );
	} );

} );
