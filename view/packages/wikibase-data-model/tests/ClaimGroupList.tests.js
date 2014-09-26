/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.ClaimGroupList' );

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
	for( var i = 0; i < testSets.length; i++ ) {
		var claimGroupList = new wb.datamodel.ClaimGroupList( testSets[i] );

		assert.ok(
			claimGroupList instanceof wb.datamodel.ClaimGroupList,
			'Test set #' + i + ': Instantiated ClaimGroupList.'
		);
	}
} );

}( wikibase, QUnit ) );
