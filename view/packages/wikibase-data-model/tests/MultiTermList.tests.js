/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.MultiTermList' );

var testSets = [
	[],
	[
		new wb.datamodel.MultiTerm( 'de', ['de-string'] ),
		new wb.datamodel.MultiTerm( 'en', ['en-string'] )
	]
];

QUnit.test( 'Constructor', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			( new wb.datamodel.MultiTermList( testSets[i] ) ) instanceof wb.datamodel.MultiTermList,
			'Test set #' + i + ': Instantiated MultiTermList.'
		);
	}
} );

}( wikibase, QUnit ) );
