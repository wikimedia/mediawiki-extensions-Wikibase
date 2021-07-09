/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */

( function( $, QUnit ) {
	'use strict';
	/* jshint newcap: false */

	var AnimationEvent = require( '../../../lib/jquery/jquery.AnimationEvent.js' ),
		PurposedCallbacks = require( '../../../lib/jquery/jquery.PurposedCallbacks.js' );

	QUnit.module( 'jquery.AnimationEvent' );

	function assertSuccessfulConstruction( assert, instance, purpose ) {
		assert.ok(
			instance.animationOptions,
			'Instantiated'
		);
		assert.ok(
			instance instanceof $.Event,
			'Instance of jQuery.Event.'
		);
		assert.strictEqual(
			instance.animationPurpose,
			purpose,
			'Animation purpose got copied into "animationPurpose" field.'
		);
		assert.ok(
			instance.animationCallbacks instanceof PurposedCallbacks.Facade,
			'"animationCallbacks" field is instance of jQuery.PurposedCallbacks.Facade'
		);
		assert.strictEqual(
			instance.type,
			'animation',
			'"type" field is set to "animation"'
		);
	}

	QUnit.test( 'construction without "new"', function( assert ) {
		assertSuccessfulConstruction( assert, AnimationEvent( 'nopurpose' ), 'nopurpose' );
	} );

	QUnit.test( 'construction with "new"', function( assert ) {
		assertSuccessfulConstruction( assert, new AnimationEvent( 'foo' ), 'foo' );
	} );

	QUnit.test( 'construction with custom fields given', function( assert ) {
		var fields = {
			someCustomField1: 'foo',
			someCustomField2: {}
		};
		var event = AnimationEvent( 'someanimation', fields );

		assertSuccessfulConstruction( assert, event, 'someanimation' );

		assert.strictEqual(
			event.foo, fields.foo,
			'Custom field got copied.'
		);
		assert.strictEqual(
			event.someCustomField2, fields.someCustomField2,
			'Another custom field got copied, copy happens by reference, no deep extend.'
		);
	} );

	QUnit.test( 'animationOptions()', function( assert ) {
		var event = AnimationEvent( 'animationpurpose' );
		var predefined = {
			easing: 'swing',
			queue: true,
			duration: 200
		};

		var options = event.animationOptions( predefined );

		assert.ok(
			$.isPlainObject( options ),
			'Returns a plain object.'
		);
		assert.ok(
			options.easing === predefined.easing
			&& options.queue === predefined.queue
			&& options.duration === predefined.duration,
			'Returned object holds all values of the base object given to animationOptions().'
		);

		$.each( AnimationEvent.ANIMATION_STEPS, function( i, stepName ) {
			assert.ok(
				$.isFunction( options[ stepName ] ),
				'Returned options object\'s field "' + stepName + '" is a function.'
			);
		} );
	} );

	QUnit.test( 'ANIMATION_STEPS', function( assert ) {
		assert.ok(
			Array.isArray( AnimationEvent.ANIMATION_STEPS ),
			'Is an array.'
		);
		// This might be kind of pointless, but simply make sure that no one changes this without
		// changing tests as well, being absolutely sure about it.
		var expectedSteps =
			[ 'start', 'step', 'progress', 'complete', 'done', 'fail', 'always' ];
		assert.ok(
			$( AnimationEvent.ANIMATION_STEPS ).not( expectedSteps ).length === 0
			&& $( expectedSteps ).not( AnimationEvent.ANIMATION_STEPS ).length === 0,
			'Contains expected steps.'
		);
	} );

	function testAnimationOptionsGeneratedCallbacks( assert, testStep ) {
		var event = AnimationEvent( 'animationpurpose' );
		var predefined = {};

		var firedPredefined, firedCallbacksMember;
		var resetFired = function() {
			firedPredefined = firedCallbacksMember = 0;
		};

		resetFired();

		predefined[ testStep ] = function() {
			firedPredefined++;
			assert.ok(
				!firedCallbacksMember,
				'Predefined "' + testStep + '" callback got executed first.'
			);
		};
		event.animationCallbacks.add( testStep, function() {
			firedCallbacksMember++;
		} );

		var options = event.animationOptions( predefined );
		options[ testStep ]();

		assert.strictEqual(
			firedPredefined,
			1,
			'Fired predefined callback.'
		);
		assert.strictEqual(
			firedCallbacksMember,
			1,
			'Fired callback registered to event\'s "animationCallbacks" field.'
		);

		resetFired();

		// Execute all other generated step callbacks as well, verify that they execute and that
		// they do not trigger the testStep's callback again.
		$.each( AnimationEvent.ANIMATION_STEPS, function( i, step ) {
			if ( step !== testStep ) {
				options[ step ]();
			}
		} );
		assert.ok(
			firedPredefined === 0 && firedCallbacksMember === 0,
			'Fired callbacks generated for all other option fields, they are independent of the "'
				+ testStep + '" one.'
		);
	}

	$.each( AnimationEvent.ANIMATION_STEPS, function( i, step ) {
		QUnit.test(
			'animationOptions(). ' + step + ' callbacks test',
			function( assert ) {
				testAnimationOptionsGeneratedCallbacks( assert, step );
			} );
	} );

}( jQuery, QUnit ) );
