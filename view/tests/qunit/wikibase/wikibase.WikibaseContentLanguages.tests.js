( function ( wb, sinon ) {
	'use strict';

	var sandbox = sinon.sandbox.create();

	QUnit.module( 'wikibase.WikibaseContentLanguages', {
		afterEach: function () {
			sandbox.restore();
		}
	} );

	QUnit.test( 'constructor', function ( assert ) {
		assert.throws( function () {
			new wb.WikibaseContentLanguages(); // eslint-disable-line no-new
		}, 'instantiated without a language list' );
	} );

	QUnit.test( 'getAll', function ( assert ) {
		var expectedLanguages = [ 'ar', 'de', 'en', 'ko' ],
			allLanguages = ( new wb.WikibaseContentLanguages( expectedLanguages ) ).getAll();

		expectedLanguages.forEach( function ( languageCode ) {
			assert.notStrictEqual( allLanguages.indexOf( languageCode ), -1 );
		} );
	} );

	QUnit.test( 'getName', function ( assert ) {
		var ulsLanguageMap = {
			eo: 'Esperanto'
		};
		sandbox.stub( mw.config, 'get' ).returns( ulsLanguageMap );

		assert.strictEqual(
			( new wb.WikibaseContentLanguages( [ 'eo' ] ) ).getName( 'eo' ),
			ulsLanguageMap.eo
		);
	} );

	QUnit.test( 'getLanguageNameMap', function ( assert ) {
		var ulsLanguageMap = {
			en: 'English'
		};

		sandbox.stub( mw.config, 'get' ).returns( ulsLanguageMap );

		var result = ( new wb.WikibaseContentLanguages( [ 'en' ] ) ).getLanguageNameMap();
		assert.strictEqual(
			result.en,
			ulsLanguageMap.en
		);

		assert.notStrictEqual( result, ulsLanguageMap );
	} );

	QUnit.test( 'getMonolingualTextLanguages', function ( assert ) {
		var allLanguages = ( wb.WikibaseContentLanguages.getMonolingualTextLanguages() ).getAll();

		[ 'abe', 'de', 'en', 'ko' ].forEach( function ( languageCode ) {
			assert.notStrictEqual( allLanguages.indexOf( languageCode ), -1 );
		} );
	} );

	QUnit.test( 'getTermLanguages', function ( assert ) {
		var allLanguages = ( wb.WikibaseContentLanguages.getTermLanguages() ).getAll();

		[ 'bag', 'de', 'en', 'ko' ].forEach( function ( languageCode ) {
			assert.notStrictEqual( allLanguages.indexOf( languageCode ), -1 );
		} );
	} );

}( wikibase, sinon ) );
