'use strict';
/* eslint-env node */

module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-jsonlint' );
	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-karma' );
	grunt.loadNpmTasks( 'grunt-composer' );

	grunt.initConfig( {
		eslint: {
			all: '.'
		},
		banana: {
			options: {
				requireCompleteMessageDocumentation: false,
				disallowUnusedDocumentation: false,
				disallowUnusedTranslations: false,
				disallowDuplicateTranslations: false
			},
			all: 'i18n/'
		},
		jsonlint: {
			all: [
				'*.json',
				'**/*.json',
				'!node_modules/**'
			]
		},
		karma: {
			options: {
				files: [
					'node_modules/jquery/dist/jquery.js',
					'node_modules/jquery-ui/ui/jquery.ui.core.js',
					'node_modules/jquery-ui/ui/jquery.ui.widget.js',
					'node_modules/jquery-ui/ui/jquery.ui.position.js',
					'node_modules/jquery-ui/ui/jquery.ui.menu.js',
					'node_modules/jquery-client/jquery.client.js',
					'node_modules/jquery-migrate/dist/jquery-migrate.js',

					'node_modules/sinon/pkg/sinon.js',

					'vendor/data-values/javascript/lib/util/util.inherit.js',

					'lib/jquery/jquery.PurposedCallbacks.js',
					'lib/jquery/*.js',
					'lib/jquery.event/*.js',
					'lib/jquery.util/*.js',
					'lib/util/*.js',
					'lib/jquery.ui/jquery.ui.ooMenu.js',
					'lib/jquery.ui/jquery.ui.suggester.js',
					'lib/jquery.ui/*.js',

					'vendor/data-values/javascript/lib/globeCoordinate/globeCoordinate.js',
					'vendor/data-values/javascript/src/dataValues.js',
					'vendor/data-values/javascript/src/DataValue.js',
					'vendor/data-values/javascript/src/valueFormatters/valueFormatters.js',
					'vendor/data-values/javascript/src/valueFormatters/formatters/ValueFormatter.js',
					'vendor/data-values/javascript/src/valueFormatters/formatters/*.js',
					'vendor/data-values/javascript/src/valueParsers/valueParsers.js',
					'vendor/data-values/javascript/src/valueParsers/ValueParserStore.js',
					'vendor/data-values/javascript/src/valueParsers/parsers/ValueParser.js',
					'vendor/data-values/javascript/src/valueParsers/parsers/*.js',
					'vendor/data-values/javascript/src/values/*.js',

					'tests/phantomjs.bootstrap.js',

					'src/jquery.valueview.valueview.js',
					'src/*.js',
					'src/ExpertExtender/ExpertExtender.js',
					'src/ExpertExtender/*.js',
					'src/experts/StringValue.js',
					'src/experts/*.js',

					'tests/sinon-qunit.js',

					'tests/lib/jquery/*.js',
					'tests/lib/jquery.event/*.js',
					'tests/lib/jquery.ui/*.js',
					'tests/lib/jquery.util/*.js',
					'tests/lib/util/*.js',

					'tests/src/jquery.valueview.tests.MockExpert.js',
					'tests/src/*.js',
					'tests/src/experts/*.js',
					'tests/src/ExpertExtender/testExpertExtenderExtension.js',
					'tests/src/ExpertExtender/*.js'
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

	grunt.registerTask( 'test', [ 'eslint', 'jsonlint', 'banana', 'qunit' ] );
	grunt.registerTask( 'qunit', [ 'composer:install:no-dev', 'karma' ] );
	grunt.registerTask( 'default', 'test' );
};
