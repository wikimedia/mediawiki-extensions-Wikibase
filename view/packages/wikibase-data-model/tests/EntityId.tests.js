/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
	'use strict';

QUnit.module( 'wikibase.datamodel.EntityId' );

var prefixMap = {
	P: 'property',
	Q: 'item'
};

var testSets = [
	['item', 1, 'Q1'],
	['property', 1, 'P1']
];

QUnit.test( 'Constructor and getters', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		var entityId = new wb.datamodel.EntityId( testSets[i][0], testSets[i][1] );

		assert.ok(
			entityId instanceof wb.datamodel.EntityId,
			'Test set #' + i + ': Instantiated EntityId.'
		);

		assert.equal(
			entityId.getEntityType(),
			testSets[i][0],
			'Test set #' + i + ': Verified entity type being set.'
		);

		assert.strictEqual(
			entityId.getNumericId(),
			testSets[i][1],
			'Test set #' + i + ': Verified numeric id being set.'
		);

		assert.equal(
			entityId.getPrefixedId( prefixMap ),
			testSets[i][2],
			'Test set #' + i + ': Verified retrieved prefixed id.'
		);

		assert.equal(
			entityId.getValue(),
			entityId,
			'Test set #' + i + ': Verified getValue() returning original object.'
		);

		assert.ok(
			typeof entityId.getSortKey() === 'string',
			'Test set #' + i + ': getSortKey() returns a string.'
		);
	}
} );

QUnit.test( 'equals()', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		var entityId1 = new wb.datamodel.EntityId( testSets[i][0], testSets[i][1] );

		for( var j = 0; j < testSets.length; j++ ) {
			var entityId2 = new wb.datamodel.EntityId( testSets[j][0], testSets[j][1] );

			if( i === j ) {
				assert.ok(
					entityId1.equals( entityId2 ),
					'Test set #' + i + ' is equal to test set #' + j + '.'
				);
				continue;
			}

			assert.ok(
				!entityId1.equals( entityId2 ),
				'Test set #' + i + ' is not equal to test set #' + j + '.'
			);
		}
	}
} );

QUnit.test( 'toJSON() & newFromJSON()', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		var entityId = new wb.datamodel.EntityId( testSets[i][0], testSets[i][1] ),
			json = entityId.toJSON();

		assert.ok(
			wb.datamodel.EntityId.newFromJSON( json ).equals( entityId ),
			'Test set #' + i + ': Instantiated EntityId from generated JSON.'
		);
	}
} );

}( wikibase, QUnit ) );
