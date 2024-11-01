'use strict';

const chai = require( 'chai' );
const SwaggerParser = require( '@apidevtools/swagger-parser' );
const { REST } = require( 'api-testing' );
const { default: chaiResponseValidator } = require( 'chai-openapi-response-validator' );

/**
 * mocha global beforeAll hook required to support parallel execution of OpenAPI tests.
 * See https://mochajs.org/#root-hook-plugins
 * See T368902
 */
exports.mochaHooks = {
	beforeAll: [
		async function () {
			const spec = await SwaggerParser.dereference( './specs/openapi.json' );
			spec.servers = [ { url: new REST().req.app + 'rest.php/wikibase/v1' } ];
			chai.use( chaiResponseValidator( spec ) );
		}
	]
};
