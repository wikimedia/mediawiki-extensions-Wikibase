/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.TermGroup' );

var testSets = [
	['en', []],
	['en', ['en-string1', 'en-string2']],
	['de', []]
];

QUnit.test( 'Constructor (positive)', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		var termGroup = new wb.datamodel.TermGroup( testSets[i][0], testSets[i][1] );
		assert.ok(
			termGroup instanceof wb.datamodel.TermGroup,
			'Test set #' + i +': Instantiated TermGroup.'
		);
	}
} );

QUnit.test( 'Constructor (negative)', function( assert ) {
	var negativeTestSets = [
		[undefined, []],
		['', undefined],
		['de', 1],
		[1, '']
	];

	/**
	 * @param {string} languageCode
	 * @param {string[]} strings
	 * @return {Function}
	 */
	function instantiateObject( languageCode, strings ) {
		return function() {
			return new wb.datamodel.TermGroup( languageCode, strings );
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
	for( var i = 0; i < testSets.length; i++ ) {
		var termGroup1 = new wb.datamodel.TermGroup( testSets[i][0], testSets[i][1] );

		for( var j = 0; j < testSets.length; j++ ) {
			var termGroup2 = new wb.datamodel.TermGroup( testSets[j][0], testSets[j][1] );

			if( j === i ) {
				assert.ok(
					termGroup1.equals( termGroup2 ),
					'Test set #' + i + ' equals test set #' + j + '.'
				);
				continue;
			}

			assert.ok(
				!termGroup1.equals( termGroup2 ),
				'Test set #' + i + ' does not equal test set #' + j + '.'
			);
		}
	}
} );

}( wikibase, jQuery, QUnit ) );
