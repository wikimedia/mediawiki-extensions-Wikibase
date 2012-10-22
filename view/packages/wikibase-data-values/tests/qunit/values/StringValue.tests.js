/**
 * QUnit tests for inherit() function for more prototypal inheritance convenience.
 *
 * @since 0.1
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( dv, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'dataValues.StringValue', QUnit.newMwEnvironment() );

	QUnit.test( 'constructor tests', function() {
		var values = [
			'',
			'foo',
			'foo bar baz'
		];

		for ( var value in values ) {
			var instance = new dv.StringValue( value );
			QUnit.strictEqual( instance.getValue(), value );
		}
	} );

}( dataValues, jQuery, QUnit ) );
