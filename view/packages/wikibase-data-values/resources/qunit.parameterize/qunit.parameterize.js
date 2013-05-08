/**
 * Parameterize v 0.2
 * A QUnit Addon For Running Parameterized Tests
 *
 * @see https://github.com/AStepaniuk/qunit-parameterize
 * @licence MIT licence
 *
 * @example <code>
 * QUnit
 * .cases( [
 *     { a : 2, b : 2, expectedSum : 4 },
 *     { a : 5, b : 5, expectedSum : 10 },
 *     { a : 40, b : 2, expectedSum : 42 }
 * ] )
 * .test( 'Sum test', function( params, assert ) {
 *     var actualSum = sum( params.a, params.b );
 *     assert.equal( actualSum, params.expectedSum );
 * } );
 * </code>
 */
QUnit.cases = ( function( QUnit ) {
	'use strict';

	/**
	 * @param {[]|Function} testCases An Array (or a callback returning such an object) which
	 *        has to hold different Objects where each defines what will be passed to tests which
	 *        will be registered to the Object returned by the function. By providing a callback,
	 *        the parameters provided to the tests will be created separately for each test. This
	 *        allows to provide instances which involve state without running into problems when
	 *        manipulating state in one test case but expecting initial state in another one.
	 * @return {Object}
	 */
	return function(testCases) {
		var createTest = function(methodName, title, expected, callback, parameters) {
			QUnit[methodName](
				title,
				expected,
				function(assert) { return callback.call(this, parameters, assert); }
			);
		};

		var iterateTestCases = function(methodName, title, expected, callback) {
			if (!testCases) return;

			if (!callback) {
				callback = expected;
				expected = null;
			}

			var testTestCases = QUnit.is( "function", testCases )
				? testCases()
				: testCases;

			for (var i = 0; i < testTestCases.length; ++i) {
				var parameters = testTestCases[i];

				var testCaseTitle = title;
				if (parameters.title) {
					testCaseTitle += "[" + parameters.title + "]";
				}

				createTest(methodName, testCaseTitle, expected, callback, parameters);
			}
		}

		return {
			test : function(title, expected, callback) {
				iterateTestCases("test", title, expected, callback);
				return this;
			},

			asyncTest : function(title, expected, callback) {
				iterateTestCases("asyncTest", title, expected, callback);
				return this;
			}
		}
	}

}( QUnit ) );
