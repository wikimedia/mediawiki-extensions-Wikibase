/* eslint-env node */

'use strict';
module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-jsonlint' );
	grunt.loadNpmTasks( 'grunt-karma' );

	grunt.initConfig( {
		eslint: {
                        all: '.'
                },
		jsonlint: {
			all: [
				'**/*.json',
				'!node_modules/**'
			]
		},
		karma: {
			options: {
				files: [
					'node_modules/jquery/dist/jquery.js',
					'node_modules/sinon/pkg/sinon.js',

					'node_modules/wikibase-data-values/lib/util/util.inherit.js',

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

	grunt.registerTask( 'test', [ 'eslint', 'jsonlint', 'qunit' ] );
	grunt.registerTask( 'qunit', 'karma' );
	grunt.registerTask( 'default', 'test' );
};
