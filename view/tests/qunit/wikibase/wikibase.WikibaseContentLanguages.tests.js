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
		sandbox.stub( wb, 'getLanguageNameByCode' ).withArgs( 'eo' ).returns( 'Esperanto' );

		assert.strictEqual(
			( new wb.WikibaseContentLanguages( [ 'eo' ] ) ).getName( 'eo' ),
			'Esperanto'
		);
	} );

	QUnit.test( 'getLanguageNameMap', function ( assert ) {
		var callback = sandbox.stub( wb, 'getLanguageNameByCode' );
		callback.withArgs( 'en' ).returns( 'English' );
		callback.withArgs( 'eo' ).returns( 'Esperanto' );

		var result = ( new wb.WikibaseContentLanguages( [ 'en', 'eo' ] ) ).getLanguageNameMap();
		assert.deepEqual(
			result,
			{ en: 'English', eo: 'Esperanto' }
		);
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
