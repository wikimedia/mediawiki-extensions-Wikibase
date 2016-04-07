/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.StatementGroupSet' );

var testSets = [
	[],
	[
		new wb.datamodel.StatementGroup( 'P1', new wb.datamodel.StatementList( [
			new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
			)
		] ) ),
		new wb.datamodel.StatementGroup( 'P2', new wb.datamodel.StatementList( [
			new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
			)
		] ) )
	]
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		var statementGroupSet = new wb.datamodel.StatementGroupSet( testSets[i] );

		assert.ok(
			statementGroupSet instanceof wb.datamodel.StatementGroupSet,
			'Test set #' + i + ': Instantiated StatementGroupSet.'
		);
	}
} );

}( wikibase, QUnit ) );
