/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.StatementGroup' );

var defaultStatementList = new wb.datamodel.StatementList( [
	new wb.datamodel.Statement(
		new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
	)
] );

/**
 * @return {wikibase.datamodel.StatementGroup}
 */
function getDefaultStatementGroup() {
	return new wb.datamodel.StatementGroup( 'P1', defaultStatementList );
}

QUnit.test( 'Constructor', function( assert ) {
	var statementGroup = getDefaultStatementGroup();

	assert.ok(
		statementGroup instanceof wb.datamodel.StatementGroup,
		'Instantiated StatementGroup.'
	);

	assert.equal(
		statementGroup.getKey(),
		'P1',
		'Verified property id.'
	);

	assert.ok(
		statementGroup.getItemList().equals( defaultStatementList ),
		'Verified StatementList.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.StatementGroup( 'P1', new wb.datamodel.StatementList( [
				new wb.datamodel.Statement(
					new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
				)
			] ) );
		},
		'Throwing error when trying to instantiate StatementGroup mismatching property ids.'
	);
} );

QUnit.test( 'setStatementList() & getStatementList()', function( assert ) {
	var statementGroup = getDefaultStatementGroup(),
		statementList = new wb.datamodel.StatementList( [
			new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
			)
		] );

	assert.ok(
		statementGroup.getItemList() !== defaultStatementList,
		'Not returning original StatementList object.'
	);

	statementGroup.setItemList( statementList );

	assert.ok(
		statementGroup.getItemList().equals( statementList ),
		'Set new StatementList.'
	);

	assert.throws(
		function() {
			statementGroup.setItemList( new wb.datamodel.StatementList( [
				new wb.datamodel.Statement(
					new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
				)
			] ) );
		},
		'Throwing error when trying to set a StatementList with mismatching property id.'
	);
} );

QUnit.test( 'addStatement() & hasStatement()', function( assert ) {
	var statementGroup = getDefaultStatementGroup();

	statementGroup.addItem(
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertySomeValueSnak( 'P1' ) )
		)
	);

	assert.ok(
		statementGroup.hasItem(
			new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
			)
		),
		'Verified having added a Statement.'
	);

	assert.throws(
		function() {
			statementGroup.addItem(
				new wb.datamodel.Statement(
					new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
				)
			);
		},
		'Throwing error when trying to add a Statement that does not match the StatementGroup '
			+ 'object\'s property id.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	var statementGroup = getDefaultStatementGroup();

	assert.ok(
		statementGroup.equals( getDefaultStatementGroup() ),
		'Verified equals() retuning TRUE.'
	);

	statementGroup.addItem(
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertySomeValueSnak( 'P1' ) )
		)
	);

	assert.ok(
		!statementGroup.equals( getDefaultStatementGroup() ),
		'FALSE after adding another Statement object.'
	);
} );

}( wikibase, QUnit ) );
