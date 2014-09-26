/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit, $ ) {
'use strict';

QUnit.module( 'wikibase.datamodel.ClaimGroupList' );

function getDefaultClaimGroupList() {
	return new wb.datamodel.ClaimGroupList( [
		new wb.datamodel.ClaimGroup( 'P1', new wb.datamodel.ClaimList( [
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		] ) ),
		new wb.datamodel.ClaimGroup( 'P2', new wb.datamodel.ClaimList( [
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
		] ) )
	] );
}

QUnit.test( 'Constructor', function( assert ) {
	var claimGroupList = getDefaultClaimGroupList();

	assert.ok(
		claimGroupList instanceof wb.datamodel.ClaimGroupList,
		'Instantiated ClaimGroupList.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.ClaimGroupList( ['string1', 'string2'] );
		},
		'Throwing error when trying to instantiate ClaimGroupList without ClaimGroup objects.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.ClaimGroupList( [
				new wb.datamodel.ClaimGroup( 'P1', new wb.datamodel.ClaimList( [
					new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
				] ) ),
				new wb.datamodel.ClaimGroup( 'P1', new wb.datamodel.ClaimList( [
					new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
				] ) )
			] );
		},
		'Throwing error when trying to instantiate ClaimGroupList with multiple '
			+ 'ClaimGroups featuring the same property id.'
	);
} );

QUnit.test( 'getPropertyids()', function( assert ) {
	var propertyIds = getDefaultClaimGroupList().getPropertyIds();

	assert.ok(
		propertyIds.length === 2
		&& $.inArray( 'P1', propertyIds ) !== -1
		&& $.inArray( 'P2', propertyIds ) !== -1,
		'Retrieved property ids.'
	);
} );

QUnit.test( 'getByPropertyId()', function( assert ) {
	assert.ok(
		getDefaultClaimGroupList().getByPropertyId( 'P1' ).equals(
			new wb.datamodel.ClaimGroup( 'P1', new wb.datamodel.ClaimList( [
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
			] ) )
		),
		'Retrieved ClaimGroup object by property id.'
	);

	assert.strictEqual(
		getDefaultClaimGroupList().getByPropertyId( 'does-not-exist' ),
		null,
		'Returning NULL when no ClaimGroup object is set for a particular property id.'
	);
} );

QUnit.test( 'removeByPropertyId() & length attribute', function( assert ) {
	var claimGroupList = getDefaultClaimGroupList();

	assert.equal(
		claimGroupList.length,
		2,
		'ClaimGroupList contains 2 ClaimGroup objects.'
	);

	claimGroupList.removeByPropertyId( 'P1' );

	assert.strictEqual(
		claimGroupList.getByPropertyId( 'P1' ),
		null,
		'Removed ClaimGroup.'
	);

	assert.strictEqual(
		claimGroupList.length,
		1,
		'ClaimGroupList contains 1 ClaimGroup object.'
	);

	claimGroupList.removeByPropertyId( 'does-not-exist' );

	assert.strictEqual(
		claimGroupList.length,
		1,
		'ClaimGroupList contains 1 ClaimGroup object after trying to remove a ClaimGroup that is '
			+ 'not set.'
	);

	claimGroupList.removeByPropertyId( 'P2' );

	assert.strictEqual(
		claimGroupList.getByPropertyId( 'P2' ),
		null,
		'Removed ClaimGroup.'
	);

	assert.strictEqual(
		claimGroupList.length,
		0,
		'ClaimGroupList is empty.'
	);
} );

QUnit.test( 'hasGroupForPropertyId()', function( assert ) {
	assert.ok(
		getDefaultClaimGroupList().hasGroupForPropertyId( 'P2' ),
		'Verified hasGroupForPropertyId() returning TRUE.'
	);

	assert.ok(
		!getDefaultClaimGroupList().hasGroupForPropertyId( 'does-not-exist' ),
		'Verified hasGroupForPropertyId() returning FALSE.'
	);
} );

