/**
 * @license GNU GPL v2+
 */
( function( $, QUnit, vv ) {
	'use strict';

	QUnit.module( 'jquery.valueview.experts.QuantityInput' );

	vv.tests.testExpert( {
		expertConstructor: vv.experts.QuantityInput
	} );

	function newExpert() {
		return new vv.experts.QuantityInput(
			$( '<div>' ),
			new vv.tests.MockViewState(),
			undefined,
			{ messages: {} }
		);
	}

	QUnit.test( 'valueCharacteristics', function( assert ) {
		var expert = newExpert(),
			characteristics = expert.valueCharacteristics();

		assert.ok(
			'unit' in characteristics,
			'unit exists'
		);

		assert.ok(
			characteristics.unit === null || typeof characteristics.unit === 'string',
			'unit is null or a string'
		);

		assert.notStrictEqual(
			characteristics.unit, '', 'unit should not be empty string'
		);
	} );

}( jQuery, QUnit, jQuery.valueview ) );
