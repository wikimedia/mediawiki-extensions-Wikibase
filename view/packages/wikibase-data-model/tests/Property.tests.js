/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
	'use strict';

QUnit.module( 'wikibase.datamodel.Property' );

var testSets = [
	[
		'P1',
		new wb.datamodel.Fingerprint(
			new wb.datamodel.TermList(),
			new wb.datamodel.TermList(),
			new wb.datamodel.TermGroupList()
		),
		'i am a data type id',
		new wb.datamodel.StatementList()
	], [
		'P2',
		new wb.datamodel.Fingerprint(
			new wb.datamodel.TermList( [new wb.datamodel.Term( 'de', 'de-label' )] ),
			new wb.datamodel.TermList( [new wb.datamodel.Term( 'de', 'de-description' )] ),
			new wb.datamodel.TermGroupList( [new wb.datamodel.TermGroup( 'de', ['de-alias'] )] )
		),
		'i am a data type id',
		new wb.datamodel.StatementList( [
			new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
			)
		] )
	]
];

QUnit.test( 'Constructor', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		var property = new wb.datamodel.Property(
			testSets[i][0], testSets[i][1], testSets[i][2], testSets[i][3]
		);
		assert.ok(
			property instanceof wb.datamodel.Property,
			'Instantiated Property object.'
		);
	}
} );

QUnit.test( 'isEmpty()', function( assert ) {
	assert.ok(
		( new wb.datamodel.Property(
			'P1',
			new wb.datamodel.Fingerprint(
				new wb.datamodel.TermList(),
				new wb.datamodel.TermList(),
				new wb.datamodel.TermGroupList()
			),
			'i am a data type id',
			new wb.datamodel.StatementList()
		) ).isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	assert.ok(
		!( new wb.datamodel.Property(
			'P1',
			new wb.datamodel.Fingerprint(
				new wb.datamodel.TermList( [new wb.datamodel.Term( 'de', 'de-term' )] ),
				new wb.datamodel.TermList(),
				new wb.datamodel.TermGroupList()
			),
			'i am a data type id',
			new wb.datamodel.StatementList()
		) ).isEmpty(),
		'Returning FALSE when Fingerprint is not empty.'
	);

	assert.ok(
		!( new wb.datamodel.Property(
			'P1',
			new wb.datamodel.Fingerprint(
				new wb.datamodel.TermList(),
				new wb.datamodel.TermList(),
				new wb.datamodel.TermGroupList()
			),
			'i am a data type id',
			new wb.datamodel.StatementList( [new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
			)] )
		) ).isEmpty(),
		'Returning FALSE when StatementList is not empty.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		var property1 = new wb.datamodel.Property(
			testSets[i][0], testSets[i][1], testSets[i][2], testSets[i][3]
		);

		for( var j = 0; j < testSets.length; j++ ) {
			var property2 = new wb.datamodel.Property(
				testSets[j][0], testSets[j][1], testSets[j][2], testSets[j][3]
			);

			if( i === j ) {
				assert.ok(
					property1.equals( property2 ),
					'Test set #' + i + ' equals test set #' + j + '.'
				);
				continue;
			}

			assert.ok(
				!property1.equals( property2 ),
				'Test set #' + i + ' does not equal test set #' + j + '.'
			);
		}
	}
} );

}( wikibase, QUnit ) );
