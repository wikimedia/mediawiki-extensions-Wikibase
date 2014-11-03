/**
 * Test runner for QUnit tests powered by RequireJS.
 * The test runner may be instantiated and started on a plain HTML page. After starting the test
 * runner, an iframe for every test module is created and attached to the DOM one after another as
 * soon as the tests of the currently run test module have finished. The iframe loads the HTML
 * testRunner specified in the config parameters. This concept ensures loading only the dependencies
 * specified for the particular tests.
 *
 * @option {string[]} queue
 *         Set of test modules to run as defined via requireJS.
 *
 * @option {string} [testRunner]
 *         The path to the testRunner HTML file that executes QUnit tests.
 *         Default: location.path
 *
 * Example:
 * testRunner = new TestRunner( {
 *   queue: tests.modules
 * } );
 * testRunner.start();
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

/* global console */
this.TestRunner = ( function( console ) {
	'use strict';

	/**
	 * @param {HTMLElement} tests
	 * @return {string[]}
	 */
	function getFailedTestResults( tests ) {
		var assertList = tests.getElementsByClassName( 'qunit-assert-list' ),
			testResults = [],
			i;

		for( i = 0; i < assertList.length; i++ ) {
			var fail = assertList[i].getElementsByClassName( 'fail' );
			for( var j = 0; j < fail.length; j++ ) {
				testResults.push( {
					message: fail[j].getElementsByClassName( 'test-message' )[0].innerText,
					html: fail[j].innerHTML
				} );
			}
		}

		return testResults;
	}

	/**
	 * Test runner for QUnit tests powered by requrieJS.
	 *
	 * @param {Object} options
	 * @constructor
	 *
	 * @throws {Error} if a required configuration option is not set
	 */
	var TestRunner = function( options ) {
		if( !options.queue ) {
			throw new Error( 'No tests specified' );
		}

		this._options = options || {};
		this._options.testRunner = this._options.testRunner || location.pathname;
		this._interval = null;
		this._iFrames = [];
	};

	/**
	 * Starts running a set of test modules.
	 */
	TestRunner.prototype.start = function() {
		var self = this,
			currentModule,
			globalFailures = 0,
			queue = this._options.queue;

		console.log( 'TEST START' );

		// Interval polling the most recently add iframe whether its test(s) have been finished:
		this._interval = setInterval( function() {
			if( self._iFrames.length === 0 ) {
				currentModule = queue.pop();
				self._generateFrame( currentModule );
			}

			var currentFrame = self._iFrames[self._iFrames.length - 1],
				frameWindow = currentFrame.contentWindow,
				frameBody = currentFrame.contentDocument.body;

			if( !frameBody || !currentFrame.contentWindow.require ) {
				return;
			}

			var testResult = frameWindow.document.getElementById( 'qunit-testresult' ),
				tests = frameWindow.document.getElementById( 'qunit-tests' );

			if( !testResult ) {
				return;
			}

			var failed = testResult.getElementsByClassName( 'failed' );

			if( failed.length > 0 ) {
				// Test hast finished.
				var localFailures = parseInt( failed[0].firstChild.nodeValue, 10 );

				if( localFailures === 0 ) {
					console.log( currentModule + ': passed' );
				} else {
					console.error( currentModule + ': FAILED (' + localFailures + ' failures)' );

					var failedResults = getFailedTestResults( tests );
					for( var i = 0; i < failedResults.length; i++ ) {
						console.error( currentModule + ': ' + failedResults[i].message );
					}

					globalFailures += localFailures;
				}

				if( queue.length === 0 ) {
					clearInterval( self._interval );
					console.log( 'TEST END (' + globalFailures + ' failure(s))' );
				} else {
					currentModule = queue.pop();
					self._generateFrame( currentModule );
				}
			}
		}, 100 );

	};

	/**
	 * Creates an iframe to run a test module in.
	 *
	 * @param {string} module
	 */
	TestRunner.prototype._generateFrame = function( module ) {
		var iFrame = document.createElement( 'iframe' );

		document.body.appendChild( iFrame );

		iFrame.setAttribute( 'src', this._options.testRunner + '?test=' + module );
		iFrame.setAttribute( 'id', 'testFrame-' + module.replace( /\./g, '-' ) );
		iFrame.setAttribute( 'style', 'width: 100%' );

		this._iFrames.push( iFrame );
	};

	/**
	 * Extracts the module to test from the URI. Returns an array with the module name as single
	 * value. If the module is not within the list of modules passed to the function, the array
	 * of test modules is returned.
	 *
	 * @return {string[]}
	 */
	TestRunner.filterTestModules = function( testModules ) {
		var queryString = ( function( urlParams ) {
			var params = {};
			if( urlParams.length === 1 && urlParams[0] === '' ) {
				return params;
			}
			for( var i = 0; i < urlParams.length; i++ ) {
				var param = urlParams[i].split( '=' );
				if( param.length === 2 ) {
					params[param[0]] = decodeURIComponent( param[1].replace( /\+/g, ' ' ) );
				}
			}
			return params;
		} )( window.location.search.substr( 1 ).split( '&' ) );

		for( var i = 0; i < testModules.length; i++ ) {
			if( testModules[i] === queryString.test ) {
				return [queryString.test];
			}
		}

		return testModules;
	};

	return TestRunner;

}( console ) );
