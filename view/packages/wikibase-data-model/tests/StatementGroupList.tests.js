/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit, $ ) {
'use strict';

QUnit.module( 'wikibase.datamodel.StatementGroupList' );

function getDefaultStatementGroupList() {
	return new wb.datamodel.StatementGroupList( [
		new wb.datamodel.StatementGroup( 'P1', new wb.datamodel.StatementList( [
			new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
			)
		] ) ),
		new wb.datamodel.StatementGroup( 'P2', new wb.datamodel.StatementList( [
			new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
			)
		] ) )
	] );
}

QUnit.test( 'Constructor', function( assert ) {
	var statementGroupList = getDefaultStatementGroupList();

	assert.ok(
		statementGroupList instanceof wb.datamodel.StatementGroupList,
		'Instantiated StatementGroupList.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.StatementGroupList( ['string1', 'string2'] );
		},
		'Throwing error when trying to instantiate StatementGroupList without StatementGroup objects.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.StatementGroupList( [
				new wb.datamodel.StatementGroup( 'P1', new wb.datamodel.StatementList( [
					new wb.datamodel.Statement(
						new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
					)
				] ) ),
				new wb.datamodel.StatementGroup( 'P1', new wb.datamodel.StatementList( [
					new wb.datamodel.Statement(
						new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
					)
				] ) )
			] );
		},
		'Throwing error when trying to instantiate StatementGroupList with multiple '
			+ 'StatementGroups featuring the same property id.'
	);
} );

QUnit.test( 'getPropertyids()', function( assert ) {
	var propertyIds = getDefaultStatementGroupList().getPropertyIds();

	assert.ok(
		propertyIds.length === 2
		&& $.inArray( 'P1', propertyIds ) !== -1
		&& $.inArray( 'P2', propertyIds ) !== -1,
		'Retrieved property ids.'
	);
} );

QUnit.test( 'getByPropertyId()', function( assert ) {
	assert.ok(
		getDefaultStatementGroupList().getByPropertyId( 'P1' ).equals(
			new wb.datamodel.StatementGroup( 'P1', new wb.datamodel.StatementList( [
				new wb.datamodel.Statement(
					new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
				)
			] ) )
		),
		'Retrieved StatementGroup object by property id.'
	);

	assert.strictEqual(
		getDefaultStatementGroupList().getByPropertyId( 'does-not-exist' ),
		null,
		'Returning NULL when no StatementGroup object is set for a particular property id.'
	);
} );

QUnit.test( 'removeByPropertyId() & length attribute', function( assert ) {
	var statementGroupList = getDefaultStatementGroupList();

	assert.equal(
		statementGroupList.length,
		2,
		'StatementGroupList contains 2 StatementGroup objects.'
	);

	statementGroupList.removeByPropertyId( 'P1' );

	assert.strictEqual(
		statementGroupList.getByPropertyId( 'P1' ),
		null,
		'Removed StatementGroup.'
	);

	assert.strictEqual(
		statementGroupList.length,
		1,
		'StatementGroupList contains 1 StatementGroup object.'
	);

	statementGroupList.removeByPropertyId( 'does-not-exist' );

	assert.strictEqual(
		statementGroupList.length,
		1,
		'StatementGroupList contains 1 StatementGroup object after trying to remove a StatementGroup that is not '
		+ 'set.'
	);

	statementGroupList.removeByPropertyId( 'P2' );

	assert.strictEqual(
		statementGroupList.getByPropertyId( 'P2' ),
		null,
		'Removed StatementGroup.'
	);

	assert.strictEqual(
		statementGroupList.length,
		0,
		'StatementGroupList is empty.'
	);
} );

QUnit.test( 'hasGroupForPropertyId()', function( assert ) {
	assert.ok(
		getDefaultStatementGroupList().hasGroupForPropertyId( 'P2' ),
		'Verified hasGroupForPropertyId() returning TRUE.'
	);

	assert.ok(
		!getDefaultStatementGroupList().hasGroupForPropertyId( 'does-not-exist' ),
		'Verified hasGroupForPropertyId() returning FALSE.'
	);
} );

