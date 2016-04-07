/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.TermMap' );

var testSets = [
	{},
	{
		de: new wb.datamodel.Term( 'de', 'de-string' ),
		en: new wb.datamodel.Term( 'en', 'en-string' )
	},
	{
		de: new wb.datamodel.Term( 'en', 'en-string' ),
		en: new wb.datamodel.Term( 'en', 'en-string' )
	}
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 3 );
	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			( new wb.datamodel.TermMap( testSets[i] ) ) instanceof wb.datamodel.TermMap,
			'Test set #' + i + ': Instantiated TermMap.'
		);
	}
} );

}( wikibase, QUnit ) );
