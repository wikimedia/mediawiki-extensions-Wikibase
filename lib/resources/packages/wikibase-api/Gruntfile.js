/* eslint-env node */

'use strict';
module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-karma' );

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
		karma: {
			options: {
				files: [
					'node_modules/jquery/dist/jquery.js',
					'node_modules/sinon/pkg/sinon.js',

					'tests/mediaWiki.mock.js',

					'src/namespace.js',
					'src/RepoApi.js',
					'src/RepoApiError.js',

					'tests/RepoApi.tests.js',
					'tests/RepoApiError.tests.js'
				],
				singleRun: true,
				logLevel: 'DEBUG',
				frameworks: [ 'qunit' ]
			},
			all: {
				browsers: [ 'PhantomJS' ]
			}
		}
	} );

	grunt.registerTask( 'test', [ 'eslint', 'qunit' ] );
	grunt.registerTask( 'qunit', 'karma' );
	grunt.registerTask( 'default', 'test' );
};
