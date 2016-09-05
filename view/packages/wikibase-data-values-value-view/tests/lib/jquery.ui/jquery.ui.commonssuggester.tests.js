/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
( function( $, QUnit ) {
	'use strict';

	var newTestSuggester = function() {
		return $( '<input>' )
			.addClass( 'test_suggester' )
			.appendTo( 'body' )
			.commonssuggester();
	};

	QUnit.module( 'jquery.ui.commonssuggester', {
		teardown: function() {
			var $suggester = $( '.test_suggester' ),
				suggester = $suggester.data( 'commonssuggester' );
			if ( suggester ) {
				suggester.destroy();
			}
			$suggester.remove();
		}
	} );

	QUnit.test( 'Create', function( assert ) {
		assert.expect( 1 );
		var $suggester = newTestSuggester();

		assert.ok(
			$suggester.data( 'commonssuggester' ) instanceof $.ui.commonssuggester,
			'Instantiated commons suggester.'
		);
	} );

	QUnit.test( '_grepFileTitleFromTerm', function( assert ) {
		var $suggester = newTestSuggester(),
			suggester = $suggester.data( 'commonssuggester' ),
			testCases = {
				'': '',
				'File:A.jpg': 'File:A.jpg',
				'%41': 'A',
				'A &%26 B.jpg#not-sure-if-URL': 'A && B.jpg#not-sure-if-URL',

				// Find last title=… parameter
				'title=A.jpg&title=B.jpg': 'B.jpg',
				'title=A.jpg/title=%42.jpg': 'B.jpg',
				'title=File%3AA.jpg&oldid=1#title=/X.jpg': 'File:A.jpg',
				'https://commons.wikimedia.org/w/?title=File:%41.jpg&action=edit': 'File:A.jpg',
				'https://commons.wikimedia.org/w/index.php?title=File:A.jpg&oldid=1': 'File:A.jpg',
				'https://commons.wikimedia.org/w/index.php?title=File%3AA.jpg&diff=1': 'File:A.jpg',

				// Find last word after a slash
				'title=A.jpg/B.jpg': 'B.jpg',
				'w/A.jpg': 'A.jpg',
				'/w/A.jpg': 'A.jpg',
				'A.jpg/B.jpg': 'B.jpg',
				'wiki/File:%41.jpg#title=/X.jpg': 'File:A.jpg',
				'/wiki/File:A.jpg': 'File:A.jpg',
				'//commons.wikimedia.org/wiki/File:A.jpg': 'File:A.jpg',
				'https://commons.wikimedia.org/wiki/File:A.jpg': 'File:A.jpg',
				'https://commons.wikimedia.org/wiki/File:A.jpg#filehistory': 'File:A.jpg',
				'https://commons.wikimedia.org/wiki/File:A.jpg?action=history': 'File:A.jpg',
				'https://upload.wikimedia.org/wikipedia/commons/6/66/A.jpg': 'A.jpg',
				'/wikipedia/commons/thumb/6/66/A.jpg/100px-A.jpg': '100px-A.jpg',

				// Minimum is 2 characters
				'/w/': '/w/',
				'title=A': 'title=A',
				'A.jpg/B': 'A.jpg/B',
				'A.jpg/B.jpg/C': 'B.jpg',

				// Do not do anything with invalid URL encoding
				'1%': '1%',
				'title=1%.jpg': 'title=1%.jpg'
			};

		assert.expect( Object.keys( testCases ).length );

		for ( var input in testCases ) {
			var actual = suggester._grepFileTitleFromTerm( input ),
				expected = testCases[input];

			assert.strictEqual( actual, expected );
		}
	} );

}( jQuery, QUnit ) );
