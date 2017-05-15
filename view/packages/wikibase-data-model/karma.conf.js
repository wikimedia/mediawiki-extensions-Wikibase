// Karma configuration
// Generated on Mon May 15 2017 15:46:55 GMT+0200 (CEST)

module.exports = function ( config ) {
	config.set( {

		// base path that will be used to resolve all patterns (eg. files, exclude)
		basePath: '',

		// frameworks to use
		// available frameworks: https://npmjs.org/browse/keyword/karma-adapter
		frameworks: [ 'qunit' ],

		// list of files / patterns to load in the browser
		files: [
			'node_modules/jquery/dist/jquery.js',
			'vendor/data-values/javascript/src/dataValues.js',
			'vendor/data-values/javascript/lib/util/util.inherit.js',
			'vendor/data-values/javascript/src/DataValue.js',
			'src/__namespace.js',
			'src/Claim.js',
			'src/Entity.js',
			'src/Fingerprint.js',
			'src/GroupableCollection.js',
			'src/Group.js',
			'src/Map.js',
			'src/MultiTerm.js',
			'src/Reference.js',
			'src/SiteLink.js',
			'src/Snak.js',
			'src/Statement.js',
			'src/Term.js',
			'src/Set.js',
			'src/List.js',
			'vendor/data-values/javascript/lib/globeCoordinate/globeCoordinate.js',
			'vendor/data-values/javascript/lib/globeCoordinate/globeCoordinate.GlobeCoordinate.js',
			'vendor/data-values/javascript/src/valueFormatters/valueFormatters.js',
			'vendor/data-values/javascript/src/valueParsers/valueParsers.js',
			'vendor/data-values/javascript/src/dataValues.js',
			'vendor/data-values/javascript/src/*.js',
			'vendor/data-values/javascript/src/values/*.js',
			'src/*.js',
			'tests/*.js'
		],

		// list of files to exclude
		exclude: [],

		// preprocess matching files before serving them to the browser
		// available preprocessors: https://npmjs.org/browse/keyword/karma-preprocessor
		preprocessors: {},

		// test results reporter to use
		// possible values: 'dots', 'progress'
		// available reporters: https://npmjs.org/browse/keyword/karma-reporter
		reporters: [ 'progress' ],

		// web server port
		port: 9876,

		// enable / disable colors in the output (reporters and logs)
		colors: true,

		// level of logging
		// possible values: config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
		logLevel: config.LOG_INFO,

		// enable / disable watching file and executing tests whenever any file changes
		autoWatch: true,

		// start these browsers
		// available browser launchers: https://npmjs.org/browse/keyword/karma-launcher
		browsers: [ 'PhantomJS' ],

		// Continuous Integration mode
		// if true, Karma captures browsers, runs the tests and exits
		singleRun: false,

		// Concurrency level
		// how many browser should be started simultaneous
		concurrency: Infinity
	} )
}
