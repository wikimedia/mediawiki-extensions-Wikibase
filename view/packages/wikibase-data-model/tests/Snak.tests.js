/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, dv, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.snak.js' );

	QUnit.test( 'wb.datamodel.Snak.prototype, its constructor and wb.datamodel.Snak static functions', function( assert ) {
		var testSets = [
			[ wb.datamodel.PropertyNoValueSnak ],
			[ wb.datamodel.PropertySomeValueSnak ],
			[ wb.datamodel.PropertyValueSnak, [ '21', new dv.StringValue( 'test' ) ] ]
		];
		var unequalSnak = new wb.datamodel.PropertyValueSnak( '21', new dv.StringValue( 'not equal!' ) );

		for( var i = 0; i < testSets.length; i++ ) {
			var SnakConstructor = testSets[i][0],
				snakParams = testSets[i][1] || [ '42' ],
				snak = new SnakConstructor( snakParams[0], snakParams[1] );

			assert.ok(
				snak instanceof wb.datamodel.Snak,
				'New snak is an instance of wikibase.Snak'
			);

			assert.ok(
				snak.equals( snak ),
				'Snak is equal to itself'
			);

			assert.ok(
				!snak.equals( unequalSnak ) && !unequalSnak.equals( snak ),
				'Snak is not equal to some other random Snak'
			);

			assert.strictEqual(
				snak.getPropertyId(),
				snakParams[ 0 ],
				'Property id was set correctly'
			);

			assert.strictEqual(
				snak.getType(),
				SnakConstructor.TYPE,
				'Snak type "' + snak.getType() + '" was set correctly'
			);
		}

	} );

}( wikibase, dataValues, QUnit ) );
