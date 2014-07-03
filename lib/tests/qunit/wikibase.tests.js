/**
 * QUnit tests for general wikibase JavaScript code
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( mw, wb, $, QUnit ) {
	'use strict';

	/**
	 * Place for all test related Objects.
	 * @type Object
	 */
	wb.tests = {};

	QUnit.module( 'wikibase', QUnit.newWbEnvironment( { } ) );

	QUnit.test( 'basic', 1, function( assert ) {
		assert.ok(
			wb instanceof Object,
			'initiated wikibase object'
		);
	} );

	QUnit.test( 'wikibase.getLanguages()', 1, function( assert ) {
		assert.ok(
			$.isPlainObject( wb.getLanguages() ),
			'getLanguages() returns a plain object'
		);
	} );

	QUnit.test( 'wikibase.getLanguageNameByCode()', 2, function( assert ) {
		// TODO: Don't assume global state, control what languages are available for this test!
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
