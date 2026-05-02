/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit ) {
	'use strict';

var PropertyNoValueSnak = require( '../src/PropertyNoValueSnak.js' ),
	PropertySomeValueSnak = require( '../src/PropertySomeValueSnak.js' ),
	Claim = require( '../src/Claim.js' ),
	Statement = require( '../src/Statement.js' ),
	SnakList = require( '../src/SnakList.js' );

QUnit.module( 'Claim' );

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 7 );
	var argumentLists = [
		{
			mainSnak: new PropertyNoValueSnak( 'p1' )
		}, {
			mainSnak: new PropertySomeValueSnak( 'p2' ),
			qualifiers: new SnakList( [
				new PropertyNoValueSnak( 'p10' ),
				new PropertySomeValueSnak( 'p10' ),
				new PropertyNoValueSnak( 'p11' )
			] ),
			guid: 'i am a guid'
		}
	];

	for( var i = 0; i < argumentLists.length; i++ ) {
		var args = argumentLists[i],
			claim = new Claim( args.mainSnak, args.qualifiers, args.guid );

		assert.ok(
			claim.getMainSnak().equals( args.mainSnak ),
			'Test set #' + i + ': Main snak is set correctly.'
		);

		assert.ok(
			claim.getQualifiers().equals( args.qualifiers || new SnakList() ),
			'Test set #' + i + ': Qualifiers are set correctly.'
		);

		assert.ok(
			claim.getGuid() === ( args.guid || null ),
			'Test set #' + i + ': GUID is set correctly.'
		);
	}

	assert.throws(
		function() {
			return new Claim();
		},
		'Throwing error when trying to instantiate a Claim without a main Snak.'
	);
} );

QUnit.test( 'setMainSnak() & getMainSnak()', function( assert ) {
	assert.expect( 1 );
	var claim = new Claim( new PropertyNoValueSnak( 'p1' ) ),
		snak = new PropertyNoValueSnak( 'p2' );

	claim.setMainSnak( snak );

	assert.ok(
		claim.getMainSnak().equals( snak ),
		'Altered main Snak.'
	);
} );

QUnit.test( 'setQualifiers() & getQualifiers()', function( assert ) {
	assert.expect( 1 );
	var claim = new Claim( new PropertyNoValueSnak( 'p1' ) ),
		qualifiers = new SnakList( [
			new PropertyNoValueSnak( 'p10' ),
			new PropertyNoValueSnak( 'p11' ),
			new PropertySomeValueSnak( 'p10' )
		] );

	claim.setQualifiers( qualifiers );

	assert.strictEqual(
		claim.getQualifiers(),
		qualifiers,
		'Verified qualifiers being set.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	assert.expect( 17 );
	var claims = [
		new Claim( new PropertyNoValueSnak( 'p1' ) ),
		new Claim( new PropertySomeValueSnak( 'p1' ) ),
		new Claim(
			new PropertyNoValueSnak( 'p1' ),
			new SnakList( [ new PropertyNoValueSnak( 'p10' ) ] )
		),
		new Claim(
			new PropertyNoValueSnak( 'p1' ),
			new SnakList( [ new PropertyNoValueSnak( 'p11' ) ] )
		)
	];

	// Compare claims:
	for( var i = 0; i < claims.length; i++ ) {
		var clonedClaim = new Claim(
			claims[i].getMainSnak(),
			claims[i].getQualifiers(),
			claims[i].getGuid()
		);

		// Check if "cloned" claim is equal:
		assert.ok(
			claims[i].equals( clonedClaim ),
			'Verified claim "' + i + '" on equality.'
		);

		// Compare to all other claims:
		for( var j = 0; j < claims.length; j++ ) {
			if ( j !== i ) {
				assert.ok(
					!claims[i].equals( claims[j] ),
					'Claim "' + i + '" is not equal to claim "'+ j + '".'
				);
			}
		}
	}

	// Compare claim to statement:
	var claim = new Claim( new PropertyNoValueSnak( 'p42' ) ),
		statement = new Statement(
			new Claim( new PropertyNoValueSnak( 'p42' ) )
		);

	assert.ok(
		!claim.equals( statement ),
		'Claim does not equals statement that received nothing but the same claim parameters.'
	);

} );

}( QUnit ) );
