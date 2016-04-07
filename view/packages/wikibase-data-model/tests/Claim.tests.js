/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
	'use strict';

QUnit.module( 'wikibase.datamodel.Claim' );

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 7 );
	var argumentLists = [
		{
			mainSnak: new wb.datamodel.PropertyNoValueSnak( 'p1' )
		}, {
			mainSnak: new wb.datamodel.PropertySomeValueSnak( 'p2' ),
			qualifiers: new wb.datamodel.SnakList( [
				new wb.datamodel.PropertyNoValueSnak( 'p10' ),
				new wb.datamodel.PropertySomeValueSnak( 'p10' ),
				new wb.datamodel.PropertyNoValueSnak( 'p11' )
			] ),
			guid: 'i am a guid'
		}
	];

	for( var i = 0; i < argumentLists.length; i++ ) {
		var args = argumentLists[i],
			claim = new wb.datamodel.Claim( args.mainSnak, args.qualifiers, args.guid );

		assert.ok(
			claim.getMainSnak().equals( args.mainSnak ),
			'Test set #' + i + ': Main snak is set correctly.'
		);

		assert.ok(
			claim.getQualifiers().equals( args.qualifiers || new wb.datamodel.SnakList() ),
			'Test set #' + i + ': Qualifiers are set correctly.'
		);

		assert.ok(
			claim.getGuid() === ( args.guid || null ),
			'Test set #' + i + ': GUID is set correctly.'
		);
	}

	assert.throws(
		function() {
			return new wb.datamodel.Claim();
		},
		'Throwing error when trying to instantiate a Claim without a main Snak.'
	);
} );

QUnit.test( 'setMainSnak() & getMainSnak()', function( assert ) {
	assert.expect( 1 );
	var claim = new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'p1' ) ),
		snak = new wb.datamodel.PropertyNoValueSnak( 'p2' );

	claim.setMainSnak( snak );

	assert.ok(
		claim.getMainSnak().equals( snak ),
		'Altered main Snak.'
	);
} );

QUnit.test( 'setQualifiers() & getQualifiers()', function( assert ) {
	assert.expect( 1 );
	var claim = new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'p1' ) ),
		qualifiers = new wb.datamodel.SnakList( [
			new wb.datamodel.PropertyNoValueSnak( 'p10' ),
			new wb.datamodel.PropertyNoValueSnak( 'p11' ),
			new wb.datamodel.PropertySomeValueSnak( 'p10' )
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
		new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'p1' ) ),
		new wb.datamodel.Claim( new wb.datamodel.PropertySomeValueSnak( 'p1' ) ),
		new wb.datamodel.Claim(
			new wb.datamodel.PropertyNoValueSnak( 'p1' ),
			new wb.datamodel.SnakList( [ new wb.datamodel.PropertyNoValueSnak( 'p10' ) ] )
		),
		new wb.datamodel.Claim(
			new wb.datamodel.PropertyNoValueSnak( 'p1' ),
			new wb.datamodel.SnakList( [ new wb.datamodel.PropertyNoValueSnak( 'p11' ) ] )
		)
	];

	// Compare claims:
	for( var i = 0; i < claims.length; i++ ) {
		var clonedClaim = new wb.datamodel.Claim(
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
	var claim = new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'p42' ) ),
		statement = new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'p42' ) )
		);

	assert.ok(
		!claim.equals( statement ),
		'Claim does not equals statement that received nothing but the same claim parameters.'
	);

} );

}( wikibase, QUnit ) );
