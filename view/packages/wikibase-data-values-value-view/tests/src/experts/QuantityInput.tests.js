/**
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
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
			characteristics.hasOwnProperty( 'unit' ),
			'unit exists'
		);

		assert.ok(
			characteristics.unit === null || typeof characteristics.unit === 'string',
			'unit is null or a string'
		);

		assert.ok(
			!characteristics.hasOwnProperty( 'applyUnit' ),
			'applyUnit does not exist'
		);

		assert.ok(
			!characteristics.hasOwnProperty( 'applyRounding' ),
			'applyRounding does not exist'
		);
	} );

	QUnit.test( 'valueCharacteristics( \'text/plain\' )', function( assert ) {
		var expert = newExpert(),
			characteristics = expert.valueCharacteristics( 'text/plain' );

		assert.ok(
			characteristics.applyUnit === false,
			'applyUnit is false'
		);

		assert.ok(
			characteristics.applyRounding === false,
			'applyRounding is false'
		);
	} );

}( jQuery, QUnit, jQuery.valueview ) );