QUnit.test( 'setGroup() & length attribute', function( assert ) {
	var claimGroupList = getDefaultClaimGroupList(),
		newClaimGroup1 = new wb.datamodel.ClaimGroup( 'P1',
			new wb.datamodel.ClaimList( [
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
			] )
		),
		newClaimGroup3 = new wb.datamodel.ClaimGroup( 'P3',
			new wb.datamodel.ClaimList( [
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P3' ) )
			] )
		),
		emptyClaimGroup1 = new wb.datamodel.ClaimGroup( 'P1' );

	assert.equal(
		claimGroupList.length,
		2,
		'ClaimGroupList contains 2 ClaimGroup objects.'
	);

	claimGroupList.setGroup( newClaimGroup1 );

	assert.ok(
		claimGroupList.getByPropertyId( 'P1' ).equals( newClaimGroup1 ),
		'Set new "P1" ClaimGroup.'
	);

	assert.equal(
		claimGroupList.length,
		2,
		'Length remains unchanged when overwriting a ClaimGroup.'
	);

	claimGroupList.setGroup( newClaimGroup3 );

	assert.ok(
		claimGroupList.getByPropertyId( 'P3' ).equals( newClaimGroup3 ),
		'Added new ClaimGroup.'
	);

	assert.equal(
		claimGroupList.length,
		3,
		'Increased length when adding new ClaimGroup.'
	);

	claimGroupList.setGroup( emptyClaimGroup1 );

	assert.strictEqual(
		claimGroupList.getByPropertyId( 'P1' ),
		null,
		'Removed group by setting an empty group.'
	);

	assert.equal(
		claimGroupList.length,
		2,
		'Decreased length after setting an empty group.'
	);

	assert.throws(
		function() {
			claimGroupList.setGroup( ['string'] );
		},
		'Throwing error when trying to set a plain string array.'
	);
} );

QUnit.test( 'isEmpty()', function( assert ) {
	var claimGroupList = new wb.datamodel.ClaimGroupList();

	assert.ok(
		claimGroupList.isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	claimGroupList.setGroup( new wb.datamodel.ClaimGroup( 'P1',
		new wb.datamodel.ClaimList( [
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		] )
	) );

	assert.ok(
		!claimGroupList.isEmpty(),
		'Verified isEmpty() returning FALSE.'
	);

	claimGroupList.setGroup( new wb.datamodel.ClaimGroup( 'P1' ) );

	assert.ok(
		claimGroupList.isEmpty(),
		'TRUE after removing last ClaimGroup.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	var claimGroupList = getDefaultClaimGroupList();

	assert.ok(
		claimGroupList.equals( getDefaultClaimGroupList() ),
		'Verified equals() retuning TRUE.'
	);

	claimGroupList.setGroup( new wb.datamodel.ClaimGroup( 'P2',
		new wb.datamodel.ClaimList( [
			new wb.datamodel.Claim( new wb.datamodel.PropertySomeValueSnak( 'P2' ) )
		] )
	) );

	assert.ok(
		!claimGroupList.equals( getDefaultClaimGroupList() ),
		'FALSE when a ClaimGroup has been overwritten.'
	);

	claimGroupList = getDefaultClaimGroupList();
	claimGroupList.removeByPropertyId( 'P2' );

	assert.ok(
		!claimGroupList.equals( getDefaultClaimGroupList() ),
		'FALSE when a ClaimGroup has been removed.'
	);

	assert.ok(
		!claimGroupList.equals( [
			getDefaultClaimGroupList().getByPropertyId( 'P1' ),
			getDefaultClaimGroupList().getByPropertyId( 'P2' )
		] ),
		'FALSE when submitting an array instead of a ClaimGroupList instance.'
	);
} );

QUnit.test( 'hasGroup()', function( assert ) {
	assert.ok(
		getDefaultClaimGroupList().hasGroup( new wb.datamodel.ClaimGroup( 'P1',
			new wb.datamodel.ClaimList( [
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
			] )
		) ),
		'Verified hasGroup() returning TRUE.'
	);

	assert.ok(
		!getDefaultClaimGroupList().hasGroup(
			new wb.datamodel.ClaimGroup( 'P1' )
		),
		'Verified hasGroup() returning FALSE.'
	);

	assert.throws(
		function() {
			getDefaultClaimGroupList().hasGroup( 'de-text' );
		},
		'Throwing error when submitting a string array.'
	);
} );

}( wikibase, QUnit, jQuery ) );