QUnit.test( 'setGroup() & length attribute', function( assert ) {
	var statementGroupList = getDefaultStatementGroupList(),
		newStatementGroup1 = new wb.datamodel.StatementGroup( 'P1',
			new wb.datamodel.StatementList( [
				new wb.datamodel.Statement(
					new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
				)
			] )
		),
		newStatementGroup3 = new wb.datamodel.StatementGroup( 'P3',
			new wb.datamodel.StatementList( [
				new wb.datamodel.Statement(
					new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P3' ) )
				)
			] )
		),
		emptyStatementGroup1 = new wb.datamodel.StatementGroup( 'P1' );

	assert.equal(
		statementGroupList.length,
		2,
		'StatementGroupList contains 2 StatementGroup objects.'
	);

	statementGroupList.setGroup( newStatementGroup1 );

	assert.ok(
		statementGroupList.getByPropertyId( 'P1' ).equals( newStatementGroup1 ),
		'Set new "P1" StatementGroup.'
	);

	assert.equal(
		statementGroupList.length,
		2,
		'Length remains unchanged when overwriting a StatementGroup.'
	);

	statementGroupList.setGroup( newStatementGroup3 );

	assert.ok(
		statementGroupList.getByPropertyId( 'P3' ).equals( newStatementGroup3 ),
		'Added new StatementGroup.'
	);

	assert.equal(
		statementGroupList.length,
		3,
		'Increased length when adding new StatementGroup.'
	);

	statementGroupList.setGroup( emptyStatementGroup1 );

	assert.strictEqual(
		statementGroupList.getByPropertyId( 'P1' ),
		null,
		'Removed group by setting an empty group.'
	);

	assert.equal(
		statementGroupList.length,
		2,
		'Decreased length after setting an empty group.'
	);

	assert.throws(
		function() {
			statementGroupList.setGroup( ['string'] );
		},
		'Throwing error when trying to set a plain string array.'
	);
} );

QUnit.test( 'isEmpty()', function( assert ) {
	var statementGroupList = new wb.datamodel.StatementGroupList();

	assert.ok(
		statementGroupList.isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	statementGroupList.setGroup( new wb.datamodel.StatementGroup( 'P1',
		new wb.datamodel.StatementList( [
			new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
			)
		] )
	) );

	assert.ok(
		!statementGroupList.isEmpty(),
		'Verified isEmpty() returning FALSE.'
	);

	statementGroupList.setGroup( new wb.datamodel.StatementGroup( 'P1' ) );

	assert.ok(
		statementGroupList.isEmpty(),
		'TRUE after removing last StatementGroup.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	var statementGroupList = getDefaultStatementGroupList();

	assert.ok(
		statementGroupList.equals( getDefaultStatementGroupList() ),
		'Verified equals() retuning TRUE.'
	);

	statementGroupList.setGroup( new wb.datamodel.StatementGroup( 'P2',
		new wb.datamodel.StatementList( [
			new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertySomeValueSnak( 'P2' ) )
			)
		] )
	) );

	assert.ok(
		!statementGroupList.equals( getDefaultStatementGroupList() ),
		'FALSE when a StatementGroup has been overwritten.'
	);

	statementGroupList = getDefaultStatementGroupList();
	statementGroupList.removeByPropertyId( 'P2' );

	assert.ok(
		!statementGroupList.equals( getDefaultStatementGroupList() ),
		'FALSE when a StatementGroup has been removed.'
	);

	assert.ok(
		!statementGroupList.equals( [
			getDefaultStatementGroupList().getByPropertyId( 'P1' ),
			getDefaultStatementGroupList().getByPropertyId( 'P2' )
		] ),
		'FALSE when submitting an array instead of a StatementGroupList instance.'
	);
} );

QUnit.test( 'hasGroup()', function( assert ) {
	assert.ok(
		getDefaultStatementGroupList().hasGroup( new wb.datamodel.StatementGroup( 'P1',
			new wb.datamodel.StatementList( [
				new wb.datamodel.Statement(
					new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
				)
			] )
		) ),
		'Verified hasGroup() returning TRUE.'
	);

	assert.ok(
		!getDefaultStatementGroupList().hasGroup(
			new wb.datamodel.StatementGroup( 'P1' )
		),
		'Verified hasGroup() returning FALSE.'
	);

	assert.throws(
		function() {
			getDefaultStatementGroupList().hasGroup( 'de-text' );
		},
		'Throwing error when submitting a string array.'
	);
} );

}( wikibase, QUnit, jQuery ) );
