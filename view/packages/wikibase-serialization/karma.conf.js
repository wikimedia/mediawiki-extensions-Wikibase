var path = require( 'path' );

module.exports = function ( config ) {
	config.set( {
		frameworks: [ 'qunit' ],

		files: [
			'node_modules/jquery/dist/jquery.js',

			// TODO: install JS dependencies using npm
			'node_modules/wikibase-data-values/lib/util/util.inherit.js',
			'node_modules/wikibase-data-values/src/dataValues.js',
			'node_modules/wikibase-data-values/src/DataValue.js',
			'node_modules/wikibase-data-values/src/values/StringValue.js',
			'node_modules/wikibase-data-values/src/values/UnDeserializableValue.js',

			'tests/**/*.tests.js'
		],

		preprocessors: {
			'tests/**/*.tests.js': [ 'webpack' ],
			'node_modules/wikibase-data-model/src/index.js': [ 'webpack' ]
		},

		webpack: {
			mode: 'development',
			resolve: {
				alias: {
					// eslint-disable-next-line no-undef
					'wikibase.datamodel': path.resolve( __dirname, 'node_modules/wikibase-data-model/src/index.js' )
				}
			}
		},

		port: 9876,

		logLevel: config.LOG_INFO,
		browsers: [ 'PhantomJS' ]
	} );
};
