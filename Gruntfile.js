/* eslint-env node */

module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	grunt.initConfig( {
		eslint: {
			options: {
				extensions: [ '.js', '.json' ],
				cache: true,
				fix: grunt.option( 'fix' )
			},
			all: [
				'**/*.{js,json}',
				'!view/resources/jquery/ui/**',
				'!view/lib/**',
				'!**/node_modules/**',
				'!**/vendor/**',
				'!extensions/**',
				'!client/data-bridge/**',
				'!lib/resources/wikibase-api/**',
				'!lib/packages/wikibase/*/tests/**/*.json',
				'!docs/**',
				'!repo/rest-api/**'
			]
		},
		stylelint: {
			all: [
				'**/*.{css,less}',
				'!view/resources/jquery/ui/**',
				'!view/lib/**',
				'!node_modules/**',
				'!vendor/**',
				'!extensions/**',
				'!client/data-bridge/**',
				'!docs/**',
				'!lib/resources/wikibase-api/**',
				'!repo/rest-api/**'
			]
		},
		banana: {
			options: {
				disallowDuplicateTranslations: false,
				disallowUnusedTranslations: false,
				requireLowerCase: false
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

	grunt.registerTask( 'test', [ 'eslint', 'banana', 'stylelint' ] );
	grunt.registerTask( 'fix', function () {
		grunt.config.set( 'eslint.options.fix', true );
		grunt.task.run( 'eslint' );
	} );
	grunt.registerTask( 'default', 'test' );
};
