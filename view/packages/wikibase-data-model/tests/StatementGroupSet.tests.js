/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit ) {
'use strict';

var Statement = require( '../src/Statement.js' ),
	Claim = require( '../src/Claim.js' ),
	StatementList = require( '../src/StatementList.js' ),
	StatementGroupSet = require( '../src/StatementGroupSet.js' ),
	StatementGroup = require( '../src/StatementGroup.js' ),
	PropertyNoValueSnak = require( '../src/PropertyNoValueSnak.js' );

QUnit.module( 'StatementGroupSet' );

var testSets = [
	[],
	[
		new StatementGroup( 'P1', new StatementList( [
			new Statement(
				new Claim( new PropertyNoValueSnak( 'P1' ) )
			)
		] ) ),
		new StatementGroup( 'P2', new StatementList( [
			new Statement(
				new Claim( new PropertyNoValueSnak( 'P2' ) )
			)
		] ) )
	]
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		var statementGroupSet = new StatementGroupSet( testSets[i] );

		assert.ok(
			statementGroupSet instanceof StatementGroupSet,
			'Test set #' + i + ': Instantiated StatementGroupSet.'
		);
	}
} );

}( QUnit ) );
