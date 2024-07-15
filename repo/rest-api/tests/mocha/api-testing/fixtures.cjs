'use strict';
const { getOrCreateBotUser } = require( '../helpers/botUser' );

/**
 * mocha global setup required to support parallel execution of tests.
 * See https://mochajs.org/#global-fixtures
 * See T368902
 */
exports.mochaGlobalSetup = getOrCreateBotUser;
