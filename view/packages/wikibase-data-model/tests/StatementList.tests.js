/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.StatementList' );

/**
 * @return {wikibase.datamodel.StatementList}
 */
function getDefaultStatementList() {
	return new wb.datamodel.StatementList( [
		new wb.datamodel.Statement( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ),
		new wb.datamodel.Statement( new wb.datamodel.PropertyNoValueSnak( 'P2' ) ),
		new wb.datamodel.Statement( new wb.datamodel.PropertySomeValueSnak( 'P2' ) )
	] );
}

QUnit.test( 'Constructor', function( assert ) {
	assert.ok(
		getDefaultStatementList() instanceof wb.datamodel.StatementList,
		'Instantiated StatementList.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.StatementList( ['string1', 'string2'] );
		},
		'Throwing error when trying to instantiate StatementList with other than Statement objects.'
	);
} );

QUnit.test( 'hasStatement()', function( assert ) {
	assert.ok(
		getDefaultStatementList().hasStatement(
			new wb.datamodel.Statement( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
		),
		'Verified hasStatement() returning TRUE.'
	);

	assert.ok(
		!getDefaultStatementList().hasStatement(
			new wb.datamodel.Statement( new wb.datamodel.PropertyNoValueSnak( 'P9999' ) )
		),
		'Verified hasStatement() returning FALSE.'
	);
} );

QUnit.test( 'addStatement() & length attribute', function( assert ) {
	var statementList = getDefaultStatementList();

	assert.equal(
		statementList.length,
		3,
		'StatementList contains 3 Statement objects.'
	);

	statementList.addStatement(
		new wb.datamodel.Statement( new wb.datamodel.PropertyNoValueSnak( 'P3' ) )
	);

	assert.ok(
		statementList.hasStatement(
			new wb.datamodel.Statement( new wb.datamodel.PropertyNoValueSnak( 'P3' ) )
		),
		'Added Statement.'
	);

	assert.equal(
		statementList.length,
		4,
		'Increased length.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	var statementList = getDefaultStatementList();

	assert.ok(
		statementList.equals( getDefaultStatementList() ),
		'Verified equals() retuning TRUE.'
	);

	statementList.addStatement(
		new wb.datamodel.Statement( new wb.datamodel.PropertyNoValueSnak( 'P3' ) )
	);

	assert.ok(
		!statementList.equals( getDefaultStatementList() ),
		'FALSE after adding another Statement object.'
	);
} );

}( wikibase, QUnit ) );
