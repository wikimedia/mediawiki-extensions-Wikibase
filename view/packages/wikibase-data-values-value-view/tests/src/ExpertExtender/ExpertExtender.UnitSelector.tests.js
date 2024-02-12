/**
 * @license GNU GPL v2+
 * @author Jonas Kress
 */
/* jshint nonew: false */
( function( $, ExpertExtender, testExpertExtenderExtension, sinon, QUnit ) {
	'use strict';

	const UnitSelector = require( '../../../src/ExpertExtender/ExpertExtender.UnitSelector.js' );

	QUnit.module( 'UnitSelector' );

	const messageProvider = {
		getMessage: function( key, params ) {
			return params && params.length > 0 ? params.join( ' ' ) : key;
		}
	};

	testExpertExtenderExtension.all(
		UnitSelector,
		function() {
			return new UnitSelector(
				messageProvider,
				function() {
					return {};
				}
			);
		}
	);

	QUnit.test( 'getConceptUri() does change if input value changes', function( assert ) {
		const unitSelector = new UnitSelector(
			messageProvider,
			function() {
				return { label: 'Ultrameter' };
			}
		);
		const $extender = $( '<div />' );

		unitSelector.init( $extender );

		if ( unitSelector.onInitialShow ) {
			unitSelector.onInitialShow();
		}

		if ( unitSelector.draw ) {
			unitSelector.draw();
		}

		assert.strictEqual( unitSelector.getConceptUri(), 'Ultrameter' );

		$extender.find( 'input' ).val( 'foobar' );

		assert.strictEqual( unitSelector.getConceptUri(), 'foobar' );
	} );

	QUnit.test( 'returns correct value after initialization', function( assert ) {
		const unitSelector = new UnitSelector(
			messageProvider,
			function() {
				return { conceptUri: 'Ultrameter' };
			}
		);
		const $extender = $( '<div />' );

		unitSelector.init( $extender );

		if ( unitSelector.onInitialShow ) {
			unitSelector.onInitialShow();
		}

		if ( unitSelector.draw ) {
			unitSelector.draw();
		}

		assert.strictEqual( unitSelector.getConceptUri(), 'Ultrameter' );
	} );

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	sinon,
	QUnit
);
