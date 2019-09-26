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

	QUnit.test( 'initial draw works when the upstream value is null', function( assert ) {
		var languageSelector = new LanguageSelector(
			{
				getAll: function() { return [ 'en' ]; },
				getName: function( code ) { return code === 'en' ? 'en label' : null; }
			},
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
			{
				getAll: function() { return null; }
			},
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
			{
				getAll: function() { return [ 'en' ]; },
				getName: function( code ) { return code === 'en' ? 'en label' : null; }
			},
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
			{
				getAll: function() { return [ 'en', 'fr' ]; },
				getName: function( code ) { return code === 'en' || code === 'fr' ? code + ' label' : null; }
			},
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

	QUnit.test( 'returns correct value after initialization for value not in ContentLanguages', function( assert ) {
		var languageSelector = new LanguageSelector(
			{
				getAll: function() { return [ 'en' ]; },
				getName: function( code ) { return code === 'en' ? 'label' : null; }
			},
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

	QUnit.test( 'returns correct value after changing it to a value not in ContentLanguages', function( assert ) {
		var languageSelector = new LanguageSelector(
			{
				getAll: function() { return [ 'en', 'ar' ]; },
				getName: function( code ) { return code === 'en' || code === 'ar' ? code + ' label' : null; }
			},
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
