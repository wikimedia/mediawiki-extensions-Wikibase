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

		assert.strictEqual(
			wb.getSite( 'xy' ),
			null,
			'trying to get invalid site returns null'
		);

		assert.strictEqual(
			wb.hasSite( 'xy' ),
			false,
			'trying to check for invalid site returns false'
		);

		assert.ok(
			$.isPlainObject( wb.getLanguages() ),
			'getLanguages() returns object'
		);

		if( $.uls !== undefined ) {
			assert.strictEqual(
				wb.getLanguageNameByCode( 'de' ),
				'Deutsch',
				'getLanguageNameByCode returns language name'
			);
		} else {
			assert.strictEqual(
				wb.getLanguageNameByCode( 'de' ),
				'',
				'getLanguageNameByCode returns empty string (ULS not loaded)'
			);
		}

		assert.strictEqual(
			wb.getLanguageNameByCode( 'nonexistantlanguagecode' ),
			'',
			'getLanguageNameByCode returns empty string if unknown code'
		);


	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
