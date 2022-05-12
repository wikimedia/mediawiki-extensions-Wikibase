'use strict';

const SwaggerParser = require( '@apidevtools/swagger-parser' );
const chai = require( 'chai' );
const { default: chaiResponseValidator } = require( 'chai-openapi-response-validator' );
const { REST } = require( 'api-testing' );

before( async () => { // eslint-disable-line mocha/no-top-level-hooks
	const spec = await SwaggerParser.dereference( './specs/openapi.json' );
	// dynamically add CI test system to the spec
	spec.servers = [ { url: new REST().req.app + 'rest.php/wikibase/v0' } ];
	chai.use( chaiResponseValidator( spec ) );
} );
