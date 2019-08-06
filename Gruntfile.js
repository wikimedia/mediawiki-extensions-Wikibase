/* eslint-env node */

module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-jsonlint' );
	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	grunt.initConfig( {
		eslint: {
			options: {
				reportUnusedDisableDirectives: true
			},
			all: '.',
			fix: {
				options: {
					fix: true
				},
				src: [
					'**/*.js',
					'!Gruntfile.js',
					'!node_modules/**',
					'!client/data-bridge/**',
					'!view/resources/jquery/ui/**',
					'!view/lib/**',
					'!lib/resources/vendor/**',
					'!lib/tests/**'
				]
			}
		},
		jsonlint: {
			all: [
				'**/*.json',
				'!view/lib/**',
				'!node_modules/**',
				'!vendor/**',
				'!extensions/**',
				'!client/data-bridge/**'
			]
		},
		stylelint: {
			all: [
				'**/*.css',
				'**/*.less',
				'!view/resources/jquery/ui/**',
				'!view/lib/**',
				'!node_modules/**',
				'!vendor/**',
				'!extensions/**',
				'!client/data-bridge/**'
			]
		},
		banana: {
			options: {
				disallowDuplicateTranslations: false,
				disallowUnusedTranslations: false
			},
			all: [
				'client/i18n/',
				'client/i18n/api/',
				'lib/i18n/',
				'repo/i18n/',
				'repo/i18n/api/'
			]
		}
	} );

	grunt.registerTask( 'test', [ 'eslint', 'jsonlint', 'banana', 'stylelint' ] );
	grunt.registerTask( 'fix', 'eslint:fix' );
	grunt.registerTask( 'default', 'test' );
};
