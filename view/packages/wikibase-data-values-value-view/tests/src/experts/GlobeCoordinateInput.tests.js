/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit, valueview ) {
	'use strict';

	var testExpert = valueview.tests.testExpert;

	QUnit.module( 'jquery.valueview.experts.GlobeCoordinateInput' );

	testExpert( {
		expertConstructor: valueview.experts.GlobeCoordinateInput
	} );

}( QUnit, jQuery.valueview ) );
