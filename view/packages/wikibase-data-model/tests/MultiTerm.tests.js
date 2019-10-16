/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit ) {
'use strict';

var MultiTerm = require( '../src/MultiTerm.js' );

QUnit.module( 'MultiTerm' );

var testSets = [
	['en', []],
	['en', ['en-string1', 'en-string2']],
	['de', []]
];

QUnit.test( 'Constructor (positive)', function( assert ) {
	assert.expect( 3 );
	for( var i = 0; i < testSets.length; i++ ) {
		var multiTerm = new MultiTerm( testSets[i][0], testSets[i][1] );
		assert.ok(
			multiTerm instanceof MultiTerm,
			'Test set #' + i +': Instantiated MultiTerm.'
		);
	}
} );

QUnit.test( 'Constructor (negative)', function( assert ) {
	assert.expect( 5 );
	var negativeTestSets = [
		[undefined, []],
		['', undefined],
		['de', 1],
		[1, ''],
		['', []]
	];

	/**
	 * @param {string} languageCode
	 * @param {string[]} strings
	 * @return {Function}
	 */
	function instantiateObject( languageCode, strings ) {
		return function() {
			return new MultiTerm( languageCode, strings );
		};
	}

	for( var i = 0; i < negativeTestSets.length; i++ ) {
		assert.throws(
			instantiateObject( negativeTestSets[i][0], negativeTestSets[i][1] ),
			'Test set #' + i +': Threw expected error.'
		);
	}
} );

QUnit.test( 'equals()', function( assert ) {
	assert.expect( 9 );
	for( var i = 0; i < testSets.length; i++ ) {
		var multiTerm1 = new MultiTerm( testSets[i][0], testSets[i][1] );

		for( var j = 0; j < testSets.length; j++ ) {
			var multiTerm2 = new MultiTerm( testSets[j][0], testSets[j][1] );

			if( j === i ) {
				assert.ok(
					multiTerm1.equals( multiTerm2 ),
					'Test set #' + i + ' equals test set #' + j + '.'
				);
				continue;
			}

			assert.ok(
				!multiTerm1.equals( multiTerm2 ),
				'Test set #' + i + ' does not equal test set #' + j + '.'
			);
		}
	}
} );

}( QUnit ) );
