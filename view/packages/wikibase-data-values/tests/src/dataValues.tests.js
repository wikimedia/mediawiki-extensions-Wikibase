/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( define ) {
'use strict';

var DEPS = ['dataValues', 'jquery', 'qunit'];

define( DEPS, function( dv, $, QUnit ) {

	QUnit.module( 'dataValues.js' );

	QUnit.test(
		'getDataValues',
		function( assert ) {
			var dvs = dv.getDataValues();

			assert.ok( $.isArray( dvs ), 'Returns an array' );

			for ( var i = 0, s = dvs.length; i < s; i++ ) {
				assert.ok(
					typeof dvs[i] === 'string',
					'Returned DV type "' + dvs[i] + '" is a string'
				);

				assert.ok(
					dv.hasDataValue( dvs[i] ),
					'Returned DV type "' + dvs[i] + '" is present according to hasDataValue'
				);
			}
		}
	);

	QUnit.test(
		'hasDataValue',
		function( assert ) {
			// Already partially tested in getDataValues

			assert.strictEqual(
				dv.hasDataValue( 'in your code, being silly' ),
				false,
				'Non existing DV type is not present'
			);

			var dvs = dv.getDataValues();

			assert.strictEqual(
				dv.hasDataValue( dvs.pop() ),
				true,
				'Existing DV type is present'
			);
		}
	);

	QUnit.test(
		'newDataValue',
		function( assert ) {
			// This test needs dv.MonolingualTextValue to be loaded and registered

			var dataValue = dv.newDataValue(
				'monolingualtext',
				{
					'language': 'en',
					'text': '~=[,,_,,]:3'
				}
			);

			assert.strictEqual(
				dataValue.getText(),
				'~=[,,_,,]:3',
				'Value was constructed and the text was set correctly'
			);

			assert.strictEqual(
				dataValue.getLanguageCode(),
				'en',
				'Value was constructed and the language code was set correctly'
			);
		}
	);

} );

}( define ) );
