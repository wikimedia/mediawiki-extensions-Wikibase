/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit ) {
	'use strict';

	QUnit.module( 'jquery.util.highlightMatchingCharacters' );

	QUnit.test( 'Basic tests', function( assert ) {

		var testCases = [
			['', '', ''],
			['abc', 'abc', '<b>abc</b>'],
			['abcdef', 'abc', '<b>abc</b>def'],
			['abcdef', 'def', 'abcdef'],
			['abcdef', 'Abc', 'abcdef'],
			['Abcdef', 'abc', 'Abcdef'],
			['ABCDEF', 'abc', 'ABCDEF'],
			['abcdef', 'abc', '<b>abc</b>def', true],
			['Abcdef', 'abc', '<b>Abc</b>def', true],
			['abcdef', 'Abc', '<b>abc</b>def', true],
			['ABCDEF', 'ABC', '<b>ABC</b>DEF', true]
		];

		for( var i = 0; i < testCases.length; i++ ) {
			var string = testCases[i][0],
				substring = testCases[i][1],
				expected = testCases[i][2],
				caseInsensitive = !!testCases[i][3];

			assert.equal(
				$.util.highlightMatchingCharacters( substring, string, caseInsensitive ),
				expected,
				'Highlighting "' + substring + '" in "' + string + '" case '
				+ ( caseInsensitive ? 'insensitive' : 'sensitive' )
				+ ' results in "' + expected + '"'
			);
		}

	} );

}( jQuery, QUnit ) );