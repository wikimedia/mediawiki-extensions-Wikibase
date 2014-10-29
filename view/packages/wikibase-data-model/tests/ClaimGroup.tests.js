/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.ClaimGroup' );

var testSets = [
	['P1', undefined],
	[
		'P1',
		new wb.datamodel.ClaimList( [
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		] )
	]
];

QUnit.test( 'Constructor', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		var claimGroup = new wb.datamodel.ClaimGroup( testSets[i][0], testSets[i][1] );

		assert.ok(
			claimGroup instanceof wb.datamodel.ClaimGroup,
			'Test set #' + i + ': Instantiated ClaimGroup.'
		);
	}
} );

}( wikibase, QUnit ) );
