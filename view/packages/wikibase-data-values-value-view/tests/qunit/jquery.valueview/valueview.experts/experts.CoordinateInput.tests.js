/**
 * @since 0.1
 * @ingroup ValueView
 * @licence GNU GPL v2+
 *
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit, valueview, Coordinate, CoordinateParser ) {
	'use strict';

	var testExpert = valueview.tests.testExpert;

	QUnit.module( 'jquery.valueview.experts.CoordinateInput' );

	testExpert( {
		expertConstructor: valueview.experts.CoordinateInput,
		rawValues: {
			valid: [
				new Coordinate( '30, 30' ),
				new Coordinate( '-1.5 -1.25' )
			],
			unknown: testExpert.basicTestDefinition.rawValues.unknown.concat( [
				42,
				'1 1'
			] )
		},
		relatedValueParser: CoordinateParser
	} );

}( jQuery, QUnit, jQuery.valueview, coordinate.Coordinate, valueParsers.CoordinateParser ) );
