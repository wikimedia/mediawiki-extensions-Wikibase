/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, QUnit, valueview, Time ) {
	'use strict';

	var testExpert = valueview.tests.testExpert;

	QUnit.module( 'jquery.valueview.experts.TimeInput' );

	testExpert( {
		expertConstructor: valueview.experts.TimeInput,
		rawValues: {
			valid: [
				new Time( '1. April 1989' ),
				new Time( '123 bce' ),
				new Time( '1984' )
			],
			unknown: testExpert.basicTestDefinition.rawValues.unknown.concat( [
				42,
				'1. 1984'
			] )
		}
	} );

}( jQuery, QUnit, jQuery.valueview, time.Time ) );
