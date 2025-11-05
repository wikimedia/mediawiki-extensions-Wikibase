/**
 * See also: http://webdriver.io/guide/testrunner/configurationfile.html
 */

'use strict';

const { config } = require( 'wdio-mediawiki/wdio-defaults.conf.js' );
const WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

exports.config = {
	...config,

	// ==================
	// Test Files
	// ==================
	specs: [
		__dirname + '/specs/*.js',
		__dirname + '/../../../view/lib/wikibase-termbox/tests/selenium/specs/*.js'
	],

	// ===================
	// Test Configurations
	// ===================

	capabilities: [ {
		...config.capabilities[ 0 ],

		// Setting this enables automatic screenshots for when a browser command fails
		// It is also used by afterTest for capturig failed assertions.
		'mw:screenshotPath': process.env.LOG_DIR || __dirname + '/log'
	} ],

	// Default timeout for each waitFor* command.
	waitforTimeout: 10 * 1000,

	// See also: http://webdriver.io/guide/testrunner/reporters.html
	reporters: [ 'spec' ],

	// =====
	// Hooks
	// =====
	async beforeSuite() {
		await WikibaseApi.initialize(
			undefined,
			browser.options.capabilities[ 'mw:user' ],
			browser.options.capabilities[ 'mw:pwd' ]
		);
	}
};
