/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, dv, QUnit ) {
	'use strict';

QUnit.module( 'wikibase.datamodel.Snak' );

var testSets = [
	[wb.datamodel.PropertyNoValueSnak, ['P1', undefined]],
	[wb.datamodel.PropertyNoValueSnak, ['P2', 'hash']],
	[wb.datamodel.PropertySomeValueSnak, ['P1', 'hash']],
	[wb.datamodel.PropertyValueSnak, ['P1', new dv.StringValue( 'test' ), undefined]],
	[wb.datamodel.PropertyValueSnak, ['P2', new dv.StringValue( 'test' ), 'hash']]
];

/**
 * @param {Function} SnakConstructor
 * @param {*[]} params
 * @return {wikibase.datamodel.Snak}
 */
function constructSnak( SnakConstructor, params ) {
	return new SnakConstructor( params[0], params[1], params[2] );
}

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 20 );

	for( var i = 0; i < testSets.length; i++ ) {
		var SnakConstructor = testSets[i][0],
			snakParams = testSets[i][1],
			snak = constructSnak( SnakConstructor, snakParams );

		assert.ok(
			snak instanceof wb.datamodel.Snak,
			'Test set #' + i + ': Instantiated Snak object.'
		);

		assert.equal(
			snak.getPropertyId(),
			snakParams[0],
			'Test set #' + i + ': Property id was set correctly.'
		);

		assert.strictEqual(
			snak.getType(),
			SnakConstructor.TYPE,
			'Test set #' + i + ': Snak type "' + snak.getType() + '" was set correctly.'
		);

		assert.strictEqual(
			snak.getHash(),
			snakParams[snakParams.length - 1] || null,
			'Test set #' + i + ': Snak hash was set correctly.'
		);
	}
} );

QUnit.test( 'equals()', function( assert ) {
	assert.expect( 35 );

	for( var i = 0; i < testSets.length; i++ ) {
		var snak1 = constructSnak( testSets[i][0], testSets[i][1] );

		assert.ok(
			snak1.equals( snak1 ),
			'Test set #' + i + ': Snak is equal to itself.'
		);

		assert.ok(
			!snak1.equals( 'some string' ),
			'Test set #' + i + ': Snak is not equal to a plain string.'
		);

		for( var j = 0; j < testSets.length; j++ ) {
			var snak2 = constructSnak( testSets[j][0], testSets[j][1] );

			if( j === i ) {
				assert.ok(
					snak1.equals( snak2 ),
					'Test set #' + i + ' equals its clone.'
				);
				continue;
			}

			assert.ok(
				!snak1.equals( snak2 ),
				'Test set #' + i + ' is not equal not test set #' + j + '.'
			);
		}
	}

} );

}( wikibase, dataValues, QUnit ) );
