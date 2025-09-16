( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.WikibaseContentLanguages' );

	QUnit.test( 'constructor', ( assert ) => {
		assert.throws( () => {
			new wb.WikibaseContentLanguages(); // eslint-disable-line no-new
		}, 'instantiated without a language list' );

		assert.throws( () => {
			new wb.WikibaseContentLanguages( [ 'en' ] ); // eslint-disable-line no-new
		}, 'instantiated without a getName function' );
	} );

	QUnit.test( 'getAll', function ( assert ) {
		var expectedLanguages = [ 'ar', 'de', 'en', 'ko' ],
			getName = this.sandbox.stub().throws( 'should not be called in this test' ),
			allLanguages = ( new wb.WikibaseContentLanguages( expectedLanguages, getName ) ).getAll();

		expectedLanguages.forEach( ( languageCode ) => {
			assert.notStrictEqual( allLanguages.indexOf( languageCode ), -1 );
		} );
	} );

	QUnit.test( 'getName', function ( assert ) {
		var getName = this.sandbox.stub().withArgs( 'eo' ).returns( 'Esperanto' );

		assert.strictEqual(
			( new wb.WikibaseContentLanguages( [ 'eo' ], getName ) ).getName( 'eo' ),
			'Esperanto'
		);
	} );

	QUnit.test( 'getLanguageNameMap', function ( assert ) {
		var getName = this.sandbox.stub();
		getName.withArgs( 'en' ).returns( 'English' );
		getName.withArgs( 'eo' ).returns( 'Esperanto' );

		var result = ( new wb.WikibaseContentLanguages( [ 'en', 'eo' ], getName ) ).getLanguageNameMap();
		assert.deepEqual(
			result,
			{ en: 'English', eo: 'Esperanto' }
		);
	} );

	QUnit.test( 'getMonolingualTextLanguages', ( assert ) => {
		var allLanguages = ( wb.WikibaseContentLanguages.getMonolingualTextLanguages() ).getAll();

		[ 'aeb', 'de', 'en', 'ko' ].forEach( ( languageCode ) => {
			assert.notStrictEqual( allLanguages.indexOf( languageCode ), -1, languageCode );
		} );
	} );

	QUnit.test( 'getTermLanguages', ( assert ) => {
		var allLanguages = ( wb.WikibaseContentLanguages.getTermLanguages() ).getAll();

		[ 'bag', 'de', 'en', 'ko' ].forEach( ( languageCode ) => {
			assert.notStrictEqual( allLanguages.indexOf( languageCode ), -1, languageCode );
		} );
	} );

}( wikibase ) );
