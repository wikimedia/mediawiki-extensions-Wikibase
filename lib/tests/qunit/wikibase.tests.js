/**
 * QUnit tests for general wikibase JavaScript code
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */

( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	/**
	 * Place for all test related Objects.
	 * @type Object
	 */
	wb.tests = {};

	QUnit.module( 'wikibase', QUnit.newWbEnvironment() );

	QUnit.test( 'basic', function( assert ) {

		QUnit.expect( 7 );

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

		assert.ok(
			$.isPlainObject( wb.getLanguages() ),
			'getLanguages() returns object'
		);

		assert.equal(
			wb.getLanguageNameByCode( 'de' ),
			'Deutsch',
			'getLanguageNameByCode returns language name'
		);

		assert.equal(
			wb.getLanguageNameByCode( 'nonexistantlanguagecode' ),
			'',
			'getLanguageNameByCode returns empty string if unknown code'
		);


	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
