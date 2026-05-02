/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit ) {
'use strict';

var Term = require( '../src/Term.js' );

QUnit.module( 'Term' );

var testSets = {
	positive: [
		['de', ''],
		['en', 'some string']
	],
	negative: [
		[undefined, ''],
		['', undefined],
		['de', 1],
		[1, ''],
		['', 'foo']
	]
};

QUnit.test( 'Constructor (positive)', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.positive.length; i++ ) {
		var testSet = testSets.positive[i];
		assert.ok(
			( new Term( testSet[0], testSet[1] ) ) instanceof Term,
			'Test set #' + i +': Instantiated Term.'
		);
	}
} );

QUnit.test( 'Constructor (negative)', function( assert ) {
	assert.expect( 5 );
	function instantiateObject( languageCode, text ) {
		return function() {
			return new Term( languageCode, text );
		};
	}

	for( var i = 0; i < testSets.negative.length; i++ ) {
		var testSet = testSets.negative[i];
		assert.throws(
			instantiateObject( testSet[0], testSet[1] ),
			'Test set #' + i +': Threw expected error.'
		);
	}
} );

QUnit.test( 'equals()', function( assert ) {
	assert.expect( 4 );
	for( var i = 0; i < testSets.positive.length; i++ ) {
		var term1 = new Term( testSets.positive[i][0], testSets.positive[i][1] );

		for( var j = 0; j < testSets.positive.length; j++ ) {
			var term2 = new Term( testSets.positive[j][0], testSets.positive[j][1] );

			if( j === i ) {
				assert.ok(
					term1.equals( term2 ),
					'Test set #' + i + ' equals test set #' + j + '.'
				);
				continue;
			}

			assert.ok(
				!term1.equals( term2 ),
				'Test set #' + i + ' does not equal test set #' + j + '.'
			);
		}
	}
} );

}( QUnit ) );
