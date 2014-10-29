/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.MultiTermMap' );

var testSets = [
	{},
	{
		de: new wb.datamodel.MultiTerm( 'de', ['de-string'] ),
		en: new wb.datamodel.MultiTerm( 'en', ['en-string'] )
	},
	{
		de: new wb.datamodel.MultiTerm( 'en', ['en-string'] ),
		en: new wb.datamodel.MultiTerm( 'en', ['en-string'] )
	}
];

QUnit.test( 'Constructor', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			( new wb.datamodel.MultiTermMap( testSets[i] ) ) instanceof wb.datamodel.MultiTermMap,
			'Test set #' + i + ': Instantiated MultiTermMap.'
		);
	}
} );

}( wikibase, QUnit ) );
