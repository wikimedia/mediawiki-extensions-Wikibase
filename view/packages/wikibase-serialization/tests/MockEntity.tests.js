/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
	'use strict';

QUnit.module( 'wikibase.serialization.MockEntity' );

var testSets = [
	[
		'i am an id',
		new wb.datamodel.Fingerprint(
			new wb.datamodel.TermSet(),
			new wb.datamodel.TermSet(),
			new wb.datamodel.MultiTermSet()
		)
	], [
		'i am an id',
		new wb.datamodel.Fingerprint(
			new wb.datamodel.TermSet( [new wb.datamodel.Term( 'de', 'de-label' )] ),
			new wb.datamodel.TermSet( [new wb.datamodel.Term( 'de', 'de-description' )] ),
			new wb.datamodel.MultiTermSet( [new wb.datamodel.MultiTerm( 'de', ['de-alias'] )] )
		)
	]
];

QUnit.test( 'Constructor', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		var mockEntity = new wb.serialization.tests.MockEntity( testSets[i][0], testSets[i][1] );
		assert.ok(
			mockEntity instanceof wb.serialization.tests.MockEntity,
			'Test set #' + i + ': Instantiated MockEntity object.'
		);
	}
} );

QUnit.test( 'isEmpty()', function( assert ) {
	assert.ok(
		( new wb.serialization.tests.MockEntity(
			'i am an id',
			new wb.datamodel.Fingerprint(
				new wb.datamodel.TermSet(),
				new wb.datamodel.TermSet(),
				new wb.datamodel.MultiTermSet()
			)
		) ).isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	assert.ok(
		!( new wb.serialization.tests.MockEntity(
			'i am an id',
			new wb.datamodel.Fingerprint(
				new wb.datamodel.TermSet( [new wb.datamodel.Term( 'de', 'de-term' )] ),
				new wb.datamodel.TermSet(),
				new wb.datamodel.MultiTermSet()
			)
		) ).isEmpty(),
		'Returning FALSE when Fingerprint is not empty.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		var property1 = new wb.serialization.tests.MockEntity( testSets[i][0], testSets[i][1] );

		for( var j = 0; j < testSets.length; j++ ) {
			var property2 = new wb.serialization.tests.MockEntity( testSets[j][0], testSets[j][1] );

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
