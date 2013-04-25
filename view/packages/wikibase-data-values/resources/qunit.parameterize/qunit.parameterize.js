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

			for (var i = 0; i < testCases.length; ++i) {
				var parameters = testCases[i];

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
