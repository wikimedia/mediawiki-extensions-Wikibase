/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( QUnit, valueview, wb ) {
	'use strict';

	var testExpert = valueview.tests.testExpert;

	QUnit.module( 'wikibase.experts.Item' );

	testExpert( {
		expertConstructor: wb.experts.Item
	} );

}( QUnit, jQuery.valueview, wikibase ) );
