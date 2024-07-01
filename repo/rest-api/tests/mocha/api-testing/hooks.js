'use strict';
const { action } = require( 'api-testing' );

let waited = false;

/**
 * mocha global beforeAll hook required to support parallel execution of tests.
 * See https://mochajs.org/#root-hook-plugins
 * See T368902
 */
exports.mochaHooks = {
	beforeAll: [
		async function () {
			/**
			 * When the tests start up, they all want to do their per-process
			 * initialization simultaneously. The `api-testing` suite also happens
			 * to be the first test suite run during the
			 * `mediawiki-quibble-apitests-vendor-php74` CI job, so the start-up
			 * requests hit a cold server. The worker processes stampede to
			 * create a bunch of bot users - one for each worker - causing
			 * database deadlocks.
			 * We add an initial login request (`action.root()`), a fixed delay, and a
			 * worker-id-dependent delay here so that we can warm up the server a bit
			 * and avoid all hitting the `createuser` API at the same time.
			 */
			if ( process.env.MOCHA_WORKER_ID && !waited ) {
				waited = true;
				return action.root().then( function () {
					return new Promise( ( resolve ) => {
						setTimeout( resolve, 2500 + 1000 * process.env.MOCHA_WORKER_ID );
					} );
				} );
			}
		}
	]
};
