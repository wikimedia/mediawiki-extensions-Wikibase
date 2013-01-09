/**
 * QUnit tests for wikibase.Claim
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.3
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( wb, dv, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.snak.js', QUnit.newMwEnvironment() );

	QUnit.test( 'constructor', function( assert ) {
		var snakInfo = [
			[ wb.PropertyNoValueSnak ],
			[ wb.PropertySomeValueSnak ],
			[ wb.PropertyValueSnak, [ 21, new dv.StringValue( 'test' ) ] ]
		];

		$.each( snakInfo, function( i, info ) {
			var snakConstructor = info[0],
				snakParams = info[1] || [ 42 ],
				snak = new snakConstructor( snakParams[0], snakParams[1] ),
				snakJson = snak.toJSON();

			assert.ok(
				snak instanceof wb.Snak,
				'New snak is an instance of wikibase.Snak'
			);


			assert.strictEqual(
				snak.getPropertyId(),
				snakParams[ 0 ],
				'Property id was set correctly'
			);

			assert.strictEqual(
				snak.getType(),
				snakConstructor.TYPE,
				'Snak type "' + snak.getType() + '" was set correctly'
			);

			assert.ok(
				$.isPlainObject( snakJson ),
				'toJSON() will return a plain object'
			);

			assert.ok(
				snakJson.snaktype === snak.getType(),
				"In the json, the 'snaktype' field is set correctly"
			);
		} );

	} );

}( wikibase, dataValues, jQuery, QUnit ) );
