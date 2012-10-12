/**
 * QUnit tests for general wikibase JavaScript code
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */

( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase', QUnit.newWbEnvironment() );

	QUnit.test( 'basic', function( assert ) {

		QUnit.expect( 4 );

		assert.ok(
			wb instanceof Object,
			'initiated wikibase object'
		);

		assert.ok(
			wb.getSites() instanceof Object,
			'gettings sites resturns object'
		);

		assert.equal(
			wb.getSite( 'xy' ),
			null,
			'trying to get invalid site returns null'
		);

		assert.equal(
			wb.hasSite( 'xy' ),
			false,
			'trying to check for invalid site returns false'
		);

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
