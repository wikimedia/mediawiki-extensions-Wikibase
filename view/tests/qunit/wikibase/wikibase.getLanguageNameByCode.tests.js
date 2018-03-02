/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */

( function ( wb, mw, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.getLanguageNameByCode' );

	QUnit.test( 'wikibase.getLanguageNameByCode()', function ( assert ) {
		// TODO: Don't assume global state, control what languages are available for this test!
		// Better might be to turn this into a LanguageNameLookup service and set language
		// name in getEntityTermsView in ViewFactory. Then, all places that need language name
		// would then have it.
		if ( $.fn.uls ) {
			assert.strictEqual(
				wb.getLanguageNameByCode( 'de' ),
				$.fn.uls.defaults.languages.de,
				'getLanguageNameByCode() returns localized language name.'
			);
		} else if ( $.uls ) {
			assert.strictEqual(
				wb.getLanguageNameByCode( 'de' ),
				'Deutsch',
				'getLanguageNameByCode() returns native language name.'
			);
		} else {
			assert.strictEqual(
				wb.getLanguageNameByCode( 'de' ),
				'de',
				'getLanguageNameByCode() returns language code (ULS not loaded).'
			);
		}

		assert.strictEqual(
			wb.getLanguageNameByCode( 'nonexistantlanguagecode' ),
			'nonexistantlanguagecode',
			'getLanguageNameByCode() returns language code if unknown code.'
		);
	} );

}( wikibase, mediaWiki, jQuery, QUnit ) );
