/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( wb, mw, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.getLanguageNameByCode' );

	QUnit.test( 'wikibase.getLanguageNameByCode()', 2, function( assert ) {
		// TODO: Don't assume global state, control what languages are available for this test!

		// Better might be to turn this into a LanguageNameLookup service and set language
		// name in getEntityTermsView in ViewFactory. Then, all places that need language name
		// would then have it.
		if ( $.uls !== undefined ) {
			assert.strictEqual(
				wb.getLanguageNameByCode( 'de' ),
				mw.config.get( 'wgULSLanguages' ).de,
				'getLanguageNameByCode() returns language name.'
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
