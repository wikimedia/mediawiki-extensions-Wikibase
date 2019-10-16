/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit, $ ) {
'use strict';

var Statement = require( '../src/Statement.js' ),
	Claim = require( '../src/Claim.js' ),
	StatementList = require( '../src/StatementList.js' ),
	PropertySomeValueSnak = require( '../src/PropertySomeValueSnak.js' ),
	PropertyNoValueSnak = require( '../src/PropertyNoValueSnak.js' );

QUnit.module( 'StatementList' );

var testSets = [
	[],
	[
		new Statement(
			new Claim( new PropertyNoValueSnak( 'P1' ) )
		),
		new Statement(
			new Claim( new PropertyNoValueSnak( 'P2' ) )
		),
		new Statement(
			new Claim( new PropertySomeValueSnak( 'P2' ) )
		)
	]
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			( new StatementList( testSets[i] ) ) instanceof StatementList,
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

		var statementList = new StatementList( testSets[i] );

		assert.deepEqual(
			statementList.getPropertyIds(),
			expectedPropertyIds,
			'Retrieved property ids.'
		);
	}
} );

}( QUnit, jQuery ) );
