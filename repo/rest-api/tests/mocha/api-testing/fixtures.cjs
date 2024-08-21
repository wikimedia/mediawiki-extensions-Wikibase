'use strict';
const { getOrCreateBotUser, getOrCreateAuthTestUser } = require( '../helpers/testUsers' );

/**
 * mocha global setup required to support parallel execution of tests.
 * See https://mochajs.org/#global-fixtures
 * See T368902
 *
 * @return {Promise}
 */
exports.mochaGlobalSetup = () => getOrCreateBotUser().then( getOrCreateAuthTestUser );
