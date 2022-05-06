/**
 * @license GPL-2.0-or-later
 */
( function ( wb, sinon ) {
	'use strict';

	var sandbox = sinon.sandbox.create();

	QUnit.module( 'wikibase.getUserLanguages', {
		afterEach: function () {
			sandbox.restore();
		}
	} );

	function simulateUlsInstalled( isInstalled ) {
		sandbox.stub( $, 'uls', {
			data: isInstalled ? {} : false
		} );
	}

	QUnit.test( 'getUserLanguages provides languages of babel if they are defined', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = sandbox.spy( mw.uls, 'getFrequentLanguageList' );
		var babelLanguages = [ 'de', 'he', 'fr' ];
		var userLanguage = 'de';

		simulateUlsInstalled( true );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages' ).returns( babelLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' ).returns( userLanguage );

		assert.deepEqual( wb.getUserLanguages(), babelLanguages );
		assert.true( mwUlsConfigStub.notCalled );
	} );

	QUnit.test( 'getUserLanguages moves user language to front of babel', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = sandbox.spy( mw.uls, 'getFrequentLanguageList' );
		var babelLanguages = [ 'de', 'he', 'fr' ];
		var userLanguage = 'he';

		simulateUlsInstalled( true );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages' ).returns( babelLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' ).returns( userLanguage );

		assert.deepEqual( wb.getUserLanguages(), [ 'he', 'de', 'fr' ] );
		assert.true( mwUlsConfigStub.notCalled );
	} );

	QUnit.test( 'getUserLanguages adds user language to babel languages if not included', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = sandbox.spy( mw.uls, 'getFrequentLanguageList' );
		var babelLanguages = [ 'de', 'he', 'fr' ];
		var userLanguage = 'en';

		simulateUlsInstalled( true );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages' ).returns( babelLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' ).returns( userLanguage );

		assert.deepEqual( wb.getUserLanguages(), [ 'en', 'de', 'he', 'fr' ] );
		assert.true( mwUlsConfigStub.notCalled );
	} );

	QUnit.test( 'getUserLanguages uses uls languages if babel is not defined', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = sandbox.stub( mw.uls, 'getFrequentLanguageList' );
		var ulsLanguages = [ 'de', 'en', 'fr', 'zh' ];
		var babelLanguages = [];
		var userLanguage = 'de';

		simulateUlsInstalled( true );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages' ).returns( babelLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' ).returns( userLanguage );
		mwUlsConfigStub.returns( ulsLanguages );

		assert.deepEqual( wb.getUserLanguages(), ulsLanguages );
		assert.true( mwUlsConfigStub.calledOnce );
	} );

	QUnit.test( 'getUserLanguages returns just the given userLanguage if uls languages and babel is not defined', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = sandbox.stub( mw.uls, 'getFrequentLanguageList' );
		var ulsLanguages = [];
		var babelLanguages = [];
		var userLanguage = 'de';

		simulateUlsInstalled( true );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages' ).returns( babelLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' ).returns( userLanguage );
		mwUlsConfigStub.returns( ulsLanguages );

		assert.deepEqual( wb.getUserLanguages(), [ userLanguage ] );
		assert.true( mwUlsConfigStub.calledOnce );
	} );

	QUnit.test( 'getUserLanguages returns just the given userLanguage if uls is not installed and babel empty', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = sandbox.stub( mw.uls, 'getFrequentLanguageList' );
		var babelLanguages = [];
		var userLanguage = 'de';

		simulateUlsInstalled( false );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages' ).returns( babelLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' ).returns( userLanguage );

		assert.deepEqual( wb.getUserLanguages(), [ userLanguage ] );
		assert.true( mwUlsConfigStub.notCalled );
	} );

	QUnit.test( 'filters out invalid term languages', function ( assert ) {
		var babelLanguages = [ 'de', 'en', 'tokipona', 'nonsense' ];
		var userLanguage = 'de';
		var mwConfigStub = sandbox.stub( mw.config, 'get' );

		simulateUlsInstalled( false );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages' ).returns( babelLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' ).returns( userLanguage );

		assert.deepEqual( wb.getUserLanguages(), [ 'de', 'en' ] );
	} );

}( wikibase, sinon ) );
