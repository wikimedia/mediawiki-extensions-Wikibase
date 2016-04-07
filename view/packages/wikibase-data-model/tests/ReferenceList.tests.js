/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.ReferenceList' );

var testSets = [
	[],
	[
		new wb.datamodel.Reference( new wb.datamodel.SnakList(), 'i am a hash' ),
		new wb.datamodel.Reference( new wb.datamodel.SnakList(), 'i am another hash' ),
		new wb.datamodel.Reference()
	]
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			( new wb.datamodel.ReferenceList( testSets[i] ) ) instanceof wb.datamodel.ReferenceList,
			'Test set #' + i + ': Instantiated ReferenceList.'
		);
	}
} );

}( wikibase, QUnit ) );
