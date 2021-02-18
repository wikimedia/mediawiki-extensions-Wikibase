/**
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
/* jshint nonew: false */
( function( $, ExpertExtender, testExpertExtenderExtension, sinon, QUnit ) {
	'use strict';

	var LanguageSelector = require( '../../../src/ExpertExtender/ExpertExtender.LanguageSelector.js' );

	QUnit.module( 'LanguageSelector' );

	var messageProvider = {
		getMessage: function( key, params ) {
			return params && params.length > 0
				? params.join( ' ' )
				: key;
		}
	};

	testExpertExtenderExtension.all(
		LanguageSelector,
		function() {
			return new LanguageSelector(
				{
					getAll: function() { return null; }
				},
				messageProvider,
				function() { }
			);
		}
	);

	/**
	 * @param {Object} languageMap - maps language codes to language names
	 * @return {util.ContentLanguages}
	 */
	function newContentLanguagesFromLanguageMap( languageMap ) {
		return {
			getAll: function() { return Object.keys( languageMap ); },
			getName: function( code ) { return languageMap[code] || null; }
		};
	}

	QUnit.test( 'initial draw works when the upstream value is null', function( assert ) {
		var languageSelector = new LanguageSelector(
			newContentLanguagesFromLanguageMap( {
				en: 'en label'
			} ),
			messageProvider,
			function() {
				return null;
			}
		);
		var $extender = $( '<div />' );

		languageSelector.init( $extender );

		if ( languageSelector.onInitialShow ) {
			languageSelector.onInitialShow();
		}

		if ( languageSelector.draw ) {
			languageSelector.draw();
		}

		assert.ok( true );
	} );

	QUnit.test( 'value does not change if upstream value changes', function( assert ) {
		var upstreamValue = 'en';
		var languageSelector = new LanguageSelector(
			newContentLanguagesFromLanguageMap( {
				de: 'de label',
				en: 'en label'
			} ),
			messageProvider,
			function() {
				return upstreamValue;
			}
		);
		var $extender = $( '<div />' );

		languageSelector.init( $extender );

		if ( languageSelector.onInitialShow ) {
			languageSelector.onInitialShow();
		}

		if ( languageSelector.draw ) {
			languageSelector.draw();
		}

		assert.strictEqual( languageSelector.getValue(), 'en' );

		upstreamValue = 'de';

		if ( languageSelector.draw ) {
			languageSelector.draw();
		}

		assert.strictEqual( languageSelector.getValue(), 'en' );
	} );

	QUnit.test( 'returns correct value after initialization', function( assert ) {
		var languageSelector = new LanguageSelector(
			newContentLanguagesFromLanguageMap( {
				en: 'en label'
			} ),
			messageProvider,
			function() {
				return 'en';
			}
		);
		var $extender = $( '<div />' );

		languageSelector.init( $extender );

		if ( languageSelector.onInitialShow ) {
			languageSelector.onInitialShow();
		}

		if ( languageSelector.draw ) {
			languageSelector.draw();
		}

		assert.strictEqual( languageSelector.getValue(), 'en' );
		assert.strictEqual( languageSelector.$selector.val(), 'en label en' );
	} );

	QUnit.test( 'returns correct value after changing it', function( assert ) {
		var languageSelector = new LanguageSelector(
			newContentLanguagesFromLanguageMap( {
				en: 'en label',
				fr: 'fr label'
			} ),
			messageProvider,
			function() {
				return 'en';
			}
		);
		var $extender = $( '<div />' );

		languageSelector.init( $extender );

		if ( languageSelector.onInitialShow ) {
			languageSelector.onInitialShow();
		}

		if ( languageSelector.draw ) {
			languageSelector.draw();
		}

		languageSelector.$selector.val( 'fr' ).trigger( 'keydown' );

		assert.strictEqual( languageSelector.getValue(), 'fr' );
		assert.strictEqual( languageSelector.$selector.val(), 'fr' );
	} );

	QUnit.test( 'returns correct value after initialization for value without label in ContentLanguages', function( assert ) {
		var languageSelector = new LanguageSelector(
			newContentLanguagesFromLanguageMap( {
				en: 'en label',
				ar: null
			} ),
			messageProvider,
			function() {
				return 'ar';
			}
		);
		var $extender = $( '<div />' );

		languageSelector.init( $extender );

		if ( languageSelector.onInitialShow ) {
			languageSelector.onInitialShow();
		}

		if ( languageSelector.draw ) {
			languageSelector.draw();
		}

		assert.strictEqual( languageSelector.getValue(), 'ar' );
		assert.strictEqual( languageSelector.$selector.val(), 'ar' );
	} );

	QUnit.test( 'returns correct value after changing it to a value without label in ContentLanguages', function( assert ) {
		var languageSelector = new LanguageSelector(
			newContentLanguagesFromLanguageMap( {
				en: 'en label',
				fr: null
			} ),
			messageProvider,
			function() {
				return 'en';
			}
		);
		var $extender = $( '<div />' );

		languageSelector.init( $extender );

		if ( languageSelector.onInitialShow ) {
			languageSelector.onInitialShow();
		}

		if ( languageSelector.draw ) {
			languageSelector.draw();
		}

		languageSelector.$selector.val( 'fr' ).trigger( 'keydown' );

		assert.strictEqual( languageSelector.getValue(), 'fr' );
		assert.strictEqual( languageSelector.$selector.val(), 'fr' );
	} );

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	sinon,
	QUnit
);
