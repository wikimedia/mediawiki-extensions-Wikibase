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
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		),
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
		),
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertySomeValueSnak( 'P2' ) )
		)
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
			new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
			)
		),
		'Verified hasStatement() returning TRUE.'
	);

	assert.ok(
		!getDefaultStatementList().hasStatement(
			new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P9999' ) )
			)
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
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P3' ) )
		)
	);

	assert.ok(
		statementList.hasStatement(
			new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P3' ) )
			)
		),
		'Added Statement.'
	);

	assert.equal(
		statementList.length,
		4,
		'Increased length.'
	);
} );

QUnit.test( 'removeStatement()', function( assert ) {
	var statementList = getDefaultStatementList();

	assert.equal(
		statementList.length,
		3,
		'StatementList contains 3 Statement objects.'
	);

	assert.throws(
		function() {
			statementList.removeStatement(
				new wb.datamodel.Statement(
					new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P9999' ) )
				)
			);
		},
		'Throwing error when trying to remove a Statement not set.'
	);

	assert.throws(
		function() {
			statementList.removeStatement(
				new wb.datamodel.Statement(
					new wb.datamodel.Claim(
						new wb.datamodel.PropertyNoValueSnak( 'P2' ),
						null,
						'i am a guid'
					)
				)
			);
		},
		'Throwing error when trying to remove a Statement which only differs in the GUID to an '
		+ 'existing statement not set.'
	);

	statementList.removeStatement(
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
		)
	);

	assert.ok(
		!statementList.hasStatement(
			new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
			)
		),
		'Removed Statement.'
	);

	assert.equal(
		statementList.length,
		2,
		'StatementList contains 2 Statement objects.'
	);
} );

QUnit.test( 'getPropertyIds()', function( assert ) {
	var statementList = getDefaultStatementList();

	assert.deepEqual(
		statementList.getPropertyIds(),
		['P1', 'P2'],
		'Retrieved property ids.'
	);
} );

QUnit.test( 'isEmpty()', function( assert ) {
	var statementList = new wb.datamodel.StatementList(),
		statement = new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		);

	assert.ok(
		statementList.isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	statementList.addStatement( statement );

	assert.ok(
		!statementList.isEmpty(),
		'Verified isEmpty() returning FALSE.'
	);

	statementList.removeStatement( statement );

	assert.ok(
		statementList.isEmpty(),
		'TRUE after removing last Statement.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	var statementList = getDefaultStatementList();

	assert.ok(
		statementList.equals( getDefaultStatementList() ),
		'Verified equals() retuning TRUE.'
	);

	statementList.addStatement(
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P3' ) )
		)
	);

	assert.ok(
		!statementList.equals( getDefaultStatementList() ),
		'FALSE after adding another Statement object.'
	);
} );

QUnit.test( 'indexOf()', function( assert ) {
	var referenceList = getDefaultStatementList();

	assert.strictEqual(
		referenceList.indexOf( new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
		) ),
		1,
		'Retrieved correct index.'
	);
} );

}( wikibase, QUnit ) );
