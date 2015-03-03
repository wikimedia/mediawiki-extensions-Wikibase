/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( QUnit, valueview ) {
	'use strict';

	var testExpert = valueview.tests.testExpert;

	QUnit.module( 'jquery.valueview.experts.TimeInput' );

	testExpert( {
		expertConstructor: valueview.experts.TimeInput
	} );

}( QUnit, jQuery.valueview ) );
