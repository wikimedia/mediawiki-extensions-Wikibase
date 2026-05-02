/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit ) {
	'use strict';

var Statement = require( '../src/Statement.js' ),
	Claim = require( '../src/Claim.js' ),
	SnakList = require( '../src/SnakList.js' ),
	Reference = require( '../src/Reference.js' ),
	ReferenceList = require( '../src/ReferenceList.js' ),
	PropertySomeValueSnak = require( '../src/PropertySomeValueSnak.js' ),
	PropertyNoValueSnak = require( '../src/PropertyNoValueSnak.js' );

QUnit.module( 'Statement' );

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 7 );
	var argumentLists = [
		{
			claim: new Claim( new PropertyNoValueSnak( 'p1' ) )
		}, {
			claim: new Claim( new PropertyNoValueSnak( 'p1' ) ),
			references: new ReferenceList( [
				new Reference(),
				new Reference( new SnakList( [
					new PropertyNoValueSnak( 'p10' )
				] ) )
			] ),
			rank: Statement.RANK.PREFERRED
		}
	];

	for( var i = 0; i < argumentLists.length; i++ ) {
		var args = argumentLists[i],
			claim = new Statement( args.claim, args.references, args.rank );

		assert.ok(
			claim.getClaim().equals( args.claim ),
			'Claim is set correctly.'
		);

		assert.ok(
			claim.getReferences().equals( args.references || new ReferenceList() ),
			'References are set correctly.'
		);

		assert.ok(
			claim.getRank() === ( args.rank || Statement.RANK.NORMAL ),
			'Rank is set correctly.'
		);
	}

	assert.throws(
		function() {
			return new Statement();
		},
		'Throwing error when trying to instantiate a Statement without a Claim.'
	);
} );

QUnit.test( 'Rank evaluation on instantiation', function( assert ) {
	assert.expect( 2 );
	var statement = new Statement(
		new Claim(
			new PropertyNoValueSnak( 'P1' )
		)
	);

	assert.equal(
		statement.getRank(),
		Statement.RANK.NORMAL,
		'Assigning \'normal\' rank by default.'
	);

	statement = new Statement(
		new Claim(
			new PropertyNoValueSnak( 'P1' )
		),
		null,
		Statement.RANK.DEPRECATED
	);

	assert.equal(
		statement.getRank(),
		Statement.RANK.DEPRECATED,
		'Instantiated statement object with \'deprecated\' rank.'
	);
} );

QUnit.test( 'setRank() & getRank()', function( assert ) {
	assert.expect( 3 );
	var statement = new Statement(
		new Claim(
			new PropertyNoValueSnak( 'P1' )
		)
	);

	statement.setRank( Statement.RANK.PREFERRED );

	assert.equal(
		statement.getRank(),
		Statement.RANK.PREFERRED,
		'Assigned \'preferred\' rank.'
	);

	statement.setRank( Statement.RANK.DEPRECATED );

	assert.equal(
		statement.getRank(),
		Statement.RANK.DEPRECATED,
		'Assigned \'deprecated\' rank.'
	);

	statement.setRank( Statement.RANK.NORMAL );

	assert.equal(
		statement.getRank(),
		Statement.RANK.NORMAL,
		'Assigned \'normal\' rank.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	assert.expect( 26 );
	var statements = [
		new Statement(
			new Claim( new PropertyNoValueSnak( 'P1' ) )
		),
		new Statement(
			new Claim( new PropertySomeValueSnak( 'P1' ) )
		),
		new Statement(
			new Claim( new PropertySomeValueSnak( 'P1' ) ),
			null,
			Statement.RANK.PREFERRED
		),
		new Statement(
			new Claim( new PropertyNoValueSnak( 'P1' ) ),
			new ReferenceList(
				[new Reference( new SnakList(
					[new PropertyNoValueSnak( 'P10' ) ]
				) )]
			),
			Statement.RANK.PREFERRED
		),
		new Statement(
			new Claim( new PropertyNoValueSnak( 'P1' ) ),
			new ReferenceList(
				[new Reference( new SnakList(
					[new PropertySomeValueSnak( 'P10' ) ]
				) )]
			),
			Statement.RANK.PREFERRED
		)
	];

	// Compare statements:
	for( var i = 0; i < statements.length; i++ ) {
		var clonedStatement = new Statement(
			new Claim(
				statements[i].getClaim().getMainSnak(),
				statements[i].getClaim().getQualifiers(),
				statements[i].getClaim().getGuid()
			),
			statements[i].getReferences(),
			statements[i].getRank()
		);

		// Check if "cloned" statement is equal:
		assert.ok(
			statements[i].equals( clonedStatement ),
			'Verified statement "' + i + '" on equality.'
		);

		// Compare to all other statements:
		for( var j = 0; j < statements.length; j++ ) {
			if ( j !== i ) {
				assert.ok(
					!statements[i].equals( statements[j] ),
					'Statement "' + i + '" is not equal to statement "'+ j + '".'
				);
			}
		}

	}

	// Compare claim to statement:
	var claim = new Claim(
			new PropertyNoValueSnak( 'P1' )
		),
		statement = new Statement(
			new Claim(
				new PropertyNoValueSnak( 'P1' )
			)
		);

	assert.ok(
		!statement.equals( claim ),
		'Statement does not equal claim that received the same initialization parameters.'
	);

} );

}( QUnit ) );
