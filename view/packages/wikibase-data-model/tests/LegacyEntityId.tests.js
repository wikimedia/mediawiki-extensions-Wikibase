/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
	'use strict';

QUnit.module( 'wikibase.datamodel.LegacyEntityId' );

var prefixMap = {
	'P': 'property',
	'Q': 'item'
};

var testSets = [
	['item', 1, 'Q1'],
	['property', 1, 'P1']
];

QUnit.test( 'Constructor and getters', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		var legacyEntityId = new wb.datamodel.LegacyEntityId( testSets[i][0], testSets[i][1] );

		assert.ok(
			legacyEntityId instanceof wb.datamodel.LegacyEntityId,
			'Test set #' + i + ': Instantiated LegacyEntityId.'
		);

		assert.equal(
			legacyEntityId.getEntityType(),
			testSets[i][0],
			'Test set #' + i + ': Verified entity type being set.'
		);

		assert.strictEqual(
			legacyEntityId.getNumericId(),
			testSets[i][1],
			'Test set #' + i + ': Verified numeric id being set.'
		);

		assert.equal(
			legacyEntityId.getPrefixedId( prefixMap ),
			testSets[i][2],
			'Test set #' + i + ': Verified retrieved prefixed id.'
		);

		assert.equal(
			legacyEntityId.getValue(),
			legacyEntityId,
			'Test set #' + i + ': Verified getValue() returning original object.'
		);

		assert.ok(
			typeof legacyEntityId.getSortKey() === 'string',
			'Test set #' + i + ': getSortKey() returns a string.'
		);
	}
} );

QUnit.test( 'equals()', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		var legacyEntityId1 = new wb.datamodel.LegacyEntityId( testSets[i][0], testSets[i][1] );

		for( var j = 0; j < testSets.length; j++ ) {
			var legacyEntityId2 = new wb.datamodel.LegacyEntityId( testSets[j][0], testSets[j][1] );

			if( i === j ) {
				assert.ok(
					legacyEntityId1.equals( legacyEntityId2 ),
					'Test set #' + i + ' is equal to test set #' + j + '.'
				);
				continue;
			}

			assert.ok(
				!legacyEntityId1.equals( legacyEntityId2 ),
				'Test set #' + i + ' is not equal to test set #' + j + '.'
			);
		}
	}
} );

QUnit.test( 'toJSON() & newFromJSON()', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		var entityId = new wb.datamodel.LegacyEntityId( testSets[i][0], testSets[i][1] ),
			json = entityId.toJSON();

		assert.ok(
			wb.datamodel.LegacyEntityId.newFromJSON( json ).equals( entityId ),
			'Test set #' + i + ': Instantiated LegacyEntityId from generated JSON.'
		);
	}
} );

}( wikibase, QUnit ) );
