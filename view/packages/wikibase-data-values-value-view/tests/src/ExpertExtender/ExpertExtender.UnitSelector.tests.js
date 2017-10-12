/**
 * @license GNU GPL v2+
 * @author Jonas Kress
 */
/* jshint nonew: false */
( function( $, ExpertExtender, testExpertExtenderExtension, sinon, QUnit ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.UnitSelector' );

	var messageProvider = {
		getMessage: function( key, params ) {
			return params && params.length > 0 ? params.join( ' ' ) : key;
		}
	};

	testExpertExtenderExtension.all(
		ExpertExtender.UnitSelector,
		function() {
			return new ExpertExtender.UnitSelector(
				messageProvider,
				function() {
					return {};
				}
			);
		}
	);

	QUnit.test( 'getConceptUri() does change if input value changes', function( assert ) {
		assert.expect( 2 );
		var unitSelector = new ExpertExtender.UnitSelector(
			messageProvider,
			function() {
				return { label: 'Ultrameter' };
			}
		);
		var $extender = $( '<div />' );

		unitSelector.init( $extender );

		if ( unitSelector.onInitialShow ) {
			unitSelector.onInitialShow();
		}

		if ( unitSelector.draw ) {
			unitSelector.draw();
		}

		assert.equal( unitSelector.getConceptUri(), 'Ultrameter' );

		$extender.find( 'input' ).val( 'foobar' );

		assert.equal( unitSelector.getConceptUri(), 'foobar' );
	} );

	QUnit.test( 'returns correct value after initialization', function( assert ) {
		assert.expect( 1 );
		var unitSelector = new ExpertExtender.UnitSelector(
			messageProvider,
			function() {
				return { conceptUri: 'Ultrameter' };
			}
		);
		var $extender = $( '<div />' );

		unitSelector.init( $extender );

		if ( unitSelector.onInitialShow ) {
			unitSelector.onInitialShow();
		}

		if ( unitSelector.draw ) {
			unitSelector.draw();
		}

		assert.equal( unitSelector.getConceptUri(), 'Ultrameter' );
	} );

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	sinon,
	QUnit
);
