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

		assert.throws( function () {
			new wb.WikibaseContentLanguages( [ 'en' ] ); // eslint-disable-line no-new
		}, 'instantiated without a getName function' );
	} );

	QUnit.test( 'getAll', function ( assert ) {
		var expectedLanguages = [ 'ar', 'de', 'en', 'ko' ],
			getName = sandbox.stub().throws( 'should not be called in this test' ),
			allLanguages = ( new wb.WikibaseContentLanguages( expectedLanguages, getName ) ).getAll();

		expectedLanguages.forEach( function ( languageCode ) {
			assert.notStrictEqual( allLanguages.indexOf( languageCode ), -1 );
		} );
	} );

	QUnit.test( 'getName', function ( assert ) {
		var getName = sandbox.stub().withArgs( 'eo' ).returns( 'Esperanto' );

		assert.strictEqual(
			( new wb.WikibaseContentLanguages( [ 'eo' ], getName ) ).getName( 'eo' ),
			'Esperanto'
		);
	} );

	QUnit.test( 'getLanguageNameMap', function ( assert ) {
		var getName = sandbox.stub();
		getName.withArgs( 'en' ).returns( 'English' );
		getName.withArgs( 'eo' ).returns( 'Esperanto' );

		var result = ( new wb.WikibaseContentLanguages( [ 'en', 'eo' ], getName ) ).getLanguageNameMap();
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
