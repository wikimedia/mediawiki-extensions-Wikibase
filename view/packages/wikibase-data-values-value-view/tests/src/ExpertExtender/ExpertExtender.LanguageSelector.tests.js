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

	testExpertExtenderExtension.all(
		ExpertExtender.LanguageSelector,
		function() {
			return new ExpertExtender.LanguageSelector( new util.MessageProvider(), function() { } );
		}
	);

	QUnit.test( 'value does not change if upstream value changes', function( assert ) {
		var upstreamValue = 'en';
		var languageSelector = new ExpertExtender.LanguageSelector( new util.MessageProvider( {
			messageGetter: function( key ) {
				return arguments.length > 1
					? Array.prototype.slice.call( arguments, 1 ).join( ' ' )
					: key;
			}
		} ), function() {
			return upstreamValue;
		} );
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

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	sinon,
	QUnit,
	CompletenessTest
);
