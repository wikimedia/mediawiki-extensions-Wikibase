/* eslint-env node */

'use strict';
module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-contrib-qunit' );

	grunt.initConfig( {
		eslint: {
			options: {
				reportUnusedDisableDirectives: true,
				extensions: [ '.js', '.json' ],
				cache: true
			},
			all: [
				'**/*.{js,json}',
				'!node_modules/**'
			]
		},
		qunit: {
			all: [
				'tests/index.html'
			],
			options: {
				puppeteer: {
					headless: true,
					/*
					 * no-sandbox mode is needed to make qunit work with docker.
					 * It would be nice to do this optionally, so local test runs are still sandboxed...
					 */
					args: [ '--no-sandbox', '--disable-setuid-sandbox' ],
					/*
					 * In case PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true is set, we
					 * need a way to set the Chrome path using an environment
					 * variable.
					 * When grunt-contrib-qunit updates to Puppeteer 1.8.0+, we
					 * can replace that by setting PUPPETEER_EXECUTABLE_PATH in
					 * package.json
					 */
					executablePath: process.env.CHROME_BIN || null
				}
			}
		}
	} );

	grunt.registerTask( 'test', [ 'eslint', 'qunit' ] );
	grunt.registerTask( 'default', 'test' );
};
