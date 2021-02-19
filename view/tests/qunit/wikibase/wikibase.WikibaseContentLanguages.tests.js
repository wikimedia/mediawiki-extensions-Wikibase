( function ( wb, sinon ) {
	'use strict';

	var sandbox = sinon.sandbox.create();

	QUnit.module( 'wikibase.WikibaseContentLanguages', {
		afterEach: function () {
			sandbox.restore();
		}
	} );

	QUnit.test( 'getAll', function ( assert ) {
		var allLanguages = ( new wb.WikibaseContentLanguages() ).getAll();

		[ 'ar', 'de', 'en', 'ko' ].forEach( function ( languageCode ) {
			assert.notStrictEqual( allLanguages.indexOf( languageCode ), -1 );
		} );
	} );

	QUnit.test( 'getName', function ( assert ) {
		var ulsLanguageMap = {
			eo: 'Esperanto'
		};
		sandbox.stub( mw.config, 'get' ).returns( ulsLanguageMap );

		assert.strictEqual(
			( new wb.WikibaseContentLanguages() ).getName( 'eo' ),
			ulsLanguageMap.eo
		);
	} );

	QUnit.test( 'getAllPairs', function ( assert ) {
		var ulsLanguageMap = {
			en: 'English'
		};

		sandbox.stub( mw.config, 'get' ).returns( ulsLanguageMap );

		var result = ( new wb.WikibaseContentLanguages() ).getAllPairs();
		assert.strictEqual(
			result.en,
			ulsLanguageMap.en
		);

		assert.notStrictEqual( result, ulsLanguageMap );
	} );

}( wikibase, sinon ) );
