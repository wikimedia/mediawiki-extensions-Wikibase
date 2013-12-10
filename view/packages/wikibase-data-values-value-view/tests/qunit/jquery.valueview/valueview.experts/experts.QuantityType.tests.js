/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
 ( function( $, QUnit, valueview ) {
	'use strict';

	var testExpert = valueview.tests.testExpert;

	QUnit.module( 'jquery.valueview.experts.QuantityType' );

	testExpert( {
		expertConstructor: valueview.experts.QuantityType,
		rawValues: {
			valid: [
				'+0',
				'-1',
				'+1.5'
			],
			unknown: testExpert.basicTestDefinition.rawValues.unknown.concat( [
				1
			] )
		}
	} );

}( jQuery, QUnit, jQuery.valueview ) );
