module.exports = function ( config ) {
	config.set( {
		frameworks: [ 'qunit' ],

		files: [
			'node_modules/jquery/dist/jquery.js',
			'lib/util/util.inherit.js',
			'lib/globeCoordinate/globeCoordinate.js',
			'lib/globeCoordinate/globeCoordinate.GlobeCoordinate.js',
			'lib/globeCoordinate/globeCoordinate.Formatter.js',
			'src/dataValues.js',
			'src/DataValue.js',
			'src/values/*.js',
			'src/valueFormatters/valueFormatters.js',
			'src/valueFormatters/formatters/ValueFormatter.js',
			'src/valueFormatters/formatters/*.js',
			'src/valueParsers/valueParsers.js',
			'src/valueParsers/ValueParserStore.js',
			'src/valueParsers/parsers/ValueParser.js',
			'src/valueParsers/parsers/*.js',
			'tests/lib/globeCoordinate/*.js',
			'tests/src/dataValues.tests.js',
			'tests/src/dataValues.DataValue.tests.js',
			'tests/src/values/*.js',
			'tests/src/valueFormatters/valueFormatters.tests.js',
			'tests/src/valueFormatters/formatters/*.js',
			'tests/src/valueParsers/valueParsers.tests.js',
			'tests/src/valueParsers/ValueParserStore.tests.js',
			'tests/src/valueParsers/parsers/*.js'
		],

		port: 9876,

		logLevel: config.LOG_INFO,
		browsers: [ 'PhantomJS' ]
	} );
};
