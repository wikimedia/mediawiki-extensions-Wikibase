/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.Fingerprint' );

var testSets = [
	[
		new wb.datamodel.TermList(),
		new wb.datamodel.TermList(),
		new wb.datamodel.TermGroupList()
	], [
		new wb.datamodel.TermList( [
			new wb.datamodel.Term( 'de', 'de-label' ),
			new wb.datamodel.Term( 'en', 'en-label' )
		] ),
		new wb.datamodel.TermList( [
			new wb.datamodel.Term( 'de', 'de-description' ),
			new wb.datamodel.Term( 'en', 'en-description' )
		] ),
		new wb.datamodel.TermGroupList( [
			new wb.datamodel.TermGroup( 'de', ['de-alias1', 'de-alias2'] ),
			new wb.datamodel.TermGroup( 'en', ['en-alias1'] )
		] )
	]
];

QUnit.test( 'Constructor (positive)', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		var fingerprint = new wb.datamodel.Fingerprint(
			testSets[i][0], testSets[i][1], testSets[i][2]
		);

		assert.ok(
			fingerprint instanceof wb.datamodel.Fingerprint,
			'Test set #' + i +': Instantiated Fingerprint.'
		);
	}
} );

QUnit.test( 'Constructor (negative)', function( assert ) {
	var negativeTestSets = [
		['string', new wb.datamodel.TermList(), new wb.datamodel.TermGroupList()],
		[new wb.datamodel.TermList(), 'string', new wb.datamodel.TermGroupList()],
		[new wb.datamodel.TermList(), new wb.datamodel.TermList(), 'string']
	];

	/**
	 * @param {wikibase.datamodel.TermList} labels
	 * @param {wikibase.datamodel.TermList} descriptions
	 * @param {wikibase.datamodel.TermGroupList} aliasGroups
	 * @return {Function}
	 */
	function instantiateObject( labels, descriptions, aliasGroups ) {
		return function() {
			return new wb.datamodel.Fingerprint( labels, descriptions, aliasGroups );
		};
	}

	for( var i = 0; i < negativeTestSets.length; i++ ) {
		assert.throws(
			instantiateObject(
				negativeTestSets[i][0], negativeTestSets[i][1], negativeTestSets[i][2]
			),
			'Test set #' + i +': Threw expected error.'
		);
	}
} );

QUnit.test( 'isEmpty()', function( assert ) {
	assert.ok(
		( new wb.datamodel.Fingerprint(
			new wb.datamodel.TermList(),
			new wb.datamodel.TermList(),
			new wb.datamodel.TermGroupList()
		) ).isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	assert.ok(
		!( new wb.datamodel.Fingerprint(
			new wb.datamodel.TermList( [new wb.datamodel.Term( 'en', 'en-string' )] ),
			new wb.datamodel.TermList(),
			new wb.datamodel.TermGroupList()
		) ).isEmpty(),
		'FALSE when there is a label.'
	);

	assert.ok(
		!( new wb.datamodel.Fingerprint(
			new wb.datamodel.TermList(),
			new wb.datamodel.TermList( [new wb.datamodel.Term( 'en', 'en-string' )] ),
			new wb.datamodel.TermGroupList()
		) ).isEmpty(),
		'FALSE when there is a description.'
	);

	assert.ok(
		!( new wb.datamodel.Fingerprint(
			new wb.datamodel.TermList(),
			new wb.datamodel.TermList(),
			new wb.datamodel.TermGroupList( [new wb.datamodel.TermGroup( 'en', ['en-string'] )] )
		) ).isEmpty(),
		'FALSE when there is an alias.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		var fingerprint1 = new wb.datamodel.Fingerprint(
			testSets[i][0], testSets[i][1], testSets[i][2]
		);

		for( var j = 0; j < testSets.length; j++ ) {
			var fingerprint2 = new wb.datamodel.Fingerprint(
				testSets[j][0], testSets[j][1], testSets[j][2]
			);

			if( j === i ) {
				assert.ok(
					fingerprint1.equals( fingerprint2 ),
					'Test set #' + i + ' equals test set #' + j + '.'
				);
				continue;
			}

			assert.ok(
				!fingerprint1.equals( fingerprint2 ),
				'Test set #' + i + ' does not equal test set #' + j + '.'
			);
		}
	}
} );

}( wikibase, QUnit ) );
