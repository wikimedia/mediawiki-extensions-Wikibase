/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( QUnit, valueview ) {
	'use strict';

	var testExpert = valueview.tests.testExpert;
	var expertToTest = valueview.experts.MonolingualText;

	QUnit.module( 'jquery.valueview.experts.MonolingualText' );

	if( QUnit.urlParams.completenesstest ) {
		new CompletenessTest( expertToTest, function( cur, tester, path ) {
			return false;
		} );
	}

	testExpert( {
		expertConstructor: expertToTest
	} );

}( QUnit, jQuery.valueview ) );

