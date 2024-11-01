'use strict';

const { bundle, loadConfig } = require( '@redocly/openapi-core' );
const chai = require( 'chai' );
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
			const config = await loadConfig( { configPath: 'redocly.yaml' } );
			const schema = ( await bundle( { ref: './specs/openapi.json', config, dereference: true } ) ).bundle.parsed;

			schema.servers = [ { url: new REST().req.app + 'rest.php/wikibase/v1' } ];

			chai.use( chaiResponseValidator( schema ) );
		}
	]
};
