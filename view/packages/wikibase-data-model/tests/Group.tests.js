/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit, $ ) {
'use strict';

QUnit.module( 'wikibase.datamodel.ClaimGroup' );

/**
 * @constructor
 * @param {string} key
 */
var TestConstructor = function( key ) {
	this._key = key;
};
$.extend( TestConstructor.prototype, {
	equals: function( other ) {
		return other === this;
	},
	getKey: function() {
		return this._key;
	}
} );

var TestListConstructor = function()

/**
 * @param {number} n
 * @return {TestConstructor[]}
 */
function getTestItems( n ) {
	var items = [];

	for( var i = 0; i < n; i++ ) {
		items.push( new TestConstructor( '' + i ) );
	}

	return items;
}

function createList( items ) {
	return new wb.datamodel.UnorderedList( TestConstructor, 'getKey', items );
}


QUnit.test( 'Constructor', function( assert ) {
	var items = getTestItems( 2 ),
		group = new wb.datamodel.Group( items );

	var claimGroup = getDefaultClaimGroup();

	assert.ok(
		claimGroup instanceof wb.datamodel.ClaimGroup,
		'Instantiated ClaimGroup.'
	);

	assert.equal(
		claimGroup.getPropertyId(),
		'P1',
		'Verified property id.'
	);

	assert.ok(
		claimGroup.getClaimList().equals( new wb.datamodel.ClaimList( [
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		] ) ),
		'Verified ClaimList.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.ClaimGroup( 'P1', new wb.datamodel.ClaimList( [
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
			] ) );
		},
		'Throwing error when trying to instantiate ClaimGroup mismatching property ids.'
	);
} );

QUnit.test( 'setClaimList() & getClaimList()', function( assert ) {
	var claimGroup = getDefaultClaimGroup(),
		claimList = new wb.datamodel.ClaimList( [
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		] );

	assert.ok(
		claimGroup.getClaimList() !== new wb.datamodel.ClaimList( [
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		] ),
		'Not returning original ClaimList object.'
	);

	claimGroup.setClaimList( claimList );

	assert.ok(
		claimGroup.getClaimList().equals( claimList ),
		'Set new ClaimList.'
	);

	assert.throws(
		function() {
			claimGroup.setClaimList( new wb.datamodel.ClaimList( [
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
			] ) );
		},
		'Throwing error when trying to set a ClaimList with mismatching property id.'
	);
} );

QUnit.test( 'addClaim() & hasClaim()', function( assert ) {
	var claimGroup = getDefaultClaimGroup();

	claimGroup.addClaim(
		new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'guid' )
	);

	assert.ok(
		claimGroup.hasClaim(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'guid' )
		),
		'Verified having added a Claim.'
	);

	assert.throws(
		function() {
			claimGroup.addClaim(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
			);
		},
		'Throwing error when trying to add a Claim that does not match the ClaimGroup '
			+ 'object\'s property id.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	var claimGroup = getDefaultClaimGroup();

	assert.ok(
		claimGroup.equals( getDefaultClaimGroup() ),
		'Verified equals() retuning TRUE.'
	);

	claimGroup.addClaim(
		new wb.datamodel.Claim( new wb.datamodel.PropertySomeValueSnak( 'P1' ) )
	);

	assert.ok(
		!claimGroup.equals( getDefaultClaimGroup() ),
		'FALSE after adding another Claim object.'
	);
} );

}( wikibase, QUnit, jQuery ) );
