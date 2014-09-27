/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.TermSet' );

var testSets = [
	[],
	[
		new wb.datamodel.Term( 'de', 'de-string' ),
		new wb.datamodel.Term( 'en', 'en-string' )
	]
];

QUnit.test( 'Constructor', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			( new wb.datamodel.TermSet( testSets[i] ) ) instanceof wb.datamodel.TermSet,
			'Test set #' + i + ': Instantiated TermSet.'
		);
	}
} );

}( wikibase, QUnit ) );
