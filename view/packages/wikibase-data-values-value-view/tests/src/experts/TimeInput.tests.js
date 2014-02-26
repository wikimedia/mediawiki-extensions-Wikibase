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
// FIXME: This can only be recognized as unparsable after calling the API.
// Right now, the tests expext draw() to return a promise in such a case, but
// since parsing is done transparently in the ValueView, TimeInput cannot do
// that.
//				'1. 1984'
			] )
		}
	} );

}( jQuery, QUnit, jQuery.valueview, time.Time ) );
