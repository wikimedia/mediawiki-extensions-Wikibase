/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.ClaimList' );

/**
 * @return {wikibase.datamodel.ClaimList}
 */
function getDefaultClaimList() {
	return new wb.datamodel.ClaimList( [
		new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'guid1' ),
		new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ), null, 'guid21' ),
		new wb.datamodel.Claim( new wb.datamodel.PropertySomeValueSnak( 'P2' ), null, 'guid22' )
	] );
}

QUnit.test( 'Constructor', function( assert ) {
	assert.ok(
		getDefaultClaimList() instanceof wb.datamodel.ClaimList,
		'Instantiated ClaimList.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.ClaimList( ['string1', 'string2'] );
		},
		'Throwing error when trying to instantiate ClaimList with other than Claim objects.'
	);
} );

QUnit.test( 'hasClaim()', function( assert ) {
	assert.ok(
		getDefaultClaimList().hasClaim(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ), null, 'guid21' )
		),
		'Verified hasClaim() returning TRUE.'
	);

	assert.ok(
		!getDefaultClaimList().hasClaim(
			new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P9999' ), null, 'guid9999'
			)
		),
		'Verified hasClaim() returning FALSE.'
	);
} );

QUnit.test( 'addClaim() & length attribute', function( assert ) {
	var claimList = getDefaultClaimList();

	assert.equal(
		claimList.length,
		3,
		'ClaimList contains 3 Claim objects.'
	);

	claimList.addClaim(
		new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P3' ), null, 'guid3' )
	);

	assert.ok(
		claimList.hasClaim(
			new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P3' ), null, 'guid3'
			)
		),
		'Added Claim.'
	);

	assert.equal(
		claimList.length,
		4,
		'Increased length.'
	);
} );

QUnit.test( 'removeClaim()', function( assert ) {
	var claimList = getDefaultClaimList();

	assert.equal(
		claimList.length,
		3,
		'ClaimList contains 3 Claim objects.'
	);

	assert.throws(
		function() {
			claimList.removeClaim(
				new wb.datamodel.Claim(
					new wb.datamodel.PropertyNoValueSnak( 'P9999' ), null, 'guid9999'
				)
			);
		},
		'Throwing error when trying to remove a Claim not set.'
	);

	assert.throws(
		function() {
			claimList.removeClaim(
				new wb.datamodel.Claim(
					new wb.datamodel.PropertyNoValueSnak( 'P2' ),
					null,
					'i am a guid'
				)
			);
		},
		'Throwing error when trying to remove a Claim which only differs in the GUID to an '
		+ 'existing claim not set.'
	);

	claimList.removeClaim(
		new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ), null, 'guid21' )
	);

	assert.ok(
		!claimList.hasClaim(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ), null, 'guid21' )
		),
		'Removed Claim.'
	);

	assert.equal(
		claimList.length,
		2,
		'ClaimList contains 2 Claim objects.'
	);
} );

QUnit.test( 'getPropertyIds()', function( assert ) {
	var claimList = getDefaultClaimList();

	assert.deepEqual(
		claimList.getPropertyIds(),
		['P1', 'P2'],
		'Retrieved property ids.'
	);
} );

QUnit.test( 'isEmpty()', function( assert ) {
	var claimList = new wb.datamodel.ClaimList(),
		claim = new wb.datamodel.Claim(
			new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'guid1'
		);

	assert.ok(
		claimList.isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	claimList.addClaim( claim );

	assert.ok(
		!claimList.isEmpty(),
		'Verified isEmpty() returning FALSE.'
	);

	claimList.removeClaim( claim );

	assert.ok(
		claimList.isEmpty(),
		'TRUE after removing last Claim.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	var claimList = getDefaultClaimList();

	assert.ok(
		claimList.equals( getDefaultClaimList() ),
		'Verified equals() retuning TRUE.'
	);

	claimList.addClaim(
		new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P3' ), null, 'guid3' )
	);

	assert.ok(
		!claimList.equals( getDefaultClaimList() ),
		'FALSE after adding another Claim object.'
	);
} );

QUnit.test( 'indexOf()', function( assert ) {
	var referenceList = getDefaultClaimList();

	assert.strictEqual(
		referenceList.indexOf(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ), null, 'guid21' )
		),
		1,
		'Retrieved correct index.'
	);
} );

}( wikibase, QUnit ) );
