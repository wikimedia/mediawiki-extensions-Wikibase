/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( QUnit, valueview, wb ) {
	'use strict';

	var testExpert = valueview.tests.testExpert;

	QUnit.module( 'wikibase.experts.Property' );

	testExpert( {
		expertConstructor: wb.experts.Property
	} );

}( QUnit, jQuery.valueview, wikibase ) );
