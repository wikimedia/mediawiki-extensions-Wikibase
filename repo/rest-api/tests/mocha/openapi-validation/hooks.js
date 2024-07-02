/**
 * Duplicate the setup from `setup.js`, but in a format that mocha can use as a
 * global beforeAll - https://mochajs.org/#root-hook-plugins
 *
 * We need this to support parallel execution of the openAPI tests.
 * See T368902
 */
'use strict';

const chai = require( 'chai' );
const SwaggerParser = require( '@apidevtools/swagger-parser' );
const { REST } = require( 'api-testing' );
const { default: chaiResponseValidator } = require( 'chai-openapi-response-validator' );

exports.mochaHooks = {
	beforeAll: [
		async function () {
			const spec = await SwaggerParser.dereference( './specs/openapi.json' );
			spec.servers = [ { url: new REST().req.app + 'rest.php/wikibase/v0' } ];
			chai.use( chaiResponseValidator( spec ) );
		}
	]
};
