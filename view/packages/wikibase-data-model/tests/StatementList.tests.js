/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit, $ ) {
'use strict';

QUnit.module( 'wikibase.datamodel.StatementList' );

var testSets = [
	[],
	[
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		),
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
		),
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertySomeValueSnak( 'P2' ) )
		)
	]
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			( new wb.datamodel.StatementList( testSets[i] ) ) instanceof wb.datamodel.StatementList,
			'Test set #' + i + ': Instantiated StatementList.'
		);
	}
} );

QUnit.test( 'getPropertyIds()', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		var expectedPropertyIds = [];

		for( var j = 0; j < testSets[i].length; j++ ) {
			var propertyId = testSets[i][j].getClaim().getMainSnak().getPropertyId();
			if( $.inArray( propertyId, expectedPropertyIds ) === -1 ) {
				expectedPropertyIds.push( propertyId );
			}
		}

		var statementList = new wb.datamodel.StatementList( testSets[i] );

		assert.deepEqual(
			statementList.getPropertyIds(),
			expectedPropertyIds,
			'Retrieved property ids.'
		);
	}
} );

}( wikibase, QUnit, jQuery ) );
