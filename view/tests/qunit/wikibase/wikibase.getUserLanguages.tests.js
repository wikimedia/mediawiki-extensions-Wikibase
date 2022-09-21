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

	QUnit.test( 'getUserLanguages provides preferred languages if defined', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = sandbox.spy( mw.uls, 'getFrequentLanguageList' );
		var preferredContentLanguages = [ 'de-at', 'de', 'en', 'pt' ];
		var specifiedLanguages = [ 'en', 'pt' ];
		var userLanguage = 'de-at';

		simulateUlsInstalled( true );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages', [] )
			.returns( specifiedLanguages );
		mwConfigStub.withArgs( 'wbUserPreferredContentLanguages', specifiedLanguages )
			.returns( preferredContentLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' )
			.returns( userLanguage );

		assert.deepEqual( wb.getUserLanguages(), preferredContentLanguages );
		assert.true( mwUlsConfigStub.notCalled );
	} );

	QUnit.test( 'getUserLanguages provides specified languages if preferred languages not defined', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = sandbox.spy( mw.uls, 'getFrequentLanguageList' );
		var specifiedLanguages = [ 'en', 'pt' ];
		var userLanguage = 'en';

		simulateUlsInstalled( true );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages', [] )
			.returns( specifiedLanguages );
		mwConfigStub.withArgs( 'wbUserPreferredContentLanguages', specifiedLanguages )
			.returns( specifiedLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' )
			.returns( userLanguage );

		assert.deepEqual( wb.getUserLanguages(), specifiedLanguages );
		assert.true( mwUlsConfigStub.notCalled );
	} );

	QUnit.test( 'getUserLanguages moves user language to front', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = sandbox.spy( mw.uls, 'getFrequentLanguageList' );
		var preferredContentLanguages = [ 'de', 'de-at', 'en', 'pt' ];
		var specifiedLanguages = [ 'en', 'pt' ];
		var userLanguage = 'de-at';

		simulateUlsInstalled( true );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages', [] )
			.returns( specifiedLanguages );
		mwConfigStub.withArgs( 'wbUserPreferredContentLanguages', specifiedLanguages )
			.returns( preferredContentLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' )
			.returns( userLanguage );

		assert.deepEqual( wb.getUserLanguages(), [ 'de-at', 'de', 'en', 'pt' ] );
		assert.true( mwUlsConfigStub.notCalled );
	} );

	QUnit.test( 'getUserLanguages adds user language in front', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = sandbox.spy( mw.uls, 'getFrequentLanguageList' );
		var specifiedLanguages = [ 'en', 'pt' ];
		var userLanguage = 'de';

		simulateUlsInstalled( true );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages', [] )
			.returns( specifiedLanguages );
		mwConfigStub.withArgs( 'wbUserPreferredContentLanguages', specifiedLanguages )
			.returns( specifiedLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' )
			.returns( userLanguage );

		assert.deepEqual( wb.getUserLanguages(), [ 'de', 'en', 'pt' ] );
		assert.true( mwUlsConfigStub.notCalled );
	} );

	QUnit.test( 'getUserLanguages adds ULS languages if specified languages empty', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = sandbox.stub( mw.uls, 'getFrequentLanguageList' );
		var ulsLanguages = [ 'de-at', 'de', 'fr', 'bar', 'zh' ];
		var preferredContentLanguages = [ 'de-at' ];
		var specifiedLanguages = [];
		var userLanguage = 'de-at';

		simulateUlsInstalled( true );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages', [] )
			.returns( specifiedLanguages );
		mwConfigStub.withArgs( 'wbUserPreferredContentLanguages', specifiedLanguages )
			.returns( preferredContentLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' )
			.returns( userLanguage );
		mwUlsConfigStub.returns( ulsLanguages );

		assert.deepEqual( wb.getUserLanguages(), [ 'de-at', 'de', 'fr', 'bar' ] );
		assert.true( mwUlsConfigStub.calledOnce );
	} );

	QUnit.test( 'getUserLanguages returns user language if other sources empty', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = sandbox.stub( mw.uls, 'getFrequentLanguageList' );
		var ulsLanguages = [];
		var preferredContentLanguages = [];
		var specifiedLanguages = [];
		var userLanguage = 'de-at';

		simulateUlsInstalled( true );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages', [] )
			.returns( specifiedLanguages );
		mwConfigStub.withArgs( 'wbUserPreferredContentLanguages', specifiedLanguages )
			.returns( preferredContentLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' )
			.returns( userLanguage );
		mwUlsConfigStub.returns( ulsLanguages );

		assert.deepEqual( wb.getUserLanguages(), [ userLanguage ] );
		assert.true( mwUlsConfigStub.calledOnce );
	} );

	QUnit.test( 'getUserLanguages returns user language if other sources empty and ULS not installed', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = sandbox.spy( mw.uls, 'getFrequentLanguageList' );
		var preferredContentLanguages = [];
		var specifiedLanguages = [];
		var userLanguage = 'de-at';

		simulateUlsInstalled( false );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages', [] )
			.returns( specifiedLanguages );
		mwConfigStub.withArgs( 'wbUserPreferredContentLanguages', specifiedLanguages )
			.returns( preferredContentLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' )
			.returns( userLanguage );

		assert.deepEqual( wb.getUserLanguages(), [ userLanguage ] );
		assert.true( mwUlsConfigStub.notCalled );
	} );

	QUnit.test( 'getUserLanguages filters out invalid term languages', function ( assert ) {
		var mwConfigStub = sandbox.stub( mw.config, 'get' );
		var preferredContentLanguages = [ 'de', 'en', 'tokipona', 'nonsense' ];
		var specifiedLanguages = [ 'en', 'tokipona', 'nonsense' ];
		var userLanguage = 'de';

		simulateUlsInstalled( false );
		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages', [] )
			.returns( specifiedLanguages );
		mwConfigStub.withArgs( 'wbUserPreferredContentLanguages', specifiedLanguages )
			.returns( preferredContentLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' )
			.returns( userLanguage );

		assert.deepEqual( wb.getUserLanguages(), [ 'de', 'en' ] );
	} );

}( wikibase, sinon ) );
