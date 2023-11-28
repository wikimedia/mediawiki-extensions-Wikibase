/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */

( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.getLanguageNameByCode' );

	QUnit.test( 'getLanguageNameByCode( de )', function ( assert ) {
		// this test relies on a bunch of global state :/

		var allowedLanguageNames = [
			'German',
			'Deutsch'
		];
		if ( $.fn.uls ) {
			allowedLanguageNames.push( $.fn.uls.defaults.languages.de );
		}

		var languageName = wb.getLanguageNameByCode( 'de' );
		assert.true( allowedLanguageNames.indexOf( languageName ) !== -1,
			languageName + ' should be one of ' + allowedLanguageNames.join( ', ' ) );
	} );

	QUnit.test( 'getLanguageNameByCode( nonexistantlanguagecode )', function ( assert ) {
		assert.strictEqual(
			wb.getLanguageNameByCode( 'nonexistantlanguagecode' ),
			'nonexistantlanguagecode',
			'getLanguageNameByCode() returns language code if unknown code.'
		);
	} );

	QUnit.test( 'getLanguageNameByCodeForTerms( de )', function ( assert ) {
		// this test relies on a bunch of global state :/

		var allowedLanguageNames = [
			'German',
			'Deutsch'
		];
		if ( $.fn.uls ) {
			allowedLanguageNames.push( $.fn.uls.defaults.languages.de );
		}

		var languageName = wb.getLanguageNameByCodeForTerms( 'de' );
		assert.true( allowedLanguageNames.indexOf( languageName ) !== -1,
			languageName + ' should be one of ' + allowedLanguageNames.join( ', ' ) );
	} );

	QUnit.test( 'getLanguageNameByCodeForTerms( mul )', function ( assert ) {
		// this test relies on a bunch of global state :/

		var languageName = wb.getLanguageNameByCodeForTerms( 'mul' );
		assert.strictEqual( languageName, mw.msg( 'wikibase-language-name-for-terms-mul' ) );
	} );

}( wikibase ) );
