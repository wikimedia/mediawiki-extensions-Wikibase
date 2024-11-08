/**
 * @license GPL-2.0-or-later
 */
( function ( wb ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );
	var origGetLanguageName;

	QUnit.module( 'wikibase.view.languageFallbackIndicator', QUnit.newMwEnvironment( {
		beforeEach: function () {
			origGetLanguageName = wb.getLanguageNameByCodeForTerms;
			wb.getLanguageNameByCodeForTerms = function ( code ) {
				return '<' + code + '>';
			};
		},
		afterEach: function () {
			wb.getLanguageNameByCodeForTerms = origGetLanguageName;
		}
	} ) );

	QUnit.test( 'no fallback, same language', ( assert ) => {
		var term = new datamodel.Term( 'de', 'de term' );
		var requestedLanguage = 'de';

		var html = wb.view.languageFallbackIndicator.getHtml( term, requestedLanguage );

		assert.strictEqual( html, '' );
	} );

	QUnit.test( 'fallback to same base language', ( assert ) => {
		var term = new datamodel.Term( 'de', 'de term' );
		var requestedLanguage = 'de-at';

		var html = wb.view.languageFallbackIndicator.getHtml( term, requestedLanguage );

		assert.strictEqual( html, '' );
	} );

	QUnit.test( 'fallback to mul', ( assert ) => {
		var term = new datamodel.Term( 'mul', 'mul term' );
		var requestedLanguage = 'de';

		var html = wb.view.languageFallbackIndicator.getHtml( term, requestedLanguage );

		assert.strictEqual( html, '' );
	} );

	QUnit.test( 'fallback to other language', ( assert ) => {
		var term = new datamodel.Term( 'en', 'en term' );
		var requestedLanguage = 'de';

		var html = wb.view.languageFallbackIndicator.getHtml( term, requestedLanguage );

		assert.strictEqual( html, '\u{00A0}<sup class="wb-language-fallback-indicator">&lt;en&gt;</sup>' );
	} );

}( wikibase ) );
