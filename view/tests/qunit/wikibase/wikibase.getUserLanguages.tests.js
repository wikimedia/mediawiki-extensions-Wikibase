/**
 * @license GPL-2.0-or-later
 */
( function ( wb ) {
	'use strict';

	// Sinon: Cannot stub non-existent own property uls
	if ( !( 'uls' in $ ) ) {
		$.uls = undefined;
	}
	if ( !( 'uls' in mw ) ) {
		mw.uls = undefined;
	}

	QUnit.module( 'wikibase.getUserLanguages', {
		beforeEach() {
			this.simulateUlsInstalled = ( isInstalled ) => {
				// Define or replace object, depending on whether ULS is installed.
				if ( isInstalled ) {
					this.sandbox.stub( $, 'uls', { data: {} } );
					this.sandbox.stub( mw, 'uls', { data: {}, getFrequentLanguageList() {} } );
				} else {
					this.sandbox.stub( $, 'uls', {} );
					this.sandbox.stub( mw, 'uls', {} );
				}
			};
		}
	} );

	QUnit.test( 'getUserLanguages provides preferred languages if defined', function ( assert ) {
		this.simulateUlsInstalled( true );
		var mwConfigStub = this.sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = this.sandbox.spy( mw.uls, 'getFrequentLanguageList' );
		var preferredContentLanguages = [ 'de-at', 'de', 'en', 'pt' ];
		var specifiedLanguages = [ 'en', 'pt' ];
		var userLanguage = 'de-at';

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
		this.simulateUlsInstalled( true );
		var mwConfigStub = this.sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = this.sandbox.spy( mw.uls, 'getFrequentLanguageList' );
		var specifiedLanguages = [ 'en', 'pt' ];
		var userLanguage = 'en';

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
		this.simulateUlsInstalled( true );
		var mwConfigStub = this.sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = this.sandbox.spy( mw.uls, 'getFrequentLanguageList' );
		var preferredContentLanguages = [ 'de', 'de-at', 'en', 'pt' ];
		var specifiedLanguages = [ 'en', 'pt' ];
		var userLanguage = 'de-at';

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
		this.simulateUlsInstalled( true );
		var mwConfigStub = this.sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = this.sandbox.spy( mw.uls, 'getFrequentLanguageList' );
		var specifiedLanguages = [ 'en', 'pt' ];
		var userLanguage = 'de';

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
		this.simulateUlsInstalled( true );
		var mwConfigStub = this.sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = this.sandbox.stub( mw.uls, 'getFrequentLanguageList' );
		var ulsLanguages = [ 'de-at', 'de', 'fr', 'bar', 'zh' ];
		var preferredContentLanguages = [ 'de-at' ];
		var specifiedLanguages = [];
		var userLanguage = 'de-at';

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
		this.simulateUlsInstalled( true );
		var mwConfigStub = this.sandbox.stub( mw.config, 'get' );
		var mwUlsConfigStub = this.sandbox.stub( mw.uls, 'getFrequentLanguageList' );
		var ulsLanguages = [];
		var preferredContentLanguages = [];
		var specifiedLanguages = [];
		var userLanguage = 'de-at';

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
		this.simulateUlsInstalled( false );
		var mwConfigStub = this.sandbox.stub( mw.config, 'get' );
		var preferredContentLanguages = [];
		var specifiedLanguages = [];
		var userLanguage = 'de-at';

		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages', [] )
			.returns( specifiedLanguages );
		mwConfigStub.withArgs( 'wbUserPreferredContentLanguages', specifiedLanguages )
			.returns( preferredContentLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' )
			.returns( userLanguage );

		assert.deepEqual( wb.getUserLanguages(), [ userLanguage ] );
		assert.strictEqual( mw.uls.getFrequentLanguageList, undefined );
	} );

	QUnit.test( 'getUserLanguages filters out invalid term languages', function ( assert ) {
		this.simulateUlsInstalled( false );
		var mwConfigStub = this.sandbox.stub( mw.config, 'get' );
		var preferredContentLanguages = [ 'de', 'en', 'tokipona', 'nonsense' ];
		var specifiedLanguages = [ 'en', 'tokipona', 'nonsense' ];
		var userLanguage = 'de';

		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages', [] )
			.returns( specifiedLanguages );
		mwConfigStub.withArgs( 'wbUserPreferredContentLanguages', specifiedLanguages )
			.returns( preferredContentLanguages );
		mwConfigStub.withArgs( 'wgUserLanguage' )
			.returns( userLanguage );

		assert.deepEqual( wb.getUserLanguages(), [ 'de', 'en' ] );
	} );

}( wikibase ) );
