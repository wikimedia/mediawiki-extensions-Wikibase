/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $, QUnit ) {
	'use strict';

QUnit.module( 'wikibase.datamodel.Claim' );

QUnit.test( 'Constructor', function( assert ) {
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

	$.each( argumentLists, function( i, args ) {
		var claim = new wb.datamodel.Claim( args.mainSnak, args.qualifiers, args.guid );

		assert.ok(
			claim.getMainSnak().equals( args.mainSnak ),
			'Main snak is set correctly.'
		);

		assert.ok(
			claim.getQualifiers().equals( args.qualifiers || new wb.datamodel.SnakList() ),
			'Qualifiers are set correctly.'
		);

		assert.ok(
			claim.getGuid() === ( args.guid || null ),
			'GUID is set correctly.'
		);
	} );

	assert.throws(
		function() {
			return new wb.datamodel.Claim();
		},
		'Throwing error when trying to instantiate a Claim without a main Snak.'
	);
} );

QUnit.test( 'setMainSnak() & getMainSnak()', function( assert ) {
	var claim = new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'p1' ) ),
		snak = new wb.datamodel.PropertyNoValueSnak( 'p2' );

	claim.setMainSnak( snak );

	assert.ok(
		claim.getMainSnak().equals( snak ),
		'Altered main Snak.'
	);
} );

QUnit.test( 'setQualifiers() & getQualifiers()', function( assert ) {
	var claim = new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'p1' ) ),
		qualifiers = new wb.datamodel.SnakList( [
			new wb.datamodel.PropertyNoValueSnak( 'p10' ),
			new wb.datamodel.PropertyNoValueSnak( 'p11' ),
			new wb.datamodel.PropertySomeValueSnak( 'p10' )
		] );

	claim.setQualifiers( qualifiers );

	assert.ok(
		claim.getQualifiers().equals( qualifiers )
	);

	assert.ok(
		claim.getQualifiers( 'p10' ).equals( new wb.datamodel.SnakList( [
			new wb.datamodel.PropertyNoValueSnak( 'p10' ),
			new wb.datamodel.PropertySomeValueSnak( 'p10' )
		] ) ),
		'Altered qualifiers.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
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
	$.each( claims, function( i, claim ) {
		var clonedClaim = new wb.datamodel.Claim(
			claim.getMainSnak(),
			claim.getQualifiers(),
			claim.getGuid()
		);

		// Check if "cloned" claim is equal:
		assert.ok(
			claim.equals( clonedClaim ),
			'Verified claim "' + i + '" on equality.'
		);

		// Compare to all other claims:
		$.each( claims, function( j, otherClaim ) {
			if ( j !== i ) {
				assert.ok(
					!claim.equals( otherClaim ),
					'Claim "' + i + '" is not equal to claim "'+ j + '".'
				);
			}
		} );

	} );

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

}( wikibase, jQuery, QUnit ) );
