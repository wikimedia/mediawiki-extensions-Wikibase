/**
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
/* jshint nonew: false */
( function( QUnit, valueview ) {
	'use strict';

	const testExpert = valueview.tests.testExpert;
	const expertToTest = valueview.experts.MonolingualText;

	QUnit.module( 'jquery.valueview.experts.MonolingualText' );

	testExpert( {
		expertConstructor: expertToTest
	} );

}( QUnit, jQuery.valueview ) );
