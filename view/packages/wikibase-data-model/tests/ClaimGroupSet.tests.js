/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.ClaimGroupSet' );

var testSets = [
	[],
	[
		new wb.datamodel.ClaimGroup( 'P1', new wb.datamodel.ClaimList( [
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		] ) ),
		new wb.datamodel.ClaimGroup( 'P2', new wb.datamodel.ClaimList( [
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
		] ) )
	]
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		var claimGroupSet = new wb.datamodel.ClaimGroupSet( testSets[i] );

		assert.ok(
			claimGroupSet instanceof wb.datamodel.ClaimGroupSet,
			'Test set #' + i + ': Instantiated ClaimGroupSet.'
		);
	}
} );

}( wikibase, QUnit ) );
