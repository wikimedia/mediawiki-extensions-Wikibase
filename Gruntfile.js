/* jshint node: true, strict: false */
module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-jscs' );

	grunt.initConfig( {
		jshint: {
			options: {
				jshintrc: true
			},
			all: '.'
		},
		jscs: {
			all: '.'
		}
	} );

	grunt.registerTask( 'test', [ 'jshint', 'jscs' ] );
};
