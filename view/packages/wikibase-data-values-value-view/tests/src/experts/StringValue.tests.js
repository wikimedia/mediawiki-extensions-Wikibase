/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
( function( $, QUnit, valueview ) {
	'use strict';

	var testExpert = valueview.tests.testExpert;

	QUnit.module( 'jquery.valueview.experts.StringValue' );

	testExpert( {
		expertConstructor: valueview.experts.StringValue
	} );

	function newExpert() {
		return new valueview.experts.StringValue(
			$( '<div>' ),
			new valueview.tests.MockViewState(),
			undefined,
			{ messages: {} }
		);
	}

	QUnit.test( 'string input value is sanitized at start and end', function( assert ) {
		var expert = newExpert();

		var testString = '\n\t\r [ˈdʒɛndɐ\n\tʃtɛrnçən] \n\t\r';
		var expectedString = '[ˈdʒɛndɐ\n\tʃtɛrnçən]';

		expert.$input.val( testString );

		assert.strictEqual( expert.rawValue(), expectedString, 'should trim and remove hidden chars' );
	} );

	QUnit.test( 'string value should not allow only special space characters', function( assert ) {
		var expert = newExpert();

		expert.$input.val( '\n\t\r \n\t\r \n\t\r \n\t\r' );

		assert.strictEqual( expert.rawValue(), '', 'special space character input should return empty string' );
	} );

	QUnit.test( 'string value set to null should not crash', function( assert ) {
		var expert = newExpert();

		expert.$input.val( null );

		assert.strictEqual( expert.rawValue(), '', 'null input should return empty string' );
	} );

}( jQuery, QUnit, jQuery.valueview ) );
