/**
 * @licence GNU GPL v2+
 * @author Jonas Kress
 */
/* jshint nonew: false */
( function( $, ExpertExtender, testExpertExtenderExtension, sinon, QUnit, CompletenessTest ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.UnitSelector' );

	if( QUnit.urlParams.completenesstest && CompletenessTest ) {
		new CompletenessTest(
			ExpertExtender.UnitSelector.prototype,
			function( cur, tester, path ) {
				return false;
			}
		);
	}

	var messageProvider = {
		getMessage: function( key, params ) {
			return params && params.length > 0 ?
					params.join( ' ' ) : key;
		}
	};

	testExpertExtenderExtension.all(
		ExpertExtender.UnitSelector,
		function() {
			return new ExpertExtender.UnitSelector(
				messageProvider,
				function() { }
			);
		}
	);

	QUnit.test( 'getConceptUri() does change if input value changes', function( assert ) {
		var upstreamValue = 'en';
		var unitSelector = new ExpertExtender.UnitSelector(
			messageProvider,
			function() {
				return upstreamValue;
			}
		);
		var $extender = $( '<div />' );

		unitSelector.init( $extender );

		if( unitSelector.onInitialShow ) {
			unitSelector.onInitialShow();
		}

		if( unitSelector.draw ) {
			unitSelector.draw();
		}

		assert.equal( unitSelector.getConceptUri(), 'en' );

		$extender.find( 'input' ).val( 'foobar' );

		assert.equal( unitSelector.getConceptUri(), 'foobar' );
	} );

	QUnit.test( 'returns correct value after initialization', function( assert ) {
		var unitSelector = new ExpertExtender.UnitSelector(
			messageProvider,
			function() {
				return 'en';
			}
		);
		var $extender = $( '<div />' );

		unitSelector.init( $extender );

		if( unitSelector.onInitialShow ) {
			unitSelector.onInitialShow();
		}

		if( unitSelector.draw ) {
			unitSelector.draw();
		}

		assert.equal( unitSelector.getConceptUri(), 'en' );
	} );

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	sinon,
	QUnit,
	typeof CompletenessTest !== 'undefined' ? CompletenessTest : null
);
