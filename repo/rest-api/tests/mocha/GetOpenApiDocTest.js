'use strict';

const { execSync } = require( 'node:child_process' );
const { bundle, loadConfig } = require( '@redocly/openapi-core' );
const { expect } = require( './helpers/chaiHelper' );
const { RequestBuilder } = require( './helpers/RequestBuilder' );

describe( 'GET /openapi.json', () => {

	it( 'can GET the latest version of the OpenAPI document', async () => {
		execSync( 'npm run spec:join' );
		const config = await loadConfig( { configPath: 'redocly.yaml' } );
		const schema = ( await bundle( { ref: './specs/openapi-joined.json', config, dereference: true } ) ).bundle.parsed;
		const response = await new RequestBuilder()
			.withRoute( 'GET', '/v1/openapi.json' )
			.makeRequest();

		expect( response ).to.have.status( 200 );

		// Installed extensions may register spec fragments that add paths and
		// tags to the served document, so assert exact equality of the
		// Wikibase-owned surface rather than of the whole document.
		const { paths, tags, ...rest } = schema;
		const { paths: servedPaths, tags: servedTags, ...servedRest } = response.body;
		expect( servedRest ).to.deep.equal( rest );
		for ( const [ path, pathSpec ] of Object.entries( paths ) ) {
			expect( servedPaths[ path ] ).to.deep.equal( pathSpec );
		}
		for ( const tag of tags ) {
			expect( servedTags ).to.deep.include( tag );
		}
	} );

} );
