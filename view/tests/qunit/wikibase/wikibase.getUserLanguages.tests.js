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
		assert.ok( mwUlsConfigStub.notCalled );
	} );

	QUnit.test( 'getUserLanguages uses uls languages if babel is not defined', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = sandbox.stub( mw.uls, 'getFrequentLanguageList' );
		var ulsLanguages = [ 'de', 'en', 'gr', 'zh' ];
		var babelLanguages = [];
		var userLanguage = 'de';

		simulateUlsInstalled( true );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages' ).returns( babelLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' ).returns( userLanguage );
		mwUlsConfigStub.returns( ulsLanguages );

		assert.deepEqual( wb.getUserLanguages(), ulsLanguages );
		assert.ok( mwUlsConfigStub.calledOnce );
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
		assert.ok( mwUlsConfigStub.calledOnce );
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
		assert.ok( mwUlsConfigStub.notCalled );
	} );

}( wikibase, sinon ) );
