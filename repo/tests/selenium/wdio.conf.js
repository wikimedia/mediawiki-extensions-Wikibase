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
		await WikibaseApi.initialize();
	},

	onComplete() {
		try {
			return config.onComplete();
		} catch ( _ ) {
			// ignore TypeError: Cannot read properties of undefined (reading 'project') [T407831]
			// remove this onComplete() override again once we’re on a version of wdio-mediawiki
			// with a fix (maybe 6.0.1?)
		}
	}
};
