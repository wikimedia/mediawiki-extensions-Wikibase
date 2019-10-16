/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit ) {
	'use strict';

var Property = require( '../src/Property.js' ),
	Statement = require( '../src/Statement.js' ),
	StatementGroup = require( '../src/StatementGroup.js' ),
	StatementGroupSet = require( '../src/StatementGroupSet.js' ),
	StatementList = require( '../src/StatementList.js' ),
	Claim = require( '../src/Claim.js' ),
	PropertyNoValueSnak = require( '../src/PropertyNoValueSnak.js' ),
	Fingerprint = require( '../src/Fingerprint.js' ),
	Term = require( '../src/Term.js' ),
	MultiTerm = require( '../src/MultiTerm.js' ),
	TermMap = require( '../src/TermMap.js' ),
	MultiTermMap = require( '../src/MultiTermMap.js' );

QUnit.module( 'Property' );

var testSets = [
	[
		'P1',
		'i am a data type id',
		new Fingerprint(
			new TermMap(),
			new TermMap(),
			new MultiTermMap()
		),
		new StatementGroupSet()
	], [
		'P2',
		'i am a data type id',
		new Fingerprint(
			new TermMap( { de: new Term( 'de', 'de-label' ) } ),
			new TermMap( { de: new Term( 'de', 'de-description' ) } ),
			new MultiTermMap( {
				de: new MultiTerm( 'de', ['de-alias'] )
			} )
		),
		new StatementGroupSet( [
			new StatementGroup( 'P1',
				new StatementList( [
					new Statement(
						new Claim( new PropertyNoValueSnak( 'P1' ) )
					)
				] )
			)
		] )
	]
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		var property = new Property(
			testSets[i][0], testSets[i][1], testSets[i][2], testSets[i][3]
		);
		assert.ok(
			property instanceof Property,
			'Instantiated Property object.'
		);
	}
} );

QUnit.test( 'isEmpty()', function( assert ) {
	assert.expect( 3 );
	assert.ok(
		( new Property(
			'P1',
			'i am a data type id',
			new Fingerprint(
				new TermMap(),
				new TermMap(),
				new MultiTermMap()
			),
			new StatementGroupSet()
		) ).isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	assert.ok(
		!( new Property(
			'P1',
			'i am a data type id',
			new Fingerprint(
				new TermMap( { de: new Term( 'de', 'de-term' ) } ),
				new TermMap(),
				new MultiTermMap()
			),
			new StatementGroupSet()
		) ).isEmpty(),
		'Returning FALSE when Fingerprint is not empty.'
	);

	assert.ok(
		!( new Property(
			'P1',
			'i am a data type id',
			new Fingerprint(
				new TermMap(),
				new TermMap(),
				new MultiTermMap()
			),
			new StatementGroupSet( [
				new StatementGroup( 'P1',
					new StatementList( [new Statement(
						new Claim( new PropertyNoValueSnak( 'P1' ) )
					)] )
				)
			] )
		) ).isEmpty(),
		'Returning FALSE when StatementGroupSet is not empty.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	assert.expect( 4 );
	for( var i = 0; i < testSets.length; i++ ) {
		var property1 = new Property(
			testSets[i][0], testSets[i][1], testSets[i][2], testSets[i][3]
		);

		for( var j = 0; j < testSets.length; j++ ) {
			var property2 = new Property(
				testSets[j][0], testSets[j][1], testSets[j][2], testSets[j][3]
			);

			if( i === j ) {
				assert.ok(
					property1.equals( property2 ),
					'Test set #' + i + ' equals test set #' + j + '.'
				);
				continue;
			}

			assert.ok(
				!property1.equals( property2 ),
				'Test set #' + i + ' does not equal test set #' + j + '.'
			);
		}
	}
} );

}( QUnit ) );
