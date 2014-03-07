/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit, valueview ) {
	'use strict';

	var testExpert = valueview.tests.testExpert;

	QUnit.module( 'wikibase.experts.EntityIdInput' );

	testExpert( {
		expertConstructor: wikibase.experts.EntityIdInput,
	} );

}( jQuery, QUnit, jQuery.valueview ) );

