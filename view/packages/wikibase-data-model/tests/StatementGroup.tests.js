/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit ) {
'use strict';

var Statement = require( '../src/Statement.js' ),
	Claim = require( '../src/Claim.js' ),
	StatementList = require( '../src/StatementList.js' ),
	StatementGroup = require( '../src/StatementGroup.js' ),
	PropertyNoValueSnak = require( '../src/PropertyNoValueSnak.js' );

QUnit.module( 'StatementGroup' );

var testSets = [
	['P1', undefined],
	[
		'P1',
		new StatementList( [
			new Statement(
				new Claim( new PropertyNoValueSnak( 'P1' ) )
			)
		] )
	]
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		var statementGroup = new StatementGroup( testSets[i][0], testSets[i][1] );

		assert.ok(
			statementGroup instanceof StatementGroup,
			'Test set #' + i + ': Instantiated StatementGroup.'
		);
	}
} );

}( QUnit ) );
