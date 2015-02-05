/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
/* jshint nonew: false */
( function( $, ExpertExtender, testExpertExtenderExtension, sinon, QUnit, CompletenessTest ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.LanguageSelector' );

	if( QUnit.urlParams.completenesstest ) {
		new CompletenessTest(
			ExpertExtender.LanguageSelector.prototype,
			function( cur, tester, path ) {
				return false;
			}
		);
	}

	var messageProvider = {
		getMessage: function( key, params ) {
			return params && params.length > 0
				? params.join( ' ' )
				: key;
		}
	};

	testExpertExtenderExtension.all(
		ExpertExtender.LanguageSelector,
		function() {
			return new ExpertExtender.LanguageSelector(
				{
					getAll: function() { return null; }
				},
				messageProvider,
				function() { }
			);
		}
	);

	QUnit.test( 'value does not change if upstream value changes', function( assert ) {
		var upstreamValue = 'en';
		var languageSelector = new ExpertExtender.LanguageSelector(
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

		if( languageSelector.onInitialShow ) {
			languageSelector.onInitialShow();
		}

		if( languageSelector.draw ) {
			languageSelector.draw();
		}

		assert.equal( languageSelector.getValue(), 'en' );

		upstreamValue = 'de';

		if( languageSelector.draw ) {
			languageSelector.draw();
		}

		assert.equal( languageSelector.getValue(), 'en' );
	} );

	QUnit.test( 'returns correct value after initialization', function( assert ) {
		var languageSelector = new ExpertExtender.LanguageSelector(
			{
				getAll: function() { return [ 'en' ]; },
				getName: function( code ) { return code; }
			},
			messageProvider,
			function() {
				return 'en';
			}
		);
		var $extender = $( '<div />' );

		languageSelector.init( $extender );

		if( languageSelector.onInitialShow ) {
			languageSelector.onInitialShow();
		}

		if( languageSelector.draw ) {
			languageSelector.draw();
		}

		assert.equal( languageSelector.getValue(), 'en' );
	} );

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	sinon,
	QUnit,
	CompletenessTest
);
