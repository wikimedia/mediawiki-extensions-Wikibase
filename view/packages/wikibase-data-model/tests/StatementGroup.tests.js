/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.StatementGroup' );

var testSets = [
	['P1', undefined],
	[
		'P1',
		new wb.datamodel.StatementList( [
			new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
			)
		] )
	]
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		var statementGroup = new wb.datamodel.StatementGroup( testSets[i][0], testSets[i][1] );

		assert.ok(
			statementGroup instanceof wb.datamodel.StatementGroup,
			'Test set #' + i + ': Instantiated StatementGroup.'
		);
	}
} );

}( wikibase, QUnit ) );
