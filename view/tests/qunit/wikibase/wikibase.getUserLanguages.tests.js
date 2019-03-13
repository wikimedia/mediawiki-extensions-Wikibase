( function ( wb, sinon ) {
	'use strict';

	QUnit.module( 'wikibase.getUserLanguages' );

	QUnit.test( 'getUserLanguages provides languages of babel if they are defined', function ( assert ) {
		var removeUls = false;
		var removeData = false;
		var mwConfigStub = sinon.stub( mw.config, 'get' );
		var mwUlsConfigStub = sinon.stub( mw.uls, 'getFrequentLanguageList' );
		var ulsLanguages = [ 'de', 'en', 'gr', 'zh' ];
		var languages = [ 'de', 'he', 'fr' ];

		if ( ! $.uls ) {
			removeUls = true;
			$.uls = {};
		}

		if ( !$.uls.data ) {
			removeData = true;
			$.uls.data = 'ignoreMe';
		}

		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages' ).returns( languages );
		mwConfigStub.withArgs( 'wgUserLanguage' ).returns( 'de' );
		mwUlsConfigStub.returns( ulsLanguages );

		assert.propEqual( languages, wb.getUserLanguages() );

		mwConfigStub.restore();
		mwUlsConfigStub.restore();

		if ( removeData ) {
			delete $.uls.data;
		}

		if ( removeUls ) {
			delete $.uls;
		}
	} );

	QUnit.test( 'getUserLanguages uses uls languages if babel is not defined', function ( assert ) {
		var removeUls = false;
		var removeData = false;
		var mwConfigStub = sinon.stub( mw.config, 'get' );
		var mwUlsConfigStub = sinon.stub( mw.uls, 'getFrequentLanguageList' );
		var ulsLanguages = [ 'de', 'en', 'gr', 'zh' ];
		var languages = [];

		if ( ! $.uls ) {
			removeUls = true;
			$.uls = {};
		}

		if ( !$.uls.data ) {
			removeData = true;
			$.uls.data = 'ignoreMe';
		}

		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages' ).returns( languages );
		mwConfigStub.withArgs( 'wgUserLanguage' ).returns( 'de' );
		mwUlsConfigStub.returns( ulsLanguages );

		assert.propEqual( ulsLanguages, wb.getUserLanguages() );

		mwConfigStub.restore();
		mwUlsConfigStub.restore();

		if ( removeData ) {
			delete $.uls.data;
		}

		if ( removeUls ) {
			delete $.uls;
		}
	} );

	QUnit.test( 'getUserLanguages returns just the given userLanguage if uls languages and babel is not defined', function ( assert ) {
		var restoreData = false;
		var restore;
		var mwConfigStub = sinon.stub( mw.config, 'get' );
		var mwUlsConfigStub = sinon.stub( mw.uls, 'getFrequentLanguageList' );
		var ulsLanguages = [];
		var languages = [];

		if ( $.uls.data ) {
			restoreData = true;
			restore = $.uls.data;
		}

		mwConfigStub.withArgs( 'wbUserSpecifiedLanguages' ).returns( languages );
		mwConfigStub.withArgs( 'wgUserLanguage' ).returns( 'de' );
		mwUlsConfigStub.returns( ulsLanguages );

		assert.propEqual( [ 'de' ], wb.getUserLanguages() );

		mwConfigStub.restore();
		mwUlsConfigStub.restore();

		if ( restoreData ) {
			$.uls.data = restore;
		}
	} );

}( wikibase, sinon ) );
